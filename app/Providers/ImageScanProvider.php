<?php
/**
 * Image scan provider for registering image scanning hooks.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Providers;

use FluxAIMediaAltCreator\App\Services\ImageScanner;
use FluxAIMediaAltCreator\App\Services\Logger;

/**
 * Provider for image scanning functionality.
 *
 * @since 1.0.0
 */
class ImageScanProvider {

	/**
	 * Image scanner instance.
	 *
	 * @since 1.0.0
	 * @var ImageScanner
	 */
	private $image_scanner;

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
	 * @param ImageScanner $image_scanner Image scanner instance.
	 * @param Logger       $logger Logger instance.
	 */
	public function __construct( ImageScanner $image_scanner, Logger $logger ) {
		$this->image_scanner = $image_scanner;
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

