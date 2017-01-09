<?php
/**
 * Migration Merge Tool - Abstract - Rest Controller Class
 *
 * All other controllers shold be extended from this class.
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_Controller
 *
 * @since 0.1.0
 */
abstract class MMT_REST_Controller extends WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $namespace;

	/**
	 * The base of this controller's route.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base;

	/**
	 * Permissions check for getting all users.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		$this->get_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to read a user
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {

		// todo: fix problem with submitting credentials to begin wizard
		//if ( ! current_user_can( 'manage_options' ) ) {
		//	return new WP_Error( 'rest_user_no_access', __( 'Unauthorized. No soup for you!', 'mmt' ), array( 'status' => 401 ) );
		//}

		// Allow defining the secret key in the config, or setting the site option
		if ( defined( 'MMT_SECRET_KEY' ) ) {
			$api_secret = MMT_SECRET_KEY;
		} else {
			$api_secret = get_option( 'mmt_key' );
		}

		// If secret key is not defined, assume failed authentication
		if ( empty( $api_secret ) ) {
			return false;
		}

		$api_key = $request->get_header( 'x-mmt-key' );
		if ( ! $api_key ) {
			$api_key = $request->get_param( 'x-mmt-key' );
		}

		$has_access = (bool) ( $api_secret == $api_key );

		return apply_filters( 'mmt_rest_api_permissions_check', $has_access, $request, $this->rest_base );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context'  => array_merge( array( 'default' => 'view' ), $this->get_context_param() ),
			'api_key'  => array(
				'description'       => __( 'The api key to access this resource.', 'mmt' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'mmt' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'mmt' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'exclude'  => array(
				'description'       => __( 'Ensure result set excludes specific ids.', 'mmt' ),
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'include'  => array(
				'description'       => __( 'Limit result set to specific ids.', 'mmt' ),
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'offset'   => array(
				'description'       => __( 'Offset the result set by a specific number of items.', 'mmt' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
