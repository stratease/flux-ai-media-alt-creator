<?php
/**
 * Abstracted alt text API service.
 *
 * Provides a unified interface for alt text generation and application that can be intercepted
 * via filters. The underlying API implementation (OpenAI, Pro API, etc.) is abstracted away.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */
namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;
use FluxAIMediaAltCreator\App\Services\MediaScanner;
use FluxAIMediaAltCreator\App\Services\Vision\VisionProviderFactory;

/**
 * Abstracted service for alt text generation and application API calls.
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
	 * Default prompt for vision providers (OpenAI, Gemini, Claude). Centralized so all providers use the same wording.
	 *
	 * @since 2.0.0
	 * @return string Default prompt text for alt text generation.
	 */
	public static function get_default_alt_text_prompt() {
		return __( 'Generate a concise, SEO-friendly alt text for this image that accurately describes its content and context, helpful for screen readers, and no longer than 125 characters. Reply with only the alt text itself: plain text only. Do not use markdown, headers (e.g. "# Alt Text"), labels, or quotes around the text.', 'flux-ai-media-alt-creator' );
	}

	/**
	 * Generate alt text for a media file.
	 *
	 * This method provides a unified interface that can be intercepted via filters.
	 * The actual API implementation is hidden behind filters. This method also handles
	 * updating scan status and scan data automatically.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Added automatic scan status and scan data updates.
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
				MediaScanner::get_instance()->update_scan_status( $attachment_id, 'error' );
				MediaScanner::get_instance()->update_scan_data( $attachment_id, [
					'error_message' => __( 'Could not get media URL', 'flux-ai-media-alt-creator' ),
				] );
				return [
					'success' => false,
					'alt_text' => '',
					'error' => __( 'Could not get media URL', 'flux-ai-media-alt-creator' ),
				];
			}
		}

		// Update status to processing.
		MediaScanner::get_instance()->update_scan_status( $attachment_id, 'processing' );

		/**
		 * Filter to allow intercepting alt text generation before default processing.
		 *
		 * Return a non-null array to override default generation. The array should
		 * have 'success' (bool) and 'alt_text' (string) keys, and optionally 'error', 'tokens_used', 'cost'.
		 * Return null to use configured vision provider (OpenAI, Gemini, or Claude).
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
			$result = $intercepted_result;
		} else {
			// Use configured vision provider (OpenAI, Gemini, or Claude).
			$result = VisionProviderFactory::get_provider()->generate_alt_text( $media_url, $attachment_id );
		}

		// Normalize alt text: strip any markdown headers or labels the model may have added.
		if ( ! empty( $result['alt_text'] ) && is_string( $result['alt_text'] ) ) {
			$result['alt_text'] = self::sanitize_alt_text_response( $result['alt_text'] );
		}

		// Update scan status and scan data based on result.
		if ( $result['success'] ) {
			MediaScanner::get_instance()->update_scan_status( $attachment_id, 'completed' );
			MediaScanner::get_instance()->update_scan_data( $attachment_id, [
				'recommended_alt_text' => $result['alt_text'] ?? '',
			] );
		} else {
			MediaScanner::get_instance()->update_scan_status( $attachment_id, 'error' );
			MediaScanner::get_instance()->update_scan_data( $attachment_id, [
				'error_message' => $result['error'] ?? __( 'Unknown error', 'flux-ai-media-alt-creator' ),
			] );
		}

		return $result;
	}

	/**
	 * Sanitize model output to plain alt text (strip markdown headers, labels, surrounding quotes).
	 *
	 * @since 2.0.0
	 * @param string $content Raw content from vision API.
	 * @return string Plain alt text.
	 */
	private static function sanitize_alt_text_response( $content ) {
		$content = trim( $content );
		// Remove leading markdown headers like "# Alt Text" or "## Alt text" and following newlines.
		$content = preg_replace( '/^#+\s*Alt\s+Text\s*\n*/iu', '', $content );
		// Remove common label prefixes (e.g. "Alt text:", "Alt:").
		$content = preg_replace( '/^(?:Alt\s+text|Alt)\s*:\s*/iu', '', $content );
		return trim( $content );
	}

	/**
	 * Apply alt text to a media file.
	 *
	 * This method provides a unified interface for applying alt text that can be intercepted
	 * via filters. This method also handles updating scan data automatically.
	 *
	 * @since 1.2.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $alt_text      Alt text to apply (optional, will be retrieved from scan data if not provided).
	 * @return array Result array with 'success', 'alt_text', and optionally 'error'.
	 */
	public function apply_alt_text( $attachment_id, $alt_text = '' ) {
		$attachment_id = absint( $attachment_id );

		if ( ! $attachment_id ) {
			return [
				'success' => false,
				'alt_text' => '',
				'error' => __( 'Invalid attachment ID', 'flux-ai-media-alt-creator' ),
			];
		}

		// Get alt text from scan data if not provided.
		if ( empty( $alt_text ) ) {
			$scan_data = MediaScanner::get_instance()->get_scan_data( $attachment_id );
			$alt_text = $scan_data['recommended_alt_text'] ?? '';
		}

		if ( empty( $alt_text ) ) {
			return [
				'success' => false,
				'alt_text' => '',
				'error' => __( 'No alt text provided', 'flux-ai-media-alt-creator' ),
			];
		}

		// Sanitize alt text.
		$alt_text = sanitize_text_field( $alt_text );

		/**
		 * Filter to allow intercepting alt text application before default processing.
		 *
		 * Return a non-null array to override default application. The array should
		 * have 'success' (bool) and 'alt_text' (string) keys, and optionally 'error'.
		 * Return null to use default WordPress meta update.
		 *
		 * @since 1.2.0
		 * @param null|array $result        Application result (null to use default).
		 * @param int        $attachment_id Attachment ID.
		 * @param string     $alt_text      Alt text to apply.
		 * @return null|array Result array or null to use default.
		 */
		$intercepted_result = apply_filters( 'flux_ai_alt_creator/alt_text_api_service/apply_alt_text', null, $attachment_id, $alt_text );

		if ( $intercepted_result !== null && is_array( $intercepted_result ) ) {
			// Intercepted by filter - use the provided result.
			Logger::get_instance()->debug( 'Alt text application intercepted via filter', [
				'attachment_id' => $attachment_id,
			] );
			$result = $intercepted_result;
		} else {
			// Use default WordPress meta update.
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
			$result = [
				'success' => true,
				'alt_text' => $alt_text,
			];
		}

		// Update scan data with applied status if successful.
		if ( $result['success'] ) {
			MediaScanner::get_instance()->update_scan_data( $attachment_id, [
				'applied' => true,
				'recommended_alt_text' => $alt_text,
			] );
		}

		return $result;
	}
}

