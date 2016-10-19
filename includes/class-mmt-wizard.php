<?php
/**
 * Migration Merge Tool - Migration Wizard
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
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
	 * Exit Link
	 *
	 * @since 0.1.0
	 *
	 * @var string The link to exit the migration wizard.
	 */
	protected $exit_link;

	/**
	 * Notices
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'wizard' ) );
	}

	/**
	 * Wizard
	 *
	 * @since 0.1.0
	 */
	public function wizard() {

		// Check.
		if ( empty( $_GET['tab'] ) || 'wizard' !== $_GET['tab'] ) { // Input var ok.
			return;
		}

		// Exit Link
		$this->exit_link = esc_url( add_query_arg( array( 'page' => 'mmt' ), admin_url( 'tools.php' ) ) );

		// Steps.
		$this->steps = apply_filters( 'mmt_wizard_steps', array(
			'start'    => array(
				'name'    => __( 'Start', 'mmt' ),
				'view'    => array( $this, 'start_migration' ),
				'handler' => '',
			),
			'setup'    => array(
				'name'    => __( 'Setup', 'mmt' ),
				'view'    => array( $this, 'setup_migration' ),
				'handler' => array( $this, 'setup_migration_handler' ),
			),
			'users'    => array(
				'name'    => __( 'Users', 'mmt' ),
				'view'    => array( $this, 'users_migration' ),
				'handler' => array( $this, 'users_migration_handler' ),
			),
			'posts'    => array(
				'name'    => __( 'Posts', 'mmt' ),
				'view'    => array( $this, 'posts_migration' ),
				'handler' => array( $this, 'posts_migration_handler' ),
			),
			'terms'    => array(
				'name'    => __( 'Terms', 'mmt' ),
				'view'    => array( $this, 'terms_migration' ),
				'handler' => array( $this, 'terms_migration_handler' ),
			),
			'media'    => array(
				'name'    => __( 'Media', 'mmt' ),
				'view'    => array( $this, 'media_migration' ),
				'handler' => array( $this, 'media_migration_handler' ),
			),
			'complete' => array(
				'name'    => __( 'Finish', 'mmt' ),
				'view'    => array( $this, 'complete_migration' ),
				'handler' => '',
			),
		) );

		// Get Step
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

		// Suffix
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MMT_DEBUG' ) && MMT_DEBUG ) ? '' : '.min';

		// CSS
		wp_enqueue_style( 'mmt-wizard', MMT_CSS . "mmt-wizard{$suffix}.css", array( 'dashicons', 'install' ), MMT_VERSION );

		// Javascript
		wp_register_script( 'mmt-wizard', MMT_JS . "mmt-wizard{$suffix}.js", array( 'jquery' ), MMT_VERSION );
		wp_localize_script( 'mmt-wizard', 'mmt_wizard_params', array() );

		// Handle Notices
		$this->handle_notices();

		// Call function based on post step
		if ( ! empty( $_POST['save_step'] ) && check_admin_referer( 'mmt-wizard', 'security' ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		// Output.
		ob_start();
		$this->header();
		$this->steps();
		$this->step_content();
		$this->footer();
		exit;
	}

	/**
	 * Wizard Header
	 *
	 * @since 0.1.0
	 */
	public function header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php esc_attr_e( 'Merge Migration Tool', 'mmt' ); ?></title>
			<?php wp_print_scripts( 'mmt-wizard' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="mmt-wizard wp-core-ui">
		<h1 id="mmt-logo">
			<img src="<?php echo esc_url( admin_url( 'images/wordpress-logo.svg' ) ); ?>" alt="<?php esc_attr_e( 'WordPress', 'mmt' ); ?>"/>
			<span class="mmt-title"><?php esc_html_e( 'Merge Migration Tool', 'mmt' ); ?></span>
		</h1>
		<?php
	}

	/**
	 * Wizard Steps
	 *
	 * @since 0.1.0
	 */
	public function steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="mmt-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) { ?>
				<li class="<?php
				if ( $step_key === $this->step ) {
					echo 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true ) ) {
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
	public function step_content() {
		echo '<div class="mmt-content">';
		$this->display_notices();
		call_user_func( $this->steps[ $this->step ]['view'] );
		echo '</div>';
	}

	/**
	 * Wizard Footer
	 *
	 * @since 0.1.0
	 */
	public function footer() {
		if ( 'next_steps' === $this->step ) { ?>
			<a class="mmt-return-to-dashboard" href="<?php echo esc_url( $this->exit_link ); ?>"><?php esc_attr_e( 'Return to the WordPress Dashboard', 'mmt' ); ?></a>
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
		<h1><?php esc_attr_e( 'Welcome!', 'mmt' ); ?></h1>
		<p><?php esc_html_e( 'To start using the merge migration tool, press the Get Started button below.', 'mmt' ); ?></p>
		<p class="mmt-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_attr_e( 'Get Started', 'mmt' ); ?></a>
			<a href="<?php echo esc_url( $this->exit_link ); ?>" class="button button-large"><?php esc_html_e( 'Back', 'mmt' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Setup Migration
	 *
	 * @since 0.1.0
	 */
	public function setup_migration() {
		$site_url = get_site_option( 'mmt_migration_site_url' );
		$site_key = get_site_option( 'mmt_migration_site_key' );
		?>
		<h1><?php esc_attr_e( 'Setup Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'Please enter the needed details below to move forward.', 'mmt' ); ?></p>
			<table class="form-table">
				<tr>
					<th><label for="mmt_migration_site_url"><?php esc_attr_e( 'Migration Site URL', 'mmt' ); ?></label></th>
					<td><input type="text"
							id="mmt_migration_site_url"
							name="mmt_migration_site_url"
							placeholder="<?php esc_attr_e( 'Migration URL', 'mmt' ); ?>"
							value="<?php echo ( $site_url ) ? esc_url( $site_url ) : ''; ?>"/></td>
				</tr>
				<tr>
					<th><label for="mmt_migration_site_key"><?php esc_attr_e( 'Migration Site Key', 'mmt' ); ?></label></th>
					<td><input type="password"
							id="mmt_migration_site_key"
							name="mmt_migration_site_key"
							placeholder="<?php esc_attr_e( 'Migration Key', 'mmt' ); ?>"
							value="<?php echo ( $site_key ) ? esc_attr( $site_key ) : ''; ?>"/></td>
				</tr>
			</table>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Setup Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function setup_migration_handler() {
		check_admin_referer( 'mmt-wizard', 'security' );

		// Post Vars.
		$site_url = ( ! empty( $_POST['mmt_migration_site_url'] ) ) ? sanitize_text_field( $_POST['mmt_migration_site_url'] ) : '';
		$site_key = ( ! empty( $_POST['mmt_migration_site_key'] ) ) ? sanitize_text_field( $_POST['mmt_migration_site_key'] ) : '';

		// Validate
		if ( ! $site_url || ! $site_key ) {
			wp_safe_redirect( $this->get_current_step_link( 'no-settings' ) );
			exit;
		}

		// Update Options
		update_site_option( 'mmt_migration_site_url', $site_url );
		update_site_option( 'mmt_migration_site_key', $site_key );

		// Go to next step
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Users Migration
	 *
	 * @since 0.1.0
	 */
	public function users_migration() {
		$site_url = get_site_option( 'mmt_migration_site_url' );
		$response = wp_remote_get( trailingslashit( $site_url ) . 'wp-json/mmt/v1/users' );
		$users    = json_decode( wp_remote_retrieve_body( $response ) );
		MMT::debug( json_decode( $users ) );
		?>
		<h1><?php esc_attr_e( 'Users Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'Below is the list of users to migrate.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Migrate Users', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard' ); ?>
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
		check_admin_referer( 'mmt-wizard' );
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
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
				<?php wp_nonce_field( 'mmt-wizard' ); ?>
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
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Terms Migration
	 *
	 * @since 0.1.0
	 */
	public function terms_migration() {
		?>
		<h1><?php esc_attr_e( 'Terms Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'This is the place that the users migration would take place.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Skip this step', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard' ); ?>
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
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
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
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Skip this step', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard' ); ?>
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
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Complete migration
	 *
	 * @since 0.1.0
	 */
	public function complete_migration() {
		?><h1><?php esc_attr_e( 'Migration Complete!', 'mmt' ); ?></h1><?php
	}

	/** Utilities -------------------- */

	/**
	 * Get Step Link
	 *
	 * @since 0.1.0
	 *
	 * @param string $step The step name.
	 *
	 * @return string
	 */
	public function get_step_link( $step ) {
		return add_query_arg( 'step', $step );
	}

	/**
	 * Get Current Step Link
	 *
	 * @since 0.1.0
	 *
	 * @param string $notice A notice that needs to be displayed.
	 *
	 * @return string
	 */
	public function get_current_step_link( $notice = '' ) {
		$keys = array_keys( $this->steps );

		// Check for notice
		if ( $notice ) {
			return add_query_arg( array( 'step' => $keys[ array_search( $this->step, array_keys( $this->steps ), true ) ], 'wizard-notice' => $notice ) );
		}

		return remove_query_arg( 'wizard-notice', add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) ] ) );
	}

	/**
	 * Get the Prev Step link
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_prev_step_link() {
		$keys = array_keys( $this->steps );

		return remove_query_arg( 'wizard-notice', add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) - 1 ] ) );
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

		return remove_query_arg( 'wizard-notice', add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) + 1 ] ) );
	}

	/**
	 * Add Notice
	 *
	 * @param string $message The notice message.
	 * @param string $type    The notice type. Possible values are 'success' or 'error'
	 *
	 * @since 1.0.0
	 */
	public function add_notice( $message, $type = 'general' ) {
		array_push( $this->notices, array( 'type' => $type, 'message' => $message ) );
	}

	/**
	 * Handle Notices
	 *
	 * @since 1.0.0
	 */
	public function handle_notices() {
		if ( empty( $_GET['wizard-notice'] ) ) { // Input var ok.
			return;
		}

		// No settings notice
		if ( 'no-settings' === $_GET['wizard-notice'] ) {
			$this->add_notice( esc_attr__( 'You must add a "Site Migration" URL and "Key" to continue.', 'mmt' ), 'error' );
		}
	}

	/**
	 * Display Notices
	 *
	 * @since 1.0.0
	 */
	public function display_notices() {
		if ( empty( $this->notices ) ) {
			return;
		}
		foreach ( $this->notices as $notice ) {
			switch ( $notice['type'] ) {
				case 'success' :
					printf( '<div class="mmt-notice success">%s</div>', wp_kses_post( $notice['message'] ) );
					break;
				case 'error' :
					printf( '<div class="mmt-notice error">%s</div>', wp_kses_post( $notice['message'] ) );
					break;
				default :
					printf( '<div class="mmt-notice">%s</div>', wp_kses_post( $notice['message'] ) );
					break;
			}
		}
	}
}

new MMT_Wizard();
