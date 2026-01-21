<?php
/**
 * Media scanner service for finding media files without alt text.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace FluxAIMediaAltCreator\App\Services;

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;

/**
 * Service to scan WordPress media library for media files without alt text.
 *
 * @since 1.0.0
 */
class MediaScanner {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var MediaScanner|null
	 */
	private static $instance = null;

	/**
	 * Meta key for scan status.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	const SCAN_STATUS_META_KEY = '_flux_ai_alt_creator_scan_status';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Changed to private constructor for singleton pattern.
	 */
	private function __construct() {
		// Private constructor for singleton pattern.
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 1.1.0
	 * @return MediaScanner Singleton instance.
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Scan for media files without alt text.
	 *
	 * @since 1.0.0
	 * @param int    $page Page number (1-based).
	 * @param int    $per_page Number of items per page.
	 * @param string $search Search term (optional).
	 * @param array  $additional_params Additional search parameters (optional).
	 * @return array Array with 'data', 'total', 'page', 'per_page', 'total_pages'.
	 */
	public function scan_media_without_alt( $page = 1, $per_page = 20, $search = '', $additional_params = [] ) {
		/**
		 * Fires before scanning for media files.
		 *
		 * @since 1.0.0
		 * @param int    $page Page number.
		 * @param int    $per_page Items per page.
		 * @param string $search Search term.
		 * @param array  $additional_params Additional search parameters.
		 */
		do_action( 'flux_ai_alt_creator/media_scanner/scan/before', $page, $per_page, $search, $additional_params );

		// Get default MIME types through hook (images only by default).
		/**
		 * Filter to get default MIME types for media search.
		 *
		 * By default, this returns image MIME types only. Other plugins can
		 * extend this to include additional media types. The filter receives
		 * an empty array and should return an array of MIME types.
		 *
		 * @since 1.0.0
		 * @param array $default_mime_types Default MIME types (empty by default, images added via hook).
		 * @param array $additional_params Additional search parameters from request.
		 * @return array Array of default MIME types.
		 */
		$default_mime_types = apply_filters( 'flux_ai_alt_creator/media_scanner/get_default_mime_types', self::get_default_image_mime_types(), $additional_params );

		/**
		 * Filter the MIME types to search for.
		 *
		 * Allows other plugins to extend the search to include additional media types
		 * (e.g., videos, PDFs, etc.). By default, only image types are included.
		 *
		 * @since 1.0.0
		 * @param array  $mime_types Array of MIME types to search for.
		 * @param array  $additional_params Additional search parameters from request.
		 * @return array Filtered array of MIME types.
		 */
		$mime_types = apply_filters( 'flux_ai_alt_creator/media_scanner/scan/search_mime_types', $default_mime_types, $additional_params );

		// Handle media type groups from filters (e.g., ['images', 'videos']).
		if ( ! empty( $additional_params['media_types'] ) && is_array( $additional_params['media_types'] ) ) {
			$selected_mime_types = [];
			
			/**
			 * Filter to get MIME types for a specific media type group.
			 *
			 * @since 1.0.0
			 * @param array  $mime_types Array of MIME types for the group.
			 * @param string $media_type Media type group name (e.g., 'images', 'videos').
			 * @return array Array of MIME types for the group.
			 */
			foreach ( $additional_params['media_types'] as $media_type ) {
				$group_mime_types = apply_filters( 'flux_ai_alt_creator/media_scanner/scan/media_type_mime_types', [], $media_type );
				
				// If no MIME types from hook, try to get from media type groups.
				if ( empty( $group_mime_types ) ) {
					$media_type_groups = $this->get_media_type_groups();
					if ( isset( $media_type_groups[ $media_type ] ) && isset( $media_type_groups[ $media_type ]['mime_types'] ) ) {
						$group_mime_types = $media_type_groups[ $media_type ]['mime_types'];
					}
				}
				
				if ( ! empty( $group_mime_types ) && is_array( $group_mime_types ) ) {
					$selected_mime_types = array_merge( $selected_mime_types, $group_mime_types );
				}
			}
			
			// If media types are specified, use them (OR search).
			if ( ! empty( $selected_mime_types ) ) {
				$mime_types = array_unique( $selected_mime_types );
			}
		}

		// Build query arguments.
		$query_args = [
			'post_type' => 'attachment',
			'post_mime_type' => $mime_types,
			'post_status' => 'inherit',
			'posts_per_page' => $per_page,
			'paged' => $page,
			'orderby' => 'date',
			'order' => 'DESC',
			'meta_query' => [
				'relation' => 'OR',
				[
					'key' => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				],
				[
					'key' => '_wp_attachment_image_alt',
					'value' => '',
					'compare' => '=',
				],
			],
		];

		// Add search if provided.
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		/**
		 * Filter additional query parameters before merging.
		 *
		 * Allows other plugins to add custom query parameters (e.g., date ranges,
		 * custom meta queries, taxonomy filters, etc.).
		 *
		 * @since 1.0.0
		 * @param array  $additional_query_args Additional WP_Query arguments to merge.
		 * @param array  $additional_params Additional search parameters from request.
		 * @param int    $page Page number.
		 * @param int    $per_page Items per page.
		 * @param string $search Search term.
		 * @return array Additional query arguments to merge into main query.
		 */
		$additional_query_args = apply_filters( 'flux_ai_alt_creator/media_scanner/scan/additional_query_args', [], $additional_params, $page, $per_page, $search );

		// Merge additional query arguments.
		if ( ! empty( $additional_query_args ) && is_array( $additional_query_args ) ) {
			// Handle meta_query specially to avoid recursive merge issues.
			if ( isset( $additional_query_args['meta_query'] ) && isset( $query_args['meta_query'] ) ) {
				// Merge meta_query arrays properly.
				$query_args['meta_query'] = array_merge( $query_args['meta_query'], $additional_query_args['meta_query'] );
				unset( $additional_query_args['meta_query'] );
			}
			
			// Merge remaining arguments.
			$query_args = array_merge( $query_args, $additional_query_args );
		}

		/**
		 * Filter the complete query arguments for media scanning.
		 *
		 * This is the final filter before executing the query. Use this to modify
		 * any aspect of the query, including meta_query, tax_query, date_query, etc.
		 *
		 * @since 1.0.0
		 * @param array  $query_args WP_Query arguments.
		 * @param int    $page Page number.
		 * @param int    $per_page Items per page.
		 * @param string $search Search term.
		 * @param array  $additional_params Additional search parameters from request.
		 * @return array Filtered WP_Query arguments.
		 */
		$query_args = apply_filters( 'flux_ai_alt_creator/media_scanner/scan/query_args', $query_args, $page, $per_page, $search, $additional_params );

		$query = new \WP_Query( $query_args );

		$media_files = [];
		
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$media_data = $this->prepare_media_data( $post );
				
				/**
				 * Filter media data before adding to results.
				 *
				 * @since 1.0.0
				 * @param array $media_data Media data array.
				 * @param int   $post_id Post ID.
				 */
				$media_data = apply_filters( 'flux_ai_alt_creator/media_scanner/scan/media_data', $media_data, $post->ID );
				
				$media_files[] = $media_data;
			}
		}

