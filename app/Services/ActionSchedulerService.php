<?php
/**
 * Action Scheduler service for Flux AI Media Alt Creator.
 *
 * Manages Action Scheduler initialization and provides methods for scheduling
 * async alt text generation actions. Uses Strauss-prefixed Action Scheduler to avoid
 * namespace collisions.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Service for managing Action Scheduler integration.
 *
 * @since 1.0.0
 */
class ActionSchedulerService {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

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
	 * @param Logger          $logger Logger instance.
	 * @param AsyncJobService $async_job_service Async job service instance.
	 */
	public function __construct( Logger $logger, AsyncJobService $async_job_service ) {
		$this->logger = $logger;
		$this->async_job_service = $async_job_service;
	}

	/**
	 * Initialize Action Scheduler.
	 *
	 * Loads the Strauss-prefixed Action Scheduler library and ensures
	 * Action Scheduler is ready to use. Action Scheduler functions are global,
	 * so we just need to ensure the library is loaded.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Check if Action Scheduler is already loaded (e.g., by WooCommerce or another plugin).
		if ( function_exists( 'as_schedule_single_action' ) ) {
			// Action Scheduler already loaded, just register our hooks.
			$this->register_action_hooks();
			return;
		}

		// Load Action Scheduler from vendor-prefixed directory.
		// Action Scheduler will be prefixed by Strauss but functions remain global.
		$action_scheduler_file = FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR . 'vendor-prefixed/woocommerce/action-scheduler/action-scheduler.php';
		
		if ( file_exists( $action_scheduler_file ) ) {
			require_once $action_scheduler_file;
		} else {
			// Fallback: try vendor directory (if not yet prefixed).
			$action_scheduler_file_fallback = FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
			if ( file_exists( $action_scheduler_file_fallback ) ) {
				require_once $action_scheduler_file_fallback;
			} else {
				$this->logger->error( 'Action Scheduler library not found. Please run "composer install" and then "composer run prefix-namespaces" to install and prefix Action Scheduler.' );
				return;
			}
		}

		// Verify Action Scheduler functions are available.
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			$this->logger->error( 'Action Scheduler functions not available after loading. Check Action Scheduler installation.' );
			return;
		}

		// Register action hooks.
		$this->register_action_hooks();
	}

	/**
	 * Register Action Scheduler action hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_action_hooks() {
		// Hooks are registered in AsyncJobService.
		// This method exists for future extensibility.
	}
}

