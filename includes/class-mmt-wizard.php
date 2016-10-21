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
				'name'      => __( 'Users', 'mmt' ),
				'sub_steps' => apply_filters( 'mmtm_wizard_users_sub_steps', array(
					'users'          => array(
						'name'    => __( 'Users', 'mmt' ),
						'view'    => array( $this, 'users_migration_start' ),
						'handler' => array( $this, 'users_migration_start_handler' ),
					),
					'users_process'  => array(
						'name'    => __( 'Get Users', 'mmt' ),
						'view'    => array( $this, 'users_process' ),
						'handler' => array( $this, 'users_process_handler' ),
					),
					'users_complete' => array(
						'name'    => __( 'Finish', 'mmt' ),
						'view'    => array( $this, 'users_complete' ),
						'handler' => array( $this, 'users_complete_handler' ),
					),
				) ),
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
			'posts'    => array(
				'name'    => __( 'Posts', 'mmt' ),
				'view'    => array( $this, 'posts_migration' ),
				'handler' => array( $this, 'posts_migration_handler' ),
			),
			'complete' => array(
				'name'    => __( 'Finish', 'mmt' ),
				'view'    => array( $this, 'complete_migration' ),
				'handler' => '',
			),
		) );

		// Get Step
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) ); // Input var ok.

		// Sub Steps
		$this->sub_steps = isset( $this->steps[ $this->step ]['sub_steps'] ) ? $this->steps[ $this->step ]['sub_steps'] : ''; // Input var ok.

		// Sub Step
		if ( ! empty( $this->sub_steps ) ) {
			$this->sub_step = ( isset( $_GET['sub-step'] ) ) ? sanitize_key( $_GET['sub-step'] ) : current( array_keys( $this->sub_steps ) ); // Input var ok.
		}

		// Suffix
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MMT_DEBUG' ) && MMT_DEBUG ) ? '' : '.min';

		// CSS
		wp_enqueue_style( 'mmt-wizard', MMT_CSS . "mmt-wizard{$suffix}.css", array( 'dashicons', 'install' ), MMT_VERSION );

		// Javascript
		wp_register_script( 'mmt-wizard', MMT_JS . "mmt-wizard{$suffix}.js", array( 'jquery' ), MMT_VERSION );
		wp_localize_script( 'mmt-wizard', 'mmt_wizard_params', array() );

		// Handle Notices
		$this->handle_notices();

		// Call function based on post sub step
		if ( ! empty( $_POST['save_sub_step'] ) && check_admin_referer( 'mmt-wizard', 'security' ) && isset( $this->sub_steps[ $this->sub_step ]['handler'] ) ) { // Input var ok.
			call_user_func( $this->sub_steps[ $this->sub_step ]['handler'] );
		}

		// Call function based on post step
		if ( ! empty( $_POST['save_step'] ) && check_admin_referer( 'mmt-wizard', 'security' ) && isset( $this->steps[ $this->step ]['handler'] ) ) { // Input var ok.
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		// Output.
		ob_start();
		$this->header();
		$this->steps();
		// $this->sub_steps();
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
		$remote_url      = MMT_API::get_remote_url();
		$remote_url_name = MMT_API::get_remote_url_input_name();
		$remote_key      = MMT_API::get_remote_key();
		$remote_key_name = MMT_API::get_remote_key_input_name();
		?>
		<h1><?php esc_attr_e( 'Setup Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'Please enter the needed details below to move forward.', 'mmt' ); ?></p>
			<table class="form-table">
				<tr>
					<th><label for="<?php echo esc_attr( $remote_url_name ); ?>"><?php esc_attr_e( 'Migration Site URL', 'mmt' ); ?></label></th>
					<td><input type="text"
							id="<?php echo esc_attr( $remote_url_name ); ?>"
							name="<?php echo esc_attr( $remote_url_name ); ?>"
							placeholder="<?php esc_attr_e( 'Migration URL', 'mmt' ); ?>"
							value="<?php echo ( ! empty( $remote_url ) ) ? esc_url( $remote_url ) : ''; ?>"/></td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $remote_key_name ); ?>"><?php esc_attr_e( 'Migration Site Key', 'mmt' ); ?></label></th>
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

		// Post Var Names.
		$remote_url_name = MMT_API::get_remote_url_input_name();
		$remote_key_name = MMT_API::get_remote_key_input_name();

		// Post Vars.
		$remote_url = ( ! empty( $_POST[ $remote_url_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $remote_url_name ] ) ) : ''; // Input var ok.
		$remote_key = ( ! empty( $_POST[ $remote_key_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $remote_key_name ] ) ) : ''; // Input var ok.

		// Validate.
		if ( ! $remote_url || ! $remote_key ) {
			wp_safe_redirect( esc_url_raw( $this->get_notice_link( 'no-settings' ) ) );
			exit;
		}

		// Add Settings.
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
	 * Users - Start Migration
	 *
	 * @since 0.1.0
	 */
	public function users_migration_start() {
		MMT_API::clear_data();
		$url = MMT_API::get_remote_url();
		?>
		<h1><?php esc_attr_e( 'Start Users Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'During the next few steps, this tool will migrate all users from the following site:', 'mmt' ); ?></p>
			<p><?php printf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_url( $url ) ); ?></p>
			<p><?php esc_html_e( 'To continue, please click the button below.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Users - Start Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function users_migration_start_handler() {
		check_admin_referer( 'mmt-wizard', 'security' );

		MMT_API::create_users_collection();

		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Users - Process
	 *
	 * @since 0.1.0
	 */
	public function users_process() {
		$conflicted_users  = MMT_API::get_users_conflict_collection();
		$migrateable_users = MMT_API::get_users_migratable_collection();
		?>
		<h1><?php esc_attr_e( 'Users List', 'mmt' ); ?></h1>
		<form method="post">
			<?php if ( $migrateable_users ) { ?>
				<h4><?php esc_html_e( 'Users available to be migrated:', 'mmt' ); ?></h4>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $migrateable_users as $migrateable_user ) { ?>
						<div class="mmt-user-item"><?php printf( '%s (%s)', esc_attr( $migrateable_user['user']['username'] ), esc_attr( $migrateable_user['user']['email'] ) ); ?></div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $conflicted_users ) { ?>
				<h4><?php esc_html_e( 'Users that have conflicts:', 'mmt' ); ?></h4>
				<p><?php esc_html_e( 'Please copy this list. These users will not be migrated.', 'mmt' ); ?></p>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $conflicted_users as $conflicted_user ) { ?>
						<div class="mmt-user-item">
							<?php
							printf( '<strong>%s</strong>: %s (%s)', esc_attr__( 'Current User', 'mmt' ), esc_attr( $conflicted_user['current_user']->user_login ), esc_attr( $conflicted_user['current_user']->user_email ) );
							?>
							<br/>
							<?php
							printf( '<strong>%s</strong>: %s (%s)', esc_attr__( 'Remote User', 'mmt' ), esc_attr( $conflicted_user['user']['username'] ), esc_attr( $conflicted_user['user']['email'] ) );
							?>
						</div>
						<br/>
					<?php } ?>
				</div>
			<?php } ?>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Users - Process Handler
	 *
	 * @since 0.1.0
	 */
	public function users_process_handler() {
		check_admin_referer( 'mmt-wizard', 'security' );

		MMT_API::migrate_users();

		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Users - Conflicts
	 *
	 * @since 0.1.0
	 */
	public function user_conflicts() {
		$conflicting_users = '54';
		?>
		<h1><?php esc_attr_e( 'User Conflicts', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php echo sprintf( esc_html__( 'We found %s users to that conflicted. They are listed below.', 'mmt' ), '<strong>' . esc_attr( $conflicting_users ) . '</strong>' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Users - Conflicts Handler
	 *
	 * @since 0.1.0
	 */
	public function user_conflicts_handler() {
		check_admin_referer( 'mmt-wizard', 'security' );
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Users - Complete
	 *
	 * @since 0.1.0
	 */
	public function users_complete() {
		$conflicted_users = MMT_API::get_users_conflict_collection();
		$migrated_users   = MMT_API::get_migrated_users();
		?>
		<h1><?php esc_attr_e( 'User Migration Complete!', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php echo esc_html_e( 'Congragulations! The user migration is complete. Below are the users that were migrated:', 'mmt' ); ?></p>
			<?php if ( $migrated_users ) { ?>
				<h4><?php esc_html_e( 'Users that have conflicts', 'mmt' ); ?></h4>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $migrated_users as $migrated_user ) { ?>
						<div class="mmt-user-item"><?php printf( '%s (%s)', esc_attr( $migrated_user->user_login ), esc_attr( $migrate_user->user_email ) ); ?></div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $conflicted_users ) { ?>
				<p><?php esc_html_e( 'For your reference, these were the users that were not migrated.', 'mmt' ); ?></p>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $conflicted_users as $conflicted_user ) { ?>
						<div class="mmt-user-item">
							<?php
							printf( '<strong>%s</strong>: %s (%s)', esc_attr__( 'Current User', 'mmt' ), esc_attr( $conflicted_user['current_user']->user_login ), esc_attr( $conflicted_user['current_user']->user_email ) );
							?>
							<br/>
							<?php
							printf( '<strong>%s</strong>: %s (%s)', esc_attr__( 'Remote User', 'mmt' ), esc_attr( $conflicted_user['user']['username'] ), esc_attr( $conflicted_user['user']['email'] ) );
							?>
						</div>
						<br/>
					<?php } ?>
				</div>
			<?php } ?>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Users - Complete Handler
	 *
	 * @since 0.1.0
	 */
	public function users_complete_handler() {
		check_admin_referer( 'mmt-wizard', 'security' );
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
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
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
		check_admin_referer( 'mmt-wizard', 'security' );
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
				<a href="<?php echo esc_url( $this->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
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
		check_admin_referer( 'mmt-wizard', 'security' );
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
				<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
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
		check_admin_referer( 'mmt-wizard', 'security' );
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
			<?php wp_nonce_field( 'mmt-wizard', 'security' ); ?>
		</p>
		<?php
	}

	/** Utilities -------------------- */

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
		return add_query_arg( 'step', $step_key );
	}

	/**
	 * Get Sub Step Link
	 *
	 * @since 0.1.0
	 *
	 * @param string $sub_step_key The sub step key.
	 *
	 * @return string
	 */
	public function get_sub_step_link( $sub_step_key ) {
		return add_query_arg( 'sub-step', $sub_step_key );
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
	 * Display Notice
	 *
	 * @since 0.1.0
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
}

new MMT_Wizard();
