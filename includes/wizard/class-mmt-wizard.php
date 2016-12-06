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
	 * @var string
	 */
	private $step;

	/**
	 * Current Sub Step
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $sub_step;

	/**
	 * Steps
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $steps = array();

	/**
	 * Sub Steps
	 *
	 * @since 0.1.0
	 */
	private $sub_steps = array();

	/**
	 * Notices
	 *
	 * @since 0.1.0
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

		// Steps holder.
		$steps = array();

		// Migration Steps.
		$migration_steps = apply_filters( 'mmt_wizard_steps_main', array(
			'users',
			'terms',
			'media',
			'posts',
		) );

		// Start Step.
		$steps['start'] = apply_filters( 'mmt_wizard_step_start', array(
			'name'    => __( 'Start', 'mmt' ),
			'view'    => array( $this, 'start_migration' ),
		) );

		// Setup Step.
		$steps['setup'] = apply_filters( 'mmt_wizard_step_setup', array(
			'name'    => __( 'Setup', 'mmt' ),
			'view'    => array( $this, 'setup_migration' ),
			'handler' => array( $this, 'setup_migration_handler' ),
		) );

		// Migration Steps.
		include_once MMT_INC . 'wizard/abstract-class-mmt-wizard-step.php';
		foreach ( $migration_steps as $migration_step ) {
			include_once sprintf( MMT_INC . 'wizard/class-mmt-wizard-step-%s.php', $migration_step );
			$migration_step_class_name = sprintf( 'MMT_Wizard_Step_%s', ucfirst( $migration_step ) );
			if ( ! class_exists( $migration_step_class_name ) ) {
				continue;
			}
			$step = new $migration_step_class_name( $this );
			$steps[ $step->name ] = $step->register();
		}

		// Complete Step.
		$steps['complete'] = apply_filters( 'mmt_wizard_step_complete', array(
			'name'    => __( 'Finish', 'mmt' ),
			'view'    => array( $this, 'complete_migration' ),
		) );

		// Steps.
		$this->steps = apply_filters( 'mmt_wizard_steps', $steps );

		// Get Step.
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) ); // Input var ok.

		// Get Sub Steps.
		$this->sub_steps = isset( $this->steps[ $this->step ]['sub_steps'] ) ? $this->steps[ $this->step ]['sub_steps'] : ''; // Input var ok.

		// Get Sub Step.
		if ( ! empty( $this->sub_steps ) ) {
			$this->sub_step = ( isset( $_GET['sub-step'] ) ) ? sanitize_key( $_GET['sub-step'] ) : current( array_keys( $this->sub_steps ) ); // Input var ok.
		}

		// Handle Scripts and Styles
		$this->handle_scripts_and_styles();

		// Handle Notices
		$this->handle_notices();

		// Handle Sub Steps
		$this->handle_sub_steps();

		// Handle Steps
		$this->handle_steps();

		// Output.
		ob_start();
		$this->header();
		$this->steps();
		$this->step_content();
		$this->footer();
		exit;
	}

	public function wp_ajax_create_new_post_handler() {
		// This is unfiltered, not validated and non-sanitized data.
		// Prepare everything and trust no input
		$data = $_POST['data'];

		// Do things here.
		// For example: Insert or update a post
		$post_id = wp_insert_post( array(
			'post_title' => $data['title'],
		) );

		// If everything worked out, pass in any data required for your JS callback.
		// In this example, wp_insert_post() returned the ID of the newly created post
		// This adds an `exit`/`die` by itself, so no need to call it.
		if ( ! is_wp_error( $post_id ) ) {
			wp_send_json_success( array(
				'post_id' => $post_id,
			) );
		}

		// If something went wrong, the last part will be bypassed and this part can execute:
		wp_send_json_error( array(
			'post_id' => $post_id,
		) );
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
				?><?php echo ( isset( $step['sub_steps'] ) ) ? ' has-sub-steps' : ''; ?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php } ?>
		</ol>
		<?php
	}

	/**
	 * Wizard Sub Steps
	 *
	 * @since 0.1.0
	 */
	public function sub_steps() {
		if ( ! empty( $this->sub_steps ) ) {
			$sub_steps = $this->sub_steps;
			array_shift( $sub_steps );
			?>
			<ol class="mmt-steps mmt-sub-steps">
				<?php foreach ( $sub_steps as $sub_step_key => $sub_step ) { ?>
					<li class="<?php echo ( $sub_step_key === $this->sub_step ) ? 'active' : ''; ?>"><?php echo esc_html( $sub_step['name'] ); ?></li>
				<?php } ?>
			</ol>
			<?php
		}
	}

	/**
	 * Wizard Step Content
	 *
	 * @since 0.1.0
	 */
	public function step_content() {
		echo '<div class="mmt-content">';
		$this->display_notices();
		if ( ! empty( $this->sub_step ) ) {
			call_user_func( $this->sub_steps[ $this->sub_step ]['view'] );
		} else {
			call_user_func( $this->steps[ $this->step ]['view'] );
		}
		echo '</div>';
	}

	/**
	 * Wizard Footer
	 *
	 * @since 0.1.0
	 */
	public function footer() {
		?>
				<a class="mmt-return-to-dashboard" href="<?php echo esc_url( $this->get_exit_link() ); ?>"><?php esc_attr_e( 'Return to the WordPress', 'mmt' ); ?></a>
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
		<p><?php esc_html_e( 'The following steps will walk you through migrating data from a remote site to this site. Please make sure you have all data prepared on the site you are pulling from as to not encounter any issues with the migration.', 'mmt' ); ?></p>
		<p><?php esc_html_e( 'To start using the merge migration tool, press the Get Started button below.', 'mmt' ); ?></p>
		<p class="mmt-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_attr_e( 'Get Started', 'mmt' ); ?></a>
			<a href="<?php echo esc_url( $this->get_exit_link() ); ?>" class="button button-large"><?php esc_html_e( 'Exit', 'mmt' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Setup Migration
	 *
	 * @since 0.1.0
	 */
	public function setup_migration() {
		// Vars.
		$migration_types     = MMT_API::get_migration_types();
		$migration_type_name = MMT_API::get_migration_type_input_name();
		$remote_url          = MMT_API::get_remote_url();
		$remote_url_name     = MMT_API::get_remote_url_input_name();
		$remote_key          = MMT_API::get_remote_key();
		$remote_key_name     = MMT_API::get_remote_key_input_name();
		?>
		<h1><?php esc_attr_e( 'Setup Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'Please enter the needed details below to move forward.', 'mmt' ); ?></p>
			<table class="form-table">
				<tr>
					<th><label for="<?php echo esc_attr( $migration_type_name ); ?>"><?php esc_attr_e( 'Migration Type', 'mmt' ); ?></label></th>
					<td>
						<select name="<?php echo esc_attr( $migration_type_name ); ?>" id="<?php echo esc_attr( $migration_type_name ); ?>">
							<?php echo $migration_types ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $remote_url_name ); ?>"><?php esc_attr_e( 'Remote Site URL', 'mmt' ); ?></label></th>
					<td><input type="text"
							id="<?php echo esc_attr( $remote_url_name ); ?>"
							name="<?php echo esc_attr( $remote_url_name ); ?>"
							placeholder="<?php esc_attr_e( 'Migration URL', 'mmt' ); ?>"
							value="<?php echo ( ! empty( $remote_url ) ) ? esc_url( $remote_url ) : ''; ?>"/></td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $remote_key_name ); ?>"><?php esc_attr_e( 'Remote Site Key', 'mmt' ); ?></label></th>
					<td><input type="password"
							id="<?php echo esc_attr( $remote_key_name ); ?>"
							name="<?php echo esc_attr( $remote_key_name ); ?>"
							placeholder="<?php esc_attr_e( 'Migration Key', 'mmt' ); ?>"
							value="<?php echo ( ! empty( $remote_key ) ) ? esc_attr( $remote_key ) : ''; ?>"/></td>
				</tr>
			</table>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_step"/>
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->security_field(); ?>
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

		// Post Var Names.
		$migration_type_name = MMT_API::get_migration_type_input_name();
		$remote_url_name     = MMT_API::get_remote_url_input_name();
		$remote_key_name     = MMT_API::get_remote_key_input_name();

		// Post Vars.
		$migration_type = ( ! empty( $_POST[ $migration_type_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $migration_type_name ] ) ) : ''; // Input var ok.
		$remote_url = ( ! empty( $_POST[ $remote_url_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $remote_url_name ] ) ) : ''; // Input var ok.
		$remote_key = ( ! empty( $_POST[ $remote_key_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $remote_key_name ] ) ) : ''; // Input var ok.

		// Validate.
		if ( ! $remote_url || ! $remote_key ) {
			wp_safe_redirect( esc_url_raw( $this->get_notice_link( 'no-settings' ) ) );
			exit;
		}

		// Add Settings.
		MMT_API::set_migration_type( $migration_type );
		MMT_API::set_remote_url( $remote_url );
		MMT_API::set_remote_key( $remote_key );

		// Verify Access.
		if ( ! MMT_API::verify_access() ) {
			wp_safe_redirect( esc_url_raw( $this->get_notice_link( 'no-api-access' ) ) );
			exit;
		}

		// Go to next step.
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Complete migration
	 *
	 * @since 0.1.0
	 */
	public function complete_migration() {
		?>
		<h1><?php esc_attr_e( 'Migration Complete!', 'mmt' ); ?></h1>
		<p><?php esc_html_e( 'Congragulations! The migration is now complete.', 'mmt' ); ?></p>
		<p class="mmt-actions step">
			<a href="<?php echo esc_url( $this->get_start_step_link() ); ?>" class="button button-primary button-large button-next"><?php esc_attr_e( 'Start Over', 'mmt' ); ?></a>
			<a href="<?php echo esc_url( $this->get_exit_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Exit', 'mmt' ); ?></a>
			<?php $this->security_field(); ?>
		</p>
		<?php
	}

	/**
	 * Scripts & Styles
	 *
	 * @since 0.1.0
	 */
	public function handle_scripts_and_styles() {
		// Suffix
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MMT_DEBUG' ) && MMT_DEBUG ) ? '' : '.min';

		// CSS
		wp_enqueue_style( 'mmt-wizard', MMT_CSS . "mmt-wizard{$suffix}.css", array( 'dashicons', 'install' ), MMT_VERSION );

		// Javascript
		wp_register_script( 'mmt-wizard', MMT_JS . "mmt-wizard{$suffix}.js", array( 'jquery' ), MMT_VERSION );
		// todo: add wpApiSettings nonce

		wp_localize_script( 'mmt-wizard', 'mmt_wizard_params', array(
		        'ajax_url' => admin_url( 'admin-ajax.php' ),
		        //'root' => esc_url_raw( rest_url() ),
		        'nonce' => wp_create_nonce( 'wp_rest' )
        ) );
	}

	/**
	 * Handle Steps
	 *
	 * @since 0.1.0
	 */
	public function handle_steps() {
		if ( ! empty( $_POST['save_step'] ) && check_admin_referer( 'mmt-wizard', 'security' ) && isset( $this->steps[ $this->step ]['handler'] ) ) { // Input var ok.
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}
	}

	/**
	 * Handle Sub Steps
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function handle_sub_steps() {
		if ( ! empty( $_POST['save_sub_step'] ) && check_admin_referer( 'mmt-wizard', 'security' ) && isset( $this->sub_steps[ $this->sub_step ]['handler'] ) ) { // Input var ok.
			call_user_func( $this->sub_steps[ $this->sub_step ]['handler'] );
		}
	}

	/**
	 * Handle Notices
	 *
	 * @since 0.1.0
	 */
	public function handle_notices() {
		if ( empty( $_GET['wizard-notice'] ) ) { // Input var ok.
			return;
		}

		$notice = sanitize_text_field( wp_unslash( $_GET['wizard-notice'] ) ); // Input var ok.

		// No settings notice
		if ( 'no-settings' === $notice ) {
			$this->add_notice( esc_attr__( 'You must add a "Site Migration" URL and "Key" to continue.', 'mmt' ), 'error' );
		}

		// No api access
		if ( 'no-api-access' === $notice ) {
			$this->add_notice( esc_attr__( 'Your api access was denied. Please check the "Url" and "Key" below.', 'mmt' ), 'error' );
		}
	}

	/**
	 * Add Notice
	 *
	 * @param string $message The notice message.
	 * @param string $type    The notice type. Possible values are 'info', 'success', and 'error'.
	 *
	 * @since 0.1.0
	 */
	public function add_notice( $message, $type = 'info' ) {
		array_push( $this->notices, array( 'type' => $type, 'message' => $message ) );
	}

	/**
	 * Display Notices
	 *
	 * @since 0.1.0
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

	/**
	 * Security Field
	 *
	 * @since 0.1.0
	 */
	public function security_field() {
		wp_nonce_field( 'mmt-wizard', 'security' );
	}

	/**
	 * Verify Security Field
	 *
	 * @since 0.1.0
	 */
	public function verify_security_field() {
		check_admin_referer( 'mmt-wizard', 'security' );
	}

	/**
	 * Get Start Step Link
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_start_step_link() {
		return remove_query_arg( array( 'wizard-notice', 'sub-step' ), add_query_arg( 'step', current( array_keys( $this->steps ) ) ) );
	}

	/**
	 * Get Exit Link
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_exit_link() {
		return add_query_arg( array( 'page' => 'mmt' ), admin_url( 'tools.php' ) );
	}

	/**
	 * Get Step Link
	 *
	 * @since 0.1.0
	 *
	 * @param string $step_key The step key.
	 *
	 * @return string
	 */
	public function get_step_link( $step_key ) {
		return remove_query_arg( array( 'wizard-notice', 'sub-step' ), add_query_arg( 'step', $step_key ) );
	}

	/**
	 * Get Current Step Link
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_current_step_link() {
		$keys = array_keys( $this->steps );

		if ( ! empty( $this->sub_steps ) ) {
			$sub_keys = array_key( $this->sub_steps );

			if ( isset( $sub_keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) ] ) ) {
				return remove_query_arg( 'wizard-notice', add_query_arg( 'sub-step', $sub_keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) + 1 ] ) );
			}
		}

		return remove_query_arg( array( 'wizard-notice', 'sub-step' ), add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) ] ) );
	}

	/**
	 * Get the Prev Step link
	 *
	 * @since 0.1.0
	 *
	 * @param int $step_offset The number of steps to go from the current step. Default is 1.
	 *
	 * @return string
	 */
	public function get_prev_step_link( $step_offset = 1 ) {
		$keys = array_keys( $this->steps );

		if ( ! empty( $this->sub_steps ) ) {
			$sub_keys = array_keys( $this->sub_steps );

			if ( isset( $sub_keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) - $step_offset ] ) ) {
				return remove_query_arg( 'wizard-notice', add_query_arg( 'sub-step', $sub_keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) - $step_offset ] ) );
			}
		}

		return remove_query_arg( array( 'wizard-notice', 'sub-step' ), add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) - $step_offset ] ) );
	}

	/**
	 * Get the Next Step link
	 *
	 * @since 0.1.0
	 *
	 * @param int $step_offset The number of steps to go from the current step. Default is 1.
	 *
	 * @return string
	 */
	public function get_next_step_link( $step_offset = 1 ) {
		$keys = array_keys( $this->steps );

		if ( ! empty( $this->sub_steps ) ) {
			$sub_keys = array_keys( $this->sub_steps );

			if ( isset( $sub_keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) + $step_offset ] ) ) {
				return remove_query_arg( 'wizard-notice', add_query_arg( 'sub-step', $sub_keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) + $step_offset ] ) );
			}
		}

		return remove_query_arg( array( 'wizard-notice', 'sub-step' ), add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) + $step_offset ] ) );
	}

	/**
	 * Skip Next Link
	 *
	 * @since 0.1.0
	 *
	 * @param int $step_offset The number of steps to go from the current step. Default is 1.
	 *
	 * @return string
	 */
	public function skip_next_link( $step_offset = 1 ) {
		$keys = array_keys( $this->steps );
		return remove_query_arg( array( 'wizard-notice', 'sub-step' ), add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) + $step_offset ] ) );
	}

	/**
	 * Get Notice Link
	 *
	 * This link is used to return the current page with a
	 * special paramater to display notices.
	 *
	 * @since 0.1.0
	 *
	 * @param string $notice The notice slug.
	 */
	public function get_notice_link( $notice = '' ) {
		if ( empty( $notice ) ) {
			return $this->get_next_step_link();
		}

		$keys = array_keys( $this->steps );

		if ( ! empty( $this->sub_steps ) ) {
			$sub_keys = array_keys( $this->sub_steps );

			if ( isset( $sub_keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) ] ) ) {
				return add_query_arg( array( 'sub-step' => $keys[ array_search( $this->sub_step, array_keys( $this->sub_steps ), true ) ], 'wizard-notice' => $notice ) );
			}
		}

		return add_query_arg( array( 'step' => $keys[ array_search( $this->step, array_keys( $this->steps ), true ) ], 'wizard-notice' => $notice ) );
	}
}

new MMT_Wizard();
