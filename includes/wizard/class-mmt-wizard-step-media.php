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
			) ),
		) );
	}

	/**
	 * Media Migration
	 *
	 * @since 0.1.0
	 */
	public function media_migration() {
		$url                              = MMT_API::get_remote_url();
		$migration_author_input_name      = MMT_API::get_migration_author_input_name();
		$migration_batch_media_quantity   = MMT_API::get_media_batch_quantity();
		$migration_batch_media_input_name = MMT_API::get_media_batch_quantity_input_name();
		$migration_authors                = MMT_API::get_migration_authors();
		?>
		<h1><?php esc_attr_e( 'Media Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'During the next few steps, this tool will migrate all media from the following site:', 'mmt' ); ?></p>
			<p><?php printf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_url( $url ) ); ?></p>

			<p>
				<label for="<?php echo esc_attr( $migration_author_input_name ); ?>">
					<strong><?php __( 'Fallback Author', 'mmt' ); ?></strong>
				</label>
				Set a fallback author to assign media posts where the author may not exist.
				<select name="<?php echo esc_attr( $migration_author_input_name ); ?>"
						id="<?php echo esc_attr( $migration_author_input_name ); ?>">
					<?php echo $migration_authors; ?>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr( $migration_batch_media_input_name ); ?>">
					<strong><?php esc_attr_e( 'Batch Quantity', 'mmt' ); ?></strong>
				</label> <br>
				A batch may timeout if the batch quantity is too large. If this happens, try adjusting the posts per batch amount here.
				<br>
				<input type="text" size="8"
						   id="<?php echo esc_attr( $migration_batch_media_input_name ); ?>"
						   name="<?php echo esc_attr( $migration_batch_media_input_name ); ?>"
						   value="<?php echo ( ! empty( $migration_batch_media_quantity ) ) ? esc_attr( $migration_batch_media_quantity ) : '75'; ?>"/>
			</p>
			<br>

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
		$migration_author_post = $_POST[ $migration_author_input_name ];
		$migration_fallback_author   = ( ! empty( $migration_author_post ) ) ? sanitize_text_field( wp_unslash( $migration_author_post ) ) : '';

		$migration_batch_media_input_name = MMT_API::get_media_batch_quantity_input_name();
		$migration_batch_media_post = $_POST[ $migration_batch_media_input_name ];
		$migration_batch_media_quantity = ( ! empty( $migration_batch_media_post ) ) ? sanitize_text_field( $migration_batch_media_post ) : '50';

		MMT_API::set_media_batch_quantity( $migration_batch_media_quantity );
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
		?>
		<form method="post">
			<h3><?php esc_html_e( 'Media Posts will be migrated:', 'mmt' ); ?></h3>
			<div>
				<p>Page: <span class="page-num"></span><span class="page-total"></span></p>
			</div>
			<div class="posts-batch"
				 style="width: 100%; height: 10px; margin-bottom: 30px; background-color: rgb(234,234,234);">
				<span class="posts-batch-progress"
					  style="display: block; width: 0; height: 100%; background: linear-gradient(to right, rgb(31,202,0) 0%, rgba(31,234,0,1) 100%);"></span>
			</div>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-migrate-posts"
					   value="<?php esc_attr_e( 'Migrate Media', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>" id="button-back"
				   class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
			</p>
			<div>
				<ul class="media-migrate-conflicts"></ul>
			</div>
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
