<?php
/**
 * Migration Merge Tool - API
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
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
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'controllers' ) );
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			add_filter( 'mmt_rest_api_permissions_check', array( $this, 'permissions' ), 10, 3 );
		}
	}

	/**
	 * REST API Controllers
	 *
	 * @since 0.1.0
	 */
	public function controllers() {
		$this->includes();
		$namespace   = apply_filters( 'mmt_rest_api_namespace', self::$namespace );
		$controllers = apply_filters( 'mmt_rest_api_controllers', array(
			'MMT_REST_Access_Controller',
			'MMT_REST_Users_Controller',
			'MMT_REST_Posts_Controller',
			'MMT_REST_Terms_Controller',
			'MMT_REST_Media_Controller',
		) );
		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller( $namespace );
			$this->$controller->register_routes();
		}
	}

	/**
	 * Include REST API Classes
	 *
	 * @since 0.1.0
	 */
	public function includes() {
		require_once MMT_INC . 'endpoints/abstract-class-mmt-rest-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-access-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-users-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-posts-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-terms-controller.php';
		require_once MMT_INC . 'endpoints/class-mmt-rest-media-controller.php';
	}

	/**
	 * Rest API Access Permissions
	 *
	 * @since 0.1.0
	 *
	 * @param bool            $permission The default permission of the request. Default is true.
	 * @param WP_REST_Request $request    Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function permissions( $permission = true, $request, $rest_base ) {
		$api_key = ( ! empty( $request['api_key'] ) ) ? esc_attr( $request['api_key'] ) : false;

		if ( ! $api_key || ! self::verify_remote_key( $api_key ) ) {
			return new WP_Error( 'rest_invalid_api_key', esc_html__( 'Your api key is invalid.', 'mmt' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return $permission;
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
		self::$remote_key = esc_attr( $key );
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
	 * Build out migration type options
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_migration_types() {
		$types = apply_filters('mmt_migration_types', array(
			'multisite-site-within-site'          => 'Site to site within multisite',
			'multisite-site-to-category'          => 'Site to cateogry within multisite',
			'multisite-site-from-other-multisite' => 'Site from one Multisite to another',
		));

		$options = '';
		foreach ( $types as $key => $type ) {
			$selected = selected(self::get_migration_type(), $key);
			$options .= sprintf( '<option value="%s" %s>%s</option>', $key, $selected, $type );
		}
		return $options;
	}

	/**
	 * Hash Key
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @param string $key The key to be hashed.
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
	 *
	 * @return bool|array
	 */
	public static function get_data( $endpoint, $args = array() ) {
		if ( empty( $endpoint ) ) {
			return false;
		}

		$remote_url = self::get_remote_url();
		$remote_key = self::get_remote_key();

		if ( empty( $remote_url ) || empty( $remote_key ) ) {
			return false;
		}

		// hash the key for some additional security, not much but....
		$remote_key = self::hash_key( $remote_key );

		$url = sprintf( '%s/wp-json/%s/%s', untrailingslashit( $remote_url ), self::get_namespace(), $endpoint );

		// Todo: Check for multsite support and handle.

		$url = add_query_arg( 'api_key', $remote_key, $url );

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
}

new MMT_API();
