<?php
/**
 * OpenAI service for generating alt text.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\App\Services\Settings;

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
	 * OpenAI client instance.
	 *
	 * @since 1.0.0
	 * @var object|null
	 */
	private $client = null;

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
	}

	/**
	 * Get OpenAI client instance.
	 *
	 * @since 1.0.0
	 * @return object|null OpenAI client or null if API key not set.
	 */
	private function get_client() {
		if ( $this->client !== null ) {
			return $this->client;
		}

		$api_key = Settings::get_openai_api_key();
		
		if ( empty( $api_key ) ) {
			$this->logger->warning( 'OpenAI API key not set' );
			return null;
		}

		// Check if Pro plugin is handling this.
		/**
		 * Filter to allow Pro plugin to override alt text generation.
		 *
		 * @since 1.0.0
		 * @param null|string $alt_text Alt text (null to use default generation).
		 * @param string      $image_url Image URL.
		 * @param int         $image_id Image ID.
		 */
		$pro_alt_text = apply_filters( 'flux_ai_alt_creator_generate_alt_text', null, '', 0 );
		
		if ( $pro_alt_text !== null ) {
			// Pro plugin is handling generation, don't initialize client.
			return null;
		}

		// Initialize OpenAI PHP SDK client.
		// Using Strauss-prefixed namespace.
		if ( class_exists( '\FluxAIMediaAltCreator\OpenAI\Client' ) ) {
			$this->client = \FluxAIMediaAltCreator\OpenAI\Client::client( $api_key );
		} else {
			// Fallback: try standard namespace if Strauss hasn't run yet.
			if ( class_exists( '\OpenAI\Client' ) ) {
				$this->client = \OpenAI\Client::client( $api_key );
			} else {
				$this->logger->error( 'OpenAI PHP SDK not found. Please run "composer install" and "composer run prefix-namespaces".' );
				return null;
			}
		}

		return $this->client;
	}

	/**
	 * Generate alt text for an image.
	 *
	 * @since 1.0.0
	 * @param string $image_url Image URL.
	 * @param int    $image_id Image ID.
	 * @return array Result with 'success', 'alt_text', 'tokens_used', 'cost'.
	 */
	public function generate_alt_text( $image_url, $image_id ) {
		/**
		 * Fires before generating alt text.
		 *
		 * @since 1.0.0
		 * @param string $image_url Image URL.
		 * @param int    $image_id Image ID.
		 */
		do_action( 'flux_ai_alt_creator_before_generate_alt_text', $image_url, $image_id );

		// Check if Pro plugin is handling this.
		$pro_alt_text = apply_filters( 'flux_ai_alt_creator_generate_alt_text', null, $image_url, $image_id );
		
		if ( $pro_alt_text !== null ) {
			// Pro plugin handled generation.
			$result = [
				'success' => true,
				'alt_text' => $pro_alt_text,
				'tokens_used' => 0,
				'cost' => 0.0,
			];
			
			/**
			 * Fires after generating alt text.
			 *
			 * @since 1.0.0
			 * @param array  $result Generation result.
			 * @param string $image_url Image URL.
			 * @param int    $image_id Image ID.
			 */
			do_action( 'flux_ai_alt_creator_after_generate_alt_text', $result, $image_url, $image_id );
			
			return $result;
		}

		$client = $this->get_client();
		
		if ( ! $client ) {
			return [
				'success' => false,
				'alt_text' => '',
				'error' => 'OpenAI API key not configured',
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
		 * @param string $image_url Image URL.
		 * @param int    $image_id Image ID.
		 */
		$prompt = apply_filters( 'flux_ai_alt_creator_alt_text_prompt', $prompt, $image_url, $image_id );

		try {
			// Use OpenAI Vision API with GPT-4o-mini.
			$response = $client->chat()->create( [
				'model' => 'gpt-4o-mini',
				'messages' => [
					[
						'role' => 'user',
						'content' => [
							[
								'type' => 'text',
								'text' => $prompt,
							],
							[
								'type' => 'image_url',
								'image_url' => [
									'url' => $image_url,
								],
							],
						],
					],
				],
				'max_tokens' => 150,
			] );

			$alt_text = $response->choices[0]->message->content ?? '';
			$alt_text = trim( $alt_text );
			
			// Extract usage data.
			$usage = $response->usage ?? null;
			$tokens_used = $usage ? ( $usage->promptTokens ?? 0 ) + ( $usage->completionTokens ?? 0 ) : 0;
			$input_tokens = $usage->promptTokens ?? 0;
			$output_tokens = $usage->completionTokens ?? 0;
			
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
				'image_id' => $image_id,
				'tokens_used' => $tokens_used,
				'cost' => $cost,
			] );
			
		} catch ( \Exception $e ) {
			$this->logger->error( 'Failed to generate alt text', [
				'image_id' => $image_id,
				'error' => $e->getMessage(),
			] );
			
			$result = [
				'success' => false,
				'alt_text' => '',
				'error' => $e->getMessage(),
				'tokens_used' => 0,
				'cost' => 0.0,
			];
		}

		/**
		 * Fires after generating alt text.
		 *
		 * @since 1.0.0
		 * @param array  $result Generation result.
		 * @param string $image_url Image URL.
		 * @param int    $image_id Image ID.
		 */
		do_action( 'flux_ai_alt_creator_after_generate_alt_text', $result, $image_url, $image_id );

		return $result;
	}

	/**
	 * Get default alt text prompt.
	 *
	 * @since 1.0.0
	 * @return string Prompt text.
	 */
	private function get_alt_text_prompt() {
		return __( 'Generate a concise, descriptive alt text for this image that accurately describes its content and context. The alt text should be helpful for screen readers and should not exceed 125 characters.', 'flux-ai-media-alt-creator' );
	}
}

