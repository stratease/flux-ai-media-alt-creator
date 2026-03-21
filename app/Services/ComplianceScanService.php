<?php
/**
 * Compliance scan service: classifies alt text and stores category in attachment meta.
 *
 * @package FluxAIMediaAltCreator
 * @since 3.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service to run compliance scan batches and compute summary from meta.
 *
 * @since 3.0.0
 */
class ComplianceScanService {

	/**
	 * Meta key for alt category on attachments.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	const ALT_CATEGORY_META_KEY = '_flux_ai_alt_creator_alt_category';

	/**
	 * Option key for last scan timestamp (ISO or null).
	 *
	 * @since 3.0.0
	 * @var string
	 */
	const LAST_SCAN_OPTION = 'flux_ai_alt_creator_compliance_last_scan';

	/**
	 * Option key for scan offset (batch processing).
	 *
	 * @since 3.0.0
	 * @var string
	 */
	const SCAN_OFFSET_OPTION = 'flux_ai_alt_creator_compliance_scan_offset';

	/**
	 * Option key for alt text counts (for duplicate detection across batches).
	 *
	 * @since 3.0.0
	 * @var string
	 */
	const ALT_COUNTS_OPTION = 'flux_ai_alt_creator_compliance_alt_counts';

	/**
	 * Generic/placeholder alt words (lowercase).
	 *
	 * @since 3.0.0
	 * @var string[]
	 */
	const PLACEHOLDER_WORDS = [ 'image', 'photo', 'picture', 'logo', 'banner', 'spacer' ];

	/**
	 * Valid alt category slugs for explicit "set" (meta and any category-specific behavior like decorative).
	 *
	 * @since 3.0.0
	 * @var string[]
	 */
	const VALID_ALT_CATEGORIES = [ 'missing', 'placeholder', 'duplicate', 'descriptive', 'contextual', 'decorative' ];

	/**
	 * Singleton instance.
	 *
	 * @since 3.0.0
	 * @var ComplianceScanService|null
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	private function __construct() {}

	/**
	 * Get singleton instance.
	 *
	 * @since 3.0.0
	 * @return ComplianceScanService
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Run a full scan synchronously (all image attachments in one request).
	 *
	 * Builds global alt counts first so duplicate detection is accurate. Use this for the on-demand "Run Compliance Scan" button.
	 *
	 * @since 3.0.0
	 * @return array{ processed: int } Total number of attachments processed.
	 */
	public function run_full_scan_sync() {
		$mime_types = MediaScanner::get_default_image_mime_types();
		$query      = new \WP_Query( [
			'post_type'      => 'attachment',
			'post_mime_type' => $mime_types,
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		] );
		$ids = $query->posts;
		if ( empty( $ids ) ) {
			update_option( self::LAST_SCAN_OPTION, current_time( 'c' ) );
			return [ 'processed' => 0 ];
		}
		$alt_counts = [];
		foreach ( $ids as $attachment_id ) {
			$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$alt = is_string( $alt ) ? trim( $alt ) : '';
			$normalized_alt = $this->normalize_alt_for_duplicate( $alt );
			if ( $normalized_alt !== '' ) {
				$alt_counts[ $normalized_alt ] = isset( $alt_counts[ $normalized_alt ] ) ? $alt_counts[ $normalized_alt ] + 1 : 1;
			}
		}
		foreach ( $ids as $attachment_id ) {
			$alt      = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$alt      = is_string( $alt ) ? trim( $alt ) : '';
			$existing = get_post_meta( $attachment_id, self::ALT_CATEGORY_META_KEY, true );
			$category = $this->classify( $attachment_id, $alt, $existing, $alt_counts );
			update_post_meta( $attachment_id, self::ALT_CATEGORY_META_KEY, $category );
		}
		update_option( self::LAST_SCAN_OPTION, current_time( 'c' ) );
		return [ 'processed' => count( $ids ) ];
	}

