<?php
/**
 * Main plugin class.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App;

use FluxAIMediaAltCreator\App\Services\Logger;
use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\App\Services\MediaScanner;
use FluxAIMediaAltCreator\App\Services\OpenAIService;
use FluxAIMediaAltCreator\App\Services\AltTextApiService;
use FluxAIMediaAltCreator\App\Services\UsageTracker;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;
use FluxAIMediaAltCreator\App\Services\ActionSchedulerService;
use FluxAIMediaAltCreator\FluxPlugins\Common\Services\MenuService;

use FluxAIMediaAltCreator\App\Http\Controllers\AdminController;
use FluxAIMediaAltCreator\App\Providers\ApiProvider;
use FluxAIMediaAltCreator\App\Providers\MediaScanProvider;
use FluxAIMediaAltCreator\App\Providers\AltTextProvider;
use FluxAIMediaAltCreator\App\Providers\UsageTrackingProvider;

/**
 * Main plugin class that initializes all components.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Settings instance.
	 *
	 * @since 1.0.0
	 * @var Settings
	 */
	private $settings;

	/**
	 * Media scanner instance.
	 *
	 * @since 1.0.0
	 * @var MediaScanner
	 */
	private $media_scanner;

	/**
	 * OpenAI service instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIService
	 */
	private $openai_service;

	/**
	 * Alt text API service instance (abstracted).
	 *
	 * @since 1.0.0
	 * @var AltTextApiService
	 */
	private $alt_text_api_service;

	/**
	 * Usage tracker instance.
	 *
	 * @since 1.0.0
	 * @var UsageTracker
	 */
	private $usage_tracker;

	/**
	 * Async job service instance.
	 *
	 * @since 1.0.0
	 * @var AsyncJobService
	 */
	private $async_job_service;

	/**
	 * Providers array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $providers = [];

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Setup menu pages (register during init to ensure pages are registered before menu.php loads).
		// Translations are available during init, so __() works fine here.
		if ( is_admin() ) {
			add_action( 'init', [ $this, 'register_menu_pages' ], 10 );
		}
		
		// Initialize logger first.
		$this->logger = new Logger();
		
		// Initialize settings.
		$this->settings = new Settings();
		
		// Initialize usage tracker.
		$this->usage_tracker = new UsageTracker( $this->logger );
		
		// Initialize OpenAI service.
		$this->openai_service = new OpenAIService( $this->logger, $this->usage_tracker );
		
		// Initialize abstracted alt text API service.
		$this->alt_text_api_service = new AltTextApiService( $this->logger, $this->openai_service, $this->usage_tracker );
		
		// Initialize media scanner.
		$this->media_scanner = new MediaScanner( $this->logger );
		
		// Set default image MIME types via hook.
		add_filter( 'flux_ai_alt_creator/media_scanner/get_default_mime_types', [ $this, 'get_default_image_mime_types' ], 10, 2 );
		
		// Initialize async job service.
		$this->async_job_service = new AsyncJobService( $this->logger, $this->alt_text_api_service, $this->media_scanner );
		
		// Initialize Action Scheduler service.
		$action_scheduler_service = new ActionSchedulerService( $this->logger, $this->async_job_service );
		$action_scheduler_service->init();
		
		// Initialize providers.
		$this->init_providers();
		
		// Initialize providers (register hooks).
		foreach ( $this->providers as $provider ) {
			$provider->init();
		}
	}

	/**
	 * Register menu pages.
	 *
	 * Called during init (before menu.php loads) to ensure pages are registered before WordPress checks access.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_menu_pages() {
		$menu_service = MenuService::get_instance();
		
		// Register Logs page if this plugin needs it.
		// The common library provides the page, but individual plugins decide if they want to register it.
		$menu_service->register_logs_page();
	}

	/**
	 * Initialize all providers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_providers() {
		// Admin controller - handles admin menu and UI.
		$this->providers['admin'] = new AdminController( $this->settings );
		
		// API provider - handles REST API routes.
		$this->providers['api'] = new ApiProvider(
			$this->settings,
			$this->media_scanner,
			$this->openai_service,
			$this->usage_tracker,
			$this->async_job_service,
			$this->logger
		);
		
		// Media scan provider - handles media scanning hooks.
		$this->providers['media_scan'] = new MediaScanProvider( $this->media_scanner, $this->logger );
		
		// Alt text provider - handles alt text generation hooks.
		$this->providers['alt_text'] = new AltTextProvider( $this->alt_text_api_service, $this->media_scanner, $this->logger );
		
		// Usage tracking provider - handles usage tracking hooks.
		$this->providers['usage_tracking'] = new UsageTrackingProvider( $this->usage_tracker, $this->logger );
	}

	/**
	 * Get the logger instance.
	 *
	 * @since 1.0.0
	 * @return Logger
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * Get the settings instance.
	 *
	 * @since 1.0.0
	 * @return Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get the media scanner instance.
	 *
	 * @since 1.0.0
	 * @return MediaScanner
	 */
	public function get_media_scanner() {
		return $this->media_scanner;
	}

	/**
	 * Get default image MIME types.
	 *
	 * This method provides image MIME types as the default when no other
	 * MIME types are provided via the flux_ai_alt_creator/media_scanner/get_default_mime_types hook.
	 *
	 * @since 1.0.0
	 * @param array $default_mime_types Default MIME types (empty by default).
	 * @param array $additional_params Additional search parameters.
	 * @return array Array of image MIME types.
	 */
	public function get_default_image_mime_types( $default_mime_types, $additional_params ) {
		// Only return image types if no other defaults are set.
		if ( empty( $default_mime_types ) ) {
			return [
				'image/jpeg',
				'image/jpg',
				'image/png',
				'image/gif',
				'image/webp',
				'image/avif',
				'image/svg+xml',
				'image/bmp',
				'image/tiff',
				'image/x-icon',
			];
		}
		
		return $default_mime_types;
	}

	/**
	 * Get the OpenAI service instance.
	 *
	 * @since 1.0.0
	 * @return OpenAIService
	 */
	public function get_openai_service() {
		return $this->openai_service;
	}

	/**
	 * Get the usage tracker instance.
	 *
	 * @since 1.0.0
	 * @return UsageTracker
	 */
	public function get_usage_tracker() {
		return $this->usage_tracker;
	}

	/**
	 * Get the async job service instance.
	 *
	 * @since 1.0.0
	 * @return AsyncJobService
	 */
	public function get_async_job_service() {
		return $this->async_job_service;
	}

	/**
	 * Get a provider instance.
	 *
	 * @since 1.0.0
	 * @param string $name Provider name.
	 * @return object|null Provider instance or null if not found.
	 */
	public function get_provider( $name ) {
		return $this->providers[ $name ] ?? null;
	}
}

