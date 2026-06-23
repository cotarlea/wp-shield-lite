<?php
/**
 * Password strength policy.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Password_Policy
 *
 * Rejects weak or commonly used passwords on registration, profile updates
 * and password resets.
 */
class WPSL_Password_Policy {

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
		if ( empty( $this->settings['password_policy'] ) ) {
			return;
		}

		add_action( 'user_profile_update_errors', array( $this, 'validate_profile' ), 10, 3 );
		add_filter( 'registration_errors', array( $this, 'validate_registration' ), 10, 1 );
		add_action( 'validate_password_reset', array( $this, 'validate_reset' ), 10, 2 );
	}

	/**
	 * Configured minimum length.
	 *
	 * @return int
	 */
	private function min_length() {
		return max( 6, (int) $this->settings['password_min_length'] );
	}

	/**
	 * Check a password against the policy.
	 *
	 * @param string $password Plain-text password.
	 * @return string[] List of human-readable problems (empty when valid).
	 */
	public function check( $password ) {
		$problems = array();
		$min      = $this->min_length();

		if ( strlen( $password ) < $min ) {
			$problems[] = sprintf(
				/* translators: %d: minimum number of characters. */
				_n(
					'Password must be at least %d character long.',
					'Password must be at least %d characters long.',
					$min,
					'wp-shield-lite'
				),
				$min
			);
		}
		if ( ! preg_match( '/[a-z]/', $password ) ) {
			$problems[] = __( 'Password must contain a lowercase letter.', 'wp-shield-lite' );
		}
		if ( ! preg_match( '/[A-Z]/', $password ) ) {
			$problems[] = __( 'Password must contain an uppercase letter.', 'wp-shield-lite' );
		}
		if ( ! preg_match( '/[0-9]/', $password ) ) {
			$problems[] = __( 'Password must contain a number.', 'wp-shield-lite' );
		}
		if ( ! preg_match( '/[^A-Za-z0-9]/', $password ) ) {
			$problems[] = __( 'Password must contain a special character.', 'wp-shield-lite' );
		}
		if ( $this->is_common( $password ) ) {
			$problems[] = __( 'Password is too common; please choose a less predictable one.', 'wp-shield-lite' );
		}

		return $problems;
	}

	/**
	 * Whether the password is in the bundled common-password list.
	 *
	 * @param string $password Plain-text password.
	 * @return bool
	 */
	private function is_common( $password ) {
		$common = array(
			'password', 'password1', 'password123', '123456', '1234567', '12345678',
			'123456789', '1234567890', 'qwerty', 'qwerty123', 'abc123', 'admin',
			'admin123', 'letmein', 'welcome', 'welcome1', 'iloveyou', 'monkey',
			'dragon', 'sunshine', 'princess', 'football', 'baseball', 'master',
			'superman', 'trustno1', 'passw0rd', 'p@ssw0rd', 'changeme', 'wordpress',
		);
		return in_array( strtolower( $password ), $common, true );
	}

	/**
	 * Read the submitted password from the form, if present.
	 *
	 * These hooks run inside WordPress' own nonce-verified flows.
	 *
	 * @return string|null
	 */
	private function submitted_password() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['pass1'] ) ) {
			return null;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$pass1 = trim( (string) wp_unslash( $_POST['pass1'] ) );

		return '' === $pass1 ? null : $pass1;
	}

	/**
	 * Add policy problems to an error object.
	 *
	 * @param WP_Error $errors   Error collector.
	 * @param string   $password Plain-text password.
	 * @return void
	 */
	private function apply( $errors, $password ) {
		foreach ( $this->check( $password ) as $problem ) {
			$errors->add( 'wpsl_weak_password', '<strong>' . esc_html__( 'Error:', 'wp-shield-lite' ) . '</strong> ' . esc_html( $problem ) );
		}
	}

	/**
	 * Validate on profile create/update.
	 *
	 * @param WP_Error $errors Error collector.
	 * @param bool     $update Whether this is an update.
	 * @param stdClass $user   User data being saved.
	 * @return void
	 */
	public function validate_profile( $errors, $update, $user ) {
		unset( $update, $user );
		$password = $this->submitted_password();
		if ( null !== $password ) {
			$this->apply( $errors, $password );
		}
	}

	/**
	 * Validate on user registration.
	 *
	 * @param WP_Error $errors Error collector.
	 * @return WP_Error
	 */
	public function validate_registration( $errors ) {
		$password = $this->submitted_password();
		if ( null !== $password ) {
			$this->apply( $errors, $password );
		}
		return $errors;
	}

	/**
	 * Validate on password reset.
	 *
	 * @param WP_Error         $errors Error collector.
	 * @param WP_User|WP_Error $user   User resetting the password.
	 * @return void
	 */
	public function validate_reset( $errors, $user ) {
		unset( $user );
		$password = $this->submitted_password();
		if ( null !== $password ) {
			$this->apply( $errors, $password );
		}
	}
}
