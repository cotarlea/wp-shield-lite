<?php
/**
 * One-click hardening toggles.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Hardening
 *
 * Applies common, reversible WordPress hardening measures.
 */
class WPSL_Hardening {

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
	 * Register hooks based on enabled options.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! empty( $this->settings['disable_file_edit'] ) && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}

		if ( ! empty( $this->settings['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'xmlrpc_methods', '__return_empty_array' );
		}

		if ( ! empty( $this->settings['hide_wp_version'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
			add_filter( 'style_loader_src', array( $this, 'strip_version_query' ), 9999 );
			add_filter( 'script_loader_src', array( $this, 'strip_version_query' ), 9999 );
		}

		if ( ! empty( $this->settings['disable_user_enum'] ) ) {
			add_action( 'template_redirect', array( $this, 'block_author_enumeration' ) );
			add_filter( 'rest_endpoints', array( $this, 'restrict_users_endpoint' ) );
		}
	}

	/**
	 * Remove the WordPress version query string from asset URLs.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	public function strip_version_query( $src ) {
		if ( $src && false !== strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * Block ?author=N enumeration for anonymous visitors.
	 *
	 * @return void
	 */
	public function block_author_enumeration() {
		if ( is_user_logged_in() || is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['author'] ) && ! is_author() ) {
			wp_safe_redirect( home_url(), 302 );
			exit;
		}
	}

	/**
	 * Hide the REST users endpoint from anonymous requests.
	 *
	 * @param array $endpoints REST endpoints.
	 * @return array
	 */
	public function restrict_users_endpoint( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		foreach ( array( '/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)' ) as $route ) {
			if ( isset( $endpoints[ $route ] ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
}
