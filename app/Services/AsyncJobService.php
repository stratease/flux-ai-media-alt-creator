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
	 * Image scanner instance.
	 *
	 * @since 1.0.0
	 * @var ImageScanner
	 */
	private $image_scanner;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Logger        $logger Logger instance.
	 * @param OpenAIService $openai_service OpenAI service instance.
	 * @param ImageScanner  $image_scanner Image scanner instance.
	 */
	public function __construct( Logger $logger, OpenAIService $openai_service, ImageScanner $image_scanner ) {
		$this->logger = $logger;
		$this->openai_service = $openai_service;
		$this->image_scanner = $image_scanner;
		
		// Register action hooks.
		$this->register_action_hooks();
	}

	/**
	 * Schedule alt text generation for multiple images.
	 *
	 * @since 1.0.0
	 * @param array $image_ids Array of image IDs.
	 * @param int   $batch_size Batch size for processing.
	 * @return int|false Number of jobs scheduled or false on failure.
	 */
	public function schedule_alt_text_generation( $image_ids, $batch_size = 10 ) {
		/**
		 * Filter the batch size for async job processing.
		 *
		 * @since 1.0.0
		 * @param int $batch_size Current batch size.
		 */
		$batch_size = apply_filters( 'flux_ai_alt_creator_async_job_batch_size', $batch_size );
		
		// Split into batches.
		$batches = array_chunk( $image_ids, $batch_size );
		$jobs_scheduled = 0;
		
		foreach ( $batches as $batch ) {
			/**
			 * Filter to allow modification of job scheduling.
			 *
			 * @since 1.0.0
			 * @param bool  $should_schedule Whether to schedule this job.
			 * @param array $batch Batch of image IDs.
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
					[ 'image_ids' => $batch ],
					'flux-ai-media-alt-creator'
				);
				
				if ( $action_id ) {
					$jobs_scheduled++;
					$this->logger->info( 'Scheduled alt text generation batch', [
						'action_id' => $action_id,
						'image_count' => count( $batch ),
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
	 * Schedule alt text application for multiple images.
	 *
	 * @since 1.0.0
	 * @param array $image_ids Array of image IDs.
	 * @return int|false Number of jobs scheduled or false on failure.
	 */
	public function schedule_alt_text_application( $image_ids ) {
		// Check if Action Scheduler is available.
		if ( function_exists( 'as_schedule_single_action' ) ) {
			$action_id = as_schedule_single_action(
				time(),
				'flux_ai_alt_creator_apply_alt_text_batch',
				[ 'image_ids' => $image_ids ],
				'flux-ai-media-alt-creator'
			);
			
			if ( $action_id ) {
				$this->logger->info( 'Scheduled alt text application batch', [
					'action_id' => $action_id,
					'image_count' => count( $image_ids ),
				] );
				return $action_id;
			}
		} else {
			// Fallback: process immediately.
			$this->process_alt_text_application_batch( $image_ids );
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
		$image_ids = $args['image_ids'] ?? [];
		
		if ( empty( $image_ids ) ) {
			return;
		}
		
		foreach ( $image_ids as $image_id ) {
			// Get image URL.
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
			
			if ( ! $image_url ) {
				$this->logger->warning( 'Could not get image URL', [ 'image_id' => $image_id ] );
				$this->image_scanner->update_scan_data( $image_id, [
					'ai_status' => 'error',
					'error_message' => __( 'Could not get image URL', 'flux-ai-media-alt-creator' ),
				] );
				continue;
			}
			
			// Update status to processing.
			$this->image_scanner->update_scan_data( $image_id, [
				'ai_status' => 'processing',
			] );
			
			// Generate alt text.
			$result = $this->openai_service->generate_alt_text( $image_url, $image_id );
			
			if ( $result['success'] ) {
				$this->image_scanner->update_scan_data( $image_id, [
					'ai_status' => 'completed',
					'recommended_alt_text' => $result['alt_text'],
				] );
			} else {
				$this->image_scanner->update_scan_data( $image_id, [
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
		$image_ids = $args['image_ids'] ?? [];
		
		if ( empty( $image_ids ) ) {
			return;
		}
		
		foreach ( $image_ids as $image_id ) {
			$scan_data = $this->image_scanner->get_scan_data( $image_id );
			
			if ( empty( $scan_data['recommended_alt_text'] ) ) {
				continue;
			}
			
			/**
			 * Fires before applying alt text.
			 *
			 * @since 1.0.0
			 * @param int    $image_id Image ID.
			 * @param string $alt_text Alt text to apply.
			 */
			do_action( 'flux_ai_alt_creator_before_apply_alt_text', $image_id, $scan_data['recommended_alt_text'] );
			
			// Apply alt text.
			update_post_meta( $image_id, '_wp_attachment_image_alt', $scan_data['recommended_alt_text'] );
			
			// Mark as applied.
			$this->image_scanner->update_scan_data( $image_id, [
				'applied' => true,
			] );
			
			/**
			 * Fires after applying alt text.
			 *
			 * @since 1.0.0
			 * @param int    $image_id Image ID.
			 * @param string $alt_text Applied alt text.
			 */
			do_action( 'flux_ai_alt_creator_after_apply_alt_text', $image_id, $scan_data['recommended_alt_text'] );
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

