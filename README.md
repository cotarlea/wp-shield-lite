# WP Shield Lite

[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](LICENSE)

**Lightweight, defensive security hardening for WordPress** — brute-force login
protection, one-click hardening toggles, security headers and an activity log,
with no bloat and no upsells.

> Author & maintainer: **Cotarlea Paul** ([@cotarlea](https://github.com/cotarlea))

## Features

- **Brute-force login protection** — lock out an IP after a configurable number
  of failed attempts, for a configurable duration.
- **Generic login errors** — stop leaking whether the username or password was
  wrong.
- **One-click hardening**
  - Disable the theme/plugin file editor (`DISALLOW_FILE_EDIT`)
  - Disable XML-RPC
  - Hide the WordPress version
  - Block user enumeration (`?author=N` and the REST users endpoint)
- **Security headers** — `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`.
- **Activity log** — successful logins, failed attempts and lockouts, stored in
  a dedicated table with an admin viewer.
- **New-IP login alerts** — email notification when an administrator signs in
  from an IP address not seen before.
- **Strong-password policy** — enforce length and complexity and block common
  passwords on registration, profile updates and resets.

This plugin is **defensive only**. It hardens your own site; it does not scan or
act against third parties.

## Requirements

- WordPress 5.6+
- PHP 7.4+

## Installation

### From a release ZIP

1. Download or build a `wp-shield-lite.zip`.
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose the ZIP, install
   and activate.
3. Configure under the **WP Shield** menu.

### For development

Clone into your WordPress plugins directory:

```bash
git clone https://github.com/cotarlea/wp-shield-lite.git \
  wp-content/plugins/wp-shield-lite
```

## Usage

After activation, open **WP Shield** in the admin sidebar:

- **Settings** — toggle each protection and tune the login limits.
- **Activity Log** — review recent security events and clear the log.

## Project structure

```
wp-shield-lite/
├── wp-shield-lite.php          # Plugin bootstrap & hooks
├── uninstall.php               # Full data cleanup on uninstall
├── readme.txt                  # WordPress.org readme
├── includes/
│   ├── class-plugin.php            # Orchestrator + IP helper
│   ├── class-settings.php          # Option storage & sanitisation
│   ├── class-login-protection.php  # Brute-force throttling
│   ├── class-login-notifications.php # New-IP admin login email alerts
│   ├── class-password-policy.php   # Strong-password enforcement
│   ├── class-hardening.php         # Hardening toggles
│   ├── class-security-headers.php  # Response headers
│   ├── class-activity-log.php      # Log table + queries
│   └── class-admin.php             # Settings & log admin pages
└── admin/css/admin.css         # Admin styling
```

## Contributing

Issues and pull requests are welcome on
[GitHub](https://github.com/cotarlea/wp-shield-lite). Please keep changes
focused and follow the existing coding style (WordPress coding standards).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

Copyright © Cotarlea Paul.
