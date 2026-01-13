<?php
/**
 * Options REST API controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\Settings;
use FluxAIMediaAltCreator\App\Services\Logger;
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
	 * @param Settings $settings Settings instance.
	 * @param Logger   $logger Logger instance.
	 */
	public function __construct( Settings $settings, Logger $logger ) {
		$this->settings = $settings;
		parent::__construct( $logger );
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
			
			// Mask API key for security.
			if ( ! empty( $options['openai_api_key'] ) ) {
				$options['openai_api_key'] = $this->mask_api_key( $options['openai_api_key'] );
			}
			
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

			// Update options.
			$this->settings->update( $options );

			// Get updated options (with masked API key).
			$updated_options = $this->settings->get_all();
			if ( ! empty( $updated_options['openai_api_key'] ) ) {
				$updated_options['openai_api_key'] = $this->mask_api_key( $updated_options['openai_api_key'] );
			}
			
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
	 * Mask API key for display.
	 *
	 * @since 1.0.0
	 * @param string $api_key API key.
	 * @return string Masked API key.
	 */
	private function mask_api_key( $api_key ) {
		if ( empty( $api_key ) || strlen( $api_key ) < 8 ) {
			return '••••••••';
		}
		
		$length = strlen( $api_key );
		$visible = 4;
		$masked = str_repeat( '•', $length - $visible );
		
		return substr( $api_key, 0, $visible ) . $masked;
	}
}

