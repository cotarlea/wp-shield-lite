<?php
/**
 * HTTP security headers.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Security_Headers
 *
 * Sends a conservative set of security response headers.
 */
class WPSL_Security_Headers {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param array $settings Plugin settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( empty( $this->settings['security_headers'] ) ) {
			return;
		}
		add_filter( 'wp_headers', array( $this, 'add_headers' ) );
	}

	/**
	 * Append security headers to the response.
	 *
	 * Headers are intentionally conservative so they do not break common
	 * setups. A Content-Security-Policy is deliberately omitted because it
	 * almost always needs per-site tuning.
	 *
	 * @param array $headers Existing headers.
	 * @return array
	 */
	public function add_headers( $headers ) {
		$headers['X-Content-Type-Options'] = 'nosniff';
		$headers['X-Frame-Options']        = 'SAMEORIGIN';
		$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
		$headers['X-XSS-Protection']       = '0';

		/**
		 * Filter the security headers before they are sent.
		 *
		 * @param array $headers Security headers.
		 */
		return apply_filters( 'wpsl_security_headers', $headers );
	}
}
