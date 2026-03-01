<?php
/**
 * Compliance REST API controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 3.0.0
 */

namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\App\Services\ComplianceScanService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles compliance audit REST API endpoints.
 *
 * @since 3.0.0
 */
class ComplianceController extends BaseController {

	/** Sentinel value for alt_category: re-evaluate from current alt instead of setting. */
	const RECLASSIFY = 'reclassify';

	/**
	 * Compliance scan service instance.
	 *
	 * @since 3.0.0
	 * @var ComplianceScanService
	 */
	private $compliance_scan_service;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @param ComplianceScanService $compliance_scan_service Compliance scan service.
	 */
	public function __construct( ComplianceScanService $compliance_scan_service ) {
		$this->compliance_scan_service = $compliance_scan_service;
		parent::__construct();
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'flux-ai-media-alt-creator/v1', '/compliance/summary', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_summary' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/compliance/scan', [
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_scan' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			],
		] );

		register_rest_route( 'flux-ai-media-alt-creator/v1', '/compliance/set-category', [
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'set_category' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'media_ids'    => [
						'required' => true,
						'type'     => 'array',
						'items'    => [ 'type' => 'integer' ],
					],
					'alt_category' => [
						'required' => false,
						'type'     => 'string',
						'default'  => '',
					],
				],
			],
		] );
	}

	/**
	 * Get compliance summary (coverage, high risk, by category, last scan timestamp).
	 *
	 * @since 3.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_summary( WP_REST_Request $request ) {
		try {
			$summary = $this->compliance_scan_service->get_summary();
			return $this->create_success_response( $summary, 'Compliance summary retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to retrieve compliance summary: ' . $e->getMessage() );
		}
	}

	/**
	 * Run full compliance scan (on-demand, synchronous).
	 *
	 * @since 3.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function run_scan( WP_REST_Request $request ) {
		try {
			$result = $this->compliance_scan_service->run_full_scan_sync();
			$summary = $this->compliance_scan_service->get_summary();
			$data = array_merge( $result, [ 'summary' => $summary ] );
			return $this->create_success_response( $data, 'Compliance scan completed successfully' );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to run compliance scan: ' . $e->getMessage() );
		}
	}

	/**
	 * Set alt category for given media, or reclassify from current alt.
	 *
	 * Explicit set: pass alt_category as a valid slug (e.g. 'decorative', 'missing'). Setting
	 * 'decorative' clears alt text; other categories only update stored category meta.
	 * Reclassify: pass alt_category as empty string or 'reclassify' to evaluate from current alt and update category.
	 *
	 * @since 3.0.0
	 * @param WP_REST_Request $request Request object (media_ids, alt_category).
	 * @return WP_REST_Response Response object.
	 */
	public function set_category( WP_REST_Request $request ) {
		try {
			$media_ids = $request->get_param( 'media_ids' );
			if ( empty( $media_ids ) || ! is_array( $media_ids ) ) {
				return $this->create_error_response( __( 'Invalid media IDs', 'flux-ai-media-alt-creator' ), 'invalid_media_ids', 400 );
			}
			$media_ids = array_map( 'absint', $media_ids );
			$media_ids = array_values( array_filter( $media_ids ) );
			if ( empty( $media_ids ) ) {
				return $this->create_error_response( __( 'Invalid media IDs', 'flux-ai-media-alt-creator' ), 'invalid_media_ids', 400 );
			}
			$alt_category = $request->get_param( 'alt_category' );
			$alt_category = is_string( $alt_category ) ? trim( $alt_category ) : '';

			// Reclassify: empty or sentinel → re-evaluate from current alt.
			if ( $alt_category === '' || $alt_category === self::RECLASSIFY ) {
				$updated = $this->compliance_scan_service->reclassify_attachments( $media_ids );
				return $this->create_success_response( [ 'updated' => $updated ], __( 'Category updated.', 'flux-ai-media-alt-creator' ) );
			}

			// Explicit set: valid category takes precedence.
			if ( in_array( $alt_category, ComplianceScanService::VALID_ALT_CATEGORIES, true ) ) {
				$updated = [];
				foreach ( $media_ids as $id ) {
					if ( $this->compliance_scan_service->set_attachment_category( $id, $alt_category ) ) {
						$updated[] = [ 'id' => $id, 'alt_category' => $alt_category ];
					}
				}
				return $this->create_success_response( [ 'updated' => $updated ], __( 'Category updated.', 'flux-ai-media-alt-creator' ) );
			}

			return $this->create_error_response( __( 'Invalid alt category.', 'flux-ai-media-alt-creator' ), 'invalid_alt_category', 400 );
		} catch ( \Exception $e ) {
			return $this->create_error_response( 'Failed to set category: ' . $e->getMessage() );
		}
	}
}
