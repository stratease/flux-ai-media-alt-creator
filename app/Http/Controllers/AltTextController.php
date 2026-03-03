<?php
/**
 * Alt text REST API controller for Flux AI Alt Text & Accessibility Audit plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */
namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\MediaScanner;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles alt text REST API endpoints.
 *
 * @since 1.0.0
 */
class AltTextController extends BaseController {

	/**
	 * Media scanner instance.
	 *
	 * @since 1.0.0
	 * @var MediaScanner
	 */
	private $media_scanner;

	/**
	 * Async job service instance.
	 *
	 * @since 1.0.0
	 * @var AsyncJobService
	 */
	private $async_job_service;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Removed Logger parameter, now uses Logger::get_instance() directly via BaseController.
	 * @since 1.2.0 Removed OpenAIService parameter - now uses AltTextApiService::get_instance() directly.
	 * @param MediaScanner   $media_scanner Media scanner instance.
	 * @param AsyncJobService $async_job_service Async job service instance.
	 */
	public function __construct( MediaScanner $media_scanner, AsyncJobService $async_job_service ) {
		$this->media_scanner = $media_scanner;
		$this->async_job_service = $async_job_service;
		parent::__construct();
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'flux-ai-media-alt-creator/v1', '/alt-text/generate', [
			[
				'methods' => 'POST',
				'callback' => [ $this, 'generate_alt_text' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'media_ids' => [
						'required' => true,
						'type' => 'array',
						'items' => [
							'type' => 'integer',
						],
					],
					'async' => [
						'default' => false,
						'type' => 'boolean',
					],
				],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/alt-text/apply', [
			[
				'methods' => 'POST',
				'callback' => [ $this, 'apply_alt_text' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'media_ids' => [
						'required' => true,
						'type' => 'array',
						'items' => [
							'type' => 'integer',
						],
					],
					'alt_texts' => [
						'required' => false,
						'type' => 'object',
						'description' => 'Map of media_id => alt_text for custom alt text values',
					],
				],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/alt-text/batch-generate', [
			[
				'methods' => 'POST',
				'callback' => [ $this, 'batch_generate_alt_text' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'media_ids' => [
						'required' => true,
						'type' => 'array',
						'items' => [
							'type' => 'integer',
						],
					],
					'batch_size' => [
						'default' => 10,
						'type' => 'integer',
					],
				],
			],
		] );
	}

	/**
	 * Generate AI alt text for selected media files.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Changed from 'ai_status' to 'scan_status' using dedicated meta field and update_scan_status() method.
	 * @since 1.2.0 Updated to use AltTextApiService for abstracted generation with hooks.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function generate_alt_text( WP_REST_Request $request ) {
		try {
			$media_ids = $request->get_param( 'media_ids' );
			$async = $request->get_param( 'async' );

			if ( empty( $media_ids ) || ! is_array( $media_ids ) ) {
				return $this->create_error_response( 'Invalid media IDs', 'invalid_media_ids', 400 );
			}

			if ( $async ) {
				// Schedule async jobs.
				$jobs_scheduled = $this->async_job_service->schedule_alt_text_generation( $media_ids );
				
				return $this->create_success_response( [
					'jobs_scheduled' => $jobs_scheduled,
					'message' => 'Alt text generation scheduled in background',
				], 'Alt text generation scheduled' );
			}

			// Process synchronously using abstracted service.
			$alt_text_api_service = \FluxAIMediaAltCreator\App\Services\AltTextApiService::get_instance();
			$results = [];
			
			foreach ( $media_ids as $media_id ) {
				$media_url = wp_get_attachment_url( $media_id );
				
				if ( ! $media_url ) {
					$results[] = [
						'media_id' => $media_id,
						'success' => false,
						'error' => 'Could not get media URL',
					];
					continue;
				}

				// Generate alt text via abstracted service (handles status and scan data updates).
				$result = $alt_text_api_service->generate_alt_text( $media_id, $media_url );

				$results[] = [
					'media_id' => $media_id,
					'success' => $result['success'],
					'alt_text' => $result['alt_text'] ?? '',
					'error' => $result['error'] ?? '',
				];
			}

			return $this->create_success_response( $results, 'Alt text generation completed' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to generate alt text: ' . $e->getMessage() );
		}
	}

	/**
	 * Apply alt text to WordPress.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Updated to use AltTextApiService for abstracted application with hooks.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function apply_alt_text( WP_REST_Request $request ) {
		try {
			$media_ids = $request->get_param( 'media_ids' );
			$alt_texts = $request->get_param( 'alt_texts' ) ?? [];

			if ( empty( $media_ids ) || ! is_array( $media_ids ) ) {
				return $this->create_error_response( 'Invalid media IDs', 'invalid_media_ids', 400 );
			}

			// Use abstracted service for application.
			$alt_text_api_service = \FluxAIMediaAltCreator\App\Services\AltTextApiService::get_instance();
			$results = [];
			
			foreach ( $media_ids as $media_id ) {
				// Use custom alt text if provided, otherwise let service get from scan data.
				$alt_text = '';
				if ( isset( $alt_texts[ $media_id ] ) && ! empty( $alt_texts[ $media_id ] ) ) {
					$alt_text = sanitize_text_field( $alt_texts[ $media_id ] );
				}

				// Apply alt text via abstracted service (handles scan data updates).
				$result = $alt_text_api_service->apply_alt_text( $media_id, $alt_text );

				$results[] = [
					'media_id' => $media_id,
					'success' => $result['success'],
					'alt_text' => $result['alt_text'] ?? '',
					'error' => $result['error'] ?? '',
				];
			}

			return $this->create_success_response( $results, 'Alt text applied successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to apply alt text: ' . $e->getMessage() );
		}
	}

	/**
	 * Schedule async batch generation.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function batch_generate_alt_text( WP_REST_Request $request ) {
		try {
			$media_ids = $request->get_param( 'media_ids' );
			$batch_size = $request->get_param( 'batch_size' );

			if ( empty( $media_ids ) || ! is_array( $media_ids ) ) {
				return $this->create_error_response( 'Invalid media IDs', 'invalid_media_ids', 400 );
			}

			$jobs_scheduled = $this->async_job_service->schedule_alt_text_generation( $media_ids, $batch_size );

			return $this->create_success_response( [
				'jobs_scheduled' => $jobs_scheduled,
				'message' => 'Batch alt text generation scheduled',
			], 'Batch generation scheduled' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to schedule batch generation: ' . $e->getMessage() );
		}
	}
}

