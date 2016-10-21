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
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'controllers' ) );
		add_filter( 'mmt_rest_api_permissions_check', array( $this, 'permissions' ), 10, 3 );
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
	 * @since 1.0.0
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
	 * @access private
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	private static function get_migration_key() {
		return get_option( 'mmt_key' );
	}

	/**
	 * Set REST API Remote Url
	 *
	 * @static
	 * @since 1.0.0
	 */
	public static function set_remote_url( $url ) {
		self::$remote_url = esc_url_raw( $url );
		update_option( self::$remote_url_input_name, self::$remote_url );
	}

	/**
	 * Get REST API Remote Url
	 *
	 * @static
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since  1.0.0
	 *
	 * @return 1.0.0
	 */
	public static function set_remote_key( $key ) {
		self::$remote_key = esc_attr( $key );
		update_option( self::$remote_key_input_name, self::$remote_key );
	}

	/**
	 * Get REST API Remote Key
	 *
	 * @static
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_remote_key_input_name() {
		return self::$remote_key_input_name;
	}

	/**
	 * Verify - Remote Key => Migration Key
	 *
	 * @static
	 * @since 1.0.0
	 *
	 * @param string $key The hashed key to verify.
	 *
	 * @return bool
	 */
	public static function verify_remote_key( $key ) {
		$migration_key = esc_attr( self::get_migration_key() );
		return ( hash_equals( wp_hash( $migration_key ), $key ) ) ? true : false;
	}

	/**
	 * Get REST API Data
	 *
	 * @since 1.0.0
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
		$remote_key = wp_hash( $remote_key );

		$url = sprintf( '%s/wp-json/%s/%s', untrailingslashit( $remote_url ), self::get_namespace(), $endpoint );
		$url = add_query_arg( 'api_key', $remote_key, $url );

		$response      = wp_safe_remote_get( esc_url_raw( $url ), $args );
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
	 * @since 1.0.0
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
	 * Get REST API Users
	 *
	 * @static
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_users() {
		return self::get_data( 'users' );
	}

	/**
	 * Process User Object
	 *
	 * This will do functionality to add the user to the site.
	 *
	 * @static
	 * @since 1.0.0
	 *
	 * @param object $user The user object.
	 *
	 * @return bool
	 */
	public static function process_user( $user ) {
		return true;
	}

	/**
	 * Get REST API Terms
	 *
	 * @static
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_terms() {
		return self::get_data( 'terms' );
	}
}

new MMT_API();