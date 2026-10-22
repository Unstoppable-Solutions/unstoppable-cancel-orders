# unstoppable-cancel-orders

> This file is for Claude Code sessions working on this repo. For a human-oriented overview see `README.md`.

## What this plugin does

Lets WooCommerce customers cancel eligible orders from My Account, or send a cancellation request that the store approves or declines. This repo is the **free** WordPress.org plugin.

- **Plugin name:** Unstoppable Cancel Orders for WooCommerce
- **Main PHP file:** `unstoppable-cancel-orders.php`
- **Code prefix:** `UOCA_` (constants / classes); `uoca_` (functions, options, hooks, AJAX actions, nonces, handles); `uoca-` (CSS classes, menu slug `uoca-settings`)
- **Text domain:** `unstoppable-cancel-orders` — including the Options Studio shell strings
- **Package:** `Unstoppable_Cancel_Orders`
- **WordPress.org slug:** `unstoppable-cancel-orders` (not yet submitted)
- **WP.org URL (after approval):** https://wordpress.org/plugins/unstoppable-cancel-orders/

## Governing documents

Read these before changing code. They live in the claude.ai project "Cancel Woo Order", not in this repo:

1. **WordPress Plugin Development & Submission Guide** (Unstoppable Solutions, 2026-10) — takes precedence on WordPress.org policy.
2. **Unstoppable Options Studio — Development Guide v1.2.0** — shell, menu, prefixes, assets, notices, free-vs-Pro rules.

Hard rules for this free build: no licence code, no updater, no "Check for Updates" button, no upgrade notices or PRO placeholders, no custom CSS/JS/PHP fields, no printed `<style>`/`<script>` tags, no CDN assets, notices only on the plugin's own screen. Pro is mentioned only as the static link in the settings footer.

## Architecture

| File | Responsibility |
|---|---|
| `unstoppable-cancel-orders.php` | Header, Options Studio shell v1.2.0, constants, bootstrap, HPOS/blocks compatibility |
| `includes/class-uoca-plugin.php` | Loads on `plugins_loaded`, registers the `wc-pending-cancel` status, detects the legacy build |
| `includes/class-uoca-settings.php` | Single option `uoca_settings`: defaults, `sanitize()`, one-time legacy migration |
| `includes/class-uoca-cancellation.php` | Eligibility, ownership checks, AJAX `uoca_cancel_order`, cancel / request / approve / decline |
| `includes/class-uoca-frontend.php` | My Account + order-received buttons, email link, modal, front-end assets |
| `includes/class-uoca-admin.php` | Options Studio page (Dashboard + Settings tabs), admin-post approve/decline, order-editor actions |
| `includes/class-uoca-admin-notices.php` | Welcome + flash notices, own screen only |
| `includes/class-uoca-emails.php` + `includes/emails/` | Three plugin emails; hooks WooCommerce's own Cancelled order emails to approved requests |
| `templates/` | Modal and email templates, overridable from the theme's `woocommerce/` folder |

Key facts:

- **Settings are one form on one tab.** `UOCA_Settings::sanitize()` merges into the stored array, so keys it doesn't know (e.g. ones a Pro add-on adds) survive a save. Don't split settings across tabs without the declared-fields pattern from the Options Studio guide §9.
- **Order status slug `wc-pending-cancel` is kept from the legacy build** so existing orders keep their status. Don't rename it.
- **Stock** is restored by WooCommerce itself on the transition to Cancelled. **Refunds are never created** — the legacy build's auto-refund recorded refunds without returning money; don't reintroduce that in the free build.
- **Emails:** `uoca_request_submitted` and `uoca_request_declined` are registered in `woocommerce_email_actions`, so email classes hook `{action}_notification`. Approved requests reuse `WC_Email_Cancelled_Order` / `WC_Email_Customer_Cancelled_Order` on `woocommerce_order_status_pending-cancel_to_cancelled_notification`.
- **Legacy build:** if class `WC_Order_Cancellation` exists (old Free 1.x or Pro 2.x), the customer-facing classes are not loaded, so customers never see two buttons. Settings are copied from `wc_order_cancellation_*` once (`uoca_migrated_from_legacy`); the old options are never deleted.
- **Hooks for a future Pro add-on:** `uoca_can_cancel_order`, `uoca_cancellation_reasons`, `uoca_settings_defaults`, `uoca_admin_tabs`, `uoca_render_admin_tab`, `uoca_admin_notices`, and actions `uoca_order_cancelled`, `uoca_request_submitted`, `uoca_request_approved`, `uoca_request_declined`.

## Base44 Pro mapping

