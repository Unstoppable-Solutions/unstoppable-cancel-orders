# Contributing

Thanks for your interest. This plugin lives on GitHub for development and on
WordPress.org for distribution. Issues, pull requests and discussions all
happen here on GitHub.

## Reporting bugs

Open an issue with:

- WordPress version
- WooCommerce version, and whether HPOS (High-Performance Order Storage) is on
- PHP version
- Plugin version
- Theme (block or classic)
- Mode (Request or Direct)
- Steps to reproduce
- Expected vs. actual behaviour

## Pull requests

- Branch from `main`, submit a PR back to `main`.
- The Plugin Check workflow runs on every PR and must pass.
- Run `vendor/bin/phpcs` locally; it reads `phpcs.xml.dist`.
- Follow the WordPress Coding Standards: tabs for indentation, and a doc comment on every function, class and hook.
- Prefix every global identifier with `uoca` (`UOCA_` for classes and constants): functions, options, hooks, AJAX actions, nonces, script/style handles and CSS classes.
- Every request read uses `isset()` + `wp_unslash()` + a sanitizer; every output is escaped for its context; every state change checks a nonce **and** a capability.
- Don't add custom CSS/JS/PHP input fields, printed `<style>`/`<script>` tags, remote assets, remote requests, licence code or upgrade notices. They are not allowed in a WordPress.org plugin.
- Never overwrite or delete stored settings or order data on activation or update.
- Add a `readme.txt` and `CHANGELOG.md` entry for user-facing changes.
- Test the built ZIP on a real WordPress + WooCommerce site.

## Security

Please report security issues privately rather than opening a public issue:
support@unstoppablesolutions.app.
