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
	 * Register Step
	 *
	 * @since 1.0.0
	 */
	public function register() {
		return apply_filters( "mmt_wizard_step_{$this->name}", array(
			'name'    => __( 'Posts', 'mmt' ),
			'view'    => array( $this, 'posts_migration' ),
			'handler' => array( $this, 'posts_migration_handler' ),
		) );
	}

	/**
	 * Posts Migration
	 *
	 * @since 0.1.0
	 */
	public function posts_migration() {
		?>
		<h1><?php esc_attr_e( 'Posts Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'This is the place that the users migration would take place.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Skip this step', 'mmt' ); ?></a>
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
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}
}
