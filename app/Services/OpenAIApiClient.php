<?php
/**
 * OpenAI API client using WordPress HTTP API.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Decoupled OpenAI API client for making direct HTTP requests.
 *
 * @since 1.0.0
 */
class OpenAIApiClient {

	/**
	 * OpenAI API base URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.openai.com/v1';

	/**
	 * API key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $api_key;

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
	 * @param string $api_key OpenAI API key.
	 * @param Logger $logger Logger instance.
	 */
	public function __construct( $api_key, Logger $logger ) {
		$this->api_key = $api_key;
		$this->logger = $logger;
	}

	/**
	 * Generate content using OpenAI Vision API.
	 *
	 * Supports different file types (images, videos, etc.) with a custom prompt.
	 *
	 * @since 1.0.0
	 * @param string $media_url URL of the media file.
	 * @param string $prompt Prompt text for the AI.
	 * @param string $model Model to use (default: gpt-4o-mini).
	 * @param int    $max_tokens Maximum tokens in response (default: 150).
	 * @return array Response array with 'success', 'data', 'error', 'usage'.
	 */
	public function generate_vision_content( $media_url, $prompt, $model = 'gpt-4o-mini', $max_tokens = 150 ) {
		$endpoint = self::API_BASE_URL . '/chat/completions';

		// Determine content type based on media URL.
		$content_type = $this->detect_content_type( $media_url );

		// Build request body.
		$body = [
			'model' => $model,
			'messages' => [
				[
					'role' => 'user',
					'content' => $this->build_content_array( $prompt, $media_url, $content_type ),
				],
			],
			'max_tokens' => $max_tokens,
		];

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 1.0.0
		 * @param array  $body Request body.
		 * @param string $media_url Media URL.
		 * @param string $prompt Prompt text.
		 * @return array Filtered request body.
		 */
		$body = apply_filters( 'flux_ai_alt_creator/openai_api_client/generate_vision_content/request_body', $body, $media_url, $prompt );

		// Make API request.
		$response = $this->make_request( $endpoint, $body );

		if ( ! $response['success'] ) {
			return $response;
		}

		// Parse response.
		return $this->parse_response( $response['data'] );
	}

