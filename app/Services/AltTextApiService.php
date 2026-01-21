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

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;
use FluxAIMediaAltCreator\App\Services\OpenAIService;

/**
 * Abstracted service for alt text generation API calls.
 *
 * @since 1.0.0
 */
class AltTextApiService {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var AltTextApiService|null
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Changed to private constructor for singleton pattern, removed dependency injection.
	 */
	private function __construct() {
		// Private constructor for singleton pattern.
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 1.1.0
	 * @return AltTextApiService Singleton instance.
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
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
			Logger::get_instance()->debug( 'Alt text generation intercepted via filter', [
				'attachment_id' => $attachment_id,
			] );
			return $intercepted_result;
		}

		// Use default OpenAI service.
		return OpenAIService::get_instance()->generate_alt_text( $media_url, $attachment_id );
	}
}

