<?php
/**
 * OpenAI service for generating alt text.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */
namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\App\Services\Vision\VisionProviderInterface;
use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;
use FluxAIMediaAltCreator\App\Services\UsageTracker;

/**
 * Service to interact with OpenAI API for alt text generation.
 *
 * @since 1.0.0
 * @since 2.0.0 Implements VisionProviderInterface.
 */
class OpenAIService implements VisionProviderInterface {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIService|null
	 */
	private static $instance = null;

	/**
	 * Settings instance.
	 *
	 * @since 1.0.0
	 * @var Settings
	 */
	private $settings;

	/**
	 * OpenAI API client instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIApiClient|null
	 */
	private $api_client = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->settings = new Settings();
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return OpenAIService Singleton instance.
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get OpenAI API client instance.
	 *
	 * @since 1.0.0
	 * @return OpenAIApiClient|null API client or null if API key not set.
	 */
	private function get_api_client() {
		if ( $this->api_client !== null ) {
			return $this->api_client;
		}

		$api_key = Settings::get_openai_api_key();
		
		if ( empty( $api_key ) ) {
			Logger::get_instance()->warning( 'OpenAI API key not set' );
			return null;
		}

		// Initialize API client.
		$this->api_client = new OpenAIApiClient( $api_key );

		return $this->api_client;
	}

	/**
	 * Generate alt text for a media file.
	 *
	 * @since 1.0.0
	 * @param string $media_url Media URL.
	 * @param int    $media_id Media ID.
	 * @return array Result with 'success', 'alt_text', 'tokens_used', 'cost'.
	 */
	public function generate_alt_text( $media_url, $media_id ) {
		/**
		 * Fires before generating alt text via OpenAI.
		 *
		 * @since 1.0.0
		 * @param string $media_url Media URL.
		 * @param int    $media_id Media ID.
		 */
		do_action( 'flux_ai_alt_creator/openai_service/generate_alt_text/before', $media_url, $media_id );

		$api_client = $this->get_api_client();
		
		if ( ! $api_client ) {
			return [
				'success' => false,
				'alt_text' => '',
				'error' => __( 'OpenAI API key not configured. Please add your API key in Settings.', 'flux-ai-media-alt-creator' ),
				'tokens_used' => 0,
				'cost' => 0.0,
			];
		}

		// Build prompt (centralized default; filter allows override per request).
		$prompt = AltTextApiService::get_default_alt_text_prompt();
		
		/**
		 * Filter the alt text generation prompt.
		 *
		 * @since 1.0.0
		 * @param string $prompt Prompt text.
		 * @param string $media_url Media URL.
		 * @param int    $media_id Media ID.
		 */
		$prompt = apply_filters( 'flux_ai_alt_creator/openai_service/get_alt_text_prompt', $prompt, $media_url, $media_id );

		// Append context (WooCommerce product or parent post) when available.
		$context = AltTextApiService::get_attachment_context_for_prompt( $media_id );
		if ( $context !== '' ) {
			$prompt .= "\n\n" . $context;
		}

		// Use OpenAI Vision API with GPT-4o-mini.
		$response = $api_client->generate_vision_content( $media_url, $prompt, 'gpt-4o-mini', 150 );

		if ( ! $response['success'] ) {
			Logger::get_instance()->error( 'Failed to generate alt text', [
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
		
		// Remove surrounding double quotes if present (safety measure in case quotes weren't stripped earlier).
		if ( strlen( $alt_text ) >= 2 && $alt_text[0] === '"' && $alt_text[ strlen( $alt_text ) - 1 ] === '"' ) {
			$alt_text = substr( $alt_text, 1, -1 );
		}
		
		// Extract usage data.
		$usage = $response['usage'] ?? null;
		$tokens_used = $usage ? ( $usage['total_tokens'] ?? 0 ) : 0;
		$input_tokens = $usage['prompt_tokens'] ?? 0;
		$output_tokens = $usage['completion_tokens'] ?? 0;
		
		// Calculate cost (GPT-4o-mini: $0.15 per 1M input tokens, $0.60 per 1M output tokens).
		$cost = ( $input_tokens / 1000000 * 0.15 ) + ( $output_tokens / 1000000 * 0.60 );
		
		// Track usage.
		UsageTracker::get_instance()->track_request( $tokens_used, 'gpt-4o-mini', $cost );
		
		$result = [
			'success' => true,
			'alt_text' => $alt_text,
			'tokens_used' => $tokens_used,
			'cost' => $cost,
		];
		
		Logger::get_instance()->info( 'Generated alt text', [
			'media_id' => $media_id,
			'tokens_used' => $tokens_used,
			'cost' => $cost,
		] );

		/**
		 * Fires after generating alt text via OpenAI.
		 *
		 * @since 1.0.0
		 * @param array  $result Generation result.
		 * @param string $media_url Media URL.
		 * @param int    $media_id Media ID.
		 */
		do_action( 'flux_ai_alt_creator/openai_service/generate_alt_text/after', $result, $media_url, $media_id );

		return $result;
	}
}

