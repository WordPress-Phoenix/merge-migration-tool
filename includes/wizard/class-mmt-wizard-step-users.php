<?php
/**
 * Migration Merge Tool - Wizard - Users Wizard Step
 *
 * Users step controller.
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */

defined( 'ABSPATH' ) or die();

/**
 * Class MMT_Wizard_Step
 *
 * @since 0.1.0
 */
class MMT_Wizard_Step_Users extends MMT_Wizard_Step {

	/**
	 * Name
	 *
	 * @since 0.1.0
	 */
	public $name = 'users';

	/**
	 * Register Step
	 *
	 * @since 1.0.0
	 */
	public function register() {
		return apply_filters( "mmt_wizard_step_{$this->name}", array(
			'name'      => __( 'Users', 'mmt' ),
			'sub_steps' => apply_filters( "mmtm_wizard_step_{$this->name}_sub_steps", array(
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
		) );
	}

	/**
	 * Clear Data
	 *
	 * @since 1.0.0
	 */
	public function clear_data() {
		delete_transient( 'mmt_users' );
		delete_transient( 'mmt_users_conflicted' );
		delete_transient( 'mmt_users_referenced' );
		delete_transient( 'mmt_users_migrateable' );
		delete_transient( 'mmt_users_migrated' );
		delete_transient( 'mmt_users_migrated_referenced' );
	}

	/**
	 * Users - Start Migration
	 *
	 * @since 0.1.0
	 */
	public function users_migration_start() {
		// Clear any transients before we get started.
		$this->clear_data();

		// Get the remote url from which we are pulling users.
		$url = MMT_API::get_remote_url();
		?>
		<h1><?php esc_attr_e( 'Start Users Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'During the next few steps, this tool will migrate all users from the following site:', 'mmt' ); ?></p>
			<p><?php printf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_url( $url ) ); ?></p>
			<p><?php esc_html_e( 'To continue, please click the button below.', 'mmt' ); ?></p>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
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
		$this->wizard->verify_security_field();
		$this->create_users_collection();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Users - Process
	 *
	 * @since 0.1.0
	 */
	public function users_process() {
		$conflicted_users  = $this->get_users_conflicted_collection();
		$migrateable_users = $this->get_users_migratable_collection();
		$referenced_users  = $this->get_users_referenced_collection();
		?>
		<h1><?php esc_attr_e( 'Users List', 'mmt' ); ?></h1>
		<form method="post">
			<?php if ( $migrateable_users ) { ?>
				<h3><?php esc_html_e( 'Users that will be migrated:', 'mmt' ); ?></h3>

				<div class="mmt-users-list-overflow">
					<?php foreach ( $migrateable_users as $migrateable_user ) { ?>
						<div class="mmt-user-item"><?php printf( '%s (%s)', esc_attr( $migrateable_user['user']['username'] ), esc_attr( $migrateable_user['user']['email'] ) ); ?></div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $referenced_users ) { ?>
				<h3><?php esc_html_e( 'Users that will be referenced:', 'mmt' ); ?></h3>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $referenced_users as $referenced_user ) { ?>
						<div class="mmt-user-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /> %s: %s <br /><br />',
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $referenced_user['user']['username'] ),
								esc_attr( $referenced_user['user']['email'] ),
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $referenced_user['current_user']->user_login ),
								esc_attr( $referenced_user['current_user']->user_email ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $referenced_user['conflict'] )
							);
							?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $conflicted_users ) { ?>
				<h3><?php esc_html_e( 'Users that have conflicts:', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'Please copy this list. These users will not be migrated.', 'mmt' ); ?></p>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $conflicted_users as $conflicted_user ) { ?>
						<div class="mmt-user-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /><br /> %s: %s',
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $conflicted_user['current_user']->user_login ),
								esc_attr( $conflicted_user['current_user']->user_email ),
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $conflicted_user['user']['username'] ),
								esc_attr( $conflicted_user['user']['email'] ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $conflicted_user['conflict'] )
							);
							?>
						</div>
						<br/>
					<?php } ?>
				</div>
			<?php } ?>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
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
		$this->wizard->verify_security_field();

		$this->migrate_users();
		$this->migrate_referenced_users();

		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Users - Complete
	 *
	 * @since 0.1.0
	 */
	public function users_complete() {
		$conflicted_users = $this->get_users_conflicted_collection();
		$migrated_users   = $this->get_migrated_users();
		$referenced_users = $this->get_migrated_users_referenced();
		?>
		<h1><?php esc_attr_e( 'User Migration Complete!', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php echo esc_html_e( 'Congragulations! The Users migration is complete.', 'mmt' ); ?></p>
			<?php if ( $migrated_users ) { ?>
				<h3><?php esc_html_e( 'Migrated Users', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'The users below were migrated to the current site.', 'mmt' ); ?></p>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $migrated_users as $migrated_user ) { ?>
						<div class="mmt-user-item">
							<?php
							printf(
								'%s (%s)',
								esc_attr( $migrated_user->user_login ),
								esc_attr( $migrate_user->user_email )
							);
							?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $referenced_users ) { ?>
				<h3><?php esc_html_e( 'Referenced Users', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'The users below were referenced to a current user on this site based on a conflict.', 'mmt' ); ?></p>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $referenced_users as $referenced_user ) { ?>
						<div class="mmt-user-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /> %s: %s <br /><br />',
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $referenced_user['user']['username'] ),
								esc_attr( $referenced_user['user']['email'] ),
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $referenced_user['current_user']->user_login ),
								esc_attr( $referenced_user['current_user']->user_email ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $referenced_user['conflict'] )
							);
							?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $conflicted_users ) { ?>
				<h3><?php esc_html_e( 'Conflicted Users', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'The users below were not transfered to this site based on a conflict.', 'mmt' ); ?></p>
				<div class="mmt-users-list-overflow">
					<?php foreach ( $conflicted_users as $conflicted_user ) { ?>
						<div class="mmt-user-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /><br /> %s: %s',
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $conflicted_user['current_user']->user_login ),
								esc_attr( $conflicted_user['current_user']->user_email ),
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $conflicted_user['user']['username'] ),
								esc_attr( $conflicted_user['user']['email'] ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $conflicted_user['conflict'] )
							);
							?>
						</div>
						<br/>
					<?php } ?>
				</div>
			<?php } ?>
			<p class="mmt-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'mmt' ); ?>" name="save_sub_step"/>
				<a href="<?php echo esc_url( $this->wizard->get_prev_step_link() ); ?>" class="button button-large button-next"><?php esc_attr_e( 'Back', 'mmt' ); ?></a>
				<?php $this->wizard->security_field(); ?>
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
		$this->wizard->verify_security_field();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Get Users
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_users() {
		if ( false === ( $users = get_transient( 'mmt_users' ) ) ) {
			$users = MMT_API::get_data( 'users' );
			set_transient( 'mmt_users', $users, DAY_IN_SECONDS );
		}

		return $users;
	}

	/**
	 * Create Users Collection
	 *
	 * @since 0.1.0
	 *
	 * @param array $remote_users The remote users array.
	 *
	 * @return array
	 */
	public function create_users_collection( $remote_users = array() ) {
		if ( empty( $remote_users ) ) {
			$remote_users = $this->get_users();
		}

		// Clear stale data
		delete_transient( 'mmt_users_conflicted' );
		delete_transient( 'mmt_users_referenced' );
		delete_transient( 'mmt_users_migrateable' );

		// Define collection holders
		$current_site_users = array();
		$conflicted_users   = array();
		$referenced_users   = array();
		$migrateable_users  = array();

		// Get Current Site Users
		$current_users_query = new WP_User_Query( array( 'number' => - 1 ) );
		foreach ( $current_users_query->get_results() as $user ) {
			$current_site_users[] = array(
				'username' => $user->user_login,
				'email'    => $user->user_email,
				'user'     => $user,
			);
		}

		// Check for confligcts
		foreach ( $remote_users as $remote_user ) {

			// Search to see if they match
			$match_username = array_search( $remote_user['username'], array_column( $current_site_users, 'username' ), true );
			$match_email    = array_search( $remote_user['email'], array_column( $current_site_users, 'email' ), true );

			// Both Conflict
			if ( ( false !== $match_username ) && ( false !== $match_email ) ) {
				$referenced_users[] = array(
					'user'         => $remote_user,
					'current_user' => $current_site_users[ $match_username ]['user'],
					'conflict'     => 'username_and_email',
				);
				continue;
			}

			// Username Conflict.
			if ( false !== $match_username ) {
				$conflicted_users[] = array(
					'user'         => $remote_user,
					'current_user' => $current_site_users[ $match_username ]['user'],
					'conflict'     => 'username',
				);
				continue;
			}

			// Email Conflict.
			if ( false !== $match_email ) {
				$referenced_users[] = array(
					'user'         => $remote_user,
					'current_user' => $current_site_users[ $match_email ]['user'],
					'conflict'     => 'email',
				);
				continue;
			}

			// No username or email conflicts.
			$migrateable_users[] = array( 'user' => $remote_user );
		}

		// Set Transients for later
		set_transient( 'mmt_users_conflicted', $conflicted_users, DAY_IN_SECONDS );
		set_transient( 'mmt_users_referenced', $referenced_users, DAY_IN_SECONDS );
		set_transient( 'mmt_users_migrateable', $migrateable_users, DAY_IN_SECONDS );
	}

	/**
	 * Get Users Conflicted Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_users_conflicted_collection() {
		if ( false === ( $conflicting_users = get_transient( 'mmt_users_conflicted' ) ) ) {
			$this->create_users_collection();
			$conflicting_users = get_transient( 'mmt_users_conflicted' );
		}

		return $conflicting_users;
	}

	/**
	 * Get Users Conflict Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_users_migratable_collection() {
		if ( false === ( $migrateable_users = get_transient( 'mmt_users_migrateable' ) ) ) {
			$this->create_users_collection();
			$migrateable_users = get_transient( 'mmt_users_migrateable' );
		}

		return $migrateable_users;
	}

	/**
	 * Get Users Referenced Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_users_referenced_collection() {
		if ( false === ( $referenced_users = get_transient( 'mmt_users_referenced' ) ) ) {
			$this->create_users_collection();
			$referenced_users = get_transient( 'mmt_users_referenced' );
		}

		return $referenced_users;
	}

	/**
	 * Migrate Referenced Users
	 *
	 * @since 0.1.0
	 *
	 * @param array $users The users to be referenced.
	 *
	 * @return array $created_users The created users.
	 */
	public function migrate_users( $users = array() ) {
		if ( empty( $users ) ) {
			$users = $this->get_users_migratable_collection();
		}

		$migrated_users = array();

		foreach ( $users as $user ) {
			$user     = $user['user'];
			$userdata = array(
				'user_login'      => $user['username'],
				'user_url'        => $user['url'],
				'user_email'      => $user['email'],
				'first_name'      => $user['first_name'],
				'last_name'       => $user['last_name'],
				'user_pass'       => $user['password'],
				'display_name'    => $user['name'],
				'description'     => $user['description'],
				'nickname'        => $user['nickname'],
				'user_nicename'   => $user['slug'],
				'user_registered' => $user['registered_date'],
			);

			$user_id = wp_insert_user( $userdata );

			if ( is_wp_error( $user_id ) ) {
				MMT::debug( $user_id->get_error_message() );
				continue;
			}

			$migrated_users[] = new WP_User( $user_id );
		}

		if ( ! empty( $migrated_users ) ) {
			set_transient( 'mmt_users_migrated', $migrated_users, DAY_IN_SECONDS );
		}

		return $migrated_users;
	}

	/**
	 * Migrate Users
	 *
	 * @since 0.1.0
	 *
	 * @param array $users The users to be migrated.
	 *
	 * @return array $created_users The created users.
	 */
	public function migrate_referenced_users( $users = array() ) {
		if ( empty( $users ) ) {
			$users = $this->get_users_referenced_collection();
		}

		$migrated_users = array();

		foreach ( $users as $user ) {
			$conflict     = $user['conflict'];
			$current_user = $user['current_user'];
			$user         = $user['user'];

			if ( is_a( $current_user, 'WP_User' ) ) {
				// Delete it if exists
				delete_user_meta( $current_user->ID, 'mmt_reference_user_id' );
				delete_user_meta( $current_user->ID, 'mmt_reference_user_object' );

				// Add it.
				update_user_meta( $current_user->ID, 'mmt_reference_user_id', $user['id'] );
				update_user_meta( $current_user->ID, 'mmt_reference_user_object', $user );
			}

			$migrated_users[] = array(
				'user'         => $user,
				'current_user' => $current_user,
				'conflict'     => $conflict,
			);
		}

		if ( ! empty( $migrated_users ) ) {
			set_transient( 'mmt_users_migrated_referenced', $migrated_users, DAY_IN_SECONDS );
		}

		return $migrated_users;
	}

	/**
	 * Get Migrated Users
	 *
	 * @since 0.1.0
	 *
	 * @return array $migrated_users The users that were migrated.
	 */
	public function get_migrated_users() {
		return ( false !== ( $users = get_transient( 'mmt_users_migrated' ) ) ) ? $users : array();
	}

	/**
	 * Get Migrated Users
	 *
	 * @since 0.1.0
	 *
	 * @return array $migrated_users The users that were migrated.
	 */
	public function get_migrated_users_referenced() {
		return ( false !== ( $users = get_transient( 'mmt_users_migrated_referenced' ) ) ) ? $users : array();
	}
}