	/**
	 * Run one batch of compliance scan: fetch up to $batch_size image attachments, classify, save meta.
	 *
	 * Uses stored offset and alt_counts for duplicate detection across batches.
	 * When a full scan is started (offset 0), alt_counts are reset.
	 * Fired by action flux_ai_alt_creator/compliance/run_scan_batch so it can be invoked externally.
	 *
	 * @since 3.0.0
	 * @param int $batch_size Number of attachments to process in this batch.
	 * @return array{ processed: int, done: bool } Processed count and whether scan is complete (no more items).
	 */
	public function run_scan_batch( $batch_size = 500 ) {
		$batch_size = max( 1, absint( $batch_size ) );
		$offset     = (int) get_option( self::SCAN_OFFSET_OPTION, 0 );
		$alt_counts = get_option( self::ALT_COUNTS_OPTION, [] );
		if ( ! is_array( $alt_counts ) ) {
			$alt_counts = [];
		}
		if ( $offset === 0 ) {
			$alt_counts = [];
		}

		$mime_types = MediaScanner::get_default_image_mime_types();
		$query     = new \WP_Query( [
			'post_type'      => 'attachment',
			'post_mime_type' => $mime_types,
			'post_status'    => 'inherit',
			'posts_per_page' => $batch_size,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		] );
		$ids = $query->posts;
		if ( empty( $ids ) ) {
			delete_option( self::SCAN_OFFSET_OPTION );
			delete_option( self::ALT_COUNTS_OPTION );
			update_option( self::LAST_SCAN_OPTION, current_time( 'c' ) );
			return [ 'processed' => 0, 'done' => true ];
		}

		foreach ( $ids as $attachment_id ) {
			$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$alt = is_string( $alt ) ? trim( $alt ) : '';
			$normalized_alt = $this->normalize_alt_for_duplicate( $alt );
			if ( $normalized_alt !== '' ) {
				$alt_counts[ $normalized_alt ] = isset( $alt_counts[ $normalized_alt ] ) ? $alt_counts[ $normalized_alt ] + 1 : 1;
			}
		}
		update_option( self::ALT_COUNTS_OPTION, $alt_counts );

		foreach ( $ids as $attachment_id ) {
			$alt   = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$alt   = is_string( $alt ) ? trim( $alt ) : '';
			$existing = get_post_meta( $attachment_id, self::ALT_CATEGORY_META_KEY, true );
			$category = $this->classify( $attachment_id, $alt, $existing, $alt_counts );
			update_post_meta( $attachment_id, self::ALT_CATEGORY_META_KEY, $category );
		}

		$new_offset = $offset + count( $ids );
		update_option( self::SCAN_OFFSET_OPTION, $new_offset );
		$done = count( $ids ) < $batch_size;
		if ( $done ) {
			delete_option( self::SCAN_OFFSET_OPTION );
			delete_option( self::ALT_COUNTS_OPTION );
			update_option( self::LAST_SCAN_OPTION, current_time( 'c' ) );
		}

		return [ 'processed' => count( $ids ), 'done' => $done ];
	}

	/**
	 * Normalize alt for duplicate key (trim, lowercase, collapse whitespace).
	 *
	 * @since 3.0.0
	 * @param string $alt Alt text.
	 * @return string
	 */
	private function normalize_alt_for_duplicate( $alt ) {
		$alt = preg_replace( '/\s+/', ' ', trim( $alt ) );
		return $alt === '' ? '' : mb_strtolower( $alt );
	}

