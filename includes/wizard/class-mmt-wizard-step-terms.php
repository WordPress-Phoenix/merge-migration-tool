<?php
/**
 * Migration Merge Tool - Wizard - Terms Wizard Step
 *
 * Terms step controller.
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
class MMT_Wizard_Step_Terms extends MMT_Wizard_Step {

	/**
	 * Name
	 *
	 * @since 0.1.0
	 */
	public $name = 'terms';

	/**
	 * Container for migrated terms on final term screen
	 *
	 * @var array
	 */
	public $migrated_terms = [];

	/**
	 * Register Step
	 *
	 * @since 1.0.0
	 */
	public function register() {
		return apply_filters( "mmt_wizard_step_{$this->name}", array(
			'name'      => __( 'Terms', 'mmt' ),
			'sub_steps' => apply_filters( "mmtm_wizard_step_{$this->name}_sub_steps", array(
				'terms'          => array(
					'name'    => __( 'Terms', 'mmt' ),
					'view'    => array( $this, 'terms_migration_start' ),
					'handler' => array( $this, 'terms_migration_start_handler' ),
				),
				'terms_process'  => array(
					'name'    => __( 'Get Terms', 'mmt' ),
					'view'    => array( $this, 'terms_process' ),
					'handler' => array( $this, 'terms_process_handler' ),
				),
				'terms_complete' => array(
					'name'    => __( 'Terms Migration Complete', 'mmt' ),
					'view'    => array( $this, 'terms_complete' ),
					'handler' => array( $this, 'terms_complete_handler' ),
				),
			) ),
		) );
	}

	/**
	 * Clear Data
	 *
	 * @since 0.1.0
	 */
	public function clear_data() {
		delete_transient( 'mmt_terms' );
		delete_transient( 'mmt_terms_conflicted' );
		delete_transient( 'mmt_terms_referenced' );
		delete_transient( 'mmt_terms_migrateable' );
		delete_transient( 'mmt_terms_migrated' );
		delete_transient( 'mmt_terms_migrated_referenced' );
	}

	/**
	 * Terms Migration Start
	 *
	 * @since 0.1.0
	 */
	public function terms_migration_start() {
		// Clear any data transients before we continue.
		$this->clear_data();

		// Get the remote url from which we are pulling terms.
		$url             = MMT_API::get_remote_url();
		$terms_include_empty_name = MMT_API::get_terms_empty_input_name();

		?>
		<h1><?php esc_attr_e( 'Terms Migration', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( 'During the next few steps, this tool will migrate all terms from the following site:', 'mmt' ); ?></p>
			<p><?php printf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_url( $url ) ); ?></p>
            <p><label><input type="checkbox" name="<?php echo esc_attr( $terms_include_empty_name );?>" value="1" <?php checked( get_option( $terms_include_empty_name ), '1' ); ?>> Include empty terms</label></p>
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
	 * Terms Migration Handler
	 *
	 * @since 0.1.0
	 */
	public function terms_migration_start_handler() {
		$this->wizard->verify_security_field();

		$terms_include_empty_name = MMT_API::get_terms_empty_input_name();
		$terms_include_empty = ( ! empty( $_POST[ $terms_include_empty_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $terms_include_empty_name ] ) ) : '';

		MMT_API::set_terms_empty_setting( $terms_include_empty );

		$this->create_terms_collection();

		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Terms - Process
	 *
	 * @since 0.1.0
	 */
	public function terms_process() {
		$conflicted_terms  = $this->get_terms_conflicted_collection();
		$migrateable_terms = $this->get_terms_migratable_collection();
		$referenced_terms  = $this->get_terms_referenced_collection();
		?>
		<h1><?php esc_attr_e( 'Terms List', 'mmt' ); ?></h1>
		<form method="post">
			<?php if ( $migrateable_terms ) { ?>
				<h3><?php esc_html_e( 'Terms that will be migrated:', 'mmt' ); ?></h3>
				<div class="mmt-items-list-overflow">
					<?php foreach ( $migrateable_terms as $migrateable_term ) { ?>
						<div
							class="mmt-item"><?php printf( '%s (%s)', esc_attr( $migrateable_term['name'] ), esc_attr( $migrateable_term['slug'] ) ); ?></div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $referenced_terms ) { ?>
				<h3><?php esc_html_e( 'Terms that will be referenced:', 'mmt' ); ?></h3>
				<div class="mmt-items-list-overflow">
					<?php foreach ( $referenced_terms as $referenced_term ) { ?>
						<div class="mmt-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /> %s: %s <br /><br />',
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $referenced_term['term']['name'] ),
								esc_attr( $referenced_term['term']['slug'] ),
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $referenced_term['current_term']->name ),
								esc_attr( $referenced_term['current_term']->slug ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $referenced_term['conflict'] )
							);
							?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $conflicted_terms ) { ?>
				<h3><?php esc_html_e( 'Terms that have conflicts:', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'Please copy this list. These terms will not be migrated.', 'mmt' ); ?></p>
				<div class="mmt-items-list-overflow">
					<?php foreach ( $conflicted_terms as $conflicted_term ) { ?>
						<div class="mmt-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /><br /> %s: %s',
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $conflicted_term['current_term']->name ),
								esc_attr( $conflicted_term['current_term']->slug ),
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $conflicted_term['term']['name'] ),
								esc_attr( $conflicted_term['term']['slug'] ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $conflicted_term['conflict'] )
							);
							?>
						</div>
						<br/>
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
	 * Terms - Process Handler
	 *
	 * @since 0.1.0
	 */
	public function terms_process_handler() {
		$this->wizard->verify_security_field();

		$this->migrate_terms();
		$this->migrate_referenced_terms();

		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Terms - Complete
	 *
	 * @since 0.1.0
	 */
	public function terms_complete() {
		$conflicted_terms = $this->get_terms_conflicted_collection();
		$migrated_terms   = $this->get_migrated_terms();
		$referenced_terms = $this->get_migrated_terms_referenced();
		?>
		<h1><?php esc_attr_e( 'Terms Migration Complete!', 'mmt' ); ?></h1>
		<form method="post">
			<p><?php echo esc_html_e( 'Congragulations! The terms migration is complete.', 'mmt' ); ?></p>
			<?php if ( $migrated_terms ) { ?>
				<h3><?php esc_html_e( 'Migrated Terms', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'The terms below were migrated to the current site.', 'mmt' ); ?></p>
				<div class="mmt-items-list-overflow">
					<?php foreach ( $migrated_terms as $migrated_term ) { ?>
						<div class="mmt-item">
							<?php
							printf(
								'%s (%s)',
								esc_attr( $migrated_term['name'] ),
								esc_attr( $migrated_term['slug'] )
							);
							?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $referenced_terms ) { ?>
				<h3><?php esc_html_e( 'Referenced Terms', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'The terms below were referenced to a current term on this site based on a conflict.', 'mmt' ); ?></p>
				<div class="mmt-items-list-overflow">
					<?php foreach ( $referenced_terms as $referenced_term ) { ?>
						<div class="mmt-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /> %s: %s <br /><br />',
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $referenced_term['term']['name'] ),
								esc_attr( $referenced_term['term']['slug'] ),
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $referenced_term['current_term']->name ),
								esc_attr( $referenced_term['current_term']->slug ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $referenced_term['conflict'] )
							);
							?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $conflicted_terms ) { ?>
				<h3><?php esc_html_e( 'Conflicted Terms', 'mmt' ); ?></h3>
				<p><?php esc_html_e( 'The terms below were not transfered to this site based on a conflict.', 'mmt' ); ?></p>
				<div class="mmt-items-list-overflow">
					<?php foreach ( $conflicted_terms as $conflicted_term ) { ?>
						<div class="mmt-item">
							<?php
							printf(
								'%s: %s (%s) <br /> %s: %s (%s) <br /><br /> %s: %s',
								esc_attr__( 'Local', 'mmt' ),
								esc_attr( $conflicted_term['current_term']->name ),
								esc_attr( $conflicted_term['current_term']->slug ),
								esc_attr__( 'Remote', 'mmt' ),
								esc_attr( $conflicted_term['term']['name'] ),
								esc_attr( $conflicted_term['term']['slug'] ),
								esc_attr__( 'Conflict', 'mmt' ),
								esc_attr( $conflicted_term['conflict'] )
							);
							?>
						</div>
						<br/>
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
	public function terms_complete_handler() {
		$this->wizard->verify_security_field();
		wp_safe_redirect( esc_url_raw( $this->wizard->get_next_step_link() ) );
		exit;
	}

	/**
	 * Get Terms
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_terms() {
		if ( false === ( $terms = get_transient( 'mmt_terms' ) ) ) {

            $endpoint = 'terms?hide_empty=true';

            if ( MMT_API::get_terms_empty_setting() ) {
	            $endpoint = 'terms';
            }

			$terms = MMT_API::get_data( $endpoint );
			set_transient( 'mmt_terms', $terms, DAY_IN_SECONDS );
		}

		return $terms;
	}

	/**
	 * Create Terms Collection
	 *
	 * @since 0.1.0
	 *
	 * @param array $remote_terms The remote terms array.
	 *
	 * @return array
	 */
	public function create_terms_collection( $remote_terms = array() ) {
		if ( empty( $remote_terms ) ) {
			$remote_terms = $this->get_terms();
		}

		// Clear stale data
		delete_transient( 'mmt_terms_conflicted' );
		delete_transient( 'mmt_terms_referenced' );
		delete_transient( 'mmt_terms_migrateable' );

		// Define collection holders
		$current_site_terms = array();
		$conflicted_terms   = array();
		$referenced_terms   = array();
		$migrateable_terms  = array();

		// Get Current Terms
		$current_terms_query = get_terms( array( 'post_tag', 'category' ), array( 'hide_empty' => false ) );
		foreach ( $current_terms_query as $term ) {
			$current_site_terms[] = array( 'term' => $term, 'slug' => $term->slug );
		}

		// Check for conflicts
		foreach ( $remote_terms as $remote_term ) {

			// Search to see if they match
			$match_slug = array_search( $remote_term['slug'], array_column( $current_site_terms, 'slug' ), true );

			// Both Conflict
			if ( ( false !== $match_slug ) ) {
				$referenced_terms[] = array(
					'term'         => $remote_term,
					'current_term' => $current_site_terms[ $match_slug ]['term'],
					'conflict'     => 'term_slug',
				);
				continue;
			}

			// No slug conflicts.
			$migrateable_terms[ $remote_term['id'] ] = $remote_term;
		}

		// Swap parent ids with slugs for relationships
		$migrateable_terms = $this->process_term_relationship( $migrateable_terms );

		//refactor array to other side
		$migrateable_terms = MMT_API::map( $migrateable_terms, function ( $term ) {
			array_shift( $term );

			return $term;
		} );

		// Set Transients for later
		set_transient( 'mmt_terms_conflicted', $conflicted_terms, DAY_IN_SECONDS );
		set_transient( 'mmt_terms_referenced', $referenced_terms, DAY_IN_SECONDS );
		set_transient( 'mmt_terms_migrateable', $migrateable_terms, DAY_IN_SECONDS );
	}

	/**
	 * Map the parent slug to the term before migrating
	 *
	 * @param $terms
	 *
	 * @return mixed
	 */
	public function process_term_relationship( $terms ) {
		foreach ( $terms as $term ) {
			if ( $term['parent'] ) {
				$terms[ $term['id'] ]['migrate_parent'] = $terms[ $term['parent'] ]['slug'];
			}
		}

		return $terms;
	}

	/**
	 * Get Terms Conflicted Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_terms_conflicted_collection() {
		if ( false === ( $conflicting_terms = get_transient( 'mmt_terms_conflicted' ) ) ) {
			$this->create_terms_collection();
			$conflicting_terms = get_transient( 'mmt_terms_conflicted' );
		}

		return $conflicting_terms;
	}

	/**
	 * Get Terms Conflict Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_terms_migratable_collection() {
		if ( false === ( $migrateable_terms = get_transient( 'mmt_terms_migrateable' ) ) ) {
			$this->create_terms_collection();
			$migrateable_terms = get_transient( 'mmt_terms_migrateable' );
		}

		return $migrateable_terms;
	}

	/**
	 * Get Terms Referenced Collection
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_terms_referenced_collection() {
		if ( false === ( $referenced_terms = get_transient( 'mmt_terms_referenced' ) ) ) {
			$this->create_terms_collection();
			$referenced_terms = get_transient( 'mmt_terms_referenced' );
		}

		return $referenced_terms;
	}

	/**
	 * Migrate Referenced Terms
	 *
	 * @since 0.1.0
	 *
	 * @param array $terms The terms to be referenced.
	 *
	 * @return array $created_terms The created terms.
	 */
	public function migrate_terms( $terms = array() ) {
		if ( empty( $terms ) ) {
			$terms = $this->get_terms_migratable_collection();
		}

		// Get the last key for comparison
		end( $terms );
		$last = key( $terms );

		// Make sure to start back at the beginning
		reset( $terms );

		foreach ( $terms as $key => $term ) {
			$term_name     = $term['name'];
			$term_taxonomy = $term['taxonomy'];
			$terms_args    = array(
				'description' => $term['description'],
				'slug'        => $term['slug'],
				'parent'      => 0,
			);

			if ( array_key_exists( 'migrate_parent', $term ) ) {
				$parent_term = get_term_by( 'slug', $term['migrate_parent'], $term['taxonomy'] );

				//  The term parent might not have been imported yet. Come back to it the next time around
				if ( false === $parent_term ) {
					continue;
				}

				$terms_args['parent'] = $parent_term->term_id;
			}

			$term_array = wp_insert_term( $term_name, $term_taxonomy, $terms_args );

			// Check for error
			if ( is_wp_error( $term_array ) ) {
				MMT::debug( $term_name );
				MMT::debug( $term_array->get_error_message() );
				continue;
			}

			// This method is called recursively, so the migrated data is stored in a property
			$this->migrated_terms[] = [
				'term_id' => $term_array['term_id'],
				'name'    => $term['name'],
				'slug'    => $term['slug'],
			];

			// Remove term from reference array after successful import
			if ( is_array( $term_array ) ) {
				unset( $terms[ $key ] );
			}

			// Recursion! This makes hierarchical relationship building work on import
			if ( $key == $last && count( $terms ) > 0 ) {
				reset( $terms );
				$this->migrate_terms( $terms );
			}

		}

		if ( ! empty( $this->migrated_terms ) ) {
			set_transient( 'mmt_terms_migrated', $this->migrated_terms, DAY_IN_SECONDS );
		}

		return $this->migrated_terms;
	}

	/**
	 * Migrate Terms
	 *
	 * @since 0.1.0
	 *
	 * @param array $terms The terms to be migrated.
	 *
	 * @return array $created_terms The created terms.
	 */
	public function migrate_referenced_terms( $terms = array() ) {
		if ( empty( $terms ) ) {
			$terms = $this->get_terms_referenced_collection();
		}

		$migrated_terms = array();

		foreach ( $terms as $term ) {
			$conflict     = $term['conflict'];
			$current_term = $term['current_term'];
			$term         = $term['term'];

			if ( is_a( $current_term, 'WP_Term' ) ) {
				// Delete it if exists
				delete_term_meta( $current_term->term_id, 'mmt_reference_term_id' );
				delete_term_meta( $current_term->term_id, 'mmt_reference_term_object' );

				// Add it.
				update_term_meta( $current_term->term_id, 'mmt_reference_term_id', $term['id'] );
				update_term_meta( $current_term->term_id, 'mmt_reference_term_object', $term );
			}

			$migrated_terms[] = array(
				'term'         => $term,
				'current_term' => $current_term,
				'conflict'     => $conflict,
			);
		}

		if ( ! empty( $migrated_terms ) ) {
			set_transient( 'mmt_terms_migrated_referenced', $migrated_terms, DAY_IN_SECONDS );
		}

		return $migrated_terms;
	}

	/**
	 * Get Migrated Terms
	 *
	 * @since 0.1.0
	 *
	 * @return array $migrated_terms The terms that were migrated.
	 */
	public function get_migrated_terms() {
		return ( false !== ( $terms = get_transient( 'mmt_terms_migrated' ) ) ) ? $terms : array();
	}

	/**
	 * Get Migrated Terms
	 *
	 * @since 0.1.0
	 *
	 * @return array $migrated_terms The terms that were migrated.
	 */
	public function get_migrated_terms_referenced() {
		return ( false !== ( $terms = get_transient( 'mmt_terms_migrated_referenced' ) ) ) ? $terms : array();
	}
}
