<?php
/**
 * Plugin Name: Merge Migration Tool
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
 * @package Merge_Migration_Tool
 * @category Plugin
 * @author scarstens, corycrowley, kyletheisen
 */

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'Merge_Migration_Tool' ) ) {
	/**
	 * Merge Migration Tool Class
	 *
	 * @since 0.1.0
	 */
	class Merge_Migration_Tool {
		/**
		 * Merge Migration Tool Instance
		 *
		 * @since 0.1.0
		 *
		 * @access private
		 * @var Merge_Migration_Tool
		 */
		private static $instance;

		/**
		 * Plugin Version
		 *
		 * @since 0.1.0
		 *
		 * @access private
		 * @var string
		 */
		private $version = '0.1.0';

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
		 * @since 0.1.0
		 */
		private function constants() {
			define( 'MMT_VER', $this->version );
			define( 'MMT_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'MMT_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
			define( 'MMT_LIB', trailingslashit( MMT_DIR . 'lib' ) );
			define( 'MMT_INC', trailingslashit( MMT_DIR . 'inc' ) );
			define( 'MMT_ASSETS', trailingslashit( MMT_URL . 'assets' ) );
			define( 'MMT_CSS', trailingslashit( MMT_ASSETS . 'css' ) );
			define( 'MMT_JS', trailingslashit( MMT_ASSETS . 'js' ) );
		}

		/**
		 * Libraries
		 *
		 * @access private
		 * @since 0.1.0
		 */
		private function libs() {
			if ( ! class_exists( 'WP_REST_Controller' ) ) {
				require_once MMT_LIB . 'class-wp-rest-controller.php';
			}
		}

		/**
		 * Includes
		 *
		 * @access private
		 * @since 0.1.0
		 */
		private function includes() {
			include_once MMT_INC . 'class-mmt-autoloader.php';
		}
	}
}

/* Initialize */
Merge_Migration_Tool::get_instance();
