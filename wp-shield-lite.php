<?php
/**
 * Plugin Name:       WP Shield Lite
 * Plugin URI:        https://github.com/cotarlea/wp-shield-lite
 * Description:       Lightweight defensive security plugin for WordPress: brute-force login protection, one-click hardening, security headers and an activity log.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Cotarlea Paul
 * Author URI:        https://github.com/cotarlea
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-shield-lite
 * Domain Path:       /languages
 *
 * @package WP_Shield_Lite
 * @author  Cotarlea Paul
 */

defined( 'ABSPATH' ) || exit;

define( 'WPSL_VERSION', '1.0.0' );
define( 'WPSL_FILE', __FILE__ );
define( 'WPSL_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSL_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSL_BASENAME', plugin_basename( __FILE__ ) );

require_once WPSL_DIR . 'includes/class-plugin.php';
require_once WPSL_DIR . 'includes/class-settings.php';
require_once WPSL_DIR . 'includes/class-activity-log.php';
require_once WPSL_DIR . 'includes/class-login-protection.php';
require_once WPSL_DIR . 'includes/class-hardening.php';
require_once WPSL_DIR . 'includes/class-security-headers.php';
require_once WPSL_DIR . 'includes/class-admin.php';

/**
 * Activation: create the activity-log table and seed default settings.
 */
function wpsl_activate() {
	WPSL_Activity_Log::install_table();
	WPSL_Settings::set_defaults();
	add_option( 'wpsl_version', WPSL_VERSION );
}
register_activation_hook( __FILE__, 'wpsl_activate' );

/**
 * Boot the plugin once all plugins are loaded.
 */
function wpsl_boot() {
	WPSL_Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'wpsl_boot' );
