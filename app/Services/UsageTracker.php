<?php
/**
 * Usage tracking service for OpenAI API usage.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Service for tracking OpenAI API usage and costs.
 *
 * @since 1.0.0
 */
class UsageTracker {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Option name for current month usage.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $option_name = 'flux_ai_alt_creator_usage_current_month';

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
	 * Track API request usage.
	 *
	 * @since 1.0.0
	 * @param int    $tokens_used Number of tokens used (0 if unavailable).
	 * @param string $model Model used (e.g., 'gpt-4o-mini').
	 * @param float  $cost Cost in USD (0 if unavailable).
	 * @return void
	 */
	public function track_request( $tokens_used = 0, $model = 'gpt-4o-mini', $cost = 0.0 ) {
		$usage = $this->get_current_month_usage();
		
		// Ensure we're tracking current month.
		$this->maybe_reset_monthly();
		
		// Increment request count.
		$usage['requests_count']++;
		
		// Add tokens if available.
		if ( $tokens_used > 0 ) {
			$usage['tokens_used'] += $tokens_used;
		}
		
		// Add cost if available.
		if ( $cost > 0 ) {
			$usage['cost_estimate'] += $cost;
		} else {
			// Estimate cost if not provided (GPT-4o-mini pricing: $0.15 per 1M input tokens, $0.60 per 1M output tokens).
			// Rough estimate: assume 50/50 input/output split.
			if ( $tokens_used > 0 ) {
				$estimated_cost = ( $tokens_used / 1000000 ) * 0.375; // Average of input/output pricing.
				$usage['cost_estimate'] += $estimated_cost;
			}
		}
		
		// Store per-request data if granular tracking is enabled.
		if ( ! isset( $usage['requests'] ) ) {
			$usage['requests'] = [];
		}
		
		$usage['requests'][] = [
			'timestamp' => current_time( 'mysql' ),
			'tokens_used' => $tokens_used,
			'model' => $model,
			'cost' => $cost > 0 ? $cost : ( $tokens_used > 0 ? ( $tokens_used / 1000000 ) * 0.375 : 0 ),
		];
		
		// Keep only last 1000 requests to prevent option bloat.
		if ( count( $usage['requests'] ) > 1000 ) {
			$usage['requests'] = array_slice( $usage['requests'], -1000 );
		}
		
		update_option( $this->option_name, $usage );
		
		$this->logger->debug( 'Tracked API usage', [
			'tokens_used' => $tokens_used,
			'model' => $model,
			'cost' => $cost,
		] );
	}

	/**
	 * Get current month usage statistics.
	 *
	 * @since 1.0.0
	 * @return array Usage statistics.
	 */
	public function get_current_month_usage() {
		$this->maybe_reset_monthly();
		
		$usage = get_option( $this->option_name, [] );
		
		$defaults = [
			'requests_count' => 0,
			'tokens_used' => 0,
			'cost_estimate' => 0.0,
			'last_reset_date' => gmdate( 'Y-m-01' ), // First day of current month.
			'requests' => [],
		];
		
		return wp_parse_args( $usage, $defaults );
	}

	/**
	 * Get cost estimate for current month.
	 *
	 * @since 1.0.0
	 * @return float Estimated cost in USD.
	 */
	public function get_cost_estimate() {
		$usage = $this->get_current_month_usage();
		return (float) $usage['cost_estimate'];
	}

	/**
	 * Reset monthly usage (called automatically at month start).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function reset_monthly() {
		$defaults = [
			'requests_count' => 0,
			'tokens_used' => 0,
			'cost_estimate' => 0.0,
			'last_reset_date' => gmdate( 'Y-m-01' ),
			'requests' => [],
		];
		
		update_option( $this->option_name, $defaults );
		
		$this->logger->info( 'Reset monthly usage statistics' );
		
		/**
		 * Fires after usage statistics are reset.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flux_ai_alt_creator_reset_usage' );
	}

	/**
	 * Check if we need to reset monthly usage.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function maybe_reset_monthly() {
		$usage = get_option( $this->option_name, [] );
		$last_reset = $usage['last_reset_date'] ?? gmdate( 'Y-m-01' );
		
		$current_month = gmdate( 'Y-m-01' );
		
		// If we're in a new month, reset.
		if ( $last_reset !== $current_month ) {
			$this->reset_monthly();
		}
	}
}

