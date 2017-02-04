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
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
		) );
		//register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
		//	array(
		//		'methods'             => WP_REST_Server::READABLE,
		//		'callback'            => array( $this, 'get_item' ),
		//		'permission_callback' => array( $this, 'get_item_permissions_check' ),
		//	),
		//) );
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
		$media_query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'paged'          => $request['page'],
				'posts_per_page' => $request['per_page'],
			)
		);
		if ( $media_query->have_posts() ) {
			$media = array();

			$media['total_pages'] = $media_query->max_num_pages;
			$media['page']        = $request['page'];
			$media['per_page']    = $request['per_page'];

			foreach ( $media_query->posts as $media_item ) {
				$itemdata         = $this->prepare_item_for_response( $media_item, $request );
				$media['posts'][] = $this->prepare_response_for_collection( $itemdata );
			}
		} else {
			return new WP_Error( 'rest_post_no_posts', __( 'No Media Content.' ), array( 'status' => 404 ) );
		}

		// Wrap the media in a response object
		$response = rest_ensure_response( $media );

		return $response;
	}

	/**
	 * Get a single post by id
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * // todo: move this logic into get_items method
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	//public function get_item( $request ) {
	//	$id   = (int) $request['id'];
	//	$user = get_userdata( $id );
	//
	//	if ( empty( $id ) || empty( $user->ID ) ) {
	//		return new WP_Error( 'rest_media_invalid_id', __( 'Invalid resource id.' ), array( 'status' => 404 ) );
	//	}
	//
	//	$post     = $this->prepare_item_for_response( $post, $request );
	//	$response = rest_ensure_response( $post );
	//
	//	return apply_filters( 'mmt_rest_api_post_item_response', $response );
	//}

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

		/**
		 * Swap the parent slug for migrating
		 *
		 * The post parent slug cannot be saved as a string, so it is mapped to postmeta and will
		 * be deleted upon migration cleanup.
		 */
		if ( 0 !== $media->post_parent ) {
			$parent_slug                      = get_post( $media->post_parent );
			if ( $parent_slug ) {
				$parent_slug                      = $parent_slug->post_name;
				$meta['_migrated_data']['parent'] = $parent_slug;
			}
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
			'post_meta'             => $meta,
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
		return apply_filters( 'mmt_rest_api_prepare_media', $response, $data, $request );
	}

	/**
	 * Ingest Media Posts from Remote Site
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function migrate_media_posts( $request ) {

		$data = MMT_API::get_data( 'media', [ 'timeout' => 40 ], $request->get_body_params() );

		// Setup var to not tax the server looping through each time
		$current_site_url = get_site_url();
		$migrate_site_url = rtrim( MMT_API::get_remote_url(), '/' );
		$fallback_migration_author = MMT_API::get_migration_author();

		if ( $data['posts'] ) {
			foreach ( $data['posts'] as &$postdata ) {

				// Make sure we the post does not exist already
				$post_exist = MMT_API::get_post_by_post_name( $postdata['post_name'], OBJECT, 'attachment' );
				if ( $post_exist !== null && ( $post_exist->post_name == $postdata['post_name'] ) ) {
					continue;
				}

				// look up and swap the author email with author id
				$author_email            = $postdata['post_author'];
				$existing_author         = get_user_by( 'email', $author_email );

				$postdata['post_author'] = $fallback_migration_author;

				// ensure some sort of author is selected
				if ( $existing_author ) {
					$postdata['post_author'] = $existing_author->ID;
				}

				// handle url swapping
				$postdata['guid'] = str_replace( $migrate_site_url, $current_site_url, $postdata['guid'] );

				// make it a post
				$id = wp_insert_post( $postdata );

				// if no errors add the post meta
				if ( ! is_wp_error( $id ) && $id > 0 ) {
					MMT_API::set_postmeta( $postdata['post_meta'], $id );

					// Setting to null forces garbage collection
					$postdata = null;
					unset( $postdata );
				}

			}
		}

		$data['percentage']  = ( $data['page'] / $data['total_pages'] ) * 100;

		if ( $data['page'] >= $data['total_pages'] ) {
			$data['percentage'] = 100;
		}

		$data['page']        = absint( $data['page'] ) + 1;
		$data['total_pages'] = absint( $data['total_pages'] );
		$data['per_page']    = absint( $data['per_page'] );

		// Setting to null forces garbage collection
		$data['posts'] = null;

		sleep( 2 );

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
