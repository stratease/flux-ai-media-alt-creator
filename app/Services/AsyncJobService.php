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
	 * @param Logger           $logger Logger instance.
	 * @param AltTextApiService $alt_text_api_service Alt text API service instance.
	 * @param MediaScanner     $media_scanner Media scanner instance.
	 */
	public function __construct( Logger $logger, AltTextApiService $alt_text_api_service, MediaScanner $media_scanner ) {
		$this->logger = $logger;
		$this->alt_text_api_service = $alt_text_api_service;
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
		$batch_size = apply_filters( 'flux_ai_alt_creator/async_job_service/schedule_alt_text_generation/batch_size', $batch_size );
		
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
			$should_schedule = apply_filters( 'flux_ai_alt_creator/async_job_service/schedule_alt_text_generation/should_schedule', true, $batch );

			if ( ! $should_schedule ) {
				continue;
			}

			// Check if Action Scheduler is available.
			if ( function_exists( 'as_schedule_single_action' ) ) {
				$action_id = as_schedule_single_action(
					time(),
					'flux_ai_alt_creator/async_job_service/generate_alt_text_batch',
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
				'flux_ai_alt_creator/async_job_service/apply_alt_text_batch',
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

			// Generate alt text via abstracted API service.
			$result = $this->alt_text_api_service->generate_alt_text( $media_id, $media_url );

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
			do_action( 'flux_ai_alt_creator/async_job_service/process_alt_text_application_batch/before_apply', $media_id, $scan_data['recommended_alt_text'] );

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
			do_action( 'flux_ai_alt_creator/async_job_service/process_alt_text_application_batch/after_apply', $media_id, $scan_data['recommended_alt_text'] );
		}
	}

	/**
	 * Queue a batch of pending media files for alt text generation.
	 *
	 * Finds media files that need processing (ai_status='pending' or no scan_data)
	 * and schedules batches for generation. This method is designed to be called
	 * by recurring jobs (e.g., from Pro plugin automation).
	 *
	 * @since 1.0.0
	 * @param int $batch_size Number of media files to queue per batch. Default 10.
	 * @param int $limit Maximum number of pending media files to process in this call. Default 50.
	 * @return array Result with 'queued_count', 'batches_scheduled', 'media_ids'.
	 */
	public function queue_pending_media_batch( $batch_size = 10, $limit = 50 ) {
		// Query for attachments (limit to reasonable number for automation).
		$query_args = [
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'posts_per_page' => $limit * 2, // Get more than limit to account for filtering.
			'orderby' => 'date',
			'order' => 'DESC',
			'fields' => 'ids',
		];

		/**
		 * Filter query arguments for finding pending media.
		 *
		 * @since 1.0.0
		 * @param array $query_args WP_Query arguments.
		 * @return array Filtered query arguments.
		 */
		$query_args = apply_filters( 'flux_ai_alt_creator/async_job_service/queue_pending_media_batch/query_args', $query_args );

		$query = new \WP_Query( $query_args );
		$pending_ids = [];

		// Filter to find pending media.
		foreach ( $query->posts as $attachment_id ) {
			$scan_data = $this->media_scanner->get_scan_data( $attachment_id );
			$ai_status = $scan_data['ai_status'] ?? 'pending';

			// Include if pending, error (for retry), or never processed.
			if ( in_array( $ai_status, [ 'pending', 'error' ], true ) || empty( $scan_data['scan_date'] ) ) {
				$pending_ids[] = $attachment_id;

				// Stop when we have enough.
				if ( count( $pending_ids ) >= $limit ) {
					break;
				}
			}
		}

		if ( empty( $pending_ids ) ) {
			return [
				'queued_count' => 0,
				'batches_scheduled' => 0,
				'media_ids' => [],
			];
		}

		// Schedule batches using existing method.
		$batches_scheduled = $this->schedule_alt_text_generation( $pending_ids, $batch_size );

		return [
			'queued_count' => count( $pending_ids ),
			'batches_scheduled' => $batches_scheduled ?: 0,
			'media_ids' => $pending_ids,
		];
	}

	/**
	 * Handle action hook to queue pending media.
	 *
	 * Called by recurring jobs (e.g., Pro plugin automation) to automatically
	 * queue batches of pending media for alt text generation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_queue_pending_media() {
		/**
		 * Filter the batch size for automated pending media queueing.
		 *
		 * @since 1.0.0
		 * @param int $batch_size Batch size. Default 10.
		 */
		$batch_size = apply_filters( 'flux_ai_alt_creator/async_job_service/queue_pending_media/batch_size', 10 );

		/**
		 * Filter the limit of pending media to process per call.
		 *
		 * @since 1.0.0
		 * @param int $limit Maximum number of pending media to process. Default 50.
		 */
		$limit = apply_filters( 'flux_ai_alt_creator/async_job_service/queue_pending_media/limit', 50 );

		$result = $this->queue_pending_media_batch( $batch_size, $limit );

		$this->logger->info( 'Automated queueing of pending media completed', [
			'queued_count' => $result['queued_count'],
			'batches_scheduled' => $result['batches_scheduled'],
		] );
	}

	/**
	 * Register Action Scheduler hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_action_hooks() {
		add_action( 'flux_ai_alt_creator/async_job_service/generate_alt_text_batch', [ $this, 'process_alt_text_generation_batch' ], 10, 1 );
		add_action( 'flux_ai_alt_creator/async_job_service/apply_alt_text_batch', [ $this, 'process_alt_text_application_batch' ], 10, 1 );
		
		// Action hook for automated queueing of pending media (used by Pro plugin).
		add_action( 'flux_ai_alt_creator/async_job_service/queue_pending_media', [ $this, 'handle_queue_pending_media' ], 10, 0 );
	}
}

