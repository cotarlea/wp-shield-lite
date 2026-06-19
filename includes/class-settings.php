<?php
/**
 * Settings storage and sanitisation.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Settings
 *
 * Wraps the single `wpsl_settings` option.
 */
class WPSL_Settings {

	const OPTION = 'wpsl_settings';

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'login_protection'    => 1,
			'max_attempts'        => 5,
			'lockout_minutes'     => 15,
			'generic_login_error' => 1,
			'disable_file_edit'   => 1,
			'disable_xmlrpc'      => 1,
			'hide_wp_version'     => 1,
			'disable_user_enum'   => 1,
			'security_headers'    => 1,
			'activity_log'        => 1,
			'login_notify'        => 0,
			'notify_email'        => '',
		);
	}

	/**
	 * Seed defaults on activation without overwriting existing settings.
	 *
	 * @return void
	 */
	public static function set_defaults() {
		$existing = get_option( self::OPTION, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		update_option( self::OPTION, wp_parse_args( $existing, self::defaults() ) );
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Sanitise the settings array coming from the options form.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input     = is_array( $input ) ? $input : array();
		$defaults  = self::defaults();
		$sanitized = array();

		$booleans = array(
			'login_protection',
			'generic_login_error',
			'disable_file_edit',
			'disable_xmlrpc',
			'hide_wp_version',
			'disable_user_enum',
			'security_headers',
			'activity_log',
			'login_notify',
		);

		foreach ( $booleans as $key ) {
			$sanitized[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		$notify_email              = isset( $input['notify_email'] ) ? sanitize_email( $input['notify_email'] ) : '';
		$sanitized['notify_email'] = is_email( $notify_email ) ? $notify_email : '';

		$max_attempts              = isset( $input['max_attempts'] ) ? absint( $input['max_attempts'] ) : $defaults['max_attempts'];
		$sanitized['max_attempts'] = max( 1, min( 100, $max_attempts ) );

		$lockout                      = isset( $input['lockout_minutes'] ) ? absint( $input['lockout_minutes'] ) : $defaults['lockout_minutes'];
		$sanitized['lockout_minutes'] = max( 1, min( 1440, $lockout ) );

		return $sanitized;
	}
}
