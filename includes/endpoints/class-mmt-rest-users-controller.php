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

		// Args for query.
		$user_args            = array();
		$user_args['exclude'] = $request['exclude'];
		$user_args['include'] = $request['include'];
		$user_args['order']   = $request['order'];
		$user_args['number']  = $request['per_page'];
		if ( ! empty( $request['offset'] ) ) {
			$user_args['offset'] = $request['offset'];
		} else {
			$user_args['offset'] = ( $request['page'] - 1 ) * $user_args['number'];
		}
		$orderby_possibles     = array(
			'id'              => 'ID',
			'include'         => 'include',
			'name'            => 'display_name',
			'registered_date' => 'registered',
			'slug'            => 'user_nicename',
			'email'           => 'user_email',
			'url'             => 'user_url',
		);
		$user_args['orderby']  = $orderby_possibles[ $request['orderby'] ];
		$user_args['role__in'] = $request['roles'];
		$user_args['fields']   = ( isset( $request['fields'] ) ) ? $request['fields'] : 'all';

		/**
		 * Filter User Query Arguments
		 *
		 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
		 *
		 * @param array           $user_args Array of arguments for WP_User_Query.
		 * @param WP_REST_Request $request   The current request.
		 */
		$user_args = apply_filters( 'mmt_rest_api_user_query', $user_args, $request );

		$users_query = new WP_User_Query( $user_args );

		$users = array();
		foreach ( $users_query->get_results() as $user ) {
			$data    = $this->prepare_item_for_response( $user, $request );
			$users[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $users );

		// Store pagation values for headers then unset for count query.
		$per_page = (int) $user_args['number'];
		$page     = ceil( ( ( (int) $user_args['offset'] ) / $per_page ) + 1 );

		$user_args['fields'] = 'ID';

		$total_users = $users_query->get_total();
		if ( $total_users < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count
			unset( $user_args['number'] );
			unset( $user_args['offset'] );
			$user_count_query = new WP_User_Query( $user_args );
			$total_users      = $user_count_query->get_total();
		}
		$response->header( 'X-WP-Total', (int) $total_users );
		$max_pages = ceil( $total_users / $per_page );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

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

		return apply_filters( 'mmt_rest_api_permissions_check', true, $request, $this->rest_base );
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
			'id'                 => $user->ID,
			'username'           => $user->user_login,
			'name'               => $user->display_name,
			'first_name'         => $user->first_name,
			'last_name'          => $user->last_name,
			'email'              => $user->user_email,
			'url'                => $user->user_url,
			'description'        => $user->description,
			'password'           => $user->user_pass,
			'link'               => get_author_posts_url( $user->ID, $user->user_nicename ),
			'nickname'           => $user->nickname,
			'slug'               => $user->user_nicename,
			'role'               => $user->role,
			'roles'              => array_values( $user->roles ),
			'registered_date'    => date( 'c', strtotime( $user->user_registered ) ),
			'capabilities'       => (object) $user->allcaps,
			'extra_capabilities' => (object) $user->caps,
			'avatar_urls'        => rest_get_avatar_urls( $user->user_email ),
			'meta'               => array(),
		);

		// Meta
		$meta = array_map( function ( $a ) {
			return $a[0];
		}, get_user_meta( $user->ID ) );

		// Populate Data
		if ( $meta ) {
			foreach ( $meta as $meta_key => $meta_value ) {
				$data['meta'][ $meta_key ] = $meta_value;
			}
		}

		// Todo: Clean user meta so as not to duplicate what is already in the user object.

		// Context
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Verify data
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

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
					'context'     => array( 'view' ), // Password is never displayed
					'required'    => true,
				),
				'capabilities'       => array(
					'description' => __( 'All capabilities assigned to the resource.', 'mmt' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'extra_capabilities' => array(
					'description' => __( 'Any extra capabilities assigned to the resource.', 'mmt' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'meta'               => array(
					'description' => __( 'Any user meta fields assigned to the resource', 'user' ),
					'type'        => 'array',
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
