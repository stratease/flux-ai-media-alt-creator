<?php
/**
 * Admin provider for registering admin menu and UI hooks.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Providers;

use FluxAIMediaAltCreator\App\Services\Settings;

/**
 * Provider for admin functionality.
 *
 * @since 1.0.0
 */
class AdminProvider {

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
	 * Initialize the provider.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Add admin submenu under Media menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'upload.php', // Parent: Media
			__( 'AI Media Alt Creator', 'flux-ai-media-alt-creator' ),
			__( 'AI Alt Creator', 'flux-ai-media-alt-creator' ),
			'manage_options',
			'flux-ai-media-alt-creator',
			[ $this, 'render_main_page' ]
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our admin page.
		if ( 'media_page_flux-ai-media-alt-creator' !== $hook ) {
			return;
		}

		// Get script URL based on debug mode.
		$script_url = $this->get_script_url();

		// Enqueue the main admin script.
		wp_enqueue_script(
			'flux-ai-media-alt-creator-admin',
			$script_url,
			[ 'wp-api-fetch', 'wp-element', 'wp-components', 'wp-i18n' ],
			FLUX_AI_MEDIA_ALT_CREATOR_VERSION,
			true
		);

		// Localize script with WordPress data.
		wp_localize_script( 'flux-ai-media-alt-creator-admin', 'fluxAIMediaAltCreatorAdmin', [
			'apiUrl' => rest_url( 'flux-ai-media-alt-creator/v1/' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'adminUrl' => admin_url(),
			'pluginUrl' => FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_URL,
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
			return 'http://localhost:3000/admin.bundle.js';
		}

		// Use built asset.
		return FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_URL . 'assets/js/dist/admin.bundle.js';
	}

	/**
	 * Render the main admin page.
	 *
	 * @since 1.0.0
	 * @return void
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
					<p><?php esc_html_e( 'This assumes you are testing on a localhost WordPress environment with the webpack dev server running on port 3000.', 'flux-ai-media-alt-creator' ); ?></p>
					<p><strong><?php esc_html_e( 'To use the development build:', 'flux-ai-media-alt-creator' ); ?></strong></p>
					<ol>
						<li><?php esc_html_e( 'Navigate to the plugin directory in your terminal', 'flux-ai-media-alt-creator' ); ?></li>
						<li><?php esc_html_e( 'Run "npm run start" to start the webpack dev server', 'flux-ai-media-alt-creator' ); ?></li>
						<li><?php esc_html_e( 'Ensure the dev server is running on http://localhost:3000', 'flux-ai-media-alt-creator' ); ?></li>
						<li><?php esc_html_e( 'Refresh this page to load the development build', 'flux-ai-media-alt-creator' ); ?></li>
					</ol>
				</div>
			<?php endif; ?>
			</div>
		</div>
		<?php
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
		return apply_filters( 'flux_ai_alt_creator_should_show_field', true, $field_name );
	}
}