	/**
	 * Classify an attachment into one category.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $alt           Current alt text (trimmed).
	 * @param string $existing_category Existing stored category (e.g. decorative).
	 * @param array  $alt_counts    Map of normalized_alt => count (for duplicate).
	 * @return string Category slug.
	 */
	public function classify( $attachment_id, $alt, $existing_category, array $alt_counts = [] ) {
		if ( $existing_category === 'decorative' ) {
			return 'decorative';
		}
		if ( $alt === '' ) {
			return 'missing';
		}
		$normalized = $this->normalize_alt_for_duplicate( $alt );
		if ( isset( $alt_counts[ $normalized ] ) && $alt_counts[ $normalized ] >= 3 ) {
			return 'duplicate';
		}
		if ( $this->is_placeholder( $alt ) ) {
			return 'placeholder';
		}
		$word_count = str_word_count( $alt );
		if ( $word_count < 4 ) {
			return 'placeholder';
		}
		$parent_context = $this->get_parent_context( $attachment_id );
		if ( $parent_context !== '' && $this->alt_contains_context( $alt, $parent_context ) ) {
			return 'contextual';
		}
		return 'descriptive';
	}

	/**
	 * Check if alt is placeholder (generic words, filename patterns, or very short).
	 *
	 * @since 3.0.0
	 * @param string $alt Alt text.
	 * @return bool
	 */
	private function is_placeholder( $alt ) {
		$lower = mb_strtolower( $alt );
		foreach ( self::PLACEHOLDER_WORDS as $word ) {
			if ( $lower === $word ) {
				return true;
			}
		}
		// Filename patterns: .jpg, .png, .webp, IMG_, DSC_.
		if ( preg_match( '/\.(jpg|jpeg|png|gif|webp|avif|bmp|tiff?)\b/i', $alt ) ) {
			return true;
		}
		if ( preg_match( '/^(IMG_|DSC_)/i', $alt ) ) {
			return true;
		}
		return str_word_count( $alt ) < 3;
	}

	/**
	 * Get parent context string (post title, or product title + attributes) for contextual check.
	 *
	 * @since 3.0.0
	 * @param int $attachment_id Attachment ID.
	 * @return string Context string (e.g. post title or product name).
	 */
	private function get_parent_context( $attachment_id ) {
		$post   = get_post( $attachment_id );
		$parent = null;

		if ( $post && ! empty( $post->post_parent ) ) {
			$parent = get_post( $post->post_parent );
		}

		if ( WooCommerceHelper::is_active() ) {
			$product_ids = WooCommerceHelper::get_products_for_attachment( $attachment_id );
			if ( ! empty( $product_ids ) ) {
				$primary_product_id = (int) $product_ids[0];
				$product_parent     = get_post( $primary_product_id );
				if ( $product_parent && in_array( $product_parent->post_type, [ 'product', 'product_variation' ], true ) ) {
					return trim( (string) $product_parent->post_title );
				}
			}
		}

		if ( ! $parent ) {
			return '';
		}

		return trim( (string) $parent->post_title );
	}

	/**
	 * Check if alt text contains the parent context (substring, case-insensitive).
	 *
	 * @since 3.0.0
	 * @param string $alt     Alt text.
	 * @param string $context Parent context string.
	 * @return bool
	 */
	private function alt_contains_context( $alt, $context ) {
		if ( $context === '' ) {
			return false;
		}
		return mb_stripos( $alt, $context ) !== false;
	}

