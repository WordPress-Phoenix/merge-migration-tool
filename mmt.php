<?php
/**
 * Plugin Name: Migration Merge Tool
 * Plugin URI: https://github.com/WordPress-Phoenix/merge-migration-tool
 * Description: Migration or Merging the contents and users of 2 sites - multisite or single site.
 * Author: FanSided
 * Version: 0.1.0
 * Author URI: http://fansided.com
 * License: GPL V2
 * Text Domain: mmt
 *
 * GitHub Plugin URI: https://github.com/WordPress-Phoenix/merge-migration-tool
 * GitHub Branch: master
 *
 * @package  MMT
 * @category Plugin
 * @author   scarstens, corycrowley, kyletheisen
 */

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'MMT' ) ) {
	/**
	 * Migration Merge Tool Class
	 *
	 * @since 0.1.0
	 */
	class MMT {

		/**
		 * Merge Migration Tool Instance
		 *
		 * @since  0.1.0
		 *
		 * @access private
		 * @var Merge_Migration_Tool
		 */
		private static $instance;

		/**
		 * Plugin Version
		 *
		 * @since  0.1.0
		 *
		 * @access private
		 * @var string
		 */
		private $version = '0.1.1';

		/**
		 * Merge Migration Tool Singleton Instance
		 *
		 * @since 0.1.0
		 *
		 * @return Merge_Migration_Tool
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 0.1.0
		 */
		public function __construct() {
			$this->constants();
			$this->libs();
			$this->includes();
		}

		/**
		 * Define Constants
		 *
		 * @access private
		 * @since  0.1.0
		 */
		private function constants() {
			define( 'MMT_VERSION', $this->version );
			define( 'MMT_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'MMT_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
			define( 'MMT_LIB', trailingslashit( MMT_DIR . 'lib' ) );
			define( 'MMT_INC', trailingslashit( MMT_DIR . 'includes' ) );
			define( 'MMT_ASSETS', trailingslashit( MMT_URL . 'assets' ) );
			define( 'MMT_CSS', trailingslashit( MMT_ASSETS . 'css' ) );
			define( 'MMT_JS', trailingslashit( MMT_ASSETS . 'js' ) );
		}

		/**
		 * Libraries
		 *
		 * @access private
		 * @since  0.1.0
		 */
		private function libs() {
			include_once MMT_LIB . 'wp-rest-functions.php';
			if ( ! class_exists( 'WP_REST_Controller' ) ) {
				require_once MMT_LIB . 'class-wp-rest-controller.php';
			}
		}

		/**
		 * Includes
		 *
		 * @access private
		 * @since  0.1.0
		 */
		private function includes() {
			require_once MMT_INC . 'class-mmt-api.php';
			require_once MMT_INC . 'class-mmt-admin.php';
		}

		/**
		 * Debug Function
		 *
		 * @static
		 * @since 0.1.0
		 *
		 * @param string $message The debug message.
		 *
		 * @return void
		 */
		public static function debug( $message ) {
			if ( WP_DEBUG === true ) {
				if ( is_array( $message ) || is_object( $message ) ) {
					error_log( print_r( $message, true ) );
				} else {
					error_log( $message );
				}
			}
		}
	}

	/**
	 * Initialize
	 *
	 * @since 0.1.0
	 */
	add_action( 'plugins_loaded', array( 'MMT', 'get_instance' ), 10, 1 );
}
