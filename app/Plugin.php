<?php
/**
 * Main plugin class.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App;

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
use FluxAIMediaAltCreator\App\Providers\AltTextProvider;

/**
 * Main plugin class that initializes all components.
 *
 * @since 1.0.0
 */
class Plugin {

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
		
		// Initialize settings.
		$this->settings = new Settings();
		
		// Initialize usage tracker (singleton).
		$this->usage_tracker = UsageTracker::get_instance();
		
		// Initialize OpenAI service (singleton).
		$this->openai_service = OpenAIService::get_instance();
		
		// Initialize abstracted alt text API service (singleton).
		$this->alt_text_api_service = AltTextApiService::get_instance();
		
		// Initialize media scanner (singleton).
		$this->media_scanner = MediaScanner::get_instance();
		
		
		// Initialize async job service (singleton).
		$this->async_job_service = AsyncJobService::get_instance();
		
		// Initialize Action Scheduler service.
		$action_scheduler_service = new ActionSchedulerService();
		$action_scheduler_service->init();
		
		// Admin controller - handles admin menu and UI.
		$admin_controller = new AdminController( $this->settings );
		$admin_controller->init();
		
		// API provider - handles REST API routes.
		$api_provider = new ApiProvider(
			$this->settings,
			$this->media_scanner,
			$this->openai_service,
			$this->usage_tracker,
			$this->async_job_service
		);
		$api_provider->init();
		
		// Alt text provider - handles alt text generation hooks.
		$alt_text_provider = new AltTextProvider( $this->alt_text_api_service, $this->media_scanner );
		$alt_text_provider->init();
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
}

