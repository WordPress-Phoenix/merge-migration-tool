<?php
/**
 * Migration Merge Tool - Abstract Plugin Base Extension.
 *
 * @package    MMT
 * @since      2.0.0
 */
namespace MergeMigrationTool;

use WPAZ_Plugin_Base\V_2_0\Abstract_Plugin;
Use MergeMigrationTool\Admin\MMT_Admin;
Use MergeMigrationTool\Admin\MMT_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Init extends Abstract_Plugin {

	/**
	 * Use magic constant to tell abstract class current namespace as prefix for all other namespaces in the plugin.
	 *
	 * @var string $autoload_class_prefix magic constant
	 */
	public static $autoload_class_prefix = __NAMESPACE__;

	/**
	 * Usually the depth of your namespace prefix, defaults to 1, only applies to psr-4 autoloading type.
	 *
	 * @var string $autoload_ns_match_depth more efficient when set to 2, when using package [ns_prefix]/[ns]
	 */
	public static $autoload_ns_match_depth = 1;

	/**
	 * Autoload type can be classmap or psr-4
	 *
	 * @var string $autoload_dir classmap or psr-4
	 */
	public static $autoload_type = 'psr-4';

	/**
	 * Magic constant trick that allows extended classes to pull actual server file location, copy into subclass.
	 *
	 * @var string $current_file
	 */
	protected static $current_file = __FILE__;

	/**
	 * Initialize the plugin - for admin (back end)
	 * You would expected this to be handled on action admin_init, but it does not properly handle
	 * the use case for all logged in user actions. Always keep is_user_logged_in() wrapper within
	 * this function for proper usage.
	 *
	 * @since   0.1
	 * @return  void
	 */
	public function authenticated_init() {
		if ( is_user_logged_in() ) {
			new MMT_Admin();
		}
	}

	/**
	 * Initialize the plugin - for public (front end)
	 *
	 * @since   0.1
	 * @return  void
	 */
	public function init() {
		do_action( get_called_class() . '_before_init' );

		if ( ! wp_doing_ajax() && ! defined( 'DOING_AUTOSAVE' ) ) {
			new MMT_API();
		}

		do_action( get_called_class() . '_after_init' );
	}

	/**
	 * Initialize the plugin - for public (front end)
	 *
	 * @param mixed $instance Parent instance passed through to child.
	 *
	 * @since   0.1
	 * @return  void
	 */
	public function onload( $instance ) {
		// public initialize
		add_action( 'init', array( $this, 'init' ) );
		// auth init
		add_action( 'init', array( $this, 'authenticated_init' ) );
	}

	/**
	 * Enforce that the plugin prepare any defines or globals in a standard location.
	 */
	protected function defines_and_globals() {
		define( 'MMT_VERSION', '2.0.0' );
		define( 'MMT_NAME', plugin_basename( __FILE__ ) );
		define( 'MMT_URL', plugin_dir_url( __FILE__ ) );
		define( 'MMT_PATH', dirname( __FILE__ ) );
		define( 'MMT_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'MMT_LIB', trailingslashit( MMT_DIR . 'lib' ) );
		define( 'MMT_INC', trailingslashit( MMT_DIR . 'includes' ) );
		define( 'MMT_ASSETS', trailingslashit( MMT_URL . 'assets' ) );
		define( 'MMT_CSS', trailingslashit( MMT_ASSETS . 'css' ) );
		define( 'MMT_JS', trailingslashit( MMT_ASSETS . 'js' ) );
	}
}