<?php
/**
 * Email notifications for administrator logins from new IP addresses.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Login_Notifications
 *
 * Emails a configured address when a user with admin capabilities logs in
 * from an IP that has not been seen for that account before.
 */
class WPSL_Login_Notifications {

	/**
	 * Per-user meta key holding the list of known IPs.
	 */
	const META_KEY = 'wpsl_known_ips';

	/**
	 * Maximum number of IPs remembered per user.
	 */
	const MAX_IPS = 20;

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
		if ( empty( $this->settings['login_notify'] ) ) {
			return;
		}
		add_action( 'wp_login', array( $this, 'maybe_notify' ), 20, 2 );
	}

	/**
	 * Check the login IP and alert when it is new for an admin account.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       User object (may be null on some setups).
	 * @return void
	 */
	public function maybe_notify( $user_login, $user = null ) {
		if ( ! ( $user instanceof WP_User ) ) {
			$user = get_user_by( 'login', $user_login );
		}
		if ( ! $user || ! user_can( $user, 'manage_options' ) ) {
			return;
		}

		$ip    = WPSL_Plugin::get_client_ip();
		$known = get_user_meta( $user->ID, self::META_KEY, true );
		if ( ! is_array( $known ) ) {
			$known = array();
		}

		if ( in_array( $ip, $known, true ) ) {
			return;
		}

		$is_first_record = empty( $known );

		$known[] = $ip;
		if ( count( $known ) > self::MAX_IPS ) {
			$known = array_slice( $known, -self::MAX_IPS );
		}
		update_user_meta( $user->ID, self::META_KEY, $known );

		// Seed silently the first time we ever see this account, so existing
		// users are not alerted about their own usual location on upgrade.
		if ( $is_first_record ) {
			return;
		}

		$this->send_alert( $user, $ip );
	}

	/**
	 * Resolve the destination address for alerts.
	 *
	 * @return string
	 */
	private function recipient() {
		$to = isset( $this->settings['notify_email'] ) ? $this->settings['notify_email'] : '';
		if ( empty( $to ) || ! is_email( $to ) ) {
			$to = get_option( 'admin_email' );
		}
		return $to;
	}

	/**
	 * Send the new-IP alert email.
	 *
	 * @param WP_User $user The user who logged in.
	 * @param string  $ip   The new IP address.
	 * @return void
	 */
	private function send_alert( $user, $ip ) {
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$subject = sprintf(
			/* translators: %s: site name. */
			__( '[%s] Admin login from a new IP address', 'wp-shield-lite' ),
			$site_name
		);

		$lines = array(
			sprintf(
				/* translators: %s: username. */
				__( 'An administrator account (%s) just signed in from an IP address not seen before.', 'wp-shield-lite' ),
				$user->user_login
			),
			'',
			sprintf(
				/* translators: %s: IP address. */
				__( 'IP address: %s', 'wp-shield-lite' ),
				$ip
			),
			sprintf(
				/* translators: %s: date and time. */
				__( 'Time: %s', 'wp-shield-lite' ),
				current_time( 'mysql' )
			),
			'',
			__( 'If this was you, no action is needed. If not, change your password immediately.', 'wp-shield-lite' ),
			'',
			sprintf(
				/* translators: %s: site URL. */
				__( 'Site: %s', 'wp-shield-lite' ),
				home_url()
			),
		);

		wp_mail( $this->recipient(), $subject, implode( "\n", $lines ) );
	}
}
