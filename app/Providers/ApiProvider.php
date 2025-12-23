<?php
/**
 * API provider for registering REST API routes.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Providers;

use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\App\Services\MediaScanner;
use FluxAIMediaAltCreator\App\Services\OpenAIService;
use FluxAIMediaAltCreator\App\Services\UsageTracker;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;
use FluxAIMediaAltCreator\App\Services\Logger;

use FluxAIMediaAltCreator\App\Http\Controllers\MediaController;
use FluxAIMediaAltCreator\App\Http\Controllers\AltTextController;
use FluxAIMediaAltCreator\App\Http\Controllers\OptionsController;
use FluxAIMediaAltCreator\App\Http\Controllers\UsageController;

/**
 * Provider for REST API functionality.
 *
 * @since 1.0.0
 */
class ApiProvider {

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
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Settings        $settings Settings instance.
	 * @param MediaScanner    $media_scanner Media scanner instance.
	 * @param OpenAIService   $openai_service OpenAI service instance.
	 * @param UsageTracker    $usage_tracker Usage tracker instance.
	 * @param AsyncJobService $async_job_service Async job service instance.
	 * @param Logger          $logger Logger instance.
	 */
	public function __construct(
		Settings $settings,
		MediaScanner $media_scanner,
		OpenAIService $openai_service,
		UsageTracker $usage_tracker,
		AsyncJobService $async_job_service,
		Logger $logger
	) {
		$this->settings = $settings;
		$this->media_scanner = $media_scanner;
		$this->openai_service = $openai_service;
		$this->usage_tracker = $usage_tracker;
		$this->async_job_service = $async_job_service;
		$this->logger = $logger;
	}

	/**
	 * Initialize the provider.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_routes() {
		// Initialize controllers.
		$media_controller = new MediaController( $this->media_scanner, $this->logger );
		$alt_text_controller = new AltTextController( $this->openai_service, $this->media_scanner, $this->async_job_service, $this->logger );
		$options_controller = new OptionsController( $this->settings, $this->logger );
		$usage_controller = new UsageController( $this->usage_tracker, $this->logger );

		// Register routes.
		$media_controller->register_routes();
		$alt_text_controller->register_routes();
		$options_controller->register_routes();
		$usage_controller->register_routes();

		/**
		 * Action to register additional API routes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flux_ai_alt_creator_register_api_routes' );
	}
}

