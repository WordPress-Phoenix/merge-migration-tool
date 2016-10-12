<?php
/**
 * Autoloader Class
 *
 * @package Merge_Migration_Tool
 * @subpackage Includes
 * @since 0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Autoloader Class
 *
 * @since 0.1.0
 */
class MMT_Autoloader {
	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @access private
	 * @since 0.1.0
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @access private
	 * @since 0.1.0
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );

			return true;
		}

		return false;
	}

	/**
	 * Auto-load classes on demand to reduce memory consumption.
	 *
	 * @since 0.1.0
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		$this->load_file( MMT_INC . $file );
	}
}

/* Initialize */
new MMT_Autoloader();