		$total = $query->found_posts;
		$total_pages = (int) ceil( $total / $per_page );

		/**
		 * Fires after scanning for media files.
		 *
		 * @since 1.0.0
		 * @param array $media_files Scanned media files.
		 * @param int   $total Total count.
		 * @param array $query_args Query arguments used.
		 */
		do_action( 'flux_ai_alt_creator/media_scanner/scan/after', $media_files, $total, $query_args );

		return [
			'data' => $media_files,
			'total' => $total,
			'page' => $page,
			'per_page' => $per_page,
			'total_pages' => $total_pages,
		];
	}

	/**
	 * Prepare media data for response.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Changed from 'ai_status' to 'scan_status' using dedicated meta field.
	 * @param \WP_Post $post Post object.
	 * @return array Media data.
	 */
	private function prepare_media_data( $post ) {
		$attachment_id = $post->ID;
		
		// Get scan data from post meta.
		$scan_data = get_post_meta( $attachment_id, '_flux_ai_alt_creator_scan_data', true );
		if ( ! is_array( $scan_data ) ) {
			$scan_data = [];
		}
		
		// Get media URLs.
		$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		$full_url = wp_get_attachment_url( $attachment_id );
		
		// Get MIME type.
		$mime_type = get_post_mime_type( $attachment_id );
		
		return [
			'id' => $attachment_id,
			'title' => $post->post_title,
			'filename' => basename( get_attached_file( $attachment_id ) ),
			'mime_type' => $mime_type ? $mime_type : '',
			'thumbnail_url' => $thumbnail_url ? $thumbnail_url : '',
			'full_url' => $full_url ? $full_url : '',
			'edit_url' => admin_url( "post.php?post={$attachment_id}&action=edit" ),
			'scan_status' => $this->get_scan_status( $attachment_id ),
			'recommended_alt_text' => $scan_data['recommended_alt_text'] ?? '',
			'applied' => $scan_data['applied'] ?? false,
			'error_message' => $scan_data['error_message'] ?? '',
			'scan_date' => $scan_data['scan_date'] ?? null,
		];
	}

	/**
	 * Get scan status for a specific media file.
	 *
	 * @since 1.1.0
	 * @param int $attachment_id Attachment ID.
	 * @return string Scan status. Default 'pending'.
	 */
	public function get_scan_status( $attachment_id ) {
		$status = get_post_meta( $attachment_id, self::SCAN_STATUS_META_KEY, true );
		if ( empty( $status ) || ! is_string( $status ) ) {
			return 'pending';
		}
		return $status;
	}

	/**
	 * Update scan status for a specific media file.
	 *
	 * @since 1.1.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $status Scan status.
	 * @return void
	 */
	public function update_scan_status( $attachment_id, $status ) {
		update_post_meta( $attachment_id, self::SCAN_STATUS_META_KEY, sanitize_text_field( $status ) );
	}

	/**
	 * Get scan data for a specific media file.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Updated to use separate scan_status meta field.
	 * @param int $attachment_id Attachment ID.
	 * @return array Scan data.
	 */
	public function get_scan_data( $attachment_id ) {
		$scan_data = get_post_meta( $attachment_id, '_flux_ai_alt_creator_scan_data', true );
		
		if ( ! is_array( $scan_data ) ) {
			$scan_data = [];
		}
		
		// Always include scan status from separate meta field.
		$scan_data['scan_status'] = $this->get_scan_status( $attachment_id );
		
		// Set defaults for missing fields.
		$scan_data = array_merge( [
			'recommended_alt_text' => '',
			'applied' => false,
			'error_message' => '',
			'scan_date' => null,
		], $scan_data );
		
		return $scan_data;
	}

	/**
	 * Update scan data for a specific media file.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Updated to handle scan_status separately from scan_data.
	 * @param int   $attachment_id Attachment ID.
	 * @param array $scan_data Scan data to update.
	 * @return void
	 */
	public function update_scan_data( $attachment_id, $scan_data ) {
		$existing_data = $this->get_scan_data( $attachment_id );
		
		// Extract scan_status if present and update separately.
		if ( isset( $scan_data['scan_status'] ) ) {
			$this->update_scan_status( $attachment_id, $scan_data['scan_status'] );
			unset( $scan_data['scan_status'] ); // Remove from scan_data array.
		}
		
		$updated_data = array_merge( $existing_data, $scan_data );
		// Don't overwrite scan_status in scan_data, it comes from separate meta field.
		unset( $updated_data['scan_status'] );
		$updated_data['scan_date'] = current_time( 'mysql' );
		
		update_post_meta( $attachment_id, '_flux_ai_alt_creator_scan_data', $updated_data );
	}

	/**
	 * Get default image MIME types.
	 *
	 * This method provides image MIME types as a fallback when no default
	 * MIME types are provided via the flux_ai_alt_creator/media_scanner/get_default_mime_types hook.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Changed to public static method.
	 * @return array Array of image MIME types.
	 */
	public static function get_default_image_mime_types() {
		return [
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/gif',
			'image/webp',
			'image/avif',
			'image/svg+xml',
			'image/bmp',
			'image/tiff',
			'image/x-icon',
		];
	}

	/**
	 * Get available media type groups.
	 *
	 * Returns a list of media type groups that can be used for filtering.
	 * Other plugins can extend this via the flux_ai_alt_creator/media_scanner/get_media_type_groups hook.
	 *
	 * @since 1.0.0
	 * @return array Array of media type groups with labels.
	 */
	public function get_media_type_groups() {
		$default_groups = [
			'images' => [
				'label' => __( 'Images', 'flux-ai-media-alt-creator' ),
				'mime_types' => self::get_default_image_mime_types(),
			],
		];

		/**
		 * Filter to add additional media type groups.
		 *
		 * @since 1.0.0
		 * @param array $groups Array of media type groups.
		 * @return array Filtered array of media type groups.
		 */
		return apply_filters( 'flux_ai_alt_creator/media_scanner/get_media_type_groups', $default_groups );
	}
}

