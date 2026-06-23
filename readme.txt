=== WP Shield Lite ===
Contributors: cotarlea
Tags: security, login, brute force, hardening, security headers
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, defensive WordPress hardening: brute-force login protection, one-click hardening toggles, security headers and an activity log.

== Description ==

WP Shield Lite is a small, no-bloat security plugin focused on the basics that
protect most WordPress sites. Everything is optional and reversible.

Features:

* **Brute-force login protection** – lock out an IP after a configurable number
  of failed attempts, for a configurable amount of time.
* **Generic login errors** – stop leaking whether the username or password was
  wrong.
* **One-click hardening** – disable the file editor, disable XML-RPC, hide the
  WordPress version and block user enumeration.
* **Security headers** – send X-Frame-Options, X-Content-Type-Options and
  Referrer-Policy.
* **Activity log** – record successful logins, failed attempts and lockouts.
* **New-IP login alerts** – get an email when an administrator signs in from an
  IP address that has not been seen before.
* **Strong-password policy** – require length and complexity and reject common
  passwords across registration, profile updates and resets.

The plugin is intentionally defensive only: it hardens your own site and does
not perform any scanning or action against third parties.

== Installation ==

1. Upload the `wp-shield-lite` folder to `/wp-content/plugins/`, or install the
   ZIP via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to **WP Shield** in the admin menu to configure the options.

== Frequently Asked Questions ==

= Will the lockout block me too? =

Yes, lockouts apply per IP. If you lock yourself out, wait for the lockout to
expire or clear the related transients from your database.

= Does it add a Content-Security-Policy? =

No. A CSP almost always needs per-site tuning, so it is intentionally left out.

== Changelog ==

= 1.2.0 =
* New: strong-password policy enforced on registration, profile updates and
  password resets, with configurable minimum length and a common-password
  blocklist.

= 1.1.0 =
* New: email alert when an administrator logs in from a new IP address, with a
  configurable recipient.

= 1.0.0 =
* Initial release: login protection, hardening toggles, security headers and
  activity log.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
