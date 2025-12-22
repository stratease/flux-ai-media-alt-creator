<?php
/**
 * Alt text provider for registering alt text generation hooks.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Providers;

use FluxAIMediaAltCreator\App\Services\OpenAIService;
use FluxAIMediaAltCreator\App\Services\Logger;

/**
 * Provider for alt text generation functionality.
 *
 * @since 1.0.0
 */
class AltTextProvider {

	/**
	 * OpenAI service instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIService
	 */
	private $openai_service;

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
	 * @param OpenAIService $openai_service OpenAI service instance.
	 * @param Logger       $logger Logger instance.
	 */
	public function __construct( OpenAIService $openai_service, Logger $logger ) {
		$this->openai_service = $openai_service;
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

