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
use FluxAIMediaAltCreator\App\Services\ImageScanner;
use FluxAIMediaAltCreator\App\Services\OpenAIService;
use FluxAIMediaAltCreator\App\Services\UsageTracker;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;
use FluxAIMediaAltCreator\App\Services\ActionSchedulerService;

use FluxAIMediaAltCreator\App\Providers\AdminProvider;
use FluxAIMediaAltCreator\App\Providers\ApiProvider;
use FluxAIMediaAltCreator\App\Providers\ImageScanProvider;
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
	 * Image scanner instance.
	 *
	 * @since 1.0.0
	 * @var ImageScanner
	 */
	private $image_scanner;

	/**
	 * OpenAI service instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIService
	 */
	private $openai_service;

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
		// Initialize logger first.
		$this->logger = new Logger();
		
		// Initialize settings.
		$this->settings = new Settings();
		
		// Initialize usage tracker.
		$this->usage_tracker = new UsageTracker( $this->logger );
		
		// Initialize OpenAI service.
		$this->openai_service = new OpenAIService( $this->logger, $this->usage_tracker );
		
		// Initialize image scanner.
		$this->image_scanner = new ImageScanner( $this->logger );
		
		// Initialize async job service.
		$this->async_job_service = new AsyncJobService( $this->logger, $this->openai_service, $this->image_scanner );
		
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
	 * Initialize all providers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_providers() {
		// Admin provider - handles admin menu and UI.
		$this->providers['admin'] = new AdminProvider( $this->settings );
		
		// API provider - handles REST API routes.
		$this->providers['api'] = new ApiProvider(
			$this->settings,
			$this->image_scanner,
			$this->openai_service,
			$this->usage_tracker,
			$this->async_job_service,
			$this->logger
		);
		
		// Image scan provider - handles image scanning hooks.
		$this->providers['image_scan'] = new ImageScanProvider( $this->image_scanner, $this->logger );
		
		// Alt text provider - handles alt text generation hooks.
		$this->providers['alt_text'] = new AltTextProvider( $this->openai_service, $this->logger );
		
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
	 * Get the image scanner instance.
	 *
	 * @since 1.0.0
	 * @return ImageScanner
	 */
	public function get_image_scanner() {
		return $this->image_scanner;
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

