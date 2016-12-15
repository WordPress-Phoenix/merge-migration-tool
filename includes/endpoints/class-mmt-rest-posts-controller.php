<?php
/**
 * Migration Merge Tool - Posts - Rest Controller Class
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_REST_Posts_Controller
 *
 * @since 0.1.0
 */
class MMT_REST_Posts_Controller extends MMT_REST_Controller {

	/**
	 * Rest Base
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'posts';

	/**
	 * MMT_REST_Posts_Controller constructor.
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
			)
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'migrate_blog_posts' ),
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

		$posts_query = new WP_Query(
			array(
				'post_type'      => 'post',
				'paged'           => $request['page'],
				'posts_per_page'  => $request['per_page'],
			)
		);
		$posts = array();

		$posts['total_pages'] = $posts_query->max_num_pages;
		$posts['page'] = $request['page'];
		$posts['per_page'] = $request['per_page'];

		foreach ( $posts_query->posts as $post ) {
			$itemdata = $this->prepare_item_for_response( $post, $request );
			$posts['posts'][]  = $this->prepare_response_for_collection( $itemdata );
		}

		// Wrap the media in a response object
		$response = rest_ensure_response( $posts );

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

		// add switch to handle case different cases

		$id    = (int) $request['id'];
		$post  = get_post( $id );

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
			return new WP_Error( 'rest_user_invalid_id', __( 'Invalid resource id.' ), array( 'status' => 404 ) );
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
	 * @param mixed           $post    The post object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return mixed
	 */
	public function prepare_item_for_response( $post, $request ) {
		$data   = array();
		$schema = $this->get_item_schema();

		$author = get_the_author_meta( 'email', $post->post_author );

		// grab any post meta
		$meta = get_post_meta( $post->ID );

		if ( isset( $meta['_thumbnail_id'] ) ) {
			$image_id = $meta['_thumbnail_id'][0];
			$featured_image = get_post( $image_id );
			$meta['_thumbnail_id'][0] = $featured_image->post_name;
		}

		$meta['_migrated_data']['migrated'] = true;
		$meta['_migrated_data']             = maybe_serialize( $meta['_migrated_data'] );

		$data = array(
			'post_author'           => $author,
			'post_date'             => $post->post_date,
			'post_date_gmt'         => $post->post_date_gmt,
			'post_content'          => $post->post_content,
			'post_title'            => $post->post_title,
			'post_excerpt'          => $post->post_excerpt,
			'post_status'           => $post->post_status,
			'comment_status'        => $post->comment_status,
			'ping_status'           => $post->ping_status,
			'post_password'         => $post->post_password,
			'post_name'             => $post->post_name,
			'to_ping'               => $post->to_ping,
			'pinged'                => $post->ping,
			'post_modified'         => $post->post_modified,
			'post_modified_gmt'     => $post->post_modified_gmt,
			'post_content_filtered' => $post->post_content_filtered,
			'post_parent'           => $post->post_parent,
			'guid'                  => $post->guid,
			'menu_order'            => $post->menu_order,
			'post_type'             => $post->post_type,
			'post_mime_type'        => $post->post_mime_type,
			'comment_count'         => $post->comment_count,
			'post_meta'             => $meta,
			'post_terms'            => $this->get_post_term_data( $post->ID )
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

		/**
		 * Filter media data returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $post     The post object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'mmt_rest_api_prepare_post', $response, $post, $request );
	}

	/**
	 * Ingest Posts from Remote Site
	 *
	 * @since 0.1.1
	 */
	public function migrate_blog_posts( $request ) {

		// Wrap the data in a response object
		$data = $request->get_body_params();
		$migrate_posts = $data['posts'];

		// setup url video swapping
		$current_site_url = get_site_url();
		$migrate_site_url = rtrim( MMT_API::get_remote_url(), '/' );

		foreach ( $migrate_posts as $postdata ) {

			// Make sure we the post does not exist already
			// todo: is it enough to check slug
			$post_exist = get_page_by_path( $postdata['post_name'], OBJECT, 'post' );
			if ( $post_exist->post_name === $postdata['post_name'] ) {
				continue;
			}

			// look up and swap the author email with author id
			$author_email            = $postdata['post_author'];
			$existing_author         = get_user_by( 'email', $author_email );
			$postdata['post_author'] = $existing_author->ID;

			// highly unlikely, but just in case
			if ( ! $existing_author ) {
				$postdata['post_author'] = MMT_API::get_migration_author();
			}

			// swap url in guid
			$postdata['guid'] = str_replace( $migrate_site_url, $current_site_url, $postdata['guid'] );

			// swap url in content
			$postdata['post_content'] = str_replace( $migrate_site_url, $current_site_url, $postdata['post_content'] );

			// make it a post
			$id = wp_insert_post( $postdata );

			// if no errors add the post meta
			if ( ! is_wp_error( $id ) ) {

				// set the taxonomy terms
				foreach ( $postdata['post_terms'] as $term => $val ) {
					wp_set_object_terms( $id, $val, $term );
				}

				// set the featured image if there is one
				if ( isset( $postdata['post_meta']['_thumbnail_id'] ) ) {
					$migrate_title                          = $postdata['post_meta']['_thumbnail_id'][0];
					$attachment_post                        = get_page_by_title( $migrate_title, OBJECT, 'attachment' );
					$postdata['post_meta']['_thumbnail_id'] = $attachment_post->ID;
				}

				MMT_API::set_postmeta( $postdata['post_meta'], $id );

				// Track IDs for confirmation on the final media page
				//$this->migrated_media_ids[] = $id;

				//maybe remove from original array
				unset( $postdata );
			}
			//
			//if ( ! empty( $this->migrated_media_ids ) ) {
			//	set_transient( 'mmt_media_ids_migrated', $this->migrated_media_ids, DAY_IN_SECONDS );
			//}

			//todo: what do we do with posts that dont get inserted, recursion call?
		}


		$data['percentage'] = ( $data['page'] / $data['total_pages'] ) * 100;
		$data['page'] = absint( $data['page'] ) + 1;
		$data['total_pages'] = absint( $data['total_pages'] );
		$data['per_page'] = absint( $data['per_page'] );

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
		$post_query_params = array(
			'post_status' => array(
				'default'           => 'publish',
				'description'       => __( 'Current page of the collection.', 'mmt' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			)
		);
		return apply_filters( 'mmt_rest_api_post_params', array_merge( $post_query_params, $query_params ) );
	}

	/**
	 * Get post term data
	 *
	 * This function is used transfer taxonomy relationships and usage counts
	 *
	 * @param $post_id Post to retrieve terms from
	 *
	 * @return array
	 */
	public function get_post_term_data( $post_id ) {
		//todo: test with custom taxonomies
		// get register taxonomies
		$taxonomies = get_taxonomies( array( 'public' => 'true'), 'names' );

		// post_format not needed
		unset( $taxonomies['post_format'] );

		// ensure no dupes and set data to values for taxonomy lookup
		array_unique( $taxonomies );
		$taxonomies = array_values( $taxonomies );

		// get post terms
		$terms = wp_get_post_terms( $post_id, $taxonomies, array( "fields" => "all" ) );

		// send over only the information we need
		$terms_grouped = [];
		foreach ( $terms as $term ) {
			$terms_grouped[ $term->taxonomy ][] = $term->slug;
		}
		return $terms_grouped;
	}
}
