<?php
/**
 * Migration Merge Tool - Users - Rest Controller Class
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_Terms_Controller
 *
 * @since 0.1.0
 */
class MMT_REST_Terms_Controller extends MMT_REST_Controller {

	/**
	 * Rest Base
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'terms';

	/**
	 * Term Query
	 *
	 * Holds values of mapped term data
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $term_query;

	/**
	 * MMT_REST_Terms_Controller constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register REST API Routes
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
		) );
	}

	/**
	 * Get a collection of items
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$prepared_args = array(
			'exclude' => $request['exclude'],
			'include' => $request['include'],
			'order'   => $request['order'],
			'orderby' => $request['orderby'],
			'number'  => $request['number'],
			'hide_empty' => ( isset( $request['hide_empty'] ) ) ? $request['hide_empty'] : false,
		);

		/**
		 * Filter the query arguments, before passing them to `get_terms()`.
		 *
		 * Enables adding extra arguments or setting defaults for a terms
		 * collection request.
		 *
		 * @see https://developer.wordpress.org/reference/functions/get_terms/
		 *
		 * @param array           $prepared_args Array of arguments to be
		 *                                       passed to get_terms.
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( 'mmt_rest_api_terms_query', $prepared_args, $request );

		// TODO: maybe add filters for taxonomies
		$registered_terms = get_object_taxonomies( 'post' );
		$term_query = get_terms( $registered_terms, $prepared_args );

		// Map data by key for parent relationship lookup on import
		$this->term_query = $this->create_taxonomy_lookup( $term_query );

		$response['terms'] = $registered_terms;

		// Add counts to rest call.
		foreach ( $registered_terms as $key => $registered_term ) {
			$response['counts'][ $registered_term ] = wp_count_terms( $registered_term, array( 'hide_empty' => false ) );
		}

		// Output terms to array
		foreach ( $term_query as $term ) {
			$data       = $this->prepare_item_for_response( $term, $request );
			$response['posts'][] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 0.1.0
	 *
	 * @param mixed           $term    The term object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return mixed
	 */
	public function prepare_item_for_response( $term, $request ) {

		// Data
		$data = array(
			'id'          => (int) $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'taxonomy'    => $term->taxonomy,
			'link'        => get_term_link( $term ),
			'count'       => (int) $term->count,
			'parent'      => $term->parent,
		);

		if ( $term->parent !== 0 ) {
			$arr         = $this->term_query;
			$data['parent_slug'] = $arr[ $term->parent ]['slug'];
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

		/**
		 * Filter media data returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $term     The term object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'mmt_rest_api_prepare_term', $response, $term, $request );
	}

	/**
	 * Get the query params for collections
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		$custom_query_params = array(
			'hide_empty' => array(
				'default'           => false,
				'description'       => __( 'Hide terms without assignment', 'mmt' ),
				'enum'              => array( true, false ),
				'sanitize_callback' => 'sanitize_key',
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'number' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'mmt' ),
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
		return apply_filters( 'mmt_rest_api_term_params', array_merge( $query_params, $custom_query_params ) );
	}

	/**
	 * Map data with key as index
	 *
	 * @param $terms
	 *
	 * @return array
	 */
	public function create_taxonomy_lookup( $terms ) {
		$items = [];
		foreach( $terms as $term ) {
			$items[ $term->term_id ] = (array) $term;
		}

		return $items;
	}
}
