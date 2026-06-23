<?php
/**
 * Main plugin orchestrator.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Plugin
 *
 * Loads sub-modules and wires up shared hooks.
 */
final class WPSL_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var WPSL_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Activity log module.
	 *
	 * @var WPSL_Activity_Log
	 */
	public $activity_log;

	/**
	 * Get the singleton instance.
	 *
	 * @return WPSL_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor is private to enforce the singleton.
	 */
	private function __construct() {}

	/**
	 * Initialise the plugin and its modules.
	 *
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain( 'wp-shield-lite', false, dirname( WPSL_BASENAME ) . '/languages' );

		$settings = WPSL_Settings::get_all();

		$this->activity_log = new WPSL_Activity_Log();
		$this->activity_log->register();

		( new WPSL_Login_Protection( $settings, $this->activity_log ) )->register();
		( new WPSL_Login_Notifications( $settings ) )->register();
		( new WPSL_Password_Policy( $settings ) )->register();
		( new WPSL_Hardening( $settings ) )->register();
		( new WPSL_Security_Headers( $settings ) )->register();

		if ( is_admin() ) {
			( new WPSL_Admin( $this->activity_log ) )->register();
		}
	}

	/**
	 * Resolve the best-guess client IP address.
	 *
	 * By default only REMOTE_ADDR is trusted. Proxy/forwarded headers are
	 * intentionally ignored because they are trivial to spoof unless a
	 * trusted reverse proxy is in front of the site.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		return $ip ? $ip : '0.0.0.0';
	}
}
