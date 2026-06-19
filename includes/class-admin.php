<?php
/**
 * Admin interface: settings page and activity-log viewer.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Admin
 */
class WPSL_Admin {

	const SETTINGS_GROUP = 'wpsl_settings_group';
	const MENU_SLUG      = 'wp-shield-lite';
	const LOG_SLUG       = 'wp-shield-lite-log';

	/**
	 * Activity log.
	 *
	 * @var WPSL_Activity_Log
	 */
	private $log;

	/**
	 * Constructor.
	 *
	 * @param WPSL_Activity_Log $log Activity log instance.
	 */
	public function __construct( $log ) {
		$this->log = $log;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_clear_log' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . WPSL_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the top-level menu and sub-pages.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'WP Shield Lite', 'wp-shield-lite' ),
			__( 'WP Shield', 'wp-shield-lite' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-shield',
			80
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'wp-shield-lite' ),
			__( 'Settings', 'wp-shield-lite' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Activity Log', 'wp-shield-lite' ),
			__( 'Activity Log', 'wp-shield-lite' ),
			'manage_options',
			self::LOG_SLUG,
			array( $this, 'render_log_page' )
		);
	}

	/**
	 * Register the single settings option.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			WPSL_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'WPSL_Settings', 'sanitize' ),
			)
		);
	}

	/**
	 * Add a quick Settings link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wp-shield-lite' ) . '</a>' );
		return $links;
	}

	/**
	 * Enqueue admin CSS on our pages only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'wpsl-admin', WPSL_URL . 'admin/css/admin.css', array(), WPSL_VERSION );
	}

	/**
	 * Render a single checkbox field.
	 *
	 * @param array  $settings Current settings.
	 * @param string $key      Setting key.
	 * @param string $label    Field label.
	 * @param string $desc     Optional description.
	 * @return void
	 */
	private function checkbox( $settings, $key, $label, $desc = '' ) {
		$name = WPSL_Settings::OPTION . '[' . $key . ']';
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
					<?php esc_html_e( 'Enable', 'wp-shield-lite' ); ?>
				</label>
				<?php if ( $desc ) : ?>
					<p class="description"><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = WPSL_Settings::get_all();
		?>
		<div class="wrap wpsl-wrap">
			<h1><?php esc_html_e( 'WP Shield Lite', 'wp-shield-lite' ); ?></h1>
			<p class="wpsl-tagline"><?php esc_html_e( 'Lightweight, defensive WordPress hardening.', 'wp-shield-lite' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>

				<h2><?php esc_html_e( 'Login protection', 'wp-shield-lite' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$this->checkbox( $s, 'login_protection', __( 'Brute-force protection', 'wp-shield-lite' ), __( 'Temporarily lock out an IP after too many failed logins.', 'wp-shield-lite' ) );
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max failed attempts', 'wp-shield-lite' ); ?></th>
						<td>
							<input type="number" min="1" max="100" name="<?php echo esc_attr( WPSL_Settings::OPTION . '[max_attempts]' ); ?>" value="<?php echo esc_attr( $s['max_attempts'] ); ?>" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Lockout duration (minutes)', 'wp-shield-lite' ); ?></th>
						<td>
							<input type="number" min="1" max="1440" name="<?php echo esc_attr( WPSL_Settings::OPTION . '[lockout_minutes]' ); ?>" value="<?php echo esc_attr( $s['lockout_minutes'] ); ?>" class="small-text" />
						</td>
					</tr>
					<?php
					$this->checkbox( $s, 'generic_login_error', __( 'Generic login errors', 'wp-shield-lite' ), __( 'Hide whether the username or the password was wrong.', 'wp-shield-lite' ) );
					?>
				</table>

				<h2><?php esc_html_e( 'Hardening', 'wp-shield-lite' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$this->checkbox( $s, 'disable_file_edit', __( 'Disable theme/plugin file editor', 'wp-shield-lite' ), __( 'Sets DISALLOW_FILE_EDIT so code cannot be edited from wp-admin.', 'wp-shield-lite' ) );
					$this->checkbox( $s, 'disable_xmlrpc', __( 'Disable XML-RPC', 'wp-shield-lite' ), __( 'Blocks a common brute-force and pingback vector.', 'wp-shield-lite' ) );
					$this->checkbox( $s, 'hide_wp_version', __( 'Hide WordPress version', 'wp-shield-lite' ), __( 'Removes the generator meta tag and asset version strings.', 'wp-shield-lite' ) );
					$this->checkbox( $s, 'disable_user_enum', __( 'Block user enumeration', 'wp-shield-lite' ), __( 'Stops ?author=N probing and hides the REST users endpoint.', 'wp-shield-lite' ) );
					?>
				</table>

				<h2><?php esc_html_e( 'Response headers', 'wp-shield-lite' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$this->checkbox( $s, 'security_headers', __( 'Security headers', 'wp-shield-lite' ), __( 'Send X-Frame-Options, X-Content-Type-Options and Referrer-Policy.', 'wp-shield-lite' ) );
					?>
				</table>

				<h2><?php esc_html_e( 'Logging', 'wp-shield-lite' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$this->checkbox( $s, 'activity_log', __( 'Activity log', 'wp-shield-lite' ), __( 'Record logins, failed attempts and lockouts.', 'wp-shield-lite' ) );
					?>
				</table>

				<h2><?php esc_html_e( 'Notifications', 'wp-shield-lite' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$this->checkbox( $s, 'login_notify', __( 'New-IP admin login alert', 'wp-shield-lite' ), __( 'Email an alert when an administrator logs in from an IP not seen before.', 'wp-shield-lite' ) );
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Alert email address', 'wp-shield-lite' ); ?></th>
						<td>
							<input type="email" class="regular-text" name="<?php echo esc_attr( WPSL_Settings::OPTION . '[notify_email]' ); ?>" value="<?php echo esc_attr( $s['notify_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Leave empty to use the site admin email.', 'wp-shield-lite' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the "clear log" POST action.
	 *
	 * @return void
	 */
	public function handle_clear_log() {
		if ( empty( $_POST['wpsl_clear_log'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'wpsl_clear_log' );

		$this->log->clear();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::LOG_SLUG,
					'cleared' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the activity-log page.
	 *
	 * @return void
	 */
	public function render_log_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$per_page = 50;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$total   = $this->log->count_entries();
		$entries = $this->log->get_entries( $per_page, $page );
		$pages   = (int) ceil( $total / $per_page );
		?>
		<div class="wrap wpsl-wrap">
			<h1><?php esc_html_e( 'WP Shield – Activity Log', 'wp-shield-lite' ); ?></h1>

			<?php if ( isset( $_GET['cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Activity log cleared.', 'wp-shield-lite' ); ?></p></div>
			<?php endif; ?>

			<form method="post" class="wpsl-clear-form">
				<?php wp_nonce_field( 'wpsl_clear_log' ); ?>
				<input type="hidden" name="wpsl_clear_log" value="1" />
				<?php submit_button( __( 'Clear log', 'wp-shield-lite' ), 'delete', 'submit', false ); ?>
			</form>

			<table class="widefat fixed striped wpsl-log-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'wp-shield-lite' ); ?></th>
						<th><?php esc_html_e( 'Event', 'wp-shield-lite' ); ?></th>
						<th><?php esc_html_e( 'User', 'wp-shield-lite' ); ?></th>
						<th><?php esc_html_e( 'IP', 'wp-shield-lite' ); ?></th>
						<th><?php esc_html_e( 'Details', 'wp-shield-lite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $entries ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No activity recorded yet.', 'wp-shield-lite' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $entries as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->logged_at ); ?></td>
								<td><code><?php echo esc_html( $row->event_type ); ?></code></td>
								<td><?php echo esc_html( $row->username ); ?></td>
								<td><?php echo esc_html( $row->ip ); ?></td>
								<td><?php echo esc_html( $row->message ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $page,
								'total'     => $pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
