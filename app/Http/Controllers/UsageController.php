<?php
/**
 * Usage REST API controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */
namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\App\Services\UsageTracker;
use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles usage statistics REST API endpoints.
 *
 * @since 1.0.0
 */
class UsageController extends BaseController {

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
	 * @since 1.1.0 Removed Logger parameter, now uses Logger::get_instance() directly via BaseController.
	 * @param UsageTracker $usage_tracker Usage tracker instance.
	 */
	public function __construct( UsageTracker $usage_tracker ) {
		$this->usage_tracker = $usage_tracker;
		parent::__construct();
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'flux-ai-media-alt-creator/v1', '/usage', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_usage' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );
	}

	/**
	 * Get display label for the active vision provider (for tooltips).
	 *
	 * @since 2.0.0
	 * @return string Human-readable provider and model label.
	 */
	private static function get_provider_display_label() {
		$provider = Settings::get_vision_provider();
		$labels = [
			'openai' => 'OpenAI (gpt-4o-mini)',
			'gemini' => 'Google Gemini (gemini-2.5-flash-lite)',
			'claude' => 'Anthropic Claude (claude-haiku-4-5-20251001)',
		];
		return $labels[ $provider ] ?? $labels['openai'];
	}

	/**
	 * Get current month usage statistics.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Added provider and provider_display_label to response.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_usage( WP_REST_Request $request ) {
		try {
			$usage = $this->usage_tracker->get_current_month_usage();
			$usage['provider'] = Settings::get_vision_provider();
			$usage['provider_display_label'] = self::get_provider_display_label();

			/**
			 * Filter usage data before returning.
			 *
			 * @since 1.0.0
			 * @param array $usage Usage data.
			 */
			$usage = apply_filters( 'flux_ai_alt_creator_usage_data', $usage );

			return $this->create_success_response( $usage, 'Usage statistics retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve usage statistics: ' . $e->getMessage() );
		}
	}
}

