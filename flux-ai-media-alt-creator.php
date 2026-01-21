<?php
/**
 * Plugin Name: Flux AI Media Alt Creator by Flux Plugins
 * Plugin URI: https://fluxplugins.com/ai-media-alt-creator
 * Description: Automatically generate AI-powered alt text for media files using OpenAI's GPT-4o-mini.
 * Version: 1.1.1
 * Author: Flux Plugins
 * Author URI: https://fluxplugins.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flux-ai-media-alt-creator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 8.0
 *
 * Copyright 2025 Flux Plugins
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

use FluxAIMediaAltCreator\FluxPlugins\Common\FluxPlugins;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'FLUX_AI_MEDIA_ALT_CREATOR_VERSION', '1.1.1' );
define( 'FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_FILE', __FILE__ );
define( 'FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_SLUG', 'flux-ai-media-alt-creator' );

// Check PHP version compatibility.
// @since 1.0.0
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action( 'admin_notices', 'flux_ai_media_alt_creator_php_version_notice' );
	return;
}

// Check WordPress version compatibility.
// @since 1.0.0
global $wp_version;
if ( version_compare( $wp_version, '5.8', '<' ) ) {
	add_action( 'admin_notices', 'flux_ai_media_alt_creator_wp_version_notice' );
	return;
}

/**
 * Display PHP version compatibility notice.
 *
 * @since 1.0.0
 */
function flux_ai_media_alt_creator_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Current PHP version, 2: Required PHP version */
				esc_html__( 'Flux AI Media Alt Creator requires PHP %2$s or higher. You are running PHP %1$s.', 'flux-ai-media-alt-creator' ),
				esc_html( PHP_VERSION ),
				'8.0'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display WordPress version compatibility notice.
 *
 * @since 1.0.0
 */
function flux_ai_media_alt_creator_wp_version_notice() {
	global $wp_version;
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Current WordPress version, 2: Required WordPress version */
				esc_html__( 'Flux AI Media Alt Creator requires WordPress %2$s or higher. You are running WordPress %1$s.', 'flux-ai-media-alt-creator' ),
				esc_html( $wp_version ),
				'5.8'
			);
			?>
		</p>
	</div>
	<?php
}

// Load Composer autoloader.
if ( file_exists( FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR . 'vendor/autoload.php' )
	&& file_exists( FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR . 'vendor-prefixed/autoload.php' ) ) {
	require_once FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR . 'vendor/autoload.php';
	require_once FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR . 'vendor-prefixed/autoload.php';
} else {
	add_action( 'admin_notices', 'flux_ai_media_alt_creator_composer_notice' );
	return;
}

/**
 * Display Composer dependencies notice.
 *
 * @since 1.0.0
 */
function flux_ai_media_alt_creator_composer_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'Flux AI Media Alt Creator requires Composer dependencies. Please run "composer install" in the plugin directory.', 'flux-ai-media-alt-creator' ); ?>
		</p>
	</div>
	<?php
}

// Initialize the plugin.
add_action( 'plugins_loaded', 'flux_ai_media_alt_creator_init' );

// Handle activation redirect.
add_action( 'admin_init', 'flux_ai_media_alt_creator_activation_redirect' );

// Translations are automatically loaded by WordPress.org for hosted plugins (WordPress 4.6+).

/**
 * Initialize the Flux AI Media Alt Creator plugin.
 *
 * @since 1.0.0
 */
function flux_ai_media_alt_creator_init() {
	// Initialize Flux Plugins common library.
	// This handles account ID, menu setup, REST API routes, and required pages.
	FluxPlugins::init( FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_SLUG, FLUX_AI_MEDIA_ALT_CREATOR_VERSION, 'flux-ai-media-alt-creator' );
	
	// Initialize the main plugin class.
	$flux_ai_media_alt_creator = new FluxAIMediaAltCreator\App\Plugin();
	$flux_ai_media_alt_creator->init();
}

/**
 * Check if Flux AI Media Alt Creator is activated on the network.
 *
 * @since 1.0.0
 *
 * @return bool True if Flux AI Media Alt Creator is activated on the network.
 */
