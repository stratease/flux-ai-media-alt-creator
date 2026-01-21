<?php
/**
 * Alt text provider for registering alt text generation hooks.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Providers;

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
		// Register action hook to process individual attachments.
		add_action( 'flux_ai_alt_creator/alt_text_provider/process_attachment', [ $this, 'process_attachment' ], 10, 1 );

		// Register Action Scheduler hooks for batch processing.
		$async_job_service = AsyncJobService::get_instance();
		add_action( 'flux_ai_alt_creator/async_job_service/generate_alt_text_batch', [ $async_job_service, 'process_alt_text_generation_batch' ], 10, 1 );
		add_action( 'flux_ai_alt_creator/async_job_service/apply_alt_text_batch', [ $async_job_service, 'process_alt_text_application_batch' ], 10, 1 );
	}

	/**
	 * Process a single attachment for alt text generation.
	 *
	 * This method can be called via the action hook:
	 * do_action( 'flux_ai_alt_creator/alt_text_provider/process_attachment', $attachment_id );
	 *
	 * @since 1.0.0
	 * @param int $attachment_id Attachment ID to process.
	 * @return array Result array with 'success', 'alt_text', and optionally 'error'.
	 */
	public function process_attachment( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		
		if ( ! $attachment_id ) {
			Logger::get_instance()->warning( 'Invalid attachment ID provided to process_attachment', [ 'attachment_id' => $attachment_id ] );
			return [
				'success' => false,
				'alt_text' => '',
				'error' => __( 'Invalid attachment ID', 'flux-ai-media-alt-creator' ),
			];
		}

		// Get media URL.
		$media_url = wp_get_attachment_url( $attachment_id );
		
		if ( ! $media_url ) {
			Logger::get_instance()->warning( 'Could not get media URL for attachment', [ 'attachment_id' => $attachment_id ] );
			return [
				'success' => false,
				'alt_text' => '',
				'error' => __( 'Could not get media URL', 'flux-ai-media-alt-creator' ),
			];
		}

		// Update status to processing.
		// @since 1.1.0 Changed from update_scan_data with 'ai_status' to update_scan_status.
		$this->media_scanner->update_scan_status( $attachment_id, 'processing' );

		// Generate alt text via abstracted API service.
		$result = $this->alt_text_api_service->generate_alt_text( $attachment_id, $media_url );

		if ( $result['success'] ) {
			// @since 1.1.0 Changed from update_scan_data with 'ai_status' to update_scan_status.
			$this->media_scanner->update_scan_status( $attachment_id, 'completed' );
			$this->media_scanner->update_scan_data( $attachment_id, [
				'recommended_alt_text' => $result['alt_text'],
			] );
		} else {
			// @since 1.1.0 Changed from update_scan_data with 'ai_status' to update_scan_status.
			$this->media_scanner->update_scan_status( $attachment_id, 'error' );
			$this->media_scanner->update_scan_data( $attachment_id, [
				'error_message' => $result['error'] ?? __( 'Unknown error', 'flux-ai-media-alt-creator' ),
			] );
		}

		return $result;
	}
}

