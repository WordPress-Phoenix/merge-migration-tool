<?php
/**
 * Migration Merge Tool - Wizard - Posts Wizard Step
 *
 * Users step controller.
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_Wizard_Step_Posts
 *
 * todo: cleanup migration
 *
 * @since 0.1.0
 */
class MMT_Wizard_Step_Posts extends MMT_Wizard_Step {

	/**
	 * Name
	 *
	 * @since 0.1.0
	 */
	public $name = 'posts';

	/**
	 * Container for migrated media post ids on final
	 * media migration screen
	 *
	 * @var array
	 */
	public $migrated_media_ids = [];

	/**
	 * Register Step
	 *
	 * @since 1.0.0
	 */
	public function register() {
		return apply_filters( "mmt_wizard_step_{$this->name}", array(
			'name' => __( 'Posts', 'mmt' ),
			'sub_steps' => apply_filters( "mmtm_wizard_step_{$this->name}_sub_steps", array(
				'posts'          => array(
					'name'    => __( 'Posts', 'mmt' ),
					'view'    => array( $this, 'posts_migration' ),
					'handler' => array( $this, 'posts_migration_handler' ),
				),
				'posts_process'  => array(
					'name'    => __( 'Get Posts', 'mmt' ),
					'view'    => array( $this, 'posts_process' ),
					'handler' => array( $this, 'posts_process_handler' ),
				),
				'posts_complete' => array(
					'name'    => __( 'Post Migration Complete', 'mmt' ),
					'view'    => array( $this, 'posts_complete' ),
					'handler' => array( $this, 'posts_complete_handler' ),
				),
			) ),
		) );
	}

	/**
	 * Posts Migration
	 *
	 * @since 0.1.0
	 */
	public function posts_migration() {
		$url = MMT_API::get_remote_url();

		// todo: maybe swap this out
		$migration_author_input_name = MMT_API::get_migration_author_input_name();
		$migration_authors           = MMT_API::get_migration_authors();
		?>
		<h1><?php esc_attr_e( 'Posts Migration', 'mmt' ); ?></h1>
		<form method="post">
            <p><?php esc_html_e( 'During the next few steps, this tool will migrate all media from the following site:', 'mmt' ); ?></p>
            <p><?php printf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_url( $url ) ); ?></p>

            <div>
                <strong>Fallback Author</strong>
                <p>
                    Set a fallback author to assign media posts where the author may not exist. <br>
                    <select name="<?php echo esc_attr( $migration_author_input_name ); ?>"
                            id="<?php echo esc_attr( $migration_author_input_name ); ?>">
						<?php echo $migration_authors; ?>
                    </select>
                </p>
            </div>

            <p><?php esc_html_e( 'To continue, please click the button below.', 'mmt' ); ?></p>

			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->wizard->skip_next_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Skip', 'mmt' ); ?></a>
				<a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Posts Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function posts_migration_handler() {
		$this->wizard->verify_security_field();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}


	public function posts_process() {
		$posts = $this->get_blog_posts();
		?>
        <form method="post">
            <h3><?php echo count( $posts ); ?> <?php esc_html_e( 'Posts will be migrated:', 'mmt' ); ?></h3>
            <div class="mmt-items-list-overflow">
				<?php foreach ( $posts as $post ) { ?>
                    <div class="mmt-item"><?php esc_attr_e( $post['guid'] ) ?></div>
				<?php } ?>
            </div>
            <p class="mmt-actions step">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
                <a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>"
                   class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
            </p>
        </form>
		<?php
	}

	public function posts_process_handler() {
		$this->wizard->verify_security_field();
		$this->migrate_blog_posts();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	public function posts_complete() {
		?>
        <h1><?php esc_attr_e( 'Media Migration Complete!', 'mmt' ); ?></h1>
        <form method="post">
            <h3><?php esc_html_e( 'Migrated Media', 'mmt' ); ?></h3>
            <p><?php echo '10' ?><?php esc_html_e( 'media posts were migrated to the current site.', 'mmt' ); ?></p>
            <div class="mmt-items-list-overflow">
                <div class="mmt-item"></div>
            </div>
            <p class="mmt-actions step">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
                <a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>"
                   class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
            </p>
        </form>
		<?php
	}

	public function posts_complete_handler() {
		$this->wizard->verify_security_field();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Make the api call to grab regular posts
	 *
	 * @return array|bool
	 */
	public function get_blog_posts() {
		$posts = MMT_API::get_data( 'posts' );
		return $posts;
	}

	/**
	 * Ingest Posts from Remote Site
	 *
	 * @since 0.1.1
	 */
	public function migrate_blog_posts() {
		// todo: maybe process this differently?
		$migrate_posts = $this->get_blog_posts();

		// setup url video swapping
		$current_site_url = get_site_url();
		$migrate_site_url = rtrim( MMT_API::get_remote_url(), '/' );

		foreach ( $migrate_posts as $postdata ) {

			// todo: check this for performance
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
			        $migrate_title = $postdata['post_meta']['_thumbnail_id'][0];
				    $attachment_post = get_page_by_title( $migrate_title, OBJECT, 'attachment' );
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
	}
}
