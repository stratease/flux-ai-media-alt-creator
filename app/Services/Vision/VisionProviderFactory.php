<?php
/**
 * Factory for vision API providers (OpenAI, Gemini, Claude).
 *
 * @package FluxAIMediaAltCreator
 * @since 2.0.0
 */

namespace FluxAIMediaAltCreator\App\Services\Vision;

use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\App\Services\OpenAIService;
use FluxAIMediaAltCreator\App\Services\GeminiService;
use FluxAIMediaAltCreator\App\Services\ClaudeService;

/**
 * Returns the appropriate vision provider based on settings.
 *
 * @since 2.0.0
 */
class VisionProviderFactory {

	/**
	 * Get the vision provider instance for the current settings.
	 *
	 * Uses the configured provider if it has an API key; otherwise falls back
	 * to OpenAI if it has a key. If no provider has a key, returns a no-config
	 * provider that returns a clear error.
	 *
	 * @since 2.0.0
	 * @return VisionProviderInterface Provider instance.
	 */
	public static function get_provider() {
		$provider = Settings::get_vision_provider();
		$api_key = Settings::get_vision_api_key();

		if ( ! empty( $api_key ) ) {
			switch ( $provider ) {
				case 'gemini':
					return new GeminiService();
				case 'claude':
					return new ClaudeService();
				case 'openai':
				default:
					return OpenAIService::get_instance();
			}
		}

		// Fallback: try OpenAI if it has a key (backwards compatibility).
		$openai_key = Settings::get_openai_api_key();
		if ( ! empty( $openai_key ) ) {
			return OpenAIService::get_instance();
		}

		return new NoConfigVisionProvider();
	}
}
