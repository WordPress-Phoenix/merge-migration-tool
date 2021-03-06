<?php
/**
 * Migration Merge Tool - API
 *
 * @package    MMT
 * @subpackage Admin
 * @since      0.1.0
 */

namespace MergeMigrationTool\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * @var string
	 */
	protected static $namespace = 'mmt/v1';

	/**
	 * Remote API Url
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $remote_url;

	/**
	 * Remote API Url setting name
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $remote_url_input_name = 'mmt_remote_api_url';

	/**
	 * Remote API Key
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $remote_key;

	/**
	 * Hide empty terms argument value
	 *
	 * @static
	 * @since 0.1.1
	 * @var boolean
	 */
	protected static $terms_hide_empty;

	/**
	 * Hide empty terms argument input name
	 *
	 * @static
	 * @since 0.1.1
	 * @var string
	 */
	protected static $terms_hide_empty_name = 'mmt_terms_include_empty';

	/**
	 * Remote API Key setting name
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $remote_key_input_name = 'mmt_remote_api_key';

	/**
	 * Type of Migration to Execute
	 *
	 * @static
	 * @since 0.1.1
	 * @var string
	 */
	protected static $migration_type;

	/**
	 * Methods of migration
	 *
	 * @static
	 * @since 0.1.1
	 * @var string
	 */
	protected static $migration_types = array();

	/**
	 * Migration Type Key setting name
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $migration_type_input_name = 'mmt_migration_type';

	/**
	 * Type of Migration to Execute
	 *
	 * @static
	 * @since 0.1.1
	 * @var string
	 */
	protected static $migration_author_id;

	/**
	 * Migration Type Key setting name
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $migration_author_input_name = 'mmt_fallback_author_id';

	/**
	 * Migration Batch Processing Quantity for Media setting name
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $migration_batch_media_input_name = 'mmt_media_batch_quantity_id';

	/**
	 * Migration Batch Processing Quantity for Posts setting name
	 *
	 * @static
	 * @since 0.1.0
	 * @var string
	 */
	protected static $migration_batch_post_input_name = 'mmt_post_batch_quantity_id';

	/**
	 * Posts per batch for media migration
	 *
	 * @static
	 * @since 0.1.1
	 * @var string
	 */
	protected static $migration_batch_media_quantity;

	/**
	 * Posts per batch for post migration
	 *
	 * @static
	 * @since 0.1.1
	 * @var string
	 */
	protected static $migration_batch_post_quantity;


	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'controllers' ) );
	}

	/**
	 * REST API Controllers
	 *
	 * @since 0.1.0
	 */
	public function controllers() {
		$namespace = apply_filters( 'mmt_rest_api_namespace', self::$namespace );

		$controllers = apply_filters( 'mmt_rest_api_controllers', array(
			'access',
			'users',
			'posts',
			'terms',
			'media',
		) );

		foreach ( $controllers as $controller ) {
			$psr_namespace = 'MergeMigrationTool\\Includes\\Endpoints';
			$controller_name = sprintf( '%s\\MMT_REST_%s_Controller', $psr_namespace, ucfirst( $controller ) );

			if ( ! class_exists( $controller_name ) ) {
				continue;
			}

			$controller = new $controller_name( $namespace );
			$controller->register_routes();
		}
	}

	/**
	 * Get REST API Namespace
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_namespace() {
		return apply_filters( 'mmt_rest_api_namespace', self::$namespace );
	}

	/**
	 * Get REST API Migration Key
	 *
	 * @static
	 * @access protected
	 *
	 * @since  0.1.0
	 *
	 * @return string
	 */
	protected static function get_migration_key() {
		return get_option( 'mmt_key' );
	}

	/**
	 * Set REST API Remote Url
	 *
	 * @static
	 * @since 0.1.0
	 */
	public static function set_remote_url( $url ) {
		self::$remote_url = esc_url_raw( $url );
		update_option( self::$remote_url_input_name, self::$remote_url );
	}

	/**
	 * Get REST API Remote Url
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return bool|string
	 */
	public static function get_remote_url() {
		return ( ! empty( self::$remote_url ) ) ? esc_url_raw( self::$remote_url ) : get_option( self::$remote_url_input_name );
	}

	/**
	 * Get REST API Remote Url Input Name
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return bool|string
	 */
	public static function get_remote_url_input_name() {
		return self::$remote_url_input_name;
	}

	/**
	 * Set REST API Remote Key
	 *
	 * @static
	 * @since  0.1.0
	 *
	 * @return 0.1.0
	 */
	public static function set_remote_key( $key ) {
		self::$remote_key = self::hash_key( $key );
		update_option( self::$remote_key_input_name, self::$remote_key );
	}

	/**
	 * Get REST API Remote Key
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return bool|string
	 */
	public static function get_remote_key() {
		return ( ! empty( self::$remote_key ) ) ? esc_attr( self::$remote_key ) : get_option( self::$remote_key_input_name );
	}

	/**
	 * Get REST API Remote Key Input Name
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_remote_key_input_name() {
		return self::$remote_key_input_name;
	}

	/**
	 * Set the migration type for reference
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function set_migration_type( $key ) {
		self::$migration_type = esc_attr( $key );
		update_option( self::$migration_type_input_name, self::$migration_type );
	}

	/**
	 * Retrieve Migration Type
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_migration_type() {
		return get_option( self::$migration_type_input_name );
	}

	/**
	 * Retrieve Migration Type input name for select form building
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_migration_type_input_name() {
		return self::$migration_type_input_name;
	}

	/**
	 * Get the terms Input name
	 *
	 * @return string
	 */
	public static function get_terms_empty_input_name() {
		return self::$terms_hide_empty_name;
	}

	/**
	 * Set terms hide_empty value
	 *
	 * @param $key
	 */
	public static function set_terms_empty_setting( $key ) {
		self::$terms_hide_empty = esc_attr( $key );
		update_option( self::$terms_hide_empty_name, self::$terms_hide_empty );
	}

	/**
	 * Get terms hide_empty value
	 */
	public static function get_terms_empty_setting() {
		return self::$terms_hide_empty;
	}

	/**
	 * Build out migration type options
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * todo: more options to come
	 *
	 * @return string
	 */
	public static function get_migration_types() {
		$types = apply_filters( 'mmt_migration_types', array(
			'multisite-site-within-site'          => 'Site to site within multisite',
		) );

		$options = '';
		foreach ( $types as $key => $type ) {
			$selected = selected( self::get_migration_type(), $key, false );
			$options .= sprintf( '<option value="%s" %s>%s</option>', $key, $selected, $type );
		}

		return $options;
	}

	/**
	 * Build out options for fallback author
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_migration_authors() {
		$users = get_users();
		$options = '';

		foreach ( $users as $user ) {
			$selected = selected( self::get_migration_author(), $user->data->ID, false );
			$options .= sprintf( '<option value="%s" %s>%s</option>', $user->data->ID, $selected, $user->data->user_nicename );
		}

		return $options;
	}

	/**
	 * Retrieve Migration Type input name for select form building
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_migration_author_input_name() {
		return self::$migration_author_input_name;
	}

	/**
	 * Retrieve Fallback Author ID
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return integer
	 */
	public static function get_migration_author() {
		return get_option( self::$migration_author_input_name );
	}

	/**
	 *
	 *
	 * @static
	 * @since 0.1.0
	 */
	public static function set_migration_author( $key ) {
		self::$migration_author_id = esc_attr( $key );
		update_option( self::$migration_author_input_name, self::$migration_author_id );
	}

	/**
	 * Get the media batch quantity input name
	 *
	 * @return string
	 */
	public static function get_media_batch_quantity_input_name() {
		return self::$migration_batch_media_input_name;
	}

	/**
	 * Get the post batch quantity input name
	 *
	 * @return string
	 */
	public static function get_post_batch_quantity_input_name() {
		return self::$migration_batch_post_input_name;
	}

	/**
	 * Set media per batch quantity
	 *
	 * @static
	 * @since 0.1.0
	 */
	public static function set_media_batch_quantity( $key ) {
		self::$migration_batch_media_quantity = esc_attr( $key );
		update_option( self::$migration_batch_media_input_name, self::$migration_batch_media_quantity );
	}

	/**
	 * Set posts per batch quantity
	 *
	 * @static
	 * @since 0.1.0
	 */
	public static function set_post_batch_quantity( $key ) {
		self::$migration_batch_post_quantity = esc_attr( $key );
		update_option( self::$migration_batch_post_input_name, self::$migration_batch_post_quantity );
	}

	/**
	 * Retrieve media per batch quantity
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return integer
	 */
	public static function get_media_batch_quantity() {
		return get_option( self::$migration_batch_media_input_name );
	}

	/**
	 * Retrieve posts per batch quantity
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return integer
	 */
	public static function get_post_batch_quantity() {
		return get_option( self::$migration_batch_post_input_name );
	}

	/**
	 * Hash Key
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @param string $key The key to be hashed.
	 *
	 * todo: swap this for something more secure
	 *
	 * @return string $key
	 */
	protected static function hash_key( $key ) {
		return hash_hmac( 'md5', $key, '292366AFF23AA43A31BBB6E48CAD2' );
	}

	/**
	 * Verify - Remote Key => Migration Key
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @param string $key The hashed key to verify.
	 *
	 * @return bool
	 */
	public static function verify_remote_key( $key ) {
		$migration_key = esc_attr( self::get_migration_key() );

		return ( hash_equals( self::hash_key( $migration_key ), $key ) ) ? true : false;
	}

	/**
	 * Get REST API Data
	 *
	 * @since 0.1.0
	 *
	 * @param string $endpoint The api endpoint.
	 * @param array  $args     Additional request args.
	 * @param array $params    Additional query string args.
	 *
	 * @return bool|array
	 */
	public static function get_data( $endpoint, $args = array(), $params = array() ) {
		if ( empty( $endpoint ) ) {
			return false;
		}

		$remote_url = self::get_remote_url();
		$remote_key = self::get_remote_key();

		if ( empty( $remote_url ) || empty( $remote_key ) ) {
			return false;
		}

		$url = sprintf( '%s/wp-json/%s/%s', untrailingslashit( $remote_url ), self::get_namespace(), $endpoint );

		// Provide a way to send additional query params
		$url = add_query_arg( $params, $url );

		$default = array(
			'headers' => array(
				'X-MMT-KEY' => $remote_key,
			),
			'wp-rest-cache' => 'exclude',
			'timeout' => 30,
		);
		$args = wp_parse_args( $args, $default );

		$response      = wp_remote_get( esc_url_raw( $url ), $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) || 200 !== $response_code ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Verify REST API Access
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public static function verify_access() {
		$data = self::get_data( 'access' );

		if ( ! is_array( $data ) || ! $data['access'] ) {
			return false;
		}

		return esc_attr( $data['access'] );
	}

	/**
	 * Get REST API Terms
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public static function get_terms() {
		return self::get_data( 'terms' );
	}

	/**
	 * Helper function for mapping data
	 *
	 * @param $items
	 * @param $callback
	 *
	 * @return array
	 */
	public static function map( $items, $callback ) {
		$results = [];
		foreach ( $items as $item ) {
			$results[] = $callback( $item );
		}

		return $results;
	}

	/**
	 * Set post metadata for imported attachments
	 *
	 * @param $fields
	 * @param $post_id
	 */
	public static function set_postmeta( $fields, $post_id ) {
		foreach ( $fields as $key => $data ) {
			if ( is_array( $data ) ) {
				$data = array_shift( $data );
			}
			$data = maybe_unserialize( $data );
			add_post_meta( $post_id, $key, $data );
		}
	}

	/**
	 * Retrieve a post given its post_name.
	 *
	 * Uses post name to check existence before importing.
	 *
	 * @see WordPress - get_page_by_title()
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb        $wpdb       WordPress database abstraction object.
	 *
	 * @param string       $post_name  Page title
	 * @param string       $output     Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to
	 *                                 a WP_Post object, an associative array, or a numeric array, respectively. Default OBJECT.
	 * @param string|array $post_type  Optional. Post type or array of post types. Default 'page'.
	 *
	 * @return WP_Post|array|null WP_Post (or array) on success, or null on failure.
	 */
	public static function get_post_by_post_name( $post_name, $output = OBJECT, $post_type = 'post' ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT ID
			FROM $wpdb->posts
			WHERE post_name = %s
			AND post_type = %s
		", $post_name, $post_type );

		$post = $wpdb->get_var( $sql );

		if ( $post ) {
			return get_post( $post, $output );
		}
	}

	/**
	 * Retrieve a post given its guid.
	 *
	 * Uses guid to check existence before importing.
	 *
	 * @see   WordPress - get_page_by_title()
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb        $wpdb       WordPress database abstraction object.
	 *
	 * @param string       $guid_fragment  Partial post guid [/YYYY/MM/filename.ext]
	 * @param string       $output     Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to
	 *                                 a WP_Post object, an associative array, or a numeric array, respectively. Default OBJECT.
	 * @param string|array $post_type  Optional. Post type or array of post types. Default 'page'.
	 *
	 * @return WP_Post|array|null|bool WP_Post (or array) on success, or null on failure.
	 */
	public static function get_post_by_guid( $guid_fragment, $output = OBJECT, $post_type = 'post' ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT ID
			FROM $wpdb->posts
			WHERE guid LIKE %s
			AND post_type = %s
		", '%' . $wpdb->esc_like($guid_fragment), $post_type );

		$post = $wpdb->get_var( $sql );

		if ( $post ) {
			return get_post( $post, $output );
		}

		return false;
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

