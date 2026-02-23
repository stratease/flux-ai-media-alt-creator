<?php
/**
 * Options REST API controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */
namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;
use FluxAIMediaAltCreator\App\Http\Controllers\AdminController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles options/settings REST API endpoints.
 *
 * @since 1.0.0
 */
class OptionsController extends BaseController {

	/**
	 * Placeholder returned when an API key is set. Never send the real key to the client.
	 * When the client sends this value back on save, we keep the existing stored key.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const API_KEY_PLACEHOLDER = '__REDACTED__';

	/**
	 * Settings instance.
	 *
	 * @since 1.0.0
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Removed Logger parameter, now uses Logger::get_instance() directly via BaseController.
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		parent::__construct();
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'flux-ai-media-alt-creator/v1', '/options', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_options' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
			[
				'methods' => 'POST',
				'callback' => [ $this, 'update_options' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'options' => [
						'required' => true,
						'type' => 'object',
						'description' => 'Options to update',
					],
				],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/field-visibility', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_field_visibility' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'field_name' => [
						'required' => true,
						'type' => 'string',
					],
				],
			],
		] );
	}

	/**
	 * Get all options.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_options( WP_REST_Request $request ) {
		try {
			$options = $this->settings->get_all();
			$options = $this->mask_api_keys_in_options( $options );
			// Expose placeholder to frontend so it can avoid sending it as a new key and can show friendly display.
			$options['_api_key_placeholder'] = self::API_KEY_PLACEHOLDER;
			return $this->create_success_response( $options, 'Options retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve options: ' . $e->getMessage() );
		}
	}

	/**
	 * Update options.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_options( WP_REST_Request $request ) {
		try {
			$options = $request->get_param( 'options' );
			
			if ( ! is_array( $options ) ) {
				return $this->create_error_response( 'Invalid options format', 'invalid_options', 400 );
			}

			// Do not overwrite API keys when client sends the placeholder (e.g. "unchanged" from UI).
			$current = $this->settings->get_all();
			$api_key_keys = [ 'openai_api_key', 'gemini_api_key', 'claude_api_key' ];
			foreach ( $api_key_keys as $key ) {
				if ( isset( $options[ $key ] ) && $options[ $key ] === self::API_KEY_PLACEHOLDER ) {
					$options[ $key ] = $current[ $key ] ?? '';
				}
			}

			// Do not persist meta key used only for frontend display.
			unset( $options['_api_key_placeholder'] );

			// Update options.
			$this->settings->update( $options );

			// Get updated options (with masked API keys).
			$updated_options = $this->settings->get_all();
			$updated_options = $this->mask_api_keys_in_options( $updated_options );
			$updated_options['_api_key_placeholder'] = self::API_KEY_PLACEHOLDER;
			return $this->create_success_response( $updated_options, 'Options updated successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to update options: ' . $e->getMessage() );
		}
	}

	/**
	 * Get field visibility status.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_field_visibility( WP_REST_Request $request ) {
		try {
			$field_name = $request->get_param( 'field_name' );
			
			$should_show = AdminController::should_show_field( $field_name );
			
			return $this->create_success_response( [
				'field_name' => $field_name,
				'should_show' => $should_show,
			], 'Field visibility retrieved' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to get field visibility: ' . $e->getMessage() );
		}
	}

	/**
	 * Mask all provider API keys in options for display.
	 *
	 * @since 2.0.0
	 * @param array $options Options array.
	 * @return array Options with API keys masked.
	 */
	private function mask_api_keys_in_options( $options ) {
		$keys = [ 'openai_api_key', 'gemini_api_key', 'claude_api_key' ];
		foreach ( $keys as $key ) {
			if ( ! empty( $options[ $key ] ) ) {
				$options[ $key ] = $this->mask_api_key( $options[ $key ] );
			}
		}
		return $options;
	}

	/**
	 * Mask API key for display. Returns a fixed placeholder so the real key is never sent to the client.
	 * The client can send this placeholder back on save; we then keep the existing stored key.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Return fixed API_KEY_PLACEHOLDER instead of partial key to avoid overwriting on resave.
	 * @param string $api_key API key.
	 * @return string Placeholder string (API_KEY_PLACEHOLDER when key is set, empty when not).
	 */
	private function mask_api_key( $api_key ) {
		if ( empty( $api_key ) || ! is_string( $api_key ) ) {
			return '';
		}
		return self::API_KEY_PLACEHOLDER;
	}
}