	/**
	 * Get compliance summary from stored meta and last-scan option.
	 *
	 * @since 3.0.0
	 * @return array{ coverage_percent: float, total_scanned: int, high_risk_count: int, by_category: array, last_scan_timestamp: string|null }
	 */
	public function get_summary() {
		global $wpdb;
		$meta_key = self::ALT_CATEGORY_META_KEY;
		$counts   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value AS cat, COUNT(*) AS cnt FROM {$wpdb->postmeta} WHERE meta_key = %s GROUP BY meta_value",
				$meta_key
			),
			ARRAY_A
		);
		$by_category = [
			'missing'     => 0,
			'placeholder' => 0,
			'duplicate'   => 0,
			'descriptive' => 0,
			'contextual'  => 0,
			'decorative'  => 0,
		];
		$total_scanned = 0;
		foreach ( $counts as $row ) {
			$cat = is_string( $row['cat'] ) ? $row['cat'] : '';
			$cnt = (int) $row['cnt'];
			if ( isset( $by_category[ $cat ] ) ) {
				$by_category[ $cat ] = $cnt;
			}
			$total_scanned += $cnt;
		}
		$high_risk_count = $by_category['missing'] + $by_category['placeholder'] + $by_category['duplicate'];
		$with_alt        = $total_scanned - $by_category['missing'] - $by_category['decorative'];
		$coverage_percent = $total_scanned > 0
			? round( ( $with_alt / $total_scanned ) * 100, 1 )
			: 0.0;
		$last_scan = get_option( self::LAST_SCAN_OPTION, null );
		return [
			'coverage_percent'   => $coverage_percent,
			'total_scanned'      => $total_scanned,
			'high_risk_count'    => $high_risk_count,
			'by_category'        => $by_category,
			'last_scan_timestamp' => $last_scan ? (string) $last_scan : null,
		];
	}

	/**
	 * Start a full scan (reset offset so next batch starts from 0).
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function start_full_scan() {
		update_option( self::SCAN_OFFSET_OPTION, 0 );
		delete_option( self::ALT_COUNTS_OPTION );
	}

	/**
	 * Reclassify attachment(s) when core alt text meta is updated (core hook callback).
	 *
	 * Only runs when meta_key is _wp_attachment_image_alt and object is an attachment.
	 * Updates stored compliance category from current alt so UI stays in sync.
	 *
	 * @since 3.0.0
	 * @param int    $meta_id    Meta row ID.
	 * @param int    $object_id  Object ID (attachment ID).
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value (unused; we read current alt in reclassify).
	 */
	public function on_attachment_alt_updated( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key !== '_wp_attachment_image_alt' ) {
			return;
		}
		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return;
		}
		$post = get_post( $object_id );
		if ( ! $post || $post->post_type !== 'attachment' ) {
			return;
		}
		$this->reclassify_attachments( [ $object_id ] );
	}

	/**
	 * Reclassify given attachments from current alt text (ignore current stored category).
	 *
	 * Evaluates each attachment with existing_category = '' so classification is based only on
	 * current alt and global alt counts. Use for "reclassify" / "unmark" flows.
	 *
	 * @since 3.0.0
	 * @param int[] $attachment_ids Attachment IDs to reclassify.
	 * @return array<int, array{ id: int, alt_category: string }> List of updated items (id and new alt_category).
	 */
	public function reclassify_attachments( array $attachment_ids ) {
		$alt_counts = get_option( self::ALT_COUNTS_OPTION, [] );
		if ( ! is_array( $alt_counts ) ) {
			$alt_counts = [];
		}
		$updated = [];
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = absint( $attachment_id );
			if ( ! $attachment_id ) {
				continue;
			}
			$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$alt = is_string( $alt ) ? trim( $alt ) : '';
			$category = $this->classify( $attachment_id, $alt, '', $alt_counts );
			update_post_meta( $attachment_id, self::ALT_CATEGORY_META_KEY, $category );
			$updated[] = [ 'id' => $attachment_id, 'alt_category' => $category ];
		}
		return $updated;
	}

	/**
	 * Set stored alt category for an attachment (explicit set; does not change alt text unless category is decorative).
	 *
	 * For 'decorative', alt is cleared via AltTextApiService. For other categories, only meta is updated.
	 *
	 * @since 3.0.0
	 * @param int    $attachment_id Attachment ID.
	 * @param string $category      Category slug (must be in VALID_ALT_CATEGORIES).
	 * @return bool True if category is valid and was set.
	 */
	public function set_attachment_category( $attachment_id, $category ) {
		if ( ! in_array( $category, self::VALID_ALT_CATEGORIES, true ) ) {
			return false;
		}
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return false;
		}
		if ( $category === 'decorative' ) {
			$alt_text_api_service = \FluxAIMediaAltCreator\App\Services\AltTextApiService::get_instance();
			$result = $alt_text_api_service->apply_alt_text( $attachment_id, '' );
			return ! empty( $result['success'] );
		}
		update_post_meta( $attachment_id, self::ALT_CATEGORY_META_KEY, $category );
		return true;
	}
}
