<?php
/**
 * No-config vision provider that returns an error when no API key is set.
 *
 * @package FluxAIMediaAltCreator
 * @since 2.0.0
 */

namespace FluxAIMediaAltCreator\App\Services\Vision;

/**
 * Fallback provider when no vision API key is configured.
 *
 * @since 2.0.0
 */
class NoConfigVisionProvider implements VisionProviderInterface {

	/**
	 * Generate alt text (always returns error).
	 *
	 * @since 2.0.0
	 * @param string $media_url Media URL.
	 * @param int    $media_id  Media ID (unused).
	 * @return array Result with success false and error message.
	 */
	public function generate_alt_text( $media_url, $media_id ) {
		return [
			'success' => false,
			'alt_text' => '',
			'error' => __( 'No API key configured for the selected provider. Please add your API key in Settings.', 'flux-ai-media-alt-creator' ),
			'tokens_used' => 0,
			'cost' => 0.0,
		];
	}
}
