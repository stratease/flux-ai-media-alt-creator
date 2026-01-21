<?php
/**
 * Action Scheduler service for Flux AI Media Alt Creator.
 *
 * Manages Action Scheduler initialization and provides methods for scheduling
 * async alt text generation actions.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;

/**
 * Service for managing Action Scheduler integration.
 *
 * @since 1.0.0
 */
class ActionSchedulerService {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Constructor for Action Scheduler service.
	}

	/**
	 * Initialize Action Scheduler.
	 *
	 * Loads the Action Scheduler library and ensures Action Scheduler is ready to use.
	 * Action Scheduler functions are global, so we just need to ensure the library is loaded.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Check if Action Scheduler is already loaded (e.g., by WooCommerce or another plugin).
		if ( function_exists( 'as_schedule_single_action' ) ) {
			// Action Scheduler already loaded.
			return;
		}

		// Load Action Scheduler from vendor directory.
		$action_scheduler_file = FLUX_AI_MEDIA_ALT_CREATOR_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		
		if ( ! file_exists( $action_scheduler_file ) ) {
			Logger::get_instance()->error( 'Action Scheduler library not found. Please run "composer install" to install Action Scheduler.' );
			return;
		}

		require_once $action_scheduler_file;

		// Verify Action Scheduler functions are available.
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			Logger::get_instance()->error( 'Action Scheduler functions not available after loading. Check Action Scheduler installation.' );
			return;
		}
	}

}

