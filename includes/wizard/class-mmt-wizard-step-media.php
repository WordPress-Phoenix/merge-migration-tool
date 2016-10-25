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
			'name'    => __( 'Media', 'mmt' ),
			'view'    => array( $this, 'media_migration' ),
			'handler' => array( $this, 'media_migration_handler' ),
		) );
	}

	/**
	 * Media Migration
	 *
	 * @since 0.1.0
	 */
	public function media_migration() {
		?>
		<h1><?php esc_attr_e( 'Media Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'This is the place that the media migration would take place.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->wizard->skip_next_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Skip', 'mmt' ); ?></a>
				<a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Media Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function media_migration_handler() {
		$this->wizard->verify_security_field();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}
}
