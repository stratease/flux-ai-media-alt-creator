<?php
/**
 * Alt text REST API controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\OpenAIService;
use FluxAIMediaAltCreator\App\Services\ImageScanner;
use FluxAIMediaAltCreator\App\Services\AsyncJobService;
use FluxAIMediaAltCreator\App\Services\Logger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles alt text REST API endpoints.
 *
 * @since 1.0.0
 */
class AltTextController extends BaseController {

	/**
	 * OpenAI service instance.
	 *
	 * @since 1.0.0
	 * @var OpenAIService
	 */
	private $openai_service;

	/**
	 * Image scanner instance.
	 *
	 * @since 1.0.0
	 * @var ImageScanner
	 */
	private $image_scanner;

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
	 * @param OpenAIService  $openai_service OpenAI service instance.
	 * @param ImageScanner   $image_scanner Image scanner instance.
	 * @param AsyncJobService $async_job_service Async job service instance.
	 * @param Logger         $logger Logger instance.
	 */
	public function __construct( OpenAIService $openai_service, ImageScanner $image_scanner, AsyncJobService $async_job_service, Logger $logger ) {
		$this->openai_service = $openai_service;
		$this->image_scanner = $image_scanner;
		$this->async_job_service = $async_job_service;
		parent::__construct( $logger );
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
					'image_ids' => [
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
					'image_ids' => [
						'required' => true,
						'type' => 'array',
						'items' => [
							'type' => 'integer',
						],
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
					'image_ids' => [
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
	 * Generate AI alt text for selected images.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function generate_alt_text( WP_REST_Request $request ) {
		try {
			$image_ids = $request->get_param( 'image_ids' );
			$async = $request->get_param( 'async' );

			if ( empty( $image_ids ) || ! is_array( $image_ids ) ) {
				return $this->create_error_response( 'Invalid image IDs', 'invalid_image_ids', 400 );
			}

			if ( $async ) {
				// Schedule async jobs.
				$jobs_scheduled = $this->async_job_service->schedule_alt_text_generation( $image_ids );
				
				return $this->create_success_response( [
					'jobs_scheduled' => $jobs_scheduled,
					'message' => 'Alt text generation scheduled in background',
				], 'Alt text generation scheduled' );
			}

			// Process synchronously.
			$results = [];
			foreach ( $image_ids as $image_id ) {
				$image_url = wp_get_attachment_image_url( $image_id, 'full' );
				
				if ( ! $image_url ) {
					$results[] = [
						'image_id' => $image_id,
						'success' => false,
						'error' => 'Could not get image URL',
					];
					continue;
				}

				// Update status to processing.
				$this->image_scanner->update_scan_data( $image_id, [
					'ai_status' => 'processing',
				] );

				// Generate alt text.
				$result = $this->openai_service->generate_alt_text( $image_url, $image_id );

				if ( $result['success'] ) {
					$this->image_scanner->update_scan_data( $image_id, [
						'ai_status' => 'completed',
						'recommended_alt_text' => $result['alt_text'],
					] );
				} else {
					$this->image_scanner->update_scan_data( $image_id, [
						'ai_status' => 'error',
						'error_message' => $result['error'] ?? 'Unknown error',
					] );
				}

				$results[] = [
					'image_id' => $image_id,
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
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function apply_alt_text( WP_REST_Request $request ) {
		try {
			$image_ids = $request->get_param( 'image_ids' );

			if ( empty( $image_ids ) || ! is_array( $image_ids ) ) {
				return $this->create_error_response( 'Invalid image IDs', 'invalid_image_ids', 400 );
			}

			$results = [];
			foreach ( $image_ids as $image_id ) {
				$scan_data = $this->image_scanner->get_scan_data( $image_id );

				if ( empty( $scan_data['recommended_alt_text'] ) ) {
					$results[] = [
						'image_id' => $image_id,
						'success' => false,
						'error' => 'No recommended alt text found',
					];
					continue;
				}

				// Apply alt text.
				update_post_meta( $image_id, '_wp_attachment_image_alt', $scan_data['recommended_alt_text'] );

				// Mark as applied.
				$this->image_scanner->update_scan_data( $image_id, [
					'applied' => true,
				] );

				$results[] = [
					'image_id' => $image_id,
					'success' => true,
					'alt_text' => $scan_data['recommended_alt_text'],
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
			$image_ids = $request->get_param( 'image_ids' );
			$batch_size = $request->get_param( 'batch_size' );

			if ( empty( $image_ids ) || ! is_array( $image_ids ) ) {
				return $this->create_error_response( 'Invalid image IDs', 'invalid_image_ids', 400 );
			}

			$jobs_scheduled = $this->async_job_service->schedule_alt_text_generation( $image_ids, $batch_size );

			return $this->create_success_response( [
				'jobs_scheduled' => $jobs_scheduled,
				'message' => 'Batch alt text generation scheduled',
			], 'Batch generation scheduled' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to schedule batch generation: ' . $e->getMessage() );
		}
	}
}

