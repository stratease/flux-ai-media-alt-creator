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
use FluxAIMediaAltCreator\App\Services\ComplianceScanService;
use FluxAIMediaAltCreator\App\Services\WooCommerceHelper;
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
	 * Build context string for alt text prompt (WooCommerce product or parent post).
	 *
	 * For WooCommerce product images: product title, 1–2 variation attributes, category if useful.
	 * For non-WooCommerce: parent post title when available.
	 * Used to improve alt text relevance. Prompt constraints: 70–155 characters, no "image of"/"photo of", no quotes, no keyword stuffing.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return string Context string (empty if none).
	 */
	public static function get_attachment_context_for_prompt( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return '';
		}

		$post      = get_post( $attachment_id );
		$parent    = null;
		$product_id = 0;

		if ( $post && ! empty( $post->post_parent ) ) {
			$parent = get_post( $post->post_parent );
			if ( $parent && in_array( $parent->post_type, [ 'product', 'product_variation' ], true ) ) {
				$product_id = (int) $parent->ID;
			}
		}

		if ( WooCommerceHelper::is_active() && ! $product_id ) {
			$product_ids = WooCommerceHelper::get_products_for_attachment( $attachment_id );
			if ( ! empty( $product_ids ) ) {
				$product_id = (int) $product_ids[0];
				if ( ! $parent || (int) $parent->ID !== $product_id ) {
					$parent = get_post( $product_id );
				}
			}
		}

		if ( WooCommerceHelper::is_active() && $product_id ) {
			$title   = $parent ? $parent->post_title : '';
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
			$parts   = array_filter( [ $title ] );

			if ( $product && is_callable( [ $product, 'get_attribute' ] ) ) {
				$attrs = [];
				foreach ( [ 'pa_color', 'pa_size', 'pa_material', 'color', 'size', 'material' ] as $attr ) {
					$val = trim( (string) $product->get_attribute( $attr ) );
					if ( $val !== '' && count( $attrs ) < 2 ) {
						$attrs[] = $val;
					}
				}
				if ( ! empty( $attrs ) ) {
					$parts[] = implode( ', ', $attrs );
				}
			}

			$category = '';
			if ( $product && is_callable( [ $product, 'get_category_ids' ] ) ) {
				$cat_ids = $product->get_category_ids();
				if ( ! empty( $cat_ids ) ) {
					$term = get_term( $cat_ids[0], 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$category = $term->name;
					}
				}
			}
			if ( $category !== '' ) {
				$parts[] = $category;
			}

			$context = trim( implode( ' — ', array_filter( $parts ) ) );
			if ( $context !== '' ) {
				return sprintf(
					/* translators: %s: product/parent context */
					__( 'Context: %s.', 'flux-ai-media-alt-creator' ),
					$context
				);
			}
		}

		if ( ! $parent ) {
			return '';
		}

		$context = trim( (string) $parent->post_title );
		if ( $context === '' ) {
			return '';
		}

		return sprintf(
			/* translators: %s: parent post title */
			__( 'Context: Part of the content "%s".', 'flux-ai-media-alt-creator' ),
			$context
		);
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
	 * @since 3.2.2 Successful generation now clears stale error_message state.
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
			// @since 3.2.2 Clear stale error state when generation succeeds.
			MediaScanner::get_instance()->update_scan_status( $attachment_id, 'completed' );
			MediaScanner::get_instance()->update_scan_data( $attachment_id, [
				'recommended_alt_text' => $result['alt_text'] ?? '',
				'error_message' => '',
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
	 * @since 3.2.2 Successful application now resets scan_status to completed and clears stale error_message state.
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

		// Allow empty string: "mark decorative" sets alt to empty per WCAG.
		if ( $alt_text === '' ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', '' );
			update_post_meta( $attachment_id, ComplianceScanService::ALT_CATEGORY_META_KEY, 'decorative' );
			// @since 3.2.2 Ensure success paths reset prior error state and status.
			MediaScanner::get_instance()->update_scan_status( $attachment_id, 'completed' );
			MediaScanner::get_instance()->update_scan_data( $attachment_id, [
				'applied' => true,
				'recommended_alt_text' => '',
				'error_message' => '',
			] );
			return [ 'success' => true, 'alt_text' => '' ];
		}

		// Get alt text from scan data if not provided.
		if ( empty( $alt_text ) ) {
			$scan_data = MediaScanner::get_instance()->get_scan_data( $attachment_id );
			$alt_text  = $scan_data['recommended_alt_text'] ?? '';
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
			// Use default WordPress meta update (empty string is valid for decorative images).
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
			$result = [
				'success' => true,
				'alt_text' => $alt_text,
			];
		}

		// Update scan status/data with applied status if successful.
		if ( $result['success'] ) {
			// @since 3.2.2 Ensure success paths reset prior error state and status.
			MediaScanner::get_instance()->update_scan_status( $attachment_id, 'completed' );
			MediaScanner::get_instance()->update_scan_data( $attachment_id, [
				'applied' => true,
				'recommended_alt_text' => $alt_text,
				'error_message' => '',
			] );
			// Reclassify so compliance category (e.g. missing → descriptive) is updated immediately.
			ComplianceScanService::get_instance()->reclassify_attachments( [ $attachment_id ] );
		}

		return $result;
	}
}

