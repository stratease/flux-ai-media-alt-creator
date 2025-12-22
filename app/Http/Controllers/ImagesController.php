<?php
/**
 * Images REST API controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\ImageScanner;
use FluxAIMediaAltCreator\App\Services\Logger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles images REST API endpoints.
 *
 * @since 1.0.0
 */
class ImagesController extends BaseController {

	/**
	 * Image scanner instance.
	 *
	 * @since 1.0.0
	 * @var ImageScanner
	 */
	private $image_scanner;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param ImageScanner $image_scanner Image scanner instance.
	 * @param Logger       $logger Logger instance.
	 */
	public function __construct( ImageScanner $image_scanner, Logger $logger ) {
		$this->image_scanner = $image_scanner;
		parent::__construct( $logger );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'flux-ai-media-alt-creator/v1', '/images', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_images' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'page' => [
						'default' => 1,
						'sanitize_callback' => 'absint',
					],
					'per_page' => [
						'default' => 20,
						'sanitize_callback' => 'absint',
					],
					'search' => [
						'default' => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/images/(?P<id>\d+)', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_image' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'id' => [
						'required' => true,
						'sanitize_callback' => 'absint',
					],
				],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/images/scan', [
			[
				'methods' => 'POST',
				'callback' => [ $this, 'trigger_scan' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );
	}

	/**
	 * Get paginated list of images without alt text.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_images( WP_REST_Request $request ) {
		try {
			$page = $request->get_param( 'page' );
			$per_page = $request->get_param( 'per_page' );
			$search = $request->get_param( 'search' );

			$result = $this->image_scanner->scan_images_without_alt( $page, $per_page, $search );

			return $this->create_success_response( $result, 'Images retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve images: ' . $e->getMessage() );
		}
	}

	/**
	 * Get single image details.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_image( WP_REST_Request $request ) {
		try {
			$image_id = $request->get_param( 'id' );

			$scan_data = $this->image_scanner->get_scan_data( $image_id );

			$image = get_post( $image_id );
			if ( ! $image || 'attachment' !== $image->post_type ) {
				return $this->create_error_response( 'Image not found', 'image_not_found', 404 );
			}

			$thumbnail_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
			$full_url = wp_get_attachment_image_url( $image_id, 'full' );

			$image_data = [
				'id' => $image_id,
				'title' => $image->post_title,
				'filename' => basename( get_attached_file( $image_id ) ),
				'thumbnail_url' => $thumbnail_url ? $thumbnail_url : '',
				'full_url' => $full_url ? $full_url : '',
				'edit_url' => admin_url( "post.php?post={$image_id}&action=edit" ),
				'ai_status' => $scan_data['ai_status'] ?? 'pending',
				'recommended_alt_text' => $scan_data['recommended_alt_text'] ?? '',
				'applied' => $scan_data['applied'] ?? false,
				'error_message' => $scan_data['error_message'] ?? '',
				'scan_date' => $scan_data['scan_date'] ?? null,
			];

			return $this->create_success_response( $image_data, 'Image retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve image: ' . $e->getMessage() );
		}
	}

	/**
	 * Trigger manual scan.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function trigger_scan( WP_REST_Request $request ) {
		try {
			// Scan is performed on-demand when get_images is called.
			// This endpoint exists for future extensibility.
			return $this->create_success_response( null, 'Scan triggered successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to trigger scan: ' . $e->getMessage() );
		}
	}
}

