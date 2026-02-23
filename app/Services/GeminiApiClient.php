<?php
/**
 * Google Gemini API client using WordPress HTTP API.
 *
 * @package FluxAIMediaAltCreator
 * @since 2.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;

/**
 * Decoupled Gemini API client for vision (generateContent) requests.
 *
 * @since 2.0.0
 */
class GeminiApiClient {

	/**
	 * Model name (Gemini 2.5 Flash-Lite; fastest and most budget-friendly in the 2.5 family).
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const MODEL = 'gemini-2.5-flash-lite';

	/**
	 * API base URL (v1beta for Gemini 2.5 family generateContent).
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

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
	 * @param string $api_key Gemini API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Generate content using Gemini vision API.
	 *
	 * @since 2.0.0
	 * @param string $media_url URL of the media file.
	 * @param string $prompt    Prompt text for the AI.
	 * @param int    $max_tokens Maximum tokens in response (default: 500; allows room if model uses thinking tokens).
	 * @return array Response with 'success', 'content', 'usage', 'error'.
	 */
	public function generate_vision_content( $media_url, $prompt, $max_tokens = 500 ) {
		$image_data = $this->fetch_image_as_base64( $media_url );
		if ( ! $image_data ) {
			return [
				'success' => false,
				'content' => '',
				'usage' => null,
				'error' => __( 'Could not load image for Gemini.', 'flux-ai-media-alt-creator' ),
			];
		}

		$endpoint = self::API_BASE_URL . '/models/' . self::MODEL . ':generateContent?key=' . urlencode( $this->api_key );

		$body = [
			'contents' => [
				[
					'parts' => [
						[
							'inline_data' => [
								'mime_type' => $image_data['mime_type'],
								'data' => $image_data['data'],
							],
						],
						[
							'text' => $prompt,
						],
					],
				],
			],
			'generationConfig' => [
				'maxOutputTokens' => $max_tokens,
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
			'Gemini vision API response',
			[
				'raw_response' => $response['data'],
				'parsed_content' => $parsed['content'] ?? '' ,
			]
		);

		return $parsed;
	}

	/**
	 * Fetch image from URL and return base64 and mime type.
	 *
	 * @since 2.0.0
	 * @param string $media_url Image URL.
	 * @return array|null Array with 'data' (base64) and 'mime_type', or null on failure.
	 */
	private function fetch_image_as_base64( $media_url ) {
		$response = wp_remote_get( $media_url, [
			'timeout' => 30,
			'sslverify' => true,
		] );

		if ( is_wp_error( $response ) ) {
			Logger::get_instance()->warning( 'Gemini: failed to fetch image', [
				'url' => $media_url,
				'error' => $response->get_error_message(),
			] );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			Logger::get_instance()->warning( 'Gemini: image fetch returned non-2xx', [
				'url' => $media_url,
				'code' => $code,
			] );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$mime_type = 'image/jpeg';
		if ( $content_type && preg_match( '#^([^;]+)#', $content_type, $m ) ) {
			$mime_type = strtolower( trim( $m[1] ) );
		}
		// Gemini supports image/jpeg, image/png, image/gif, image/webp.
		$allowed = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
		if ( ! in_array( $mime_type, $allowed, true ) ) {
			$mime_type = 'image/jpeg';
		}

		return [
			'data' => base64_encode( $body ),
			'mime_type' => $mime_type,
		];
	}

	/**
	 * Make HTTP POST request.
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
				'error' => __( 'Gemini API key is not configured.', 'flux-ai-media-alt-creator' ),
			];
		}

		$args = [
			'method' => 'POST',
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $body ),
			'timeout' => 30,
			'sslverify' => true,
		];

		$response = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			Logger::get_instance()->error( 'Gemini API request failed', [
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
			Logger::get_instance()->error( 'Gemini API returned error', [
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
	 * Extract error message from Gemini response.
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
			__( 'Gemini API returned error code %d.', 'flux-ai-media-alt-creator' ),
			$response_code
		);
	}

	/**
	 * Parse Gemini generateContent response.
	 *
	 * Concatenates all text from content parts (Gemini 2.5 can return multiple parts,
	 * e.g. thinking + text; we take all 'text' parts to avoid truncation).
	 *
	 * @since 2.0.0
	 * @param array $response_data Response from API.
	 * @return array Keys: success, content, usage (prompt_tokens, completion_tokens, total_tokens), error.
	 */
	private function parse_response( $response_data ) {
		if ( ! is_array( $response_data ) ) {
			return [
				'success' => false,
				'content' => '',
				'usage' => null,
				'error' => __( 'Invalid response from Gemini API.', 'flux-ai-media-alt-creator' ),
			];
		}

		$content = '';
		if ( ! empty( $response_data['candidates'][0]['content']['parts'] ) ) {
			$parts = $response_data['candidates'][0]['content']['parts'];
			$text_parts = [];
			foreach ( $parts as $part ) {
				if ( isset( $part['text'] ) && is_string( $part['text'] ) ) {
					// Skip thinking/reasoning parts; keep only final answer.
					if ( isset( $part['thought'] ) && $part['thought'] === true ) {
						continue;
					}
					$text_parts[] = $part['text'];
				}
			}
			$content = implode( '', $text_parts );
			$content = trim( $content );
			if ( strlen( $content ) >= 2 && $content[0] === '"' && $content[ strlen( $content ) - 1 ] === '"' ) {
				$content = substr( $content, 1, -1 );
			}
		}

		$usage = null;
		if ( isset( $response_data['usageMetadata'] ) ) {
			$um = $response_data['usageMetadata'];
			$prompt_tokens = (int) ( $um['promptTokenCount'] ?? 0 );
			$completion_tokens = (int) ( $um['candidatesTokenCount'] ?? 0 );
			$usage = [
				'prompt_tokens' => $prompt_tokens,
				'completion_tokens' => $completion_tokens,
				'total_tokens' => $prompt_tokens + $completion_tokens,
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
