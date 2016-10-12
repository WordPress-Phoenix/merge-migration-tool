<?php
/**
 * Merge Migration Tool Rest API Controller Class
 *
 * @package Merge_Migration_Tool
 * @subpackage Includes
 * @since 0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_Rest_Controller
 *
 * @since 0.1.0
 */
class MMT_REST_API_Controller extends WP_REST_Controller {

	/**
	 * Version
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $version = '1';

	/**
	 * Rest Type
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $type;

	/**
	 * MMT_REST_API_Controller constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param $type
	 */
	public function __construct( $type ) {
		$this->type      = apply_filters( 'mmt/rest_api/type', $type );
		$this->namespace = 'mmt/v' . $this->version;
		$this->rest_base = $this->type;
		MMT::debug( $this->rest_base );
	}

	/**
	 * Register REST API Routes
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, "/{$this->rest_base}/", array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => array(
					'offset' => array(
						'description'        => esc_html__( 'Offset the result set by a specific number of items.', 'mmt' ),
						'type'               => 'integer',
						'sanitize_callback'  => 'absint',
						'validate_callback'  => 'rest_validate_request_arg',
					),
				),
			)
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
		$items = array(); //do a query, call another class, etc
		$data  = array();
		foreach ( $items as $item ) {
			$itemdata = $this->prepare_item_for_response( $item, $request );
			$data[]   = $this->prepare_response_for_collection( $itemdata );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return apply_filters( 'mmt/rest_api/item_permissions/get', true, $request, $this->type );
	}
}