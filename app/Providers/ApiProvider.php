<?php
/**
 * API provider for registering REST API routes.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

// phpcs:disable WordPress.Security.EscapeOutput.DirectOutput -- ABSPATH check is placed after namespace declaration due to PHP syntax requirements (namespace must be first statement).
namespace FluxAIMediaAltCreator\App\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:enable

use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\App\Services\MediaScanner;
use FluxAIMediaAltCreator\App\Services\OpenAIService;
use FluxAIMediaAltCreator\App\Services\UsageTracker;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;
use FluxAIMediaAltCreator\App\Services\ComplianceScanService;

use FluxAIMediaAltCreator\App\Http\Controllers\MediaController;
use FluxAIMediaAltCreator\App\Http\Controllers\AltTextController;
use FluxAIMediaAltCreator\App\Http\Controllers\OptionsController;
use FluxAIMediaAltCreator\App\Http\Controllers\UsageController;
use FluxAIMediaAltCreator\App\Http\Controllers\ComplianceController;

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
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Removed Logger parameter, controllers now use Logger::get_instance() directly via BaseController.
	 * @param Settings        $settings Settings instance.
	 * @param MediaScanner    $media_scanner Media scanner instance.
	 * @param OpenAIService   $openai_service OpenAI service instance.
	 * @param UsageTracker    $usage_tracker Usage tracker instance.
	 * @param AsyncJobService $async_job_service Async job service instance.
	 */
	public function __construct(
		Settings $settings,
		MediaScanner $media_scanner,
		OpenAIService $openai_service,
		UsageTracker $usage_tracker,
		AsyncJobService $async_job_service,
	) {
		$this->settings = $settings;
		$this->media_scanner = $media_scanner;
		$this->openai_service = $openai_service;
		$this->usage_tracker = $usage_tracker;
		$this->async_job_service = $async_job_service;
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
	 * @since 1.2.0 Updated AltTextController instantiation - removed OpenAIService dependency.
	 * @return void
	 */
	public function register_rest_routes() {
		// Initialize controllers.
		$media_controller = new MediaController( $this->media_scanner );
		$alt_text_controller = new AltTextController( $this->media_scanner, $this->async_job_service );
		$options_controller = new OptionsController( $this->settings );
		$usage_controller = new UsageController( $this->usage_tracker );
		$compliance_scan_service = ComplianceScanService::get_instance();
		$compliance_controller = new ComplianceController( $compliance_scan_service );

		// Register routes.
		$media_controller->register_routes();
		$alt_text_controller->register_routes();
		$options_controller->register_routes();
		$usage_controller->register_routes();
		$compliance_controller->register_routes();

		// Register action for compliance scan batch (can be invoked by external scheduler).
		add_action( 'flux_ai_alt_creator/compliance/run_scan_batch', [ $compliance_scan_service, 'run_scan_batch' ], 10, 1 );

		// Reclassify when attachment alt text is updated (core hook) so compliance category stays in sync.
		add_action( 'updated_post_meta', [ $compliance_scan_service, 'on_attachment_alt_updated' ], 10, 4 );

		/**
		 * Action to register additional API routes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flux_ai_alt_creator/api_provider/register_routes' );
	}
}

