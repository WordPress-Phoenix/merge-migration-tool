<?php
/**
 * Migration Merge Tool - Admin
 *
 * @package    MMT
 * @subpackage Includes
 * @since      0.1.0
 */
defined( 'ABSPATH' ) or die();

/**
 * MMT_Admin class.
 *
 * @since 0.1.0
 */
class MMT_Admin {

	/**
	 * Admin Slug
	 *
	 * @since 0.1.0
	 * @var string
	 */
	var $slug = 'mmt';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		// Admin Check.
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// Admin page and settings.
		add_action( 'admin_menu', array( $this, 'register_admin_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_admin_settings' ) );

		// Admin Wizard.
		add_action( 'init', array( $this, 'register_wizard' ) );
	}

	/**
	 * Register Admin Settings Page
	 *
	 * Add a link under the 'Tools' menu.
	 *
	 * @since  0.1.0
	 */
	public function register_admin_settings_page() {
		$hook = add_management_page( esc_attr__( 'Migration Tool', 'mmt' ), esc_attr__( 'Migration Tool', 'mmt' ), 'manage_options', 'mmt', array( $this, 'display_admin_settings' ) );
		add_action( "load-{$hook}", array( $this, 'register_admin_scripts_styles' ) );
	}

	/**
	 * Register Admin Scripts & Styles
	 *
	 * Output the scripts and styles for the main admin pages.
	 * Excludes the wizard.
	 *
	 * @since 0.1.0
	 */
	public function register_admin_scripts_styles() {
		// Version.
		$version = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MMT_DEBUG' ) && MMT_DEBUG ) ? time() : MMT_VERSION;

		// Suffix.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MMT_DEBUG' ) && MMT_DEBUG ) ? '' : '.min';

		// CSS.
		wp_enqueue_style( 'mmt', MMT_CSS . "mmt{$suffix}.css", null, $version );

		// Javascript.
		wp_register_script( 'mmt', MMT_JS . "mmt{$suffix}.js", array( 'jquery' ), $version );
		wp_localize_script( 'mmt', 'mmt_params', array() );
	}

	/**
	 * Display Admin Settings
	 *
	 * Display the content for each tab in the admin.
	 *
	 * @since  0.1.0
	 */
	public function display_admin_settings() {
		$admin_tabs = apply_filters( 'mmt_admin_tabs', array(
			'key'    => esc_attr__( 'Migration Key', 'mmt' ),
			'wizard' => esc_attr__( 'Migration Wizard', 'mmt' ),
		) );
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'key'; // Input var ok.
		?>
		<div class="wrap <?php echo 'wrap-' . esc_attr( $active_tab ); ?>">
			<h1 class="nav-tab-wrapper">
				<?php
				foreach ( $admin_tabs as $tab_id => $tab_name ) {
					$tab_url = add_query_arg( array( 'tab' => $tab_id ) );
					$active  = $active_tab === $tab_id ? ' nav-tab-active' : '';
					printf( '<a href="%s" class="nav-tab%s">%s</a>', esc_url( $tab_url ), esc_attr( $active ), esc_html( $tab_name ) );
				}
				?>
			</h1>
			<div id="tab_container" class="mmt-settings">
				<form method="post" action="options.php">
					<?php settings_errors(); ?>
					<?php settings_fields( 'mmt' ); ?>
					<?php do_settings_sections( 'mmt' ); ?>
					<?php submit_button(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Register Admin settings
	 *
	 * @since 0.1.0
	 */
	public function register_admin_settings() {
		add_settings_section( 'mmt_key-section', esc_html__( 'Migration Key', 'mmt' ), array( $this, 'key_section_callback' ), $this->slug );
		add_settings_field( 'mmt_key-field', esc_html__( 'Migration Key', 'mmt' ), array( $this, 'key_field_callback' ), $this->slug, 'mmt_key-section' );
		register_setting( $this->slug, 'mmt_key' );
	}

	/**
	 * Key Section Callback
	 *
	 * @since  0.1.0
	 *
	 * @return void
	 */
	public function key_section_callback() {
		printf( '<p>%s</p>', esc_html__( 'Input a key below to allow verification of the migration wizard. You will be asked to enter requesting to migrate data from this site.', 'mmt' ) );
	}

	/**
	 * Key Field Callback
	 *
	 * @since 0.1.0
	 *
	 * @param array $args The callback arguments.
	 *
	 * @return void
	 */
	public function key_field_callback( $args ) {
		$migration_key = get_option( 'mmt_key' );
		printf( '<input type="text" name="mmt_key" id="mmt_key" class="mmt_field" value="%s">', esc_attr( $migration_key ) );
	}

	/**
	 * Register Wizard
	 *
	 * @since 0.1.0
	 */
	public function register_wizard() {
		if ( empty( $_GET['page'] ) || empty( $_GET['tab'] ) || $this->slug !== $_GET['page'] || 'wizard' !== $_GET['tab'] ) { // Input var ok.
			return;
		}

		// Include Wizard.
		include_once MMT_INC . 'wizard/class-mmt-wizard.php';
	}
}

new MMT_Admin();
