<?php
/**
 * Async job service for batch processing.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;
use FluxAIMediaAltCreator\App\Services\AltTextApiService;
use FluxAIMediaAltCreator\App\Services\MediaScanner;

/**
 * Service for scheduling async jobs using Action Scheduler pattern.
 *
 * @since 1.0.0
 */
class AsyncJobService {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var AsyncJobService|null
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Private constructor for singleton pattern.
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return AsyncJobService Singleton instance.
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Schedule alt text generation for multiple media files.
	 *
	 * @since 1.0.0
	 * @param array $media_ids Array of media IDs.
	 * @param int   $batch_size Batch size for processing.
	 * @return int|false Number of jobs scheduled or false on failure.
	 */
	public function schedule_alt_text_generation( array $media_ids, int $batch_size = 10 ) {
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

			$action_id = as_schedule_single_action(
				time(),
				'flux_ai_alt_creator/async_job_service/generate_alt_text_batch',
				[ $batch ],
				'flux-ai-media-alt-creator'
			);
			
			if ( $action_id ) {
				$jobs_scheduled++;
				Logger::get_instance()->info( 'Scheduled alt text generation batch', [
					'action_id' => $action_id,
					'media_count' => count( $batch ),
				] );
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
				[ $media_ids ],
				'flux-ai-media-alt-creator'
			);

			if ( $action_id ) {
				Logger::get_instance()->info( 'Scheduled alt text application batch', [
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
	 * @param array $media_ids Array of media IDs.
	 * @return void
	 */
	public function process_alt_text_generation_batch( $media_ids ) {
		if ( empty( $media_ids ) ) {
			return;
		}

		$successful_ids = [];

		foreach ( $media_ids as $media_id ) {
			// Get media URL.
			$media_url = wp_get_attachment_url( $media_id );

			if ( ! $media_url ) {
				Logger::get_instance()->warning( 'Could not get media URL', [ 'media_id' => $media_id ] );
				MediaScanner::get_instance()->update_scan_status( $media_id, 'error' );
				MediaScanner::get_instance()->update_scan_data( $media_id, [
					'error_message' => __( 'Could not get media URL', 'flux-ai-media-alt-creator' ),
				] );
				continue;
			}

			// Update status to processing.
			MediaScanner::get_instance()->update_scan_status( $media_id, 'processing' );

			// Generate alt text via abstracted API service.
			$result = AltTextApiService::get_instance()->generate_alt_text( $media_id, $media_url );

			if ( $result['success'] ) {
				MediaScanner::get_instance()->update_scan_status( $media_id, 'completed' );
				MediaScanner::get_instance()->update_scan_data( $media_id, [
					'recommended_alt_text' => $result['alt_text'],
				] );
				$successful_ids[] = $media_id;
			} else {
				MediaScanner::get_instance()->update_scan_status( $media_id, 'error' );
				MediaScanner::get_instance()->update_scan_data( $media_id, [
					'error_message' => $result['error'] ?? __( 'Unknown error', 'flux-ai-media-alt-creator' ),
				] );
			}
		}

		// Fire hook after generation batch completes.
		// This allows other plugins to handle automatic application of alt text.
		if ( ! empty( $successful_ids ) ) {
			/**
			 * Fires after alt text generation batch completes.
			 *
			 * Allows other plugins to handle automatic application of generated alt text.
			 *
			 * @since 1.0.0
			 * @param array $successful_ids Array of media IDs that successfully generated alt text.
			 */
			do_action( 'flux_ai_alt_creator/async_job_service/generation_batch_completed', $successful_ids );
		}
	}

	/**
	 * Process alt text application batch (called by Action Scheduler).
	 *
	 * @since 1.0.0
	 * @param array $media_ids Array of media IDs.
	 * @return void
	 */
	public function process_alt_text_application_batch( $media_ids ) {
		if ( empty( $media_ids ) ) {
			return;
		}

		foreach ( $media_ids as $media_id ) {
			$scan_data = MediaScanner::get_instance()->get_scan_data( $media_id );

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
			MediaScanner::get_instance()->update_scan_data( $media_id, [
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
	 * Cancel all Free plugin action scheduler actions.
	 *
	 * Cleans up all scheduled actions when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function cancel_all_actions() {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}

		$action_group = 'flux-ai-media-alt-creator';

		// Cancel all known action hooks in the free plugin's group.
		as_unschedule_all_actions( 'flux_ai_alt_creator/async_job_service/generate_alt_text_batch', [], $action_group );
		as_unschedule_all_actions( 'flux_ai_alt_creator/async_job_service/apply_alt_text_batch', [], $action_group );

		Logger::get_instance()->info( 'Cancelled all Free plugin action scheduler actions' );
	}
}

