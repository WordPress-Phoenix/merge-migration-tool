<?php
/**
 * Migration Merge Tool - Users - Rest Controller Class
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_Users_Controller
 *
 * @since 0.1.0
 */
class MMT_REST_Users_Controller extends MMT_REST_Controller {

	/**
	 * Rest Base
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'users';

	/**
	 * MMT_REST_Users_Controller constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = apply_filters( 'mmt_rest_api_user_namespace', $namespace );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get users
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$users_query = new WP_User_Query( array( 'number' => 10 ) );
		$users       = array();
		foreach ( $users_query->get_results() as $user ) {
			$data    = $this->prepare_item_for_response( $user, $request );
			$users[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $users );

		return apply_filters( 'mmt_rest_api_user_items_response', $response );
	}

	/**
	 * Check if a given request has access to read a user
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$id    = (int) $request['id'];
		$user  = get_userdata( $id );
		$types = get_post_types( array( 'show_in_rest' => true ), 'names' );

		if ( empty( $id ) || empty( $user->ID ) ) {
			return new WP_Error( 'rest_user_invalid_id', __( 'Invalid user id.', 'mmt' ), array( 'status' => 404 ) );
		}

		return apply_filters( 'mmt_rest_api_permissions_check', true, $request, $this->rest_base );;
	}

	/**
	 * Get a single user by id
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$user = get_userdata( $id );

		if ( empty( $id ) || empty( $user->ID ) ) {
			return new WP_Error( 'rest_user_invalid_id', __( 'Invalid resource id.' ), array( 'status' => 404 ) );
		}

		$user     = $this->prepare_item_for_response( $user, $request );
		$response = rest_ensure_response( $user );

		return apply_filters( 'mmt_rest_api_user_item_response', $response );
	}

	/**
	 * Prepare a single user output for response
	 *
	 * @param object          $user    User object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $user, $request ) {

		// Get Schema
		$schema = $this->get_item_schema();

		// Data
		$data = array(
			'id'                 => ( ! empty( $schema['properties']['id'] ) ) ? $user->ID : '',
			'username'           => ( ! empty( $schema['properties']['username'] ) ) ? $user->user_login : '',
			'name'               => ( ! empty( $schema['properties']['name'] ) ) ? $user->display_name : '',
			'first_name'         => ( ! empty( $schema['properties']['first_name'] ) ) ? $user->first_name : '',
			'last_name'          => ( ! empty( $schema['properties']['last_name'] ) ) ? $user->last_name : '',
			'email'              => ( ! empty( $schema['properties']['email'] ) ) ? $user->user_email : '',
			'url'                => ( ! empty( $schema['properties']['url'] ) ) ? $user->user_url : '',
			'description'        => ( ! empty( $schema['properties']['description'] ) ) ? $user->description : '',
			'link'               => ( ! empty( $schema['properties']['link'] ) ) ? get_author_posts_url( $user->ID, $user->user_nicename ) : '',
			'nickname'           => ( ! empty( $schema['properties']['nickname'] ) ) ? $user->nickname : '',
			'slug'               => ( ! empty( $schema['properties']['slug'] ) ) ? $user->user_nicename : '',
			'roles'              => ( ! empty( $schema['properties']['roles'] ) ) ? array_values( $user->roles ) : array(),
			'registered_date'    => ( ! empty( $schema['properties']['registered_date'] ) ) ? date( 'c', strtotime( $user->user_registered ) ) : '',
			'capabilities'       => ( ! empty( $schema['properties']['capabilities'] ) ) ? (object) $user->allcaps : '',
			'extra_capabilities' => ( ! empty( $schema['properties']['extra_capabilities'] ) ) ? (object) $user->caps : '',
			'avatar_urls'        => ( ! empty( $schema['properties']['avatar_urls'] ) ) ? rest_get_avatar_urls( $user->user_email ) : '',
		);

		// Context
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Verify data
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $user ) );

		/**
		 * Filter user data returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $user     User object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'mmt_rest_api_user_prepare', $response, $user, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $user User object.
	 *
	 * @return array Links for the given user.
	 */
	protected function prepare_links( $user ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $user->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return apply_filters( 'mmt_rest_api_user_links', $links );
	}

	/**
	 * Get the User's schema, conforming to JSON Schema
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		// Additional Schema
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'user',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => __( 'Unique identifier for the user.', 'mmt' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'username'           => array(
					'description' => __( 'Login username for the user.', 'mmt' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_user',
					),
				),
				'name'               => array(
					'description' => __( 'Display name for the user.', 'mmt' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'first_name'         => array(
					'description' => __( 'First name for the user.', 'mmt' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'last_name'          => array(
					'description' => __( 'Last name for the user.', 'mmt' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'email'              => array(
					'description' => __( 'The email address for the user.', 'mmt' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view' ),
					'required'    => true,
				),
				'url'                => array(
					'description' => __( 'URL of the user.', 'mmt' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
				),
				'description'        => array(
					'description' => __( 'Description of the user.', 'mmt' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'link'               => array(
					'description' => __( 'Author URL to the user.', 'mmt' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'nickname'           => array(
					'description' => __( 'The nickname for the user.', 'mmt' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'               => array(
					'description' => __( 'An alphanumeric identifier for the user.', 'mmt' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'registered_date'    => array(
					'description' => __( 'Registration date for the user.', 'mmt' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'roles'              => array(
					'description' => __( 'Roles assigned to the user.', 'mmt' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
				),
				'password'           => array(
					'description' => __( 'Password for the user (never included).', 'mmt' ),
					'type'        => 'string',
					'context'     => array(), // Password is never displayed
					'required'    => true,
				),
				'capabilities'       => array(
					'description' => __( 'All capabilities assigned to the resource.', 'user' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'extra_capabilities' => array(
					'description' => __( 'Any extra capabilities assigned to the resource.', 'user' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		// Avatars
		if ( get_option( 'show_avatars' ) ) {
			$avatar_properties = array();
			$avatar_sizes      = rest_get_avatar_sizes();
			foreach ( $avatar_sizes as $size ) {
				$avatar_properties[ $size ] = array(
					'description' => sprintf( __( 'Avatar URL with image size of %d pixels.', 'mmt' ), $size ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
				);
			}
			$schema['properties']['avatar_urls'] = array(
				'description' => __( 'Avatar URLs for the resource.', 'mmt' ),
				'type'        => 'object',
				'context'     => array( 'view' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		$schema = apply_filters( 'mmt_rest_api_user_schema', $schema );

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections
	 *
	 * @since 0.1.0
	 *
	 * @return array $query_params
	 */
	public function get_collection_params() {
		$query_params      = parent::get_collection_params();
		$user_query_params = array(
			'order'   => array(
				'default'           => 'asc',
				'description'       => __( 'Order sort attribute ascending or descending.', 'mmt' ),
				'enum'              => array( 'asc', 'desc' ),
				'sanitize_callback' => 'sanitize_key',
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby' => array(
				'default'           => 'name',
				'description'       => __( 'Sort collection by object attribute.', 'mmt' ),
				'enum'              => array(
					'id',
					'include',
					'name',
					'registered_date',
					'slug',
					'email',
					'url',
				),
				'sanitize_callback' => 'sanitize_key',
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'roles'   => array(
				'description'       => __( 'Limit result set to resources matching at least one specific role provided. Accepts csv list or single role.', 'mmt' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_slug_list',
			),
		);

		return apply_filters( 'mmt_rest_api_user_params', array_merge( $user_query_params, $query_params ) );
	}
}