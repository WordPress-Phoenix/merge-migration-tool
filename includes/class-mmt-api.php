<?php
/**
 * Migration Merge Tool - API
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
class MMT_API {

	/**
	 * API Namespace
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $namespace = 'mmt/v1';

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_api_controllers' ) );
	}

	/**
	 * Register REST API Controllers
	 *
	 * @since 0.1.0
	 */
	public function register_rest_api_controllers() {
		$this->rest_api_includes();
		$this->namespace = apply_filters( 'mmt_rest_api_namespace', $this->namespace );
		$controllers     = apply_filters( 'mmt_rest_api_controllers', array(
			'MMT_REST_Users_Controller',
			'MMT_REST_Posts_Controller',
			'MMT_REST_Terms_Controller',
			'MMT_REST_Media_Controller',
		) );
		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller( $this->namespace );
			$this->$controller->register_routes();
		}
	}

	/**
	 * Include REST API Classes
	 *
	 * @since 0.1.0
	 */
	public function rest_api_includes() {
		require_once MMT_INC . 'endpoints/class-mmt-rest-users-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-posts-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-terms-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-media-controller.php';
		/**
		 * Hook: Rest API Includes
		 *
		 * @since 0.1.0
		 */
		do_action( 'mmt_rest_api_includes', $this );
	}
}

new MMT_API();