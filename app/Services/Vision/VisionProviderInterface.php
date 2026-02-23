<?php
/**
 * Vision provider interface for alt text generation.
 *
 * @package FluxAIMediaAltCreator
 * @since 2.0.0
 */

namespace FluxAIMediaAltCreator\App\Services\Vision;

/**
 * Interface for vision API providers (OpenAI, Gemini, Claude).
 *
 * @since 2.0.0
 */
interface VisionProviderInterface {

	/**
	 * Generate alt text for a media file.
	 *
	 * @since 2.0.0
	 * @param string $media_url Media URL.
	 * @param int    $media_id  Media ID (attachment ID).
	 * @return array Result with 'success', 'alt_text', and optionally 'error', 'tokens_used', 'cost'.
	 */
	public function generate_alt_text( $media_url, $media_id );
}