	/**
	 * Detect content type from media URL.
	 *
	 * @since 1.0.0
	 * @param string $media_url Media URL.
	 * @return string Content type ('image' or 'video').
	 */
	private function detect_content_type( $media_url ) {
		$extension = strtolower( pathinfo( wp_parse_url( $media_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

		$image_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'tiff', 'ico' ];
		$video_extensions = [ 'mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv', 'flv', 'mkv' ];

		if ( in_array( $extension, $image_extensions, true ) ) {
			return 'image';
		}

		if ( in_array( $extension, $video_extensions, true ) ) {
			return 'video';
		}

		// Default to image if unknown.
		return 'image';
	}

	/**
	 * Build content array for OpenAI API.
	 *
	 * @since 1.0.0
	 * @param string $prompt Prompt text.
	 * @param string $media_url Media URL.
	 * @param string $content_type Content type ('image' or 'video').
	 * @return array Content array.
	 */
	private function build_content_array( $prompt, $media_url, $content_type ) {
		$content = [
			[
				'type' => 'text',
				'text' => $prompt,
			],
		];

		// Add media content based on type.
		if ( 'image' === $content_type ) {
			$content[] = [
				'type' => 'image_url',
				'image_url' => [
					'url' => $media_url,
				],
			];
		} elseif ( 'video' === $content_type ) {
			// For videos, we use the same structure but OpenAI may handle it differently.
			// Check OpenAI docs for video support in your model version.
			$content[] = [
				'type' => 'image_url',
				'image_url' => [
					'url' => $media_url,
				],
			];
		}

		/**
		 * Filter the content array before sending to OpenAI.
		 *
		 * @since 1.0.0
		 * @param array  $content Content array.
		 * @param string $media_url Media URL.
		 * @param string $content_type Content type.
		 * @return array Filtered content array.
		 */
		return apply_filters( 'flux_ai_alt_creator/openai_api_client/build_content_array', $content, $media_url, $content_type );
	}

	/**
	 * Make HTTP request to OpenAI API.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API endpoint.
	 * @param array  $body Request body.
	 * @return array Response array with 'success', 'data', 'error'.
	 */
	private function make_request( $endpoint, $body ) {
		if ( empty( $this->api_key ) ) {
			return [
				'success' => false,
				'data' => null,
				'error' => __( 'OpenAI API key is not configured.', 'flux-ai-media-alt-creator' ),
			];
		}

		$args = [
			'method' => 'POST',
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $body ),
			'timeout' => 30,
			'sslverify' => true,
		];

		/**
		 * Filter the HTTP request arguments.
		 *
		 * @since 1.0.0
		 * @param array  $args Request arguments.
		 * @param string $endpoint API endpoint.
		 * @return array Filtered request arguments.
		 */
		$args = apply_filters( 'flux_ai_alt_creator/openai_api_client/make_request/request_args', $args, $endpoint );

		$response = wp_remote_post( $endpoint, $args );

		// Check for WordPress HTTP errors.
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error( 'OpenAI API request failed', [
				'error' => $error_message,
				'endpoint' => $endpoint,
			] );

			return [
				'success' => false,
				'data' => null,
				'error' => $error_message,
			];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		// Check for HTTP errors.
		if ( $response_code < 200 || $response_code >= 300 ) {
			$error_message = $this->extract_error_message( $response_data, $response_code );
			$this->logger->error( 'OpenAI API returned error', [
				'code' => $response_code,
				'error' => $error_message,
				'response' => $response_data,
			] );

			return [
				'success' => false,
				'data' => $response_data,
				'error' => $error_message,
				'code' => $response_code,
			];
		}

		return [
			'success' => true,
			'data' => $response_data,
			'error' => null,
		];
	}

	/**
	 * Extract error message from API response.
	 *
	 * @since 1.0.0
	 * @param array|null $response_data Response data.
	 * @param int        $response_code HTTP response code.
	 * @return string Error message.
	 */
	private function extract_error_message( $response_data, $response_code ) {
		if ( ! is_array( $response_data ) ) {
			return sprintf(
				/* translators: %d: HTTP response code */
				__( 'OpenAI API returned error code %d.', 'flux-ai-media-alt-creator' ),
				$response_code
			);
		}

		// Check for OpenAI error structure.
		if ( isset( $response_data['error']['message'] ) ) {
			return $response_data['error']['message'];
		}

		if ( isset( $response_data['error'] ) && is_string( $response_data['error'] ) ) {
			return $response_data['error'];
		}

		// Fallback to generic message.
		return sprintf(
			/* translators: %d: HTTP response code */
			__( 'OpenAI API returned error code %d.', 'flux-ai-media-alt-creator' ),
			$response_code
		);
	}

	/**
	 * Parse API response and extract relevant data.
	 *
	 * @since 1.0.0
	 * @param array $response_data Response data from API.
	 * @return array Parsed response with 'success', 'content', 'usage', 'error'.
	 */
	private function parse_response( $response_data ) {
		if ( ! is_array( $response_data ) ) {
			return [
				'success' => false,
				'content' => '',
				'usage' => null,
				'error' => __( 'Invalid response from OpenAI API.', 'flux-ai-media-alt-creator' ),
			];
		}

		// Extract content.
		$content = '';
		if ( isset( $response_data['choices'][0]['message']['content'] ) ) {
			$content = trim( $response_data['choices'][0]['message']['content'] );
		}

		// Extract usage data.
		$usage = null;
		if ( isset( $response_data['usage'] ) ) {
			$usage = [
				'prompt_tokens' => $response_data['usage']['prompt_tokens'] ?? 0,
				'completion_tokens' => $response_data['usage']['completion_tokens'] ?? 0,
				'total_tokens' => $response_data['usage']['total_tokens'] ?? 0,
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

