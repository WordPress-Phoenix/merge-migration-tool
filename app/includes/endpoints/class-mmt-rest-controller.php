<?php
/**
 * Migration Merge Tool - Abstract - Rest Controller Class
 *
 * All other controllers should be extended from this class.
 *
 * @package    MMT
 * @subpackage Includes\Endpoints
 * @since      0.1.0
 */

namespace MergeMigrationTool\Includes\Endpoints;

use MergeMigrationTool\Admin\MMT_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MMT_REST_Controller
 *
 * @since 0.1.0
 */
abstract class MMT_REST_Controller extends \WP_REST_Controller {

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

		$api_key = $request->get_header( 'X-MMT-KEY' );
		if ( ! $api_key ) {
			$api_key = $request->get_param( 'X-MMT-KEY' );
		}

		$has_access = MMT_API::verify_remote_key( $api_key );

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
