<?php
/**
 * OpenAI service for generating alt text.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Service to interact with OpenAI API for alt text generation.
 *
 * @since 1.0.0
 */
class OpenAIService {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Usage tracker instance.
	 *
	 * @since 1.0.0
	 * @var UsageTracker
	 */
	private $usage_tracker;

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
	 * @param Logger        $logger Logger instance.
	 * @param UsageTracker  $usage_tracker Usage tracker instance.
	 */
	public function __construct( Logger $logger, UsageTracker $usage_tracker ) {
		$this->logger = $logger;
		$this->usage_tracker = $usage_tracker;
		$this->settings = new Settings();
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
			$this->logger->warning( 'OpenAI API key not set' );
			return null;
		}

		/**
		 * Filter to allow overriding alt text generation.
		 *
		 * @since 1.0.0
		 * @param null|string $alt_text Alt text (null to use default generation).
		 * @param string      $media_url Media URL.
		 * @param int         $media_id Media ID.
		 */
		$alt_text_override = apply_filters( 'flux_ai_alt_creator_generate_alt_text', null, '', 0 );
		
		if ( $alt_text_override !== null ) {
			// Override is handling generation, don't initialize client.
			return null;
		}

		// Initialize API client.
		$this->api_client = new OpenAIApiClient( $api_key, $this->logger );

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
		 * Fires before generating alt text.
		 *
		 * @since 1.0.0
		 * @param string $media_url Media URL.
		 * @param int    $media_id Media ID.
		 */
		do_action( 'flux_ai_alt_creator_before_generate_alt_text', $media_url, $media_id );

		/**
		 * Filter to allow overriding alt text generation.
		 *
		 * @since 1.0.0
		 * @param null|string $alt_text Alt text (null to use default generation).
		 * @param string      $media_url Media URL.
		 * @param int         $media_id Media ID.
		 */
		$alt_text_override = apply_filters( 'flux_ai_alt_creator_generate_alt_text', null, $media_url, $media_id );
		
		if ( $alt_text_override !== null ) {
			// Override handled generation.
			$result = [
				'success' => true,
				'alt_text' => $alt_text_override,
				'tokens_used' => 0,
				'cost' => 0.0,
			];
			
			/**
			 * Fires after generating alt text.
			 *
			 * @since 1.0.0
			 * @param array  $result Generation result.
			 * @param string $media_url Media URL.
			 * @param int    $media_id Media ID.
			 */
			do_action( 'flux_ai_alt_creator_after_generate_alt_text', $result, $media_url, $media_id );
			
			return $result;
		}

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

		// Build prompt.
		$prompt = $this->get_alt_text_prompt();
		
		/**
		 * Filter the alt text generation prompt.
		 *
		 * @since 1.0.0
		 * @param string $prompt Prompt text.
		 * @param string $media_url Media URL.
		 * @param int    $media_id Media ID.
		 */
		$prompt = apply_filters( 'flux_ai_alt_creator_alt_text_prompt', $prompt, $media_url, $media_id );

		// Use OpenAI Vision API with GPT-4o-mini.
		$response = $api_client->generate_vision_content( $media_url, $prompt, 'gpt-4o-mini', 150 );

		if ( ! $response['success'] ) {
			$this->logger->error( 'Failed to generate alt text', [
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
		
		// Extract usage data.
		$usage = $response['usage'] ?? null;
		$tokens_used = $usage ? ( $usage['total_tokens'] ?? 0 ) : 0;
		$input_tokens = $usage['prompt_tokens'] ?? 0;
		$output_tokens = $usage['completion_tokens'] ?? 0;
		
		// Calculate cost (GPT-4o-mini: $0.15 per 1M input tokens, $0.60 per 1M output tokens).
		$cost = ( $input_tokens / 1000000 * 0.15 ) + ( $output_tokens / 1000000 * 0.60 );
		
		// Track usage.
		$this->usage_tracker->track_request( $tokens_used, 'gpt-4o-mini', $cost );
		
		$result = [
			'success' => true,
			'alt_text' => $alt_text,
			'tokens_used' => $tokens_used,
			'cost' => $cost,
		];
		
		$this->logger->info( 'Generated alt text', [
			'media_id' => $media_id,
			'tokens_used' => $tokens_used,
			'cost' => $cost,
		] );

		/**
		 * Fires after generating alt text.
		 *
		 * @since 1.0.0
		 * @param array  $result Generation result.
		 * @param string $media_url Media URL.
		 * @param int    $media_id Media ID.
		 */
		do_action( 'flux_ai_alt_creator_after_generate_alt_text', $result, $media_url, $media_id );

		return $result;
	}

	/**
	 * Get default alt text prompt.
	 *
	 * @since 1.0.0
	 * @return string Prompt text.
	 */
	private function get_alt_text_prompt() {
		return __( 'Generate a concise, SEO friendly alt text for this media file that accurately describes its content and context. The alt text should be helpful for screen readers and should not exceed 125 characters.', 'flux-ai-media-alt-creator' );
	}
}

