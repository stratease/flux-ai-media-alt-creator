<?php
/**
 * Usage tracking provider for registering usage tracking hooks.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Providers;

use FluxAIMediaAltCreator\App\Services\UsageTracker;
use FluxAIMediaAltCreator\App\Services\Logger;

/**
 * Provider for usage tracking functionality.
 *
 * @since 1.0.0
 */
class UsageTrackingProvider {

	/**
	 * Usage tracker instance.
	 *
	 * @since 1.0.0
	 * @var UsageTracker
	 */
	private $usage_tracker;

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
	 * @param UsageTracker $usage_tracker Usage tracker instance.
	 * @param Logger      $logger Logger instance.
	 */
	public function __construct( UsageTracker $usage_tracker, Logger $logger ) {
		$this->usage_tracker = $usage_tracker;
		$this->logger = $logger;
	}

	/**
	 * Initialize the provider.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Hooks are registered in the service classes.
		// This provider exists for extensibility and future hooks.
	}
}

