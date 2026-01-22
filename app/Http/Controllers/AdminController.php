<?php
/**
 * Admin controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

// phpcs:disable WordPress.Security.EscapeOutput.DirectOutput -- ABSPATH check is placed after namespace declaration due to PHP syntax requirements (namespace must be first statement).
namespace FluxAIMediaAltCreator\App\Http\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:enable

use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\FluxPlugins\Common\Services\MenuService;

/**
 * Handles WordPress admin page registration and management.
 *
 * @since 1.0.0
 */
class AdminController {

	/**
	 * Settings instance.
	 *
	 * @since 1.0.0
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Initialize admin functionality.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Register menu during init (before menu.php loads) to ensure page is registered before WordPress checks access.
		// menu.php is loaded at line 163 of admin.php, which is BEFORE admin_init fires at line 180.
		// We must register the page before menu.php loads, so we use init hook with is_admin() check.
		// Use priority 1 to ensure AI Media Alt Creator is registered very early.
		if ( is_admin() ) {
			add_action( 'init', [ $this, 'register_menu' ], 1 );
		}
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'admin_notices', [ $this, 'show_api_key_notice' ] );
		
		// Add "Upgrade to Pro" link to plugin action links.
		add_filter( 'plugin_action_links_' . FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_BASENAME, [ $this, 'add_plugin_action_links' ] );
	}

	/**
	 * Show admin notice if API key is not configured.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function show_api_key_notice() {
		// Only show on our admin page.
		$screen = get_current_screen();
		if ( ! $screen || 'flux-suite_page_flux-ai-media-alt-creator' !== $screen->id ) {
			return;
		}

		$api_key = Settings::get_openai_api_key();
		
		if ( empty( $api_key ) ) {
			$settings_url = admin_url( 'admin.php?page=flux-ai-media-alt-creator#/settings' );
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Flux AI Media Alt Creator:', 'flux-ai-media-alt-creator' ); ?></strong>
					<?php
					printf(
						/* translators: %s: Settings page URL */
						wp_kses_post( __( 'OpenAI API key is not configured. Please <a href="%s">add your API key in Settings</a> to enable alt text generation.', 'flux-ai-media-alt-creator' ) ),
						esc_url( $settings_url )
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Register admin menu pages.
	 *
	 * Called during init (before menu.php loads) to ensure page is registered before WordPress checks access.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_menu() {
		// Register plugin-specific submenu page using MenuService.
		// Placement 1 makes this the primary menu item (first submenu under "Flux Suite").
		$menu_service = MenuService::get_instance();
		$menu_service->register_submenu_page(
			'flux-ai-media-alt-creator',
			__( 'AI Media Alt Creator', 'flux-ai-media-alt-creator' ),
			[ $this, 'render_main_page' ],
			'manage_options',
			1 // Placement: 1 = first submenu item under "Flux Suite".
		);

		// Note: Plugin registration in Flux Suite overview is now handled centrally
		// in MenuService::init_plugin_registry() for marketing purposes only.
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * All scripts are properly enqueued using WordPress standards:
	 * - wp_enqueue_script() for JavaScript files
	 * - wp_localize_script() for passing PHP data to JavaScript
	 * - wp_enqueue_style() for CSS files
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our admin pages.
		if ( strpos( $hook, 'flux-ai-media-alt-creator' ) === false ) {
			return;
		}

		// Get script URL based on debug mode.
		$script_url = $this->get_script_url();

		// Build script dependencies.
		$dependencies = [ 'wp-api-fetch', 'wp-element', 'wp-components', 'wp-i18n' ];

		// Enqueue the main admin script.
		wp_enqueue_script(
			'flux-ai-media-alt-creator-admin',
			$script_url,
			$dependencies,
			FLUX_AI_MEDIA_ALT_CREATOR_VERSION,
			true
		);

		// Get registered tabs via filter.
		$registered_tabs = apply_filters( 'flux_ai_alt_creator/admin_controller/get_tabs', [] );

		// Check if Pro plugin is active - check both constant and plugin activation status.
		$is_pro_active = false;
		if ( defined( 'FLUX_AI_MEDIA_ALT_CREATOR_PRO_VERSION' ) ) {
			$is_pro_active = true;
		} else {
			// Fallback: check if Pro plugin file is active.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$is_pro_active = is_plugin_active( 'flux-ai-media-alt-creator-pro/flux-ai-media-alt-creator-pro.php' );
		}

		// Localize script with WordPress data.
		wp_localize_script( 'flux-ai-media-alt-creator-admin', 'fluxAIMediaAltCreatorAdmin', [
			'apiUrl' => rest_url( 'flux-ai-media-alt-creator/v1/' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'adminUrl' => admin_url(),
			'pluginUrl' => FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_URL,
			'tabs' => $registered_tabs,
			'isProActive' => $is_pro_active,
		] );

		// Enqueue WordPress admin styles.
		wp_enqueue_style( 'wp-components' );
	}

	/**
	 * Get script URL based on debug mode.
	 *
	 * @since 1.0.0
	 * @return string Script URL.
	 */
	private function get_script_url() {
		// Use webpack dev server if debug mode is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return 'http://localhost:3002/admin.bundle.js';
		}

		// Use built asset.
		return FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_URL . 'assets/js/dist/admin.bundle.js';
	}