- **Base44 app:** `fm-web-solutions-plugin-hub-d05f2182` (licences and updates for Pro only). The free plugin makes **no** remote requests.
- Pro is to be rebuilt as a separate add-on, `unstoppable-cancel-orders-pro`, in its own private repo, extending this plugin through the hooks above. The current Pro 2.3.0 is a standalone legacy build.
- When updating code that affects both the free plugin and the Pro add-on, apply the matching change in both places in the same session.

## Release workflow

Releases are deployed to WordPress.org SVN automatically by GitHub Actions. The workflow is identical across every `unstoppable-solutions/*` plugin:

1. Finish feature work on `main`.
2. Bump the version in **all** of these:
   - `unstoppable-cancel-orders.php` — `Version:` header
   - `unstoppable-cancel-orders.php` — `UOCA_VERSION` constant
   - `readme.txt` — `Stable tag:`
   - `readme.txt` — add a `== Changelog ==` entry
   - `CHANGELOG.md` — add the same entry
3. Regenerate the translation template: `wp i18n make-pot . languages/unstoppable-cancel-orders.pot --slug=unstoppable-cancel-orders`
4. Commit and push `main`.
5. On GitHub, create a new **Release** tagged `vX.Y.Z` (semver) with the changelog in the body.
6. The `deploy-to-wporg.yml` workflow fires and pushes the release to WP.org SVN.

**Never publish a Release, or run the deploy workflow by hand, without Dave's explicit approval.** The deploy is dormant until the plugin is accepted into the directory and `SVN_USERNAME` / `SVN_PASSWORD` are available to the repo.

## CI

Every push and PR runs the official WordPress Plugin Check against the codebase (`.github/workflows/plugin-check.yml`). CI must pass before merging. The deploy workflow (`.github/workflows/deploy-to-wporg.yml`) only fires on published Releases or a manual run.

**Secrets used by deploy:** `SVN_USERNAME`, `SVN_PASSWORD` — org-level secrets on `unstoppable-solutions`. Nothing per-repo.

Both workflows carry an `if: ${{ ! github.event.repository.is_template }}` guard, so they are inert on the template itself.

Local checks before pushing:

```bash
vendor/bin/phpcs                        # uses phpcs.xml.dist (WordPress + PHPCompatibilityWP 7.4+)
wp plugin check unstoppable-cancel-orders --include-experimental
```

## Known phpcs:ignores specific to this plugin

- `includes/class-uoca-admin.php` — `NonceVerification.Recommended` on reading `$_GET['tab']`: read-only navigation, validated against the tab list, changes no state.
- `includes/class-uoca-frontend.php` — `NonceVerification.Recommended` on reading `$_GET['key']`: the order-received URL's order key, only compared with `hash_equals()` against the order's own key.
- `templates/emails/uoca-cancellation.php` and `templates/emails/plain/uoca-cancellation.php` — file-level `PrefixAllGlobals.NonPrefixedHooknameFound`: these templates fire WooCommerce's own email hooks (`woocommerce_email_header`, `woocommerce_email_order_details`, …) exactly as WooCommerce's templates do.

## Shared conventions across the Unstoppable Solutions org

- **GPL-2.0-or-later** on everything.
- **One author** for Contributors: in `readme.txt`: `paramedic192` (Dave Stevens).
- **Tested up to** is bumped as WordPress ships each new .x (major.minor only, e.g. `7.1`).
- **Plugin Check CI config** (`.github/workflows/plugin-check.yml`) is standard across the org: `exclude-checks: late_escaping` + `exclude-files` lists the GitHub-only files.
- **`.distignore`** keeps GitHub-only files out of the WP.org zip (CI, docs, this file, `phpcs.xml.dist`).
- **Prefer `phpcs:ignore` + inline rationale** over removing the ignored check from CI entirely.
- **Don't `-c user.email=` with a random identity** when committing — use `fieldmedics4@gmail.com` / `Dave Stevens` for authorship, with Claude as Co-Authored-By.

## Working with the plugin from Claude Code

- Workflow for a bugfix / feature:
  1. Branch from `main`, open a PR.
  2. Watch Plugin Check CI — do not merge red.
  3. Test the **built ZIP** on a real WordPress + WooCommerce install (Options Studio guide §17): every settings save, request → approve, request → decline, direct cancel, guest cancel, block and classic theme, HPOS on and off.
  4. For a release: follow the Release workflow section above.
- Don't start speculative `ROADMAP.md` items without Dave's approval.
- The free plugin and its Pro add-on live in **separate repos**. This repo is the free side.

## Repository layout

Standard WordPress plugin layout — `unstoppable-cancel-orders.php` is the bootstrap, `includes/` holds the classes, `templates/` holds overridable templates, `assets/` holds CSS/JS, `languages/` holds the `.pot`, `readme.txt` is the WP.org directory listing, `.github/` holds the CI and deploy workflows. `ROADMAP.md` tracks planned work.
