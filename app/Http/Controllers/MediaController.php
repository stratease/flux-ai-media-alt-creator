<?php
/**
 * Media REST API controller for Flux AI Alt Text & Accessibility Audit plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\MediaScanner;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles media REST API endpoints.
 *
 * @since 1.0.0
 */
class MediaController extends BaseController {

	/**
	 * Media scanner instance.
	 *
	 * @since 1.0.0
	 * @var MediaScanner
	 */
	private $media_scanner;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Removed Logger parameter, now uses Logger::get_instance() directly via BaseController.
	 * @param MediaScanner $media_scanner Media scanner instance.
	 */
	public function __construct( MediaScanner $media_scanner ) {
		$this->media_scanner = $media_scanner;
		parent::__construct();
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'flux-ai-media-alt-creator/v1', '/media', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_media' ],
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
					'filters' => [
						'default' => '',
						'type' => 'string',
						'description' => 'Additional search filters as JSON string (e.g., {"media_types":["images","videos"],"date_from":"2024-01-01"})',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/media/(?P<id>\d+)', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_media_item' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args' => [
					'id' => [
						'required' => true,
						'sanitize_callback' => 'absint',
					],
				],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/media/scan', [
			[
				'methods' => 'POST',
				'callback' => [ $this, 'trigger_scan' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/media/type-groups', [
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_media_type_groups' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );
	}

	/**
	 * Get paginated list of media files.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_media( WP_REST_Request $request ) {
		try {
			$page = $request->get_param( 'page' );
			$per_page = $request->get_param( 'per_page' );
			$search = $request->get_param( 'search' );
			$filters_param = $request->get_param( 'filters' );

			// Parse filters from JSON string if provided.
			$filters = [];
			if ( ! empty( $filters_param ) ) {
				if ( is_string( $filters_param ) ) {
					$decoded = json_decode( $filters_param, true );
					if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
						$filters = $decoded;
					}
				} elseif ( is_array( $filters_param ) ) {
					$filters = $filters_param;
				}
			}

			// Sanitize filters if provided.
			if ( ! empty( $filters ) && is_array( $filters ) ) {
				$filters = $this->sanitize_filters( $filters );
			} else {
				$filters = [];
			}

			$result = $this->media_scanner->scan_media( $page, $per_page, $search, $filters );

			return $this->create_success_response( $result, 'Media files retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve media files: ' . $e->getMessage() );
		}
	}

	/**
	 * Sanitize additional search filters.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added alt_category and woocommerce_only for compliance filtering.
	 * @param array $filters Raw filters from request.
	 * @return array Sanitized filters.
	 */
	private function sanitize_filters( $filters ) {
		$sanitized = [];

		// Sanitize media_types array if provided.
		if ( isset( $filters['media_types'] ) && is_array( $filters['media_types'] ) ) {
			$sanitized['media_types'] = array_map( 'sanitize_text_field', $filters['media_types'] );
		}

		// Compliance alt category filter (all, missing, placeholder, duplicate, descriptive, contextual, decorative, woocommerce).
		$allowed_categories = [ 'all', 'missing', 'placeholder', 'duplicate', 'descriptive', 'contextual', 'decorative', 'woocommerce' ];
		if ( isset( $filters['alt_category'] ) && in_array( sanitize_text_field( $filters['alt_category'] ), $allowed_categories, true ) ) {
			$sanitized['alt_category'] = sanitize_text_field( $filters['alt_category'] );
		}

		// Restrict to WooCommerce product images only.
		if ( isset( $filters['woocommerce_only'] ) ) {
			$sanitized['woocommerce_only'] = (bool) $filters['woocommerce_only'];
		}

		// Allow plugins to define their own filter sanitization.
		/**
		 * Filter to sanitize additional search filters.
		 *
		 * Plugins can use this hook to sanitize their custom filter parameters.
		 *
		 * @since 1.0.0
		 * @param array $sanitized Sanitized filters array.
		 * @param array $filters Raw filters from request.
		 * @return array Sanitized filters.
		 */
		$sanitized = apply_filters( 'flux_ai_alt_creator_sanitize_filters', $sanitized, $filters );

		return $sanitized;
	}

	/**
	 * Get single media file details.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Changed from 'ai_status' to 'scan_status' using dedicated meta field and get_scan_status() method.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_media_item( WP_REST_Request $request ) {
		try {
			$media_id = $request->get_param( 'id' );

			$scan_data = $this->media_scanner->get_scan_data( $media_id );

			$media = get_post( $media_id );
			if ( ! $media || 'attachment' !== $media->post_type ) {
				return $this->create_error_response( 'Media file not found', 'media_not_found', 404 );
			}

			$thumbnail_url = wp_get_attachment_image_url( $media_id, 'thumbnail' );
			$full_url = wp_get_attachment_url( $media_id );
			$mime_type = get_post_mime_type( $media_id );

			$media_data = [
				'id' => $media_id,
				'title' => $media->post_title,
				'filename' => basename( get_attached_file( $media_id ) ),
				'mime_type' => $mime_type ? $mime_type : '',
				'thumbnail_url' => $thumbnail_url ? $thumbnail_url : '',
				'full_url' => $full_url ? $full_url : '',
				'edit_url' => admin_url( "post.php?post={$media_id}&action=edit" ),
				'scan_status' => $this->media_scanner->get_scan_status( $media_id ),
				'recommended_alt_text' => $scan_data['recommended_alt_text'] ?? '',
				'applied' => $scan_data['applied'] ?? false,
				'error_message' => $scan_data['error_message'] ?? '',
				'scan_date' => $scan_data['scan_date'] ?? null,
			];

			return $this->create_success_response( $media_data, 'Media file retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve media file: ' . $e->getMessage() );
		}
	}

	/**
	 * Get available media type groups.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_media_type_groups( WP_REST_Request $request ) {
		try {
			$groups = $this->media_scanner->get_media_type_groups();
			
			// Format for frontend consumption.
			$formatted_groups = [];
			foreach ( $groups as $key => $group ) {
				$formatted_groups[] = [
					'id' => $key,
					'label' => $group['label'] ?? $key,
					'mime_types' => $group['mime_types'] ?? [],
				];
			}

			return $this->create_success_response( $formatted_groups, 'Media type groups retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve media type groups: ' . $e->getMessage() );
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
			// Scan is performed on-demand when get_media is called.
			// This endpoint exists for future extensibility.
			return $this->create_success_response( null, 'Scan triggered successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to trigger scan: ' . $e->getMessage() );
		}
	}
}

