<?php
/**
 * Brute-force login protection.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Login_Protection
 *
 * Throttles repeated failed logins from the same IP using transients.
 */
class WPSL_Login_Protection {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Activity log.
	 *
	 * @var WPSL_Activity_Log
	 */
	private $log;

	/**
	 * Constructor.
	 *
	 * @param array             $settings Plugin settings.
	 * @param WPSL_Activity_Log $log      Activity log instance.
	 */
	public function __construct( $settings, $log ) {
		$this->settings = $settings;
		$this->log      = $log;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( empty( $this->settings['login_protection'] ) ) {
			if ( ! empty( $this->settings['generic_login_error'] ) ) {
				add_filter( 'login_errors', array( $this, 'generic_error' ) );
			}
			return;
		}

		add_filter( 'authenticate', array( $this, 'block_locked_out' ), 30 );
		add_action( 'wp_login_failed', array( $this, 'on_failed_login' ) );
		add_action( 'wp_login', array( $this, 'on_successful_login' ), 10, 2 );

		if ( ! empty( $this->settings['generic_login_error'] ) ) {
			add_filter( 'login_errors', array( $this, 'generic_error' ) );
		}
	}

	/**
	 * Transient key for the failed-attempt counter.
	 *
	 * @return string
	 */
	private function attempts_key() {
		return 'wpsl_att_' . md5( WPSL_Plugin::get_client_ip() );
	}

	/**
	 * Transient key for an active lockout.
	 *
	 * @return string
	 */
	private function lockout_key() {
		return 'wpsl_lock_' . md5( WPSL_Plugin::get_client_ip() );
	}

	/**
	 * Reject authentication while the IP is locked out.
	 *
	 * @param WP_User|WP_Error|null $user Current auth result.
	 * @return WP_User|WP_Error|null
	 */
	public function block_locked_out( $user ) {
		$until = get_transient( $this->lockout_key() );
		if ( false === $until ) {
			return $user;
		}

		$remaining = max( 1, (int) ceil( ( (int) $until - time() ) / 60 ) );

		return new WP_Error(
			'wpsl_locked_out',
			sprintf(
				/* translators: %d: minutes remaining. */
				_n(
					'<strong>Too many failed attempts.</strong> Try again in %d minute.',
					'<strong>Too many failed attempts.</strong> Try again in %d minutes.',
					$remaining,
					'wp-shield-lite'
				),
				$remaining
			)
		);
	}

	/**
	 * Count a failed login and trigger a lockout when the limit is hit.
	 *
	 * @param string $username Attempted username.
	 * @return void
	 */
	public function on_failed_login( $username ) {
		$max     = max( 1, (int) $this->settings['max_attempts'] );
		$minutes = max( 1, (int) $this->settings['lockout_minutes'] );

		$attempts = (int) get_transient( $this->attempts_key() );
		++$attempts;

		if ( $attempts >= $max ) {
			$until = time() + ( $minutes * MINUTE_IN_SECONDS );
			set_transient( $this->lockout_key(), $until, $minutes * MINUTE_IN_SECONDS );
			delete_transient( $this->attempts_key() );

			$this->log->record(
				'lockout',
				sprintf( 'IP locked out for %d minute(s) after %d failed attempts.', $minutes, $attempts ),
				$username
			);
			return;
		}

		// Keep the counter alive for the lockout window.
		set_transient( $this->attempts_key(), $attempts, $minutes * MINUTE_IN_SECONDS );

		$this->log->record(
			'login_failed',
			sprintf( 'Failed login (%d/%d).', $attempts, $max ),
			$username
		);
	}

	/**
	 * Clear counters after a successful login.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       User object.
	 * @return void
	 */
	public function on_successful_login( $user_login, $user = null ) {
		delete_transient( $this->attempts_key() );
		delete_transient( $this->lockout_key() );

		$this->log->record( 'login_success', 'Successful login.', $user_login );
	}

	/**
	 * Replace detailed login errors with a generic message.
	 *
	 * @param string $error Original error markup.
	 * @return string
	 */
	public function generic_error( $error ) {
		// Preserve our own lockout message so users know why they are blocked.
		if ( false !== strpos( (string) $error, 'Too many failed attempts' ) ) {
			return $error;
		}
		return __( '<strong>Error:</strong> Invalid username or password.', 'wp-shield-lite' );
	}
}
