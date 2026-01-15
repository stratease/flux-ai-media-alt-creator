<?php
/**
 * Abstracted alt text API service.
 *
 * Provides a unified interface for alt text generation that can be intercepted
 * via filters. The underlying API implementation (OpenAI, Pro API, etc.) is
 * abstracted away.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Abstracted service for alt text generation API calls.
 *
 * @since 1.0.0
 */
class AltTextApiService {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * OpenAI service instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIService
	 */
	private $openai_service;

	/**
	 * Usage tracker instance.
	 *
	 * @since 1.0.0
	 * @var UsageTracker
	 */
	private $usage_tracker;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Logger        $logger Logger instance.
	 * @param OpenAIService $openai_service OpenAI service instance.
	 * @param UsageTracker  $usage_tracker Usage tracker instance.
	 */
	public function __construct( Logger $logger, OpenAIService $openai_service, UsageTracker $usage_tracker ) {
		$this->logger = $logger;
		$this->openai_service = $openai_service;
		$this->usage_tracker = $usage_tracker;
	}

	/**
	 * Generate alt text for a media file.
	 *
	 * This method provides a unified interface that can be intercepted via filters.
	 * The actual API implementation is hidden behind filters.
	 *
	 * @since 1.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $media_url     Media URL (optional, will be retrieved from attachment if not provided).
	 * @return array Result array with 'success', 'alt_text', and optionally 'error', 'tokens_used', 'cost'.
	 */
	public function generate_alt_text( $attachment_id, $media_url = '' ) {
		$attachment_id = absint( $attachment_id );

		if ( ! $attachment_id ) {
			return [
				'success' => false,
				'alt_text' => '',
				'error' => __( 'Invalid attachment ID', 'flux-ai-media-alt-creator' ),
			];
		}

		// Get media URL if not provided.
		if ( empty( $media_url ) ) {
			$media_url = wp_get_attachment_url( $attachment_id );
			if ( ! $media_url ) {
				return [
					'success' => false,
					'alt_text' => '',
					'error' => __( 'Could not get media URL', 'flux-ai-media-alt-creator' ),
				];
			}
		}

		/**
		 * Filter to allow intercepting alt text generation before default processing.
		 *
		 * Return a non-null array to override default generation. The array should
		 * have 'success' (bool) and 'alt_text' (string) keys, and optionally 'error', 'tokens_used', 'cost'.
		 * Return null to use default OpenAI processing.
		 *
		 * @since 1.0.0
		 * @param null|array $result   Generation result (null to use default).
		 * @param int        $attachment_id Attachment ID.
		 * @param string     $media_url Media URL.
		 * @return null|array Result array or null to use default.
		 */
		$intercepted_result = apply_filters( 'flux_ai_alt_creator/alt_text_api_service/generate_alt_text', null, $attachment_id, $media_url );

		if ( $intercepted_result !== null && is_array( $intercepted_result ) ) {
			// Intercepted by filter - use the provided result.
			$this->logger->debug( 'Alt text generation intercepted via filter', [
				'attachment_id' => $attachment_id,
			] );
			return $intercepted_result;
		}

		// Use default OpenAI service.
		return $this->openai_service->generate_alt_text( $media_url, $attachment_id );
	}
}

