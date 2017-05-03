<?php
/**
 * Migration Merge Tool - Users - Rest Controller Class
 *
 * @package    MMT
 * @subpackage Includes\Endpoints
 * @since      0.1.0
 */

namespace MergeMigrationTool\Includes\Endpoints;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
				'methods'             => \WP_REST_Server::READABLE,
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
			'exclude'    => $request['exclude'],
			'order'      => $request['order'],
			'orderby'    => $request['orderby'],
			'number'     => $request['number'],
			'hide_empty' => $request['hide_empty'],
			'taxonomy'   => $request['taxonomy']
		);

		// Run the requested terms or get them all.
		$taxonomies = $request['taxonomy'];
		$taxonomies = explode(',', $taxonomies );

		$prepared_args['taxonomy'] = $taxonomies;

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

		//$registered_terms = get_object_taxonomies( 'post' );
		$term_query = get_terms( $prepared_args );

		// Map data by key for parent relationship lookup on import
		$this->term_query = $this->create_taxonomy_lookup( $term_query );

		$response['terms'] = $taxonomies;

		// Add counts to rest call.
		foreach ( $taxonomies as $key => $taxonomy ) {
			$count_args = array( 'hide_empty' => $request['hide_empty'] );
			$response['counts'][ $taxonomy ] = wp_count_terms( $taxonomy, $count_args );
		}

		// Output terms to array
		foreach ( $term_query as $term ) {
			$data = $this->prepare_item_for_response( $term, $request );
			$response['site_terms'][] = $this->prepare_response_for_collection( $data );
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
				'default'           => 0,
				'description'       => __( 'Hide terms without assignment', 'mmt' ),
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'taxonomy' => array(
				'description'       => __( 'Ascending or descending order for terms', 'mmt' ),
				'type'              => 'string',
				'default'           => implode( ',', get_object_taxonomies( 'post' ) ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'   => array(
				'description'       => __( 'Ascending or descending order for terms', 'mmt' ),
				'type'              => 'string',
				'default'           => 'ASC',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby' => array(
				'description'       => __( 'Order terms by ("name", "slug", "term_group", "term_id", "id", "description")', 'mmt' ),
				'type'              => 'string',
				'default'           => 'name',
				'sanitize_callback' => 'sanitize_text_field',
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
