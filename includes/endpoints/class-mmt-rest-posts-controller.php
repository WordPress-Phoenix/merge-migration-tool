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

		// todo: add call per page request
		$posts_query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 10, 'post_status' => 'publish' ) );
		$posts       = array();
		foreach ( $posts_query->posts as $post ) {
			$itemdata = $this->prepare_item_for_response( $post, $request );
			$posts[]  = $this->prepare_response_for_collection( $itemdata );
		}

		// Wrap the media in a response object
		$response = rest_ensure_response( $posts );

		return $response;
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