	/**
	 * Render the main admin page.
	 *
	 * @since 1.0.0
	 */
	public function render_main_page() {
		$is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		?>
		<div class="wrap">
			<div id="flux-ai-media-alt-creator-app">
			<?php if ( $is_debug ) : ?>
				<div class="notice notice-warning" style="margin: 20px 0; padding: 15px;">
					<p><strong><?php esc_html_e( 'Development Mode Active', 'flux-ai-media-alt-creator' ); ?></strong></p>
					<p><?php esc_html_e( 'Development mode is enabled. The admin interface is attempting to load the React development bundle from:', 'flux-ai-media-alt-creator' ); ?></p>
					<p><code><?php echo esc_html( $this->get_script_url() ); ?></code></p>
					<p><?php esc_html_e( 'This assumes you are testing on a localhost WordPress environment with the webpack dev server running on port 3002.', 'flux-ai-media-alt-creator' ); ?></p>
					<p><strong><?php esc_html_e( 'To use the development build:', 'flux-ai-media-alt-creator' ); ?></strong></p>
					<ol>
						<li><?php esc_html_e( 'Navigate to the plugin directory in your terminal', 'flux-ai-media-alt-creator' ); ?></li>
						<li><?php esc_html_e( 'Run "npm run start" to start the webpack dev server', 'flux-ai-media-alt-creator' ); ?></li>
						<li><?php esc_html_e( 'Ensure the dev server is running on http://localhost:3002', 'flux-ai-media-alt-creator' ); ?></li>
						<li><?php esc_html_e( 'Refresh this page to load the development build', 'flux-ai-media-alt-creator' ); ?></li>
					</ol>
				</div>
			<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Add action links to plugin row in plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links ) {
		// Only show "Upgrade to Pro" if Pro plugin is not active.
		if ( ! defined( 'FLUX_AI_MEDIA_ALT_CREATOR_PRO_VERSION' ) ) {
			$upgrade_link = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" style="color: #2271b1; font-weight: 600;">%s</a>',
				esc_url( 'https://fluxplugins.com/ai-media-alt-creator-pro/' ),
				esc_html__( 'Upgrade to Pro', 'flux-ai-media-alt-creator' )
			);
			
			// Add upgrade link at the beginning.
			array_unshift( $links, $upgrade_link );
		}
		
		return $links;
	}

	/**
	 * Check if a field should be shown.
	 *
	 * @since 1.0.0
	 * @param string $field_name Field name.
	 * @return bool True if field should be shown.
	 */
	public static function should_show_field( $field_name ) {
		/**
		 * Filter to show/hide admin fields.
		 *
		 * @since 1.0.0
		 * @param bool   $show Whether to show the field.
		 * @param string $field_name Field name.
		 */
		return apply_filters( 'flux_ai_alt_creator/admin_controller/should_show_field', true, $field_name );
	}
}

