<?php
/**
 * Async job service for batch processing.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Service for scheduling async jobs using Action Scheduler pattern.
 *
 * @since 1.0.0
 */
class AsyncJobService {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * OpenAI service instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIService
	 */
	private $openai_service;

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
	 * @param Logger        $logger Logger instance.
	 * @param OpenAIService $openai_service OpenAI service instance.
	 * @param MediaScanner  $media_scanner Media scanner instance.
	 */
	public function __construct( Logger $logger, OpenAIService $openai_service, MediaScanner $media_scanner ) {
		$this->logger = $logger;
		$this->openai_service = $openai_service;
		$this->media_scanner = $media_scanner;
		
		// Register action hooks.
		$this->register_action_hooks();
	}

	/**
	 * Schedule alt text generation for multiple media files.
	 *
	 * @since 1.0.0
	 * @param array $media_ids Array of media IDs.
	 * @param int   $batch_size Batch size for processing.
	 * @return int|false Number of jobs scheduled or false on failure.
	 */
	public function schedule_alt_text_generation( $media_ids, $batch_size = 10 ) {
		/**
		 * Filter the batch size for async job processing.
		 *
		 * @since 1.0.0
		 * @param int $batch_size Current batch size.
		 */
		$batch_size = apply_filters( 'flux_ai_alt_creator_async_job_batch_size', $batch_size );
		
		// Split into batches.
		$batches = array_chunk( $media_ids, $batch_size );
		$jobs_scheduled = 0;

		foreach ( $batches as $batch ) {
			/**
			 * Filter to allow modification of job scheduling.
			 *
			 * @since 1.0.0
			 * @param bool  $should_schedule Whether to schedule this job.
			 * @param array $batch Batch of media IDs.
			 */
			$should_schedule = apply_filters( 'flux_ai_alt_creator_schedule_async_job', true, $batch );

			if ( ! $should_schedule ) {
				continue;
			}

			// Check if Action Scheduler is available.
			if ( function_exists( 'as_schedule_single_action' ) ) {
				$action_id = as_schedule_single_action(
					time(),
					'flux_ai_alt_creator_generate_alt_text_batch',
					[ 'media_ids' => $batch ],
					'flux-ai-media-alt-creator'
				);
				
				if ( $action_id ) {
					$jobs_scheduled++;
					$this->logger->info( 'Scheduled alt text generation batch', [
						'action_id' => $action_id,
						'media_count' => count( $batch ),
					] );
				}
			} else {
				// Fallback: process immediately if Action Scheduler not available.
				$this->process_alt_text_generation_batch( $batch );
				$jobs_scheduled++;
			}
		}
		
		return $jobs_scheduled > 0 ? $jobs_scheduled : false;
	}

	/**
	 * Schedule alt text application for multiple media files.
	 *
	 * @since 1.0.0
	 * @param array $media_ids Array of media IDs.
	 * @return int|false Number of jobs scheduled or false on failure.
	 */
	public function schedule_alt_text_application( $media_ids ) {
		// Check if Action Scheduler is available.
		if ( function_exists( 'as_schedule_single_action' ) ) {
			$action_id = as_schedule_single_action(
				time(),
				'flux_ai_alt_creator_apply_alt_text_batch',
				[ 'media_ids' => $media_ids ],
				'flux-ai-media-alt-creator'
			);

			if ( $action_id ) {
				$this->logger->info( 'Scheduled alt text application batch', [
					'action_id' => $action_id,
					'media_count' => count( $media_ids ),
				] );
				return $action_id;
			}
		} else {
			// Fallback: process immediately.
			$this->process_alt_text_application_batch( $media_ids );
			return true;
		}

		return false;
	}

	/**
	 * Process alt text generation batch (called by Action Scheduler).
	 *
	 * @since 1.0.0
	 * @param array $args Action arguments.
	 * @return void
	 */
	public function process_alt_text_generation_batch( $args ) {
		$media_ids = $args['media_ids'] ?? [];

		if ( empty( $media_ids ) ) {
			return;
		}

		foreach ( $media_ids as $media_id ) {
			// Get media URL.
			$media_url = wp_get_attachment_url( $media_id );

			if ( ! $media_url ) {
				$this->logger->warning( 'Could not get media URL', [ 'media_id' => $media_id ] );
				$this->media_scanner->update_scan_data( $media_id, [
					'ai_status' => 'error',
					'error_message' => __( 'Could not get media URL', 'flux-ai-media-alt-creator' ),
				] );
				continue;
			}

			// Update status to processing.
			$this->media_scanner->update_scan_data( $media_id, [
				'ai_status' => 'processing',
			] );

			// Generate alt text.
			$result = $this->openai_service->generate_alt_text( $media_url, $media_id );

			if ( $result['success'] ) {
				$this->media_scanner->update_scan_data( $media_id, [
					'ai_status' => 'completed',
					'recommended_alt_text' => $result['alt_text'],
				] );
			} else {
				$this->media_scanner->update_scan_data( $media_id, [
					'ai_status' => 'error',
					'error_message' => $result['error'] ?? __( 'Unknown error', 'flux-ai-media-alt-creator' ),
				] );
			}
		}
	}

	/**
	 * Process alt text application batch (called by Action Scheduler).
	 *
	 * @since 1.0.0
	 * @param array $args Action arguments.
	 * @return void
	 */
	public function process_alt_text_application_batch( $args ) {
		$media_ids = $args['media_ids'] ?? [];

		if ( empty( $media_ids ) ) {
			return;
		}

		foreach ( $media_ids as $media_id ) {
			$scan_data = $this->media_scanner->get_scan_data( $media_id );

			if ( empty( $scan_data['recommended_alt_text'] ) ) {
				continue;
			}

			/**
			 * Fires before applying alt text.
			 *
			 * @since 1.0.0
			 * @param int    $media_id Media ID.
			 * @param string $alt_text Alt text to apply.
			 */
			do_action( 'flux_ai_alt_creator_before_apply_alt_text', $media_id, $scan_data['recommended_alt_text'] );

			// Apply alt text.
			update_post_meta( $media_id, '_wp_attachment_image_alt', $scan_data['recommended_alt_text'] );

			// Mark as applied.
			$this->media_scanner->update_scan_data( $media_id, [
				'applied' => true,
			] );

			/**
			 * Fires after applying alt text.
			 *
			 * @since 1.0.0
			 * @param int    $media_id Media ID.
			 * @param string $alt_text Applied alt text.
			 */
			do_action( 'flux_ai_alt_creator_after_apply_alt_text', $media_id, $scan_data['recommended_alt_text'] );
		}
	}

	/**
	 * Register Action Scheduler hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_action_hooks() {
		add_action( 'flux_ai_alt_creator_generate_alt_text_batch', [ $this, 'process_alt_text_generation_batch' ], 10, 1 );
		add_action( 'flux_ai_alt_creator_apply_alt_text_batch', [ $this, 'process_alt_text_application_batch' ], 10, 1 );
	}
}

