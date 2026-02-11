<?php
/**
 * Alt text provider for registering alt text generation hooks.
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

use FluxAIMediaAltCreator\App\Services\AltTextApiService;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;
use FluxAIMediaAltCreator\App\Services\MediaScanner;
use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;

/**
 * Provider for alt text generation functionality.
 *
 * @since 1.0.0
 */
class AltTextProvider {

	/**
	 * Alt text API service instance (abstracted).
	 *
	 * @since 1.0.0
	 * @var AltTextApiService
	 */
	private $alt_text_api_service;

	/**
	 * Media scanner instance.
	 *
	 * @since 1.0.0
	 * @var MediaScanner
	 */
	private $media_scanner;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Removed Logger parameter, now uses Logger::get_instance() directly.
	 * @param AltTextApiService $alt_text_api_service Alt text API service instance.
	 * @param MediaScanner      $media_scanner Media scanner instance.
	 */
	public function __construct( AltTextApiService $alt_text_api_service, MediaScanner $media_scanner ) {
		$this->alt_text_api_service = $alt_text_api_service;
		$this->media_scanner = $media_scanner;
	}

	/**
	 * Initialize the provider.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added Action Scheduler hook registrations for batch processing.
	 * @return void
	 */
	public function init() {
		// Register Action Scheduler hooks for batch processing.
		$async_job_service = AsyncJobService::get_instance();
		add_action( 'flux_ai_alt_creator/async_job_service/generate_alt_text_batch', [ $async_job_service, 'process_alt_text_generation_batch' ], 10, 1 );
		add_action( 'flux_ai_alt_creator/async_job_service/apply_alt_text_batch', [ $async_job_service, 'process_alt_text_application_batch' ], 10, 1 );
	}
}