function flux_ai_media_alt_creator_is_active_for_network() {
	static $is;

	if ( isset( $is ) ) {
		return $is;
	}

	if ( ! is_multisite() ) {
		$is = false;
		return $is;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$is = is_plugin_active_for_network( plugin_basename( FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_FILE ) );

	return $is;
}

/**
 * Handle activation redirect to admin page.
 *
 * This is a one-time redirect that occurs only immediately after plugin activation.
 * The redirect helps users discover the plugin settings page after installation.
 *
 * Safety measures to prevent dashboard hijacking:
 * - Only redirects if transient is set (created only on activation)
 * - Transient expires after 60 seconds (failsafe)
 * - Transient is immediately deleted on redirect (ensures one-time only)
 * - Only redirects users with 'manage_options' capability (admins only)
 * - Redirects to plugin's own admin page (not external site)
 * - Only runs on admin_init hook (not on frontend)
 *
 * @since 1.0.0
 * @return void
 */
function flux_ai_media_alt_creator_activation_redirect() {
	// Only redirect in admin area and if transient is set.
	if ( ! is_admin() ) {
		return;
	}

	// Only redirect if transient is set and user has proper capabilities.
	$redirect_transient = flux_ai_media_alt_creator_is_active_for_network()
		? get_site_transient( 'flux_ai_media_alt_creator_activation_redirect' )
		: get_transient( 'flux_ai_media_alt_creator_activation_redirect' );

	if ( $redirect_transient && current_user_can( 'manage_options' ) ) {
		// Delete the transient immediately to ensure this only happens once.
		if ( flux_ai_media_alt_creator_is_active_for_network() ) {
			delete_site_transient( 'flux_ai_media_alt_creator_activation_redirect' );
		} else {
			delete_transient( 'flux_ai_media_alt_creator_activation_redirect' );
		}
		
		// Redirect to plugin's own admin page (not external site).
		wp_safe_redirect( admin_url( 'admin.php?page=flux-ai-media-alt-creator' ) );
		exit;
	}
}

// Activation and deactivation hooks.
register_activation_hook( __FILE__, 'flux_ai_media_alt_creator_activate' );
register_deactivation_hook( __FILE__, 'flux_ai_media_alt_creator_deactivate' );
register_uninstall_hook( __FILE__, 'flux_ai_media_alt_creator_uninstall' );

/**
 * Plugin activation handler.
 *
 * @since 1.0.0
 */
function flux_ai_media_alt_creator_activate() {
	// Check requirements before activation.
	global $wp_version;
	if ( version_compare( PHP_VERSION, '8.0', '<' ) || version_compare( $wp_version, '5.8', '<' ) ) {
		return;
	}

	// Initialize settings with defaults.
	$settings = new FluxAIMediaAltCreator\App\Services\Settings();
	$settings->initialize_defaults();
	
	// Set transient to redirect to admin page after activation.
	if ( flux_ai_media_alt_creator_is_active_for_network() ) {
		set_site_transient( 'flux_ai_media_alt_creator_activation_redirect', true, 60 );
	} else {
		set_transient( 'flux_ai_media_alt_creator_activation_redirect', true, 60 );
	}
}

/**
 * Plugin deactivation handler.
 *
 * @since 1.0.0
 */
function flux_ai_media_alt_creator_deactivate() {
	// Clear any scheduled WP Cron events.
	wp_clear_scheduled_hook( 'flux_ai_media_alt_creator_cleanup' );

	// Cancel all Free plugin action scheduler actions.
	$async_job_service = AsyncJobService::get_instance();
	$async_job_service->cancel_all_actions();
}

/**
 * Plugin uninstall handler.
 *
 * @since 1.0.0
 */
function flux_ai_media_alt_creator_uninstall() {
	defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

	global $wpdb;

	// Remove plugin options.
	$options = [
		'flux_ai_alt_creator_settings',
		'flux_ai_alt_creator_usage_current_month',
		'flux_ai_media_alt_creator_version',
		'flux_ai_media_alt_creator_activation_redirect',
	];

	foreach ( $options as $option ) {
		delete_option( $option );
		delete_site_option( $option );
	}

	// Remove post meta for all attachments.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			$wpdb->esc_like( '_flux_ai_alt_creator_' ) . '%'
		)
	);

	// Clear any scheduled WP Cron jobs.
	wp_clear_scheduled_hook( 'flux_ai_media_alt_creator_cleanup' );

	// Remove any transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_flux_ai_media_alt_creator_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_flux_ai_media_alt_creator_' ) . '%'
		)
	);
}

