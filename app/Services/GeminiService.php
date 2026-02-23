<?php
/**
 * Google Gemini service for generating alt text.
 *
 * @package FluxAIMediaAltCreator
 * @since 2.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\App\Services\Vision\VisionProviderInterface;
use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;

/**
 * Service to interact with Gemini API for alt text generation.
 *
 * Uses model gemini-1.5-flash. Cost: input $0.075/1M tokens, output $0.30/1M tokens.
 *
 * @since 2.0.0
 */
class GeminiService implements VisionProviderInterface {

	/**
	 * Model name for usage tracking (Gemini 2.5 Flash-Lite).
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const MODEL = 'gemini-2.5-flash-lite';

	/**
	 * Cost per 1M input tokens (USD). Gemini 2.5 Flash-Lite pricing.
	 *
	 * @since 2.0.0
	 * @var float
	 */
	private const COST_PER_MILLION_INPUT = 0.10;

	/**
	 * Cost per 1M output tokens (USD). Gemini 2.5 Flash-Lite pricing.
	 *
	 * @since 2.0.0
	 * @var float
	 */
	private const COST_PER_MILLION_OUTPUT = 0.40;

	/**
	 * Get Gemini API client instance.
	 *
	 * @since 2.0.0
	 * @return GeminiApiClient|null Client or null if no API key.
	 */
	private function get_api_client() {
		$settings = get_option( 'flux_ai_alt_creator_settings', [] );
		$api_key = $settings['gemini_api_key'] ?? '';
		if ( empty( $api_key ) ) {
			Logger::get_instance()->warning( 'Gemini API key not set' );
			return null;
		}
		return new GeminiApiClient( $api_key );
	}

	/**
	 * Generate alt text for a media file.
	 *
	 * @since 2.0.0
	 * @param string $media_url Media URL.
	 * @param int    $media_id  Media ID (attachment ID).
	 * @return array Result with 'success', 'alt_text', 'error', 'tokens_used', 'cost'.
	 */
	public function generate_alt_text( $media_url, $media_id ) {
		$api_client = $this->get_api_client();
		if ( ! $api_client ) {
			return [
				'success' => false,
				'alt_text' => '',
				'error' => __( 'Gemini API key not configured. Please add your API key in Settings.', 'flux-ai-media-alt-creator' ),
				'tokens_used' => 0,
				'cost' => 0.0,
			];
		}

		$prompt = AltTextApiService::get_default_alt_text_prompt();
		$prompt = apply_filters( 'flux_ai_alt_creator/openai_service/get_alt_text_prompt', $prompt, $media_url, $media_id );

		$response = $api_client->generate_vision_content( $media_url, $prompt, 500 );

		if ( ! $response['success'] ) {
			Logger::get_instance()->error( 'Gemini: failed to generate alt text', [
				'media_id' => $media_id,
				'error' => $response['error'] ?? 'Unknown error',
			] );
			return [
				'success' => false,
				'alt_text' => '',
				'error' => $response['error'] ?? __( 'Unknown error occurred.', 'flux-ai-media-alt-creator' ),
				'tokens_used' => 0,
				'cost' => 0.0,
			];
		}

		$alt_text = $response['content'] ?? '';
		$alt_text = trim( $alt_text );
		if ( strlen( $alt_text ) >= 2 && $alt_text[0] === '"' && $alt_text[ strlen( $alt_text ) - 1 ] === '"' ) {
			$alt_text = substr( $alt_text, 1, -1 );
		}

		$usage = $response['usage'] ?? null;
		$input_tokens = $usage['prompt_tokens'] ?? 0;
		$output_tokens = $usage['completion_tokens'] ?? 0;
		$tokens_used = $input_tokens + $output_tokens;
		$cost = ( $input_tokens / 1000000 * self::COST_PER_MILLION_INPUT ) + ( $output_tokens / 1000000 * self::COST_PER_MILLION_OUTPUT );

		UsageTracker::get_instance()->track_request( $tokens_used, self::MODEL, $cost );

		Logger::get_instance()->info( 'Gemini: generated alt text', [
			'media_id' => $media_id,
			'tokens_used' => $tokens_used,
			'cost' => $cost,
		] );

		return [
			'success' => true,
			'alt_text' => $alt_text,
			'tokens_used' => $tokens_used,
			'cost' => $cost,
		];
	}
}
