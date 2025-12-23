<?php
/**
 * Media scan provider for registering media scanning hooks.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Providers;

use FluxAIMediaAltCreator\App\Services\MediaScanner;
use FluxAIMediaAltCreator\App\Services\Logger;

/**
 * Provider for media scanning functionality.
 *
 * @since 1.0.0
 */
class MediaScanProvider {

	/**
	 * Media scanner instance.
	 *
	 * @since 1.0.0
	 * @var MediaScanner
	 */
	private $media_scanner;

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
	 * @param MediaScanner $media_scanner Media scanner instance.
	 * @param Logger       $logger Logger instance.
	 */
	public function __construct( MediaScanner $media_scanner, Logger $logger ) {
		$this->media_scanner = $media_scanner;
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

