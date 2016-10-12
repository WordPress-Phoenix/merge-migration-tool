<?php
/**
 * Utility Class
 *
 * @package Merge_Migration_Tool
 * @subpackage Includes
 * @since 0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Utility Class
 *
 * @since 0.1.0
 */
class MMT_Utils {
	/**
	 * Debug Function
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @param string $message
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