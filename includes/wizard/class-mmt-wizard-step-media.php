<?php
/**
 * Migration Merge Tool - Wizard - Media Wizard Step
 *
 * Users step controller.
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_Wizard_Step_Terms
 *
 * todo: maybe add imported media post ids to transient for processing
 * todo: implement conflict management
 * todo: fix title migration bug
 *
 * @since 0.1.0
 */
class MMT_Wizard_Step_Media extends MMT_Wizard_Step {

	/**
	 * Name
	 *
	 * @since 0.1.0
	 */
	public $name = 'media';

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
			'name'      => __( 'Media', 'mmt' ),
			'sub_steps' => apply_filters( "mmtm_wizard_step_{$this->name}_sub_steps", array(
				'media'          => array(
					'name'    => __( 'Media', 'mmt' ),
					'view'    => array( $this, 'media_migration' ),
					'handler' => array( $this, 'media_migration_handler' ),
				),
				'media_process'  => array(
					'name'    => __( 'Get Media', 'mmt' ),
					'view'    => array( $this, 'media_process' ),
					'handler' => array( $this, 'media_process_handler' ),
				),
				'media_complete' => array(
					'name'    => __( 'Media Migration Complete', 'mmt' ),
					'view'    => array( $this, 'media_complete' ),
					'handler' => array( $this, 'media_complete_handler' ),
				),
			) ),
		) );
	}

	/**
	 * Media Migration
	 *
	 * @since 0.1.0
	 */
	public function media_migration() {
		$url                         = MMT_API::get_remote_url();
		$migration_author_input_name = MMT_API::get_migration_author_input_name();
		$migration_authors           = MMT_API::get_migration_authors();
		?>
		<h1><?php esc_attr_e( 'Media Migration', 'mmt' ); ?></h1>
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
				<input type="submit" class="button-primary button button-large button-next"
				       value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->wizard->skip_next_link() ); ?>"
				   class="button button-large button-next"><?php esc_attr_e( 'Skip', 'mmt' ); ?></a>
				<a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>"
				   class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Media Migration Setup
	 *
	 * @since 0.1.0
	 */
	public function media_migration_handler() {
		$this->wizard->verify_security_field();

		$migration_author_input_name = MMT_API::get_migration_author_input_name();
		$migration_fallback_author   = ( ! empty( $_POST[ $migration_author_input_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $migration_author_input_name ] ) ) : ''; // Input var ok.

		MMT_API::set_migration_author( $migration_fallback_author );

		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Media - Process
	 *
	 * List posts to migrate
	 *
	 * @since 0.1.0
	 */
	public function media_process() {
		delete_transient( 'mmt_media_ids_migrated' );
		$media_posts = $this->get_media_posts();
		?>
		<form method="post">
			<h3><?php echo count( $media_posts ); ?><?php esc_html_e( 'Media Posts will be migrated:', 'mmt' ); ?></h3>
			<div class="mmt-items-list-overflow">
				<?php foreach ( $media_posts as $media ) { ?>
					<div class="mmt-item"><?php esc_attr_e( $media['guid'] ) ?></div>
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

	/**
	 * Media Post Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function media_process_handler() {
		$this->wizard->verify_security_field();
		$this->migrate_media();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Media - Complete
	 *
	 * @since 0.1.0
	 */
	public function media_complete() {
		$migrated_media_ids = $this->get_migrated_media_posts();
		?>
		<h1><?php esc_attr_e( 'Media Migration Complete!', 'mmt' ); ?></h1>
		<form method="post">
			<?php if ( $migrated_media_ids ) {

				$media = new WP_Query( [
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
					'posts__in'   => array_values( $migrated_media_ids )
				] );
				?>

				<h3><?php esc_html_e( 'Migrated Media', 'mmt' ); ?></h3>
				<p><?php echo $media->post_count ?><?php esc_html_e( 'media posts were migrated to the current site.', 'mmt' ); ?></p>
				<div class="mmt-items-list-overflow">
					<?php foreach ( $media->posts as $post ) { ?>
						<div class="mmt-item">
							<?php printf( '(ID: %s) %s', esc_attr( $post->ID ), esc_attr( $post->post_name ) ); ?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
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

	/**
	 * Terms - Complete Handler
	 *
	 * @since 0.1.0
	 */
	public function media_complete_handler() {
		$this->wizard->verify_security_field();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Make the api call to grab posts
	 *
	 * @return array|bool
	 */
	public function get_media_posts() {
		$media = MMT_API::get_data( 'media' );
		return $media;
	}

	/**
	 * Injest Media Posts from Remote Site
	 *
	 * @since 0.1.1
	 */
	public function migrate_media() {
		// todo: maybe process this differently?
		$migrate_posts = $this->get_media_posts();

		foreach ( $migrate_posts as $postdata ) {

		    // todo: this might not be the best way to do this
			$post_exist = get_page_by_title( $postdata['post_name'], OBJECT, 'attachment' );
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

			// handle url swapping
			$current_site_url = get_site_url();
			$migrate_site_url = rtrim( MMT_API::get_remote_url(), '/' );
			$postdata['guid'] = str_replace( $migrate_site_url, $current_site_url, $postdata['guid'] );

			// make it a post
			$id = wp_insert_post( $postdata );

			// if no errors add the post meta
			if ( ! is_wp_error( $id ) ) {
				MMT_API::set_postmeta( $postdata['post_meta'], $id );

				// Track IDs for confirmation on the final media page
				$this->migrated_media_ids[] = $id;

				//maybe remove from original array
				unset( $postdata );
			}

			if ( ! empty( $this->migrated_media_ids ) ) {
				set_transient( 'mmt_media_ids_migrated', $this->migrated_media_ids, DAY_IN_SECONDS );
			}

			//todo: what do we do with posts that dont get inserted, recursion call?
		}
	}

	/**
	 * Get Migrated Terms
	 *
	 * @since 0.1.0
	 *
	 * @return array $migrated_terms The terms that were migrated.
	 */
	public function get_migrated_media_posts() {
		return ( false !== ( $media = get_transient( 'mmt_media_ids_migrated' ) ) ) ? $media : array();
	}
}
