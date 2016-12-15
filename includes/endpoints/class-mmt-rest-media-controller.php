<?php
/**
 * Migration Merge Tool - Media - Rest Controller Class
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_Media_Controller
 *
 * @since 0.1.0
 */
class MMT_REST_Media_Controller extends MMT_REST_Controller {

	/**
	 * Rest Base
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'media';

	/**
	 * MMT_REST_Media_Controller constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register REST API Routes
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
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch', array(
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'migrate_media_posts' ),
			),
		) );
	}

	/**
	 * Get a collection of items
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		//check_ajax_referer( 'mmt_batch_data', 'security');

		$media_query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'paged'          => $request['page'],
				'posts_per_page' => $request['per_page'],
			)
		);

		$media = array();

		$media['total_pages'] = $media_query->max_num_pages;
		$media['page']        = $request['page'];
		$media['per_page']    = $request['per_page'];

		foreach ( $media_query->posts as $media_item ) {
			$itemdata         = $this->prepare_item_for_response( $media_item, $request );
			$media['posts'][] = $this->prepare_response_for_collection( $itemdata );
		}

		// Wrap the media in a response object
		$response = rest_ensure_response( $media );

		return $response;
	}

	/**
	 * Check if a given request has access to read a post
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post id.', 'mmt' ), array( 'status' => 404 ) );
		}

		return apply_filters( 'mmt_rest_api_permissions_check', true, $request, $this->rest_single_base );
	}

	/**
	 * Get a single post by id
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) ) {
			return new WP_Error( 'rest_user_invalid_id', __( 'Invalid media resource id.' ), array( 'status' => 404 ) );
		}

		$post     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $post );

		return apply_filters( 'mmt_rest_api_post_item_response', $response );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 0.1.0
	 *
	 * @param mixed           $media   The Media Item object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return mixed
	 */
	public function prepare_item_for_response( $media, $request ) {
		$meta = get_post_meta( $media->ID );

		// swap the parent slug for migrating
		// The post parent slug cannot be saved as a string, so it is
		// mapped to postmeta and will be deleted upon migration cleanup
		if ( 0 !== $media->post_parent ) {
			$parent_slug                      = get_post( $media->post_parent );
			$parent_slug                      = $parent_slug->post_name;
			$meta['_migrated_data']['parent'] = $parent_slug;
		}

		$meta['_migrated_data']['migrated'] = true;
		$meta['_migrated_data']             = maybe_serialize( $meta['_migrated_data'] );

		//swap the user id with email for migrating
		$author = get_the_author_meta( 'email', $media->post_author );

		$data = array(
			'post_author'           => $author,
			'post_date'             => $media->post_date,
			'post_date_gmt'         => $media->post_date_gmt,
			'post_content'          => $media->post_content,
			'post_title'            => $media->post_title,
			'post_excerpt'          => $media->post_excerpt,
			'post_status'           => $media->post_status,
			'comment_status'        => $media->comment_status,
			'ping_status'           => $media->ping_status,
			'post_password'         => $media->post_password,
			'post_name'             => $media->post_name,
			'to_ping'               => $media->to_ping,
			'ping'                  => $media->ping,
			'post_modified'         => $media->post_modified,
			'post_modified_gmt'     => $media->post_modified_gmt,
			'post_content_filtered' => $media->post_content_filtered,
			'guid'                  => $media->guid,
			'menu_order'            => $media->menu_order,
			'post_type'             => $media->post_type,
			'post_mime_type'        => $media->post_mime_type,
			'comment_count'         => $media->comment_count,
			'post_meta'             => $meta
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

		/**
		 * Filter media data returned from the REST API.
		 *
		 * @param WP_REST_Response $response   The response object.
		 * @param object           $media_item Media Item object used to create response.
		 * @param WP_REST_Request  $request    Request object.
		 */
		return apply_filters( 'mmt_rest_api_prepare_media', $response, $media_item, $request );
	}

	/**
	 * Ingest Media Posts from Remote Site
	 *
	 * @since 0.1.1
	 */
	public function migrate_media_posts( $request ) {

		$data          = $request->get_body_params();
		$migrate_posts = $data['posts'];

		foreach ( $migrate_posts as $postdata ) {

			// Make sure we the post does not exist already
			// todo: is it enough to check slug
			$post_exist = get_page_by_title( $postdata['post_name'], OBJECT, 'attachment' );
			if ( $post_exist->post_name === $postdata['post_name'] ) {
				continue;
			}

			// look up and swap the author email with author id
			$author_email            = $postdata['post_author'];
			$existing_author         = get_user_by( 'email', $author_email );
			$postdata['post_author'] = $existing_author->ID;

			// ensure some sort of author is selected
			if ( ! $existing_author ) {
				$postdata['post_author'] = MMT_API::get_migration_author();
			}

			// handle url swapping
			$current_site_url = get_site_url();
			$migrate_site_url = rtrim( MMT_API::get_remote_url(), '/' );
			$postdata['guid'] = str_replace( $migrate_site_url, $current_site_url, $postdata['guid'] );

			// make it a post
			$id = wp_insert_post( $postdata );

			// if no errors add the post meta
			if ( ! is_wp_error( $id ) ) {
				MMT_API::set_postmeta( $postdata['post_meta'], $id );

				//maybe remove from original array
				unset( $postdata );
			}

			if ( ! empty( $this->migrated_media_ids ) ) {
				set_transient( 'mmt_media_ids_migrated', $this->migrated_media_ids, DAY_IN_SECONDS );
			}

			//todo: what do we do with posts that do not get inserted, recursion call?
		}

		$data['percentage']  = ( $data['page'] / $data['total_pages'] ) * 100;
		$data['page']        = absint( $data['page'] ) + 1;
		$data['total_pages'] = absint( $data['total_pages'] );
		$data['per_page']    = absint( $data['per_page'] );

		unset( $data['posts'] );

		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get the query params for collections
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		return $query_params;
	}
}
