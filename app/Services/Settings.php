<?php
/**
 * Centralized settings management for Flux AI Alt Text & Accessibility Audit plugin.
 *
 * @package FluxAIMediaAltCreator
 * @since 1.0.0
 */
namespace FluxAIMediaAltCreator\App\Services;

/**
 * Settings management class.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * WordPress option name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $option_name = 'flux_ai_alt_creator_settings';

	/**
	 * Valid vision provider slugs.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	private static $valid_providers = [ 'openai', 'gemini', 'claude' ];

	/**
	 * Get all default settings.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Added provider, gemini_api_key, claude_api_key.
	 * @return array Default settings array.
	 */
	public static function get_defaults() {
		return [
			'openai_api_key' => '',
			'gemini_api_key' => '',
			'claude_api_key' => '',
			'provider' => 'openai',
			'last_scan_date' => null,
		];
	}

	/**
	 * Initialize default settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function initialize_defaults() {
		$current_settings = get_option( self::$option_name, [] );
		$defaults = self::get_defaults();
		
		// Merge defaults with existing settings.
		$merged_settings = wp_parse_args( $current_settings, $defaults );
		
		// Only update if settings changed.
		if ( $current_settings !== $merged_settings ) {
			update_option( self::$option_name, $merged_settings );
		}
	}

	/**
	 * Get all settings.
	 *
	 * @since 1.0.0
	 * @return array Settings array.
	 */
	public function get_all() {
		$settings = get_option( self::$option_name, [] );
		$defaults = self::get_defaults();
		
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get a specific setting.
	 *
	 * @since 1.0.0
	 * @param string $key Setting key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Setting value.
	 */
	public function get( $key, $default = null ) {
		$settings = $this->get_all();
		
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Update settings.
	 *
	 * @since 1.0.0
	 * @param array $new_settings Settings to update.
	 * @return void
	 */
	public function update( $new_settings ) {
		$current_settings = $this->get_all();
		$updated_settings = array_merge( $current_settings, $new_settings );
		
		update_option( self::$option_name, $updated_settings );
	}

	/**
	 * Get OpenAI API key.
	 *
	 * @since 1.0.0
	 * @return string API key (may be encrypted).
	 */
	public static function get_openai_api_key() {
		$settings = get_option( self::$option_name, [] );
		return $settings['openai_api_key'] ?? '';
	}

	/**
	 * Set OpenAI API key.
	 *
	 * @since 1.0.0
	 * @param string $api_key API key.
	 * @return void
	 */
	public static function set_openai_api_key( $api_key ) {
		$settings = get_option( self::$option_name, [] );
		$settings['openai_api_key'] = $api_key;
		update_option( self::$option_name, $settings );
	}

	/**
	 * Get the effective vision provider (openai, gemini, or claude).
	 *
	 * Defaults to openai when unset or invalid for backwards compatibility.
	 *
	 * @since 2.0.0
	 * @return string Provider slug.
	 */
	public static function get_vision_provider() {
		$settings = get_option( self::$option_name, [] );
		$provider = isset( $settings['provider'] ) ? $settings['provider'] : 'openai';
		if ( ! in_array( $provider, self::$valid_providers, true ) ) {
			return 'openai';
		}
		return $provider;
	}

	/**
	 * Get the API key for the current vision provider.
	 *
	 * @since 2.0.0
	 * @return string API key or empty string.
	 */
	public static function get_vision_api_key() {
		$provider = self::get_vision_provider();
		$settings = get_option( self::$option_name, [] );
		$key_map = [
			'openai' => 'openai_api_key',
			'gemini' => 'gemini_api_key',
			'claude' => 'claude_api_key',
		];
		$key_name = $key_map[ $provider ] ?? 'openai_api_key';
		return $settings[ $key_name ] ?? '';
	}
}

