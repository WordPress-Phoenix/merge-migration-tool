<?php
/**
 * Migration Merge Tool - Rest API
 *
 * @package MMT
 * @subpackage Includes
 * @since 0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_API
 *
 * @since 0.1.0
 */
class MMT_REST_API {
	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		do_action( 'mmt/rest_api/init' );
	}

	/**
	 * Register Rest Routes
	 *
	 * @since 0.1.0
	 */
	public function register_rest_routes() {
		$this->endpoints();
		$types = apply_filters( 'mmt/rest_api/types', array( 'users', 'terms', 'posts', 'media' ) );
		foreach ( $types as $type ) {
			$controller = new MMT_REST_API_Controller( $type );
			$controller->register_routes();
		}
	}

	/**
	 * Rest API Endpoints
	 *
	 * @since 0.1.0
	 */
	public function endpoints() {
		require_once MMT_INC . 'endpoints/class-mmt-rest-api-controller.php';
		do_action( 'mmt/rest_api/endpoints' );
	}
}

new MMT_REST_API();