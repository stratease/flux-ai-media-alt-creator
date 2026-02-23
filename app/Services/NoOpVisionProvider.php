<?php
/**
 * No-op vision provider when no API key is configured.
 *
 * @package FluxAIMediaAltCreator
 * @since 2.0.0
 */
namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\App\Services\Vision\VisionProviderInterface;

/**
 * Returns a clear error when the selected provider has no API key.
 *
 * @since 2.0.0
 */
class NoOpVisionProvider implements VisionProviderInterface {

	/**
	 * Provider slug (openai, gemini, claude).
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private $provider;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @param string $provider Provider slug.
	 */
	public function __construct( $provider = 'openai' ) {
		$this->provider = $provider;
	}

	/**
	 * Generate alt text (always returns error when no API key).
	 *
	 * @since 2.0.0
	 * @param string $media_url Media URL.
	 * @param int    $media_id  Media ID.
	 * @return array Result with success false and error message.
	 */
	public function generate_alt_text( $media_url, $media_id ) {
		$labels = [
			'openai' => __( 'OpenAI', 'flux-ai-media-alt-creator' ),
			'gemini' => __( 'Google Gemini', 'flux-ai-media-alt-creator' ),
			'claude' => __( 'Anthropic Claude', 'flux-ai-media-alt-creator' ),
		];
		$label = $labels[ $this->provider ] ?? __( 'AI', 'flux-ai-media-alt-creator' );

		return [
			'success' => false,
			'alt_text' => '',
			'error' => sprintf(
				/* translators: %s: Provider name */
				__( '%s API key not configured. Please add your API key in Settings.', 'flux-ai-media-alt-creator' ),
				$label
			),
			'tokens_used' => 0,
			'cost' => 0.0,
		];
	}
}
