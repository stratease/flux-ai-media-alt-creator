<?php
/**
 * Anthropic Claude API client using WordPress HTTP API.
 *
 * @package FluxAIMediaAltCreator
 * @since 2.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;

/**
 * Decoupled Claude API client for Messages API (vision) requests.
 *
 * @since 2.0.0
 */
class ClaudeApiClient {

	/**
	 * Model name (Claude Haiku 4.5; claude-3-haiku-20240307 is deprecated).
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const MODEL = 'claude-haiku-4-5-20251001';

	/**
	 * API base URL.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.anthropic.com/v1';

	/**
	 * API key.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private $api_key;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @param string $api_key Anthropic API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Generate content using Claude Messages API with vision.
	 *
	 * @since 2.0.0
	 * @param string $media_url   URL of the media file.
	 * @param string $prompt     Prompt text for the AI.
	 * @param int    $max_tokens  Maximum tokens in response (default: 150).
	 * @return array Response with 'success', 'content', 'usage', 'error'.
	 */
	public function generate_vision_content( $media_url, $prompt, $max_tokens = 150 ) {
		$endpoint = self::API_BASE_URL . '/messages';

		$body = [
			'model' => self::MODEL,
			'max_tokens' => $max_tokens,
			'messages' => [
				[
					'role' => 'user',
					'content' => [
						[
							'type' => 'image',
							'source' => [
								'type' => 'url',
								'url' => $media_url,
							],
						],
						[
							'type' => 'text',
							'text' => $prompt,
						],
					],
				],
			],
		];

		$response = $this->make_request( $endpoint, $body );

		if ( ! $response['success'] ) {
			return [
				'success' => false,
				'content' => '',
				'usage' => null,
				'error' => $response['error'] ?? __( 'Unknown error.', 'flux-ai-media-alt-creator' ),
			];
		}

		$parsed = $this->parse_response( $response['data'] );
		Logger::get_instance()->debug(
			'Claude vision API response',
			[
				'raw_response' => $response['data'],
				'parsed_content' => $parsed['content'] ?? '',
				'parsed_content_length' => strlen( $parsed['content'] ?? '' ),
			]
		);
		return $parsed;
	}

	/**
	 * Make HTTP POST request to Anthropic API.
	 *
	 * @since 2.0.0
	 * @param string $endpoint Full endpoint URL.
	 * @param array  $body     Request body.
	 * @return array Response with 'success', 'data', 'error'.
	 */
	private function make_request( $endpoint, $body ) {
		if ( empty( $this->api_key ) ) {
			return [
				'success' => false,
				'data' => null,
				'error' => __( 'Claude API key is not configured.', 'flux-ai-media-alt-creator' ),
			];
		}

		$args = [
			'method' => 'POST',
			'headers' => [
				'Content-Type' => 'application/json',
				'x-api-key' => $this->api_key,
				'anthropic-version' => '2023-06-01',
			],
			'body' => wp_json_encode( $body ),
			'timeout' => 30,
			'sslverify' => true,
		];

		$response = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			Logger::get_instance()->error( 'Claude API request failed', [
				'error' => $response->get_error_message(),
			] );
			return [
				'success' => false,
				'data' => null,
				'error' => $response->get_error_message(),
			];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( $response_code < 200 || $response_code >= 300 ) {
			$error_message = $this->extract_error_message( $response_data, $response_code );
			Logger::get_instance()->error( 'Claude API returned error', [
				'code' => $response_code,
				'error' => $error_message,
			] );
			return [
				'success' => false,
				'data' => $response_data,
				'error' => $error_message,
			];
		}

		return [
			'success' => true,
			'data' => $response_data,
			'error' => null,
		];
	}

	/**
	 * Extract error message from Anthropic response.
	 *
	 * @since 2.0.0
	 * @param array|null $response_data Response data.
	 * @param int        $response_code HTTP status code.
	 * @return string Error message.
	 */
	private function extract_error_message( $response_data, $response_code ) {
		if ( is_array( $response_data ) && isset( $response_data['error']['message'] ) ) {
			return $response_data['error']['message'];
		}
		return sprintf(
			/* translators: %d: HTTP response code */
			__( 'Claude API returned error code %d.', 'flux-ai-media-alt-creator' ),
			$response_code
		);
	}

	/**
	 * Parse Claude Messages API response.
	 *
	 * @since 2.0.0
	 * @param array $response_data Response from API.
	 * @return array Keys: success, content, usage, error.
	 */
	private function parse_response( $response_data ) {
		if ( ! is_array( $response_data ) ) {
			return [
				'success' => false,
				'content' => '',
				'usage' => null,
				'error' => __( 'Invalid response from Claude API.', 'flux-ai-media-alt-creator' ),
			];
		}

		$content = '';
		if ( ! empty( $response_data['content'] ) && is_array( $response_data['content'] ) ) {
			$text_parts = [];
			foreach ( $response_data['content'] as $block ) {
				if ( isset( $block['text'] ) && is_string( $block['text'] ) ) {
					$text_parts[] = $block['text'];
				}
			}
			$content = implode( '', $text_parts );
			$content = trim( $content );
			if ( strlen( $content ) >= 2 && $content[0] === '"' && $content[ strlen( $content ) - 1 ] === '"' ) {
				$content = substr( $content, 1, -1 );
			}
		}

		$usage = null;
		if ( isset( $response_data['usage'] ) ) {
			$u = $response_data['usage'];
			$input_tokens = (int) ( $u['input_tokens'] ?? 0 );
			$output_tokens = (int) ( $u['output_tokens'] ?? 0 );
			$usage = [
				'prompt_tokens' => $input_tokens,
				'completion_tokens' => $output_tokens,
				'total_tokens' => $input_tokens + $output_tokens,
			];
		}

		return [
			'success' => true,
			'content' => $content,
			'usage' => $usage,
			'error' => null,
		];
	}
}
