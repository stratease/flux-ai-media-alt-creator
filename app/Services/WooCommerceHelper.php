<?php
/**
 * WooCommerce detection and product-image helpers.
 *
 * @package FluxAIMediaAltCreator
 * @since 3.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper for WooCommerce presence and product image usage.
 *
 * @since 3.0.0
 */
class WooCommerceHelper {
	/**
	 * Whether WooCommerce is active.
	 *
	 * @since 3.0.0
	 * @return bool True if WooCommerce is active.
	 */
	public static function is_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check if an attachment is used as a WooCommerce product image.
	 *
	 * Considered used if: (a) featured image of any product/variation (_thumbnail_id),
	 * or (b) in any product gallery (_product_image_gallery, comma-separated IDs).
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return bool True if the attachment is used by a WooCommerce product/variation.
	 */
	public static function is_attachment_used_by_product( $attachment_id ) {
		if ( ! self::is_active() ) {
			return false;
		}

		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return false;
		}

		global $wpdb;

		// Featured image: _thumbnail_id equals attachment ID on product/product_variation posts.
		$as_thumbnail = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT pm.post_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_thumbnail_id'
				   AND pm.meta_value = %s
				   AND p.post_type IN ('product', 'product_variation')
				   AND p.post_status != 'trash'
				 LIMIT 1",
				(string) $attachment_id
			)
		);
		if ( $as_thumbnail ) {
			return true;
		}

		// Gallery: _product_image_gallery contains attachment ID (comma-separated list) on product posts.
		$in_gallery = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT pm.post_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_product_image_gallery'
				   AND p.post_type = 'product'
				   AND p.post_status != 'trash'
				   AND (
					pm.meta_value = %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
				   )
				 LIMIT 1",
				(string) $attachment_id,
				$attachment_id . ',%',
				'%,' . $attachment_id . ',%',
				'%,' . $attachment_id,
				$attachment_id . ', %',
				'%, ' . $attachment_id . ',%',
				'%, ' . $attachment_id
			)
		);

		return (bool) $in_gallery;
	}

	/**
	 * Get all attachment IDs used as WooCommerce product images (featured, gallery, variation).
	 *
	 * @since 3.0.0
	 * @return int[] Attachment IDs.
	 */
	public static function get_product_attachment_ids() {
		if ( ! self::is_active() ) {
			return [];
		}

		global $wpdb;
		$ids = [];

		// Featured images from product + variation posts only.
		$thumb_ids = $wpdb->get_col(
			"SELECT DISTINCT pm.meta_value
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_thumbnail_id'
			   AND pm.meta_value != ''
			   AND p.post_type IN ('product', 'product_variation')
			   AND p.post_status != 'trash'"
		);
		if ( is_array( $thumb_ids ) ) {
			foreach ( $thumb_ids as $id ) {
				$ids[] = absint( $id );
			}
		}

		// Gallery images from product posts only.
		$galleries = $wpdb->get_col(
			"SELECT pm.meta_value
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_product_image_gallery'
			   AND pm.meta_value != ''
			   AND p.post_type = 'product'
			   AND p.post_status != 'trash'"
		);
		if ( is_array( $galleries ) ) {
			foreach ( $galleries as $gallery ) {
				$parts = array_map( 'absint', array_filter( explode( ',', $gallery ) ) );
				$ids   = array_merge( $ids, $parts );
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Get product/variation post IDs that reference an attachment as featured or gallery image.
	 *
	 * @since 3.1.2
	 * @param int $attachment_id Attachment ID.
	 * @return int[] Product/variation post IDs.
	 */
	public static function get_products_for_attachment( $attachment_id ) {
		if ( ! self::is_active() ) {
			return [];
		}

		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return [];
		}

		global $wpdb;
		$ids = [];

		$thumb_products = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.post_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_thumbnail_id'
				   AND pm.meta_value = %s
				   AND p.post_type IN ('product', 'product_variation')
				   AND p.post_status != 'trash'",
				(string) $attachment_id
			)
		);
		if ( is_array( $thumb_products ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $thumb_products ) );
		}

		$gallery_products = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.post_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_product_image_gallery'
				   AND p.post_type = 'product'
				   AND p.post_status != 'trash'
				   AND (
					pm.meta_value = %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
					OR pm.meta_value LIKE %s
				   )",
				(string) $attachment_id,
				$attachment_id . ',%',
				'%,' . $attachment_id . ',%',
				'%,' . $attachment_id,
				$attachment_id . ', %',
				'%, ' . $attachment_id . ',%',
				'%, ' . $attachment_id
			)
		);
		if ( is_array( $gallery_products ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $gallery_products ) );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}
}
