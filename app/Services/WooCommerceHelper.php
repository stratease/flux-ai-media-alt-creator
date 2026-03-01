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
	 * Considered used if: (a) featured image of any product (_thumbnail_id),
	 * or (b) in any product gallery (_product_image_gallery, comma-separated IDs).
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return bool True if the attachment is used by a product.
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

		// Featured image: _thumbnail_id equals attachment ID.
		$as_thumbnail = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s LIMIT 1",
				$attachment_id
			)
		);
		if ( $as_thumbnail ) {
			return true;
		}

		// Gallery: _product_image_gallery contains attachment ID (comma-separated list).
		$pattern = '%' . $wpdb->esc_like( (string) $attachment_id ) . '%';
		// Match whole ID in list: ,123, or 123, or ,123 or exactly 123.
		$in_gallery = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_product_image_gallery' AND (meta_value = %s OR meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s) LIMIT 1",
				(string) $attachment_id,
				$attachment_id . ',%',
				'%,' . $attachment_id . ',%',
				'%,' . $attachment_id
			)
		);
		return (bool) $in_gallery;
	}

	/**
	 * Get all attachment IDs used as WooCommerce product images (featured or in gallery).
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

		// Featured images: _thumbnail_id values.
		$thumb_ids = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value != ''" );
		if ( is_array( $thumb_ids ) ) {
			foreach ( $thumb_ids as $id ) {
				$ids[] = absint( $id );
			}
		}

		// Gallery: _product_image_gallery comma-separated IDs.
		$galleries = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_image_gallery' AND meta_value != ''" );
		if ( is_array( $galleries ) ) {
			foreach ( $galleries as $gallery ) {
				$parts = array_map( 'absint', array_filter( explode( ',', $gallery ) ) );
				$ids   = array_merge( $ids, $parts );
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}
}
