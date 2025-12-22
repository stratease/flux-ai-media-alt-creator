<?php
/**
 * Image scanner service for finding images without alt text.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Service to scan WordPress media library for images without alt text.
 *
 * @since 1.0.0
 */
class ImageScanner {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Logger $logger Logger instance.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Scan for images without alt text.
	 *
	 * @since 1.0.0
	 * @param int $page Page number (1-based).
	 * @param int $per_page Number of items per page.
	 * @param string $search Search term (optional).
	 * @return array Array with 'data', 'total', 'page', 'per_page', 'total_pages'.
	 */
	public function scan_images_without_alt( $page = 1, $per_page = 20, $search = '' ) {
		/**
		 * Fires before scanning for images.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flux_ai_alt_creator_before_scan', $page, $per_page, $search );

		// Build query arguments.
		$query_args = [
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
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
		 * Filter the query arguments for image scanning.
		 *
		 * @since 1.0.0
		 * @param array  $query_args WP_Query arguments.
		 * @param int    $page Page number.
		 * @param int    $per_page Items per page.
		 * @param string $search Search term.
		 */
		$query_args = apply_filters( 'flux_ai_alt_creator_scan_query_args', $query_args, $page, $per_page, $search );

		$query = new \WP_Query( $query_args );

		$images = [];
		
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$image_data = $this->prepare_image_data( $post );
				
				/**
				 * Filter image data before adding to results.
				 *
				 * @since 1.0.0
				 * @param array $image_data Image data array.
				 * @param int   $post_id Post ID.
				 */
				$image_data = apply_filters( 'flux_ai_alt_creator_image_data', $image_data, $post->ID );
				
				$images[] = $image_data;
			}
		}

		$total = $query->found_posts;
		$total_pages = (int) ceil( $total / $per_page );

		/**
		 * Fires after scanning for images.
		 *
		 * @since 1.0.0
		 * @param array $images Scanned images.
		 * @param int   $total Total count.
		 */
		do_action( 'flux_ai_alt_creator_after_scan', $images, $total );

		return [
			'data' => $images,
			'total' => $total,
			'page' => $page,
			'per_page' => $per_page,
			'total_pages' => $total_pages,
		];
	}

	/**
	 * Prepare image data for response.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return array Image data.
	 */
	private function prepare_image_data( $post ) {
		$attachment_id = $post->ID;
		
		// Get scan data from post meta.
		$scan_data = get_post_meta( $attachment_id, '_flux_ai_alt_creator_scan_data', true );
		if ( ! is_array( $scan_data ) ) {
			$scan_data = [];
		}
		
		// Get image URLs.
		$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		$full_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		
		return [
			'id' => $attachment_id,
			'title' => $post->post_title,
			'filename' => basename( get_attached_file( $attachment_id ) ),
			'thumbnail_url' => $thumbnail_url ? $thumbnail_url : '',
			'full_url' => $full_url ? $full_url : '',
			'edit_url' => admin_url( "post.php?post={$attachment_id}&action=edit" ),
			'ai_status' => $scan_data['ai_status'] ?? 'pending',
			'recommended_alt_text' => $scan_data['recommended_alt_text'] ?? '',
			'applied' => $scan_data['applied'] ?? false,
			'error_message' => $scan_data['error_message'] ?? '',
			'scan_date' => $scan_data['scan_date'] ?? null,
		];
	}

	/**
	 * Get scan data for a specific image.
	 *
	 * @since 1.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return array Scan data.
	 */
	public function get_scan_data( $attachment_id ) {
		$scan_data = get_post_meta( $attachment_id, '_flux_ai_alt_creator_scan_data', true );
		
		if ( ! is_array( $scan_data ) ) {
			return [
				'ai_status' => 'pending',
				'recommended_alt_text' => '',
				'applied' => false,
				'error_message' => '',
				'scan_date' => null,
			];
		}
		
		return $scan_data;
	}

	/**
	 * Update scan data for a specific image.
	 *
	 * @since 1.0.0
	 * @param int   $attachment_id Attachment ID.
	 * @param array $scan_data Scan data to update.
	 * @return void
	 */
	public function update_scan_data( $attachment_id, $scan_data ) {
		$existing_data = $this->get_scan_data( $attachment_id );
		$updated_data = array_merge( $existing_data, $scan_data );
		$updated_data['scan_date'] = current_time( 'mysql' );
		
		update_post_meta( $attachment_id, '_flux_ai_alt_creator_scan_data', $updated_data );
	}
}

