<?php
/**
 * Base REST API controller for Flux AI Media Alt Creator plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace FluxAIMediaAltCreator\App\Http\Controllers;

use FluxAIMediaAltCreator\FluxPlugins\Common\Logger\Logger;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Base controller with common functionality for all REST API controllers.
 *
 * @since 1.0.0
 */
abstract class BaseController extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Removed Logger parameter, now uses Logger::get_instance() directly.
	 */
	public function __construct() {
		// Base controller constructor.
	}

	/**
	 * Create a standardized error response.
	 *
	 * @since 1.0.0
	 * @param string $message Error message.
	 * @param string $error_code Error code.
	 * @param int    $http_status HTTP status code.
	 * @return WP_REST_Response Error response.
	 */
	protected function create_error_response( $message, $error_code = 'error', $http_status = 500 ) {
		// Log the error.
		Logger::get_instance()->error( $message, [
			'error_code' => $error_code,
			'http_status' => $http_status,
		] );

		return new WP_REST_Response( [
			'success' => false,
			'message' => $message,
			'error_code' => $error_code,
		], $http_status );
	}

	/**
	 * Create a standardized success response.
	 *
	 * @since 1.0.0
	 * @param mixed  $data Response data.
	 * @param string $message Success message.
	 * @param int    $http_status HTTP status code.
	 * @return WP_REST_Response Success response.
	 */
	protected function create_success_response( $data = null, $message = 'Success', $http_status = 200 ) {
		$response = [
			'success' => true,
			'message' => $message,
			'timestamp' => current_time( 'mysql' ),
		];

		if ( $data !== null ) {
			$response['data'] = $data;
		}

		return new WP_REST_Response( $response, $http_status );
	}

	/**
	 * Check if user has permission to access endpoints.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if user has permission.
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_options' );
	}
}

