<?php
/**
 * Migration Merge Tool - Wizard
 *
 * @package MMT
 * @subpackage Includes
 * @since 0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_Wizard
 *
 * @since 0.1.0
 */
class MMT_Wizard {
	/**
	 * Current Step
	 *
	 * @since 0.1.0
	 *
	 * @var string Currenct Step
	 */
	private $step;

	/**
	 * Steps
	 *
	 * @since 0.1.0
	 *
	 * @var array Steps for the setup wizard
	 */
	private $steps = array();

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		if ( apply_filters( 'mmt/wizard', true ) && current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_init', array( $this, 'wizard' ) );
		}
	}

	/**
	 * Merge Migration Tool Menu
	 *
	 * This menu item is displayed below the 'Tools' menu.
	 *
	 * @since 0.1.0
	 */
	public function menu() {
		add_management_page( __( 'Migration Tool', 'mmt' ), __( 'Migration Tool', 'mmt' ), 'manage_options', 'mmt-wizard', array( $this, 'wizard' ) );
	}

	/**
	 * Wizard
	 *
	 * @since 0.1.0
	 */
	public function wizard() {

		// Check
		if ( empty( $_GET['page'] ) || 'mmt-wizard' !== $_GET['page'] ) {
			return;
		}

		// Steps
		$this->steps = apply_filters( 'mmt_wizard_steps', array(
			'start'    => array(
				'name'    => __( 'Start Migration', 'mmt' ),
				'view'    => array( $this, 'start_migration' ),
				'handler' => ''
			),
			'users'    => array(
				'name'    => __( 'User Migration', 'mmt' ),
				'view'    => array( $this, 'users_migration' ),
				'handler' => array( $this, 'users_migration_handler' )
			),
			'posts'    => array(
				'name'    => __( 'Posts Migration', 'mmt' ),
				'view'    => array( $this, 'posts_migration' ),
				'handler' => array( $this, 'posts_migration_handler' )
			),
			'terms'    => array(
				'name'    => __( 'Terms Migration', 'mmt' ),
				'view'    => array( $this, 'terms_migration' ),
				'handler' => array( $this, 'terms_migration_handler' )
			),
			'media'    => array(
				'name'    => __( 'Media Migration', 'mmt' ),
				'view'    => array( $this, 'media_migration' ),
				'handler' => array( $this, 'media_migration_handler' )
			),
			'complete' => array(
				'name'    => __( 'Migration Complete!', 'mmt' ),
				'view'    => array( $this, 'complete_migration' ),
				'handler' => ''
			)
		) );

		// Get Step
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

		// Suffix
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MMT_DEBUG' ) && MMT_DEBUG ) ? '' : '.min';

		// CSS
		wp_enqueue_style( 'mmt', MMT_CSS . "mmt{$suffix}.css", array( 'dashicons', 'install' ), MMT_VERSION );

		// Javascript
		wp_register_script( 'mmt', MMT_CSS . "mmt{$suffix}.js", array( 'jquery' ), MMT_VERSION );
		wp_localize_script( 'mmt', 'mmt_params', array() );

		// Call function based on post step
		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		// Output
		ob_start();
		$this->wizard_header();
		$this->wizard_steps();
		$this->wizard_step_content();
		$this->wizard_footer();
		exit;
	}

	/**
	 * Get the Next Step link
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_next_step_link() {
		$keys = array_keys( $this->steps );

		return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ) ) + 1 ] );
	}

	/**
	 * Wizard Header
	 *
	 * @since 0.1.0
	 */
	public function wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php _e( 'Merge Migration Tool', 'mmt' ); ?></title>
			<?php wp_print_scripts( 'mmt' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="mmt-wizard wp-core-ui">
		<h1 id="mmt-logo"><?php esc_html__( 'Merge Migration Tool', 'mmt' ); ?></h1>
		<?php
	}

	/**
	 * Wizard Steps
	 *
	 * @since 0.1.0
	 */
	public function wizard_steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="mmt-setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) { ?>
				<li class="<?php
				if ( $step_key === $this->step ) {
					echo 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
					echo 'done';
				}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php } ?>
		</ol>
		<?php
	}

	/**
	 * Wizard Step Content
	 *
	 * @since 0.1.0
	 */
	public function wizard_step_content() {
		echo '<div class="mmt-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'] );
		echo '</div>';
	}

	/**
	 * Wizard Footer
	 *
	 * @since 0.1.0
	 */
	public function wizard_footer() {
		if ( 'next_steps' === $this->step ) { ?>
			<a class="mmt-return-to-dashboard"
			   href="<?php echo esc_url( admin_url() ); ?>"><?php _e( 'Return to the WordPress Dashboard', 'mmt' ); ?></a>
		<?php } ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Start Migration
	 *
	 * @since 0.1.0
	 */
	public function start_migration() {
		?>
		<h1><?php _e( 'Begin using the merge migration tool!', 'mmt' ); ?></h1>
		<p class="mmt-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php _e( 'Get Started', 'mmt' ); ?></a>
			<a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large"><?php esc_html_e( 'Not right now', 'mmt' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Users Migration
	 *
	 * @since 0.1.0
	 */
	public function users_migration() {
		?>
		<h1><?php _e( 'Users Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'This is the place that the users migration would take place.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-users' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * User Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function users_migration_handler() {
		check_admin_referer( 'mmt-users' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Posts Migration
	 *
	 * @since 0.1.0
	 */
	public function posts_migration() {
		?>
		<h1><?php _e( 'Posts Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'This is the place that the users migration would take place.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-posts' ); ?>
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
		check_admin_referer( 'mmt-posts' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Terms Migration
	 *
	 * @since 0.1.0
	 */
	public function terms_migration() {
		?>
		<h1><?php _e( 'Terms Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'This is the place that the users migration would take place.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-terms' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Terms Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function terms_migration_handler() {
		check_admin_referer( 'mmt-terms' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Media Migration
	 *
	 * @since 0.1.0
	 */
	public function media_migration() {
		?>
		<h1><?php _e( 'Media Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'This is the place that the media migration would take place.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-media' ); ?>
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
		check_admin_referer( 'mmt-media' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Complete migration
	 *
	 * @since 0.1.0
	 */
	public function complete_migration() {
		?><h1><?php esc_html_e( 'Migration Complete!', 'mmt' ); ?></h1><?php
	}
}

new MMT_Wizard();