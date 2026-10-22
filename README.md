# Unstoppable Cancel Orders for WooCommerce

Let WooCommerce customers cancel eligible orders from My Account, or send a cancellation request for the store to approve or decline.

This repository is the development home of the **free** plugin distributed on WordPress.org. The WordPress.org listing text lives in [`readme.txt`](readme.txt).

| | |
|---|---|
| Slug / text domain | `unstoppable-cancel-orders` |
| Current version | 2.0.0 |
| Requires | WordPress 6.5+, PHP 7.4+, WooCommerce 7.0+ |
| Tested with | WordPress 7.1.3, WooCommerce 11.2.1, PHP 8.3 |
| Licence | GPL-2.0-or-later |

## Features

- **Two modes.** *Request*: the order moves to **Pending Cancellation** and the store approves or declines. *Direct*: the order is cancelled immediately.
- **Approve or decline** from the plugin's Dashboard tab or from the order editor's *Order actions* box. Declined orders return to their previous status.
- **Eligibility rules:** cancellable order statuses, customer roles, and guest orders (verified by the order key).
- **Reason picker** (off / optional / required) with an editable list, and an **additional notes** box (off / optional / required).
- **Customisable text** for buttons, popup title and popup message.
- **Emails:** new request (admin), request received and request declined (customer). Approved requests send WooCommerce's own *Cancelled order* emails.
- **Cancel link in customer order emails** while the order can still be cancelled.
- Stock is restored by WooCommerce when an order is cancelled. **No refunds are created**; refunding stays a manual store decision.
- HPOS compatible. No external services, no tracking.
- Lives under the shared **Options Studio** admin menu used by every Unstoppable Solutions plugin.

## Installation

1. Install and activate WooCommerce.
2. Download the latest release ZIP from this repository's Releases page (or, once listed, install from Plugins > Add New).
3. Upload it under Plugins > Add New > Upload Plugin and activate it.
4. Configure it under **Options Studio > Order Cancellation > Settings**.

### Upgrading from "Unstoppable WC Cancel Orders" (`wc-order-cancellation`)

The earlier plugin used a different slug, so WordPress treats this as a new plugin. Install it alongside the old one: its settings are copied automatically on first run and the old settings are left untouched. While the old plugin is active, this one keeps its customer buttons hidden. Check the settings, then deactivate and delete the old plugin.

## Development

### Requirements

- PHP 7.4+ (CI and local testing use 8.3)
- Composer, for the coding-standards tools
- WP-CLI, for building the translation template and running Plugin Check

### Coding standards

```bash
composer require --dev wp-coding-standards/wpcs:^3 phpcompatibility/phpcompatibility-wp
vendor/bin/phpcs          # reads phpcs.xml.dist
vendor/bin/phpcbf         # auto-fix what can be fixed
```

The ruleset applies the WordPress standard, PHP 7.4+ compatibility, the `manage_woocommerce` capability, the text domain, and the `uoca` / `unstoppable_options_studio` / `unstoppable_studio` prefixes.

### Plugin Check

The [Plugin Check workflow](.github/workflows/plugin-check.yml) runs on every push and pull request to `main`. To run it locally against a WordPress install:

```bash
wp plugin install plugin-check --activate
wp plugin check unstoppable-cancel-orders --include-experimental
```

### Translations

```bash
wp i18n make-pot . languages/unstoppable-cancel-orders.pot --slug=unstoppable-cancel-orders
```

### Live testing

Always test the **built ZIP** on a real WordPress + WooCommerce site before a release: save the settings, then run request → approve, request → decline, direct cancel and guest cancel. Test on a block theme and a classic theme, with HPOS on and off. See `CLAUDE.md` for the full checklist.

## Releasing

Development → pull request (Plugin Check must pass) → merge to `main` → live ZIP test → approved GitHub Release `vX.Y.Z` → automated SVN deploy → WordPress.org.

1. Bump the version in the plugin header, the `UOCA_VERSION` constant and the `readme.txt` `Stable tag`. Add the changelog entry to `readme.txt` and `CHANGELOG.md`.
2. Merge to `main`.
3. Publish a GitHub Release tagged `vX.Y.Z`. [`deploy-to-wporg.yml`](.github/workflows/deploy-to-wporg.yml) pushes `trunk/` and `tags/X.Y.Z` to WordPress.org SVN using the files not excluded by [`.distignore`](.distignore), and attaches the ZIP to the Release.

The deploy is dormant until the plugin is accepted into the WordPress.org directory and the `SVN_USERNAME` / `SVN_PASSWORD` secrets are available. Publishing a Release is the only step that reaches WordPress.org, so do it only for an approved release.

## Repository layout

```
unstoppable-cancel-orders.php   Bootstrap, plugin header, Options Studio shell
includes/                       Plugin classes (settings, cancellation, front end, admin, emails)
templates/                      Modal and email templates (theme-overridable)
assets/                         CSS and JavaScript
languages/                      Translation template (.pot)
readme.txt                      WordPress.org listing
uninstall.php                   Optional settings removal on delete
.github/workflows/              Plugin Check CI and WordPress.org deploy
ROADMAP.md                      Planned work
CHANGELOG.md                    Release history
```

## Support

- Website: https://unstoppablesolutions.app
- Support: https://unstoppablesolutions.app/support · support@unstoppablesolutions.app
- Bugs and feature requests: GitHub Issues on this repository (see [`CONTRIBUTING.md`](CONTRIBUTING.md))

## Licence

GPL-2.0-or-later. See [`license.txt`](license.txt).
