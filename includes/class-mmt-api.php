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
	 * @since  0.1.0
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
	 * Hash Key
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @param string $key The key to be hashed.
	 *
	 * @return string $key
	 */
	private static function hash_key( $key ) {
		return hash_hmac( 'md5', $key, '292366AFF23AA43A31BBB6E48CAD2' );
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

		return ( hash_equals( self::hash_key( $migration_key ), $key ) ) ? true : false;
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
		$remote_key = self::hash_key( $remote_key );

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
	 * Clear Data
	 *
	 * @static
	 * @since 1.0.0
	 */
	public static function clear_data() {
		delete_transient( 'mmt_users' );
		delete_transient( 'mmt_users_conflicted' );
		delete_transient( 'mmt_users_referenced' );
		delete_transient( 'mmt_users_migrateable' );
		delete_transient( 'mmt_users_migrated' );
		delete_transient( 'mmt_users_migrated_referenced' );
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
		if ( false === ( $users = get_transient( 'mmt_users' ) ) ) {
			$users = self::get_data( 'users' );
			set_transient( 'mmt_users', $users, DAY_IN_SECONDS );
		}

		return $users;
	}

	/**
	 * Create Users Collection
	 *
	 * @static
	 * @since 0.1.0
	 *
	 * @param array $remote_users The remote users array.
	 *
	 * @return array
	 */
	public static function create_users_collection( $remote_users = array() ) {
		if ( empty( $remote_users ) ) {
			$remote_users = self::get_users();
		}

		// Clear stale data
		delete_transient( 'mmt_users_conflicted' );
		delete_transient( 'mmt_users_referenced' );
		delete_transient( 'mmt_users_migrateable' );

		// Define collection holders
		$current_site_users = array();
		$conflicted_users   = array();
		$referenced_users   = array();
		$migrateable_users  = array();

		// Get Current Site Users
		$current_users_query = new WP_User_Query( array( 'number' => - 1 ) );
		foreach ( $current_users_query->get_results() as $user ) {
			$current_site_users[] = array(
				'username' => $user->user_login,
				'email'    => $user->user_email,
				'user'     => $user,
			);
		}

		// Check for confligcts
		foreach ( $remote_users as $remote_user ) {

			// Search to see if they match
			$match_username = array_search( $remote_user['username'], array_column( $current_site_users, 'username' ), true );
			$match_email    = array_search( $remote_user['email'], array_column( $current_site_users, 'email' ), true );

			// Both Conflict
			if ( ( false !== $match_username ) && ( false !== $match_email ) ) {
				$referenced_users[] = array(
					'user'         => $remote_user,
					'current_user' => $current_site_users[ $match_username ]['user'],
					'conflict'     => 'username_and_email',
				);
				continue;
			}

			// Username Conflict.
			if ( false !== $match_username ) {
				$conflicted_users[] = array(
					'user'         => $remote_user,
					'current_user' => $current_site_users[ $match_username ]['user'],
					'conflict'     => 'username',
				);
				continue;
			}

			// Email Conflict.
			if ( false !== $match_email ) {
				$referenced_users[] = array(
					'user'         => $remote_user,
					'current_user' => $current_site_users[ $match_email ]['user'],
					'conflict'     => 'email',
				);
				continue;
			}

			// No username or email conflicts.
			$migrateable_users[] = array( 'user' => $remote_user );
		}

		// Set Transients for later
		set_transient( 'mmt_users_conflicted', $conflicted_users, DAY_IN_SECONDS );
		set_transient( 'mmt_users_referenced', $referenced_users, DAY_IN_SECONDS );
		set_transient( 'mmt_users_migrateable', $migrateable_users, DAY_IN_SECONDS );
	}

	/**
	 * Get Users Conflicted Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public static function get_users_conflicted_collection() {
		if ( false === ( $conflicting_users = get_transient( 'mmt_users_conflicted' ) ) ) {
			self::create_users_collection();
			$conflicting_users = get_transient( 'mmt_users_conflicted' );
		}

		return $conflicting_users;
	}

	/**
	 * Get Users Conflict Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public static function get_users_migratable_collection() {
		if ( false === ( $migrateable_users = get_transient( 'mmt_users_migrateable' ) ) ) {
			self::create_users_collection();
			$migrateable_users = get_transient( 'mmt_users_migrateable' );
		}

		return $migrateable_users;
	}

	/**
	 * Get Users Referenced Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public static function get_users_referenced_collection() {
		if ( false === ( $referenced_users = get_transient( 'mmt_users_referenced' ) ) ) {
			self::create_users_collection();
			$referenced_users = get_transient( 'mmt_users_referenced' );
		}

		return $referenced_users;
	}

	/**
	 * Migrate Referenced Users
	 *
	 * @since 0.1.0
	 *
	 * @param array $users The users to be referenced.
	 *
	 * @return array $created_users The created users.
	 */
	public static function migrate_users( $users = array() ) {
		if ( empty( $users ) ) {
			$users = self::get_users_migratable_collection();
		}

		$migrated_users = array();

		foreach ( $users as $user ) {
			$user     = $user['user'];
			$userdata = array(
				'user_login'      => $user['username'],
				'user_url'        => $user['url'],
				'user_email'      => $user['email'],
				'first_name'      => $user['first_name'],
				'last_name'       => $user['last_name'],
				'user_pass'       => null,
				'display_name'    => $user['name'],
				'description'     => $user['description'],
				'nickname'        => $user['nickname'],
				'user_nicename'   => $user['slug'],
				'user_registered' => $user['registered_date'],
			);

			$user_id = wp_insert_user( $userdata );

			if ( is_wp_error( $user_id ) ) {
				MMT::debug( $user_id->get_error_message() );
				continue;
			}

			$migrated_users[] = new WP_User( $user_id );
		}

		if ( ! empty( $migrated_users ) ) {
			set_transient( 'mmt_users_migrated', $migrated_users, DAY_IN_SECONDS );
		}

		return $migrated_users;
	}

	/**
	 * Migrate Users
	 *
	 * @since 0.1.0
	 *
	 * @param array $users The users to be migrated.
	 *
	 * @return array $created_users The created users.
	 */
	public static function migrate_referenced_users( $users = array() ) {
		if ( empty( $users ) ) {
			$users = self::get_users_referenced_collection();
		}

		$migrated_users = array();

		foreach ( $users as $user ) {
			$conflict     = $user['conflict'];
			$current_user = $user['current_user'];
			$user         = $user['user'];

			if ( is_a( $current_user, 'WP_User' ) ) {
				update_user_meta( $current_user->ID, 'mmt_reference_user_id', $user['id'] );
				update_user_meta( $current_user->ID, 'mmt_reference_user_object', $user );
			}

			$migrated_users[] = array(
				'user'         => $user,
				'current_user' => $current_user,
				'conflict'     => $conflict,
			);
		}

		if ( ! empty( $migrated_users ) ) {
			set_transient( 'mmt_users_migrated_referenced', $migrated_users, DAY_IN_SECONDS );
		}

		return $migrated_users;
	}

	/**
	 * Get Migrated Users
	 *
	 * @since 0.1.0
	 *
	 * @return array $migrated_users The users that were migrated.
	 */
	public static function get_migrated_users() {
		return ( false !== ( $users = get_transient( 'mmt_users_migrated' ) ) ) ? $users : array();
	}

	/**
	 * Get Migrated Users
	 *
	 * @since 0.1.0
	 *
	 * @return array $migrated_users The users that were migrated.
	 */
	public static function get_migrated_users_referenced() {
		return ( false !== ( $users = get_transient( 'mmt_users_migrated_referenced' ) ) ) ? $users : array();
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
