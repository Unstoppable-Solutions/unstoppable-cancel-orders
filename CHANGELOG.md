# Changelog

All notable user-facing changes are documented here. For WordPress.org users,
the same changelog lives in `readme.txt` and renders on the plugin's directory
page. This file follows the format described at https://keepachangelog.com.

## [Unreleased]

- Nothing yet.

## [2.0.0] - Not yet released

First release under the WordPress.org-compliant name and slug. Rebuilt against
the Unstoppable Solutions WordPress Plugin Development & Submission Guide
(2026-10) and the Options Studio Development Guide v1.2.0.

### Changed

- New name, slug and text domain: **Unstoppable Cancel Orders for WooCommerce** (`unstoppable-cancel-orders`). The earlier plugin was "Unstoppable WC Cancel Orders" (`wc-order-cancellation`).
- Settings moved from WooCommerce > Settings to **Options Studio > Order Cancellation**, with Dashboard and Settings tabs.
- All settings stored in one option, `uoca_settings`; all code uses the `uoca` prefix.
- Cancellations no longer create refund records. The earlier build recorded a refund automatically, without returning money through the payment gateway.
- Stock is restored by WooCommerce itself rather than by the plugin, so it is never restored twice.
- A declined request returns the order to the status it had before, instead of always Processing.

### Added

- Approve or decline requests from the Dashboard tab or the order editor's Order actions box.
- Emails: new cancellation request (admin), request received and request declined (customer). Approved requests trigger WooCommerce's own Cancelled order emails.
- Editable reason list; off / optional / required settings for reasons and notes.
- One-time copy of settings from the earlier plugin (originals kept).
- Option to remove settings on uninstall (off by default).
- Options Studio shell v1.2.0 (enqueued styles, escaped admin bar, host text domain).

### Fixed

- The cancel button in the My Account orders table did nothing.
- The order confirmation page showed the cancel button twice.
- The "Network: true" header prevented per-site activation on multisite.

### Security

- All request data is checked with `isset()`, unslashed, sanitized and validated against allowlists.
- Every state change checks a nonce and a capability; guest orders are verified with the order key using `hash_equals()`.
- Order IDs cannot be probed: "not found" and "not yours" return the same response.

### Removed

- Licence code, update checker, upgrade page and promotional notices.
- Hidden settings with no controls in this plugin (time limits, automatic refunds, notification toggles).
- The custom `wc_order_cancellation_requests` table is no longer written to. Requests are stored as order meta (HPOS-safe), and the old table is left in place.

### Verified

- WordPress 7.1.3, WooCommerce 11.2.1, PHP 8.3; Twenty Twenty-Five (block) and Storefront (classic); HPOS on and off.
- Plugin Check 2.1.0 with experimental and low-severity checks: no findings.
- PHPCS (WordPress, PHPCompatibilityWP 7.4+): no findings.

## [1.1.0]

- Earlier release as "Unstoppable WC Cancel Orders" (`wc-order-cancellation`), distributed from unstoppablesolutions.app.

## [1.0.9]

- Earlier release as "Unstoppable Order Cancellation" (free).
