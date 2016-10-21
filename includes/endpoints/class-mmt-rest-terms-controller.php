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
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
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
		$terms_query = new WP_Term_Query( array( 'number' => 10 ) );
		$terms       = array();
		foreach ( $terms_query->get_terms() as $term ) {
			$termdata = $this->prepare_item_for_response( $term, $request );
			$terms[]  = $this->prepare_response_for_collection( $termdata );
		}

		// Wrap the terms in a response object
		$response = rest_ensure_response( $terms );

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
		$data   = array();
		$schema = $this->get_item_schema();

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

		/**
		 * Filter media data returned from the REST API.
		 *
		 * @param WP_REST_Response $response   The response object.
		 * @param object           $term The term object.
		 * @param WP_REST_Request  $request    Request object.
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

		return $query_params;
	}
}
