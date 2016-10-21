<?php
/**
 * Migration Merge Tool - Access - Rest Controller Class
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_Access_Controller
 *
 * @since 0.1.0
 */
class MMT_REST_Access_Controller extends MMT_REST_Controller {

	/**
	 * Rest Base
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'access';

	/**
	 * MMT_REST_Access_Controller constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = apply_filters( 'mmt_rest_api_user_namespace', $namespace );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Verify Access
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$api_key = esc_attr( $request['api_key'] );

		if ( ! $api_key || ! MMT_API::verify_remote_key( $api_key ) ) {
			return new WP_Error( 'rest_invalid_api_key', esc_html__( 'Your api key is invalid.', 'mmt' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$access   = $this->prepare_item_for_response( true, $request );
		$response = rest_ensure_response( $access );

		return apply_filters( 'mmt_rest_api_access_verified_response', $response );
	}

	/**
	 * Prepare a single user output for response
	 *
	 * @param bool            $access  The access state.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $access, $request ) {

		// Get Schema
		$schema = $this->get_item_schema();

		// Data
		$data = array( 'access' => ( ! empty( $schema['properties']['access'] ) ) ? $access : false );

		// Context
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Verify data
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

		/**
		 * Filter user data returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param bool             $access   The access state.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'mmt_rest_api_access_prepare', $response, $access, $request );
	}

	/**
	 * Get the User's schema, conforming to JSON Schema
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		// Additional Schema
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'user',
			'type'       => 'object',
			'properties' => array(
				'access' => array(
					'description' => __( 'Verify if the current request has access', 'mmt' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		$schema = apply_filters( 'mmt_rest_api_access_schema', $schema );

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => array_merge( array( 'default' => 'view' ), $this->get_context_param() ),
			'api_key' => array(
				'description'       => __( 'The api key to verify access to rest api.', 'mmt' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
