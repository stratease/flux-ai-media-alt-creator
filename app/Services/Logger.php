<?php
/**
 * Logger utility class.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

namespace FluxAIMediaAltCreator\App\Services;

/**
 * Logger utility class.
 *
 * @since 1.0.0
 */
class Logger {

	/**
	 * Log debug message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function debug( $message, $context = [] ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Flux AI Alt Creator DEBUG] ' . $message . ( ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '' ) );
		}
	}

	/**
	 * Log info message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function info( $message, $context = [] ) {
		error_log( '[Flux AI Alt Creator INFO] ' . $message . ( ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '' ) );
	}

	/**
	 * Log warning message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function warning( $message, $context = [] ) {
		error_log( '[Flux AI Alt Creator WARNING] ' . $message . ( ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '' ) );
	}

	/**
	 * Log error message.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	public function error( $message, $context = [] ) {
		error_log( '[Flux AI Alt Creator ERROR] ' . $message . ( ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '' ) );
	}
}

