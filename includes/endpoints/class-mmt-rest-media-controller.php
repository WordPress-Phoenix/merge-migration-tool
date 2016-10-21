<?php
/**
 * Migration Merge Tool - Media - Rest Controller Class
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_Media_Controller
 *
 * @since 0.1.0
 */
class MMT_REST_Media_Controller extends MMT_REST_Controller {

	/**
	 * Rest Base
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'media';

	/**
	 * MMT_REST_Media_Controller constructor.
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
		$media_items = array();
		$media       = array();
		foreach ( $media_items as $media_item ) {
			$itemdata = $this->prepare_item_for_response( $media_item, $request );
			$media[]  = $this->prepare_response_for_collection( $itemdata );
		}

		// Wrap the media in a response object
		$response = rest_ensure_response( $media );

		return $response;
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 0.1.0
	 *
	 * @param mixed           $media_item The Media Item object.
	 * @param WP_REST_Request $request    Request object.
	 *
	 * @return mixed
	 */
	public function prepare_item_for_response( $media_item, $request ) {
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
		 * @param object           $media_item Media Item object used to create response.
		 * @param WP_REST_Request  $request    Request object.
		 */
		return apply_filters( 'mmt_rest_api_prepare_media', $response, $media_item, $request );
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
