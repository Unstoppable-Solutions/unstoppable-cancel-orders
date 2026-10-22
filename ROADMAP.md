# Roadmap

Planned work for **Unstoppable Cancel Orders for WooCommerce** (free, WordPress.org) and its future Pro add-on.

Nothing here is a commitment until it's marked **Planned**. Items marked **Proposed** need Dave's approval before work starts; approved items get a GitHub Issue and a milestone.

**Columns:** Priority High / Medium / Low · Size Small / Medium / Large · Status Proposed / Planned / In Progress / Completed · Source:

- **Spec**: the original project requirements (a confirmed requirement)
- **Discussed**: raised in earlier project conversations
- **Code review**: found while rebuilding 2.0.0
- **New**: a new suggestion

**Free or Pro.** WordPress.org forbids locking features inside the free plugin. Anything marked *Pro* must ship as code in the separate `unstoppable-cancel-orders-pro` add-on, never as a gated feature here.

---

## Completed in 2.0.0

| Item | What changed | Status | Source |
|---|---|---|---|
| WordPress.org compliance rebuild | New slug and text domain, `uoca` prefix, Options Studio shell v1.2.0; licence code, updater and upgrade page removed | Completed | Code review |
| Settings migration | One-time copy from `wc_order_cancellation_*`; originals kept | Completed | Code review |
| Request workflow made usable | Approve or decline from the Dashboard tab and the order editor; decline restores the previous status | Completed | Spec |
| Request emails | Admin new request; customer received and declined; approvals reuse WooCommerce's Cancelled order emails | Completed | Spec |
| Editable reasons, notes required/optional/off | Reasons list and both fields configurable | Completed | Spec |
| Guest cancellation | Order-received page, verified with the order key | Completed | Spec / Discussed |
| Cancel link in customer emails | Registered customers, while the order is still cancellable | Completed | Spec / Discussed |
| Removed misleading auto-refund | Earlier build recorded refunds without returning money | Completed | Code review |
| Fixed orders-table button, duplicate thank-you button | Both confirmed broken in 1.1.0 | Completed | Code review |
| Repo docs and CI | README, CLAUDE.md, CHANGELOG, CONTRIBUTING, ROADMAP; Plugin Check CI; deploy slug set | Completed | Spec (migration brief) |

---

## Immediate priorities: 2.0.0 release

| Item | Description and purpose | User benefit | Priority | Size | Milestone | Status | Source |
|---|---|---|---|---|---|---|---|
| Decide the free/Pro split | 2.0.0 gives free users the request workflow and editable reasons, which earlier plans reserved for Pro. Confirm, or remove them before submission. | Clear product boundaries | High | Small | 2.0.0 | Proposed | Discussed |
| Confirm the Plugin URI page | `unstoppablesolutions.app/plugins/unstoppable-cancel-orders/` returns 200 for any path, so its content couldn't be verified automatically. | Reviewers and users reach a real page | High | Small | 2.0.0 | Proposed | Code review |
| Plugin Check CI green on `main` | Merge the 2.0.0 PR only after the workflow passes. | Directory compliance | High | Small | 2.0.0 | In Progress | Spec (migration brief) |
| Submit to WordPress.org | Submit the built ZIP; answer reviewer feedback; add `SVN_USERNAME`/`SVN_PASSWORD` after approval. | Free distribution and updates | High | Small | 2.0.0 | Proposed | Spec (migration brief) |
| Validate the deploy workflow | After approval, make a first release and confirm `trunk/`, `tags/2.0.0` and the Release ZIP are correct. Don't run it before approval. | Reliable releases | High | Small | 2.0.0 | Planned | Spec (migration brief) |
| WordPress.org assets and screenshots | Icon, banner and 3–5 real screenshots (popup, Dashboard tab, Settings tab, order editor). Assets go in SVN `assets/`, not the plugin ZIP. | Better listing | Medium | Small | 2.0.0 | Proposed | Discussed |
| Retire the old free build | Stop distributing `wc-order-cancellation` 1.1.x from Base44 once 2.0.0 is live; point existing users at the migration notes. | One supported version | Medium | Small | 2.0.0 | Proposed | Code review |

## Short-term improvements: 2.1.0

| Item | Description and purpose | User benefit | Priority | Size | Milestone | Status | Source |
|---|---|---|---|---|---|---|---|
| Time limit | Hide the button X minutes / hours / days / months / years after the order was created (UTC-safe). Free or Pro is to be decided. | Stops late cancellations | High | Medium | 2.1.0 | Proposed | Spec |
| Notes only for chosen reasons | Show the notes box only when a chosen reason (e.g. "Other") is picked, as well as "always". Existed hidden in 1.1.0; dropped in 2.0.0. | Less friction for customers | Medium | Small | 2.1.0 | Proposed | Spec |
| Pending-request badge | Count bubble on the Options Studio > Order Cancellation menu item. | Requests aren't missed | Medium | Small | 2.1.0 | Proposed | Discussed |
| Orders-list indicator | Colour for the Pending Cancellation status badge, plus a reason tooltip in WooCommerce > Orders. | Faster triage | Medium | Small | 2.1.0 | Proposed | Spec |
| Dashboard paging and filters | The Dashboard tab lists the oldest 20 requests; add paging and an approved/declined history view. | Works for busy stores | Medium | Small | 2.1.0 | Proposed | Code review |
| Decline message to customer | Optional note from the store, included in the declined email. | Clearer communication | Medium | Small | 2.1.0 | Proposed | New |
| Retry limit after decline | Choose whether a customer can request again after a decline. | Prevents repeat requests | Low | Small | 2.1.0 | Proposed | Discussed |
| Automated tests | PHPUnit with the WordPress and WooCommerce test suites, covering eligibility, ownership, settings sanitizing and migration. Add PHPCS to CI. | Fewer regressions | Medium | Medium | 2.1.0 | Proposed | Code review |
| Remove legacy meta fallback | Stop reading `_cancellation_*` meta and remove the legacy-build detection once old installs have migrated. | Simpler code | Low | Small | 2.3.0 | Proposed | Code review |

## Mid-term feature development: 2.2.0 – 2.x

| Item | Description and purpose | User benefit | Priority | Size | Milestone | Status | Source |
|---|---|---|---|---|---|---|---|
| Status mapping per event | Choose the status applied on request submitted / approved / declined / direct cancel (default Cancelled). Free or Pro is to be decided. | Fits custom order flows | Medium | Medium | 2.2.0 | Proposed | Spec |
| Auto-approve requests | Approve requests automatically, keeping the request record and emails. | Less admin work | Medium | Small | 2.2.0 | Proposed | Spec |
| Mode per status | e.g. direct cancel while Pending/On hold, request once Processing. | Flexible policy | Medium | Medium | 2.2.0 | Proposed | Discussed |
| Bulk approve / decline | Bulk actions in the Dashboard tab and WooCommerce > Orders. | Handles volume | Medium | Medium | 2.2.0 or Pro | Proposed | Discussed |
| Order-total rules | Allow direct cancel below $X, require a request above $Y. | Protects high-value orders | Low | Medium | 2.x or Pro | Proposed | Discussed |
| Guest order lookup | A page where guests enter their email and order number to find a cancellable order. | Guests can cancel later, not just on the confirmation page | Low | Medium | 2.x | Proposed | Discussed |
| Logging | Write cancellations and decisions to the WooCommerce logger (WooCommerce > Status > Logs). | Audit trail | Low | Small | 2.x | Proposed | Spec |
| Pending-request reminder email | Daily admin digest of requests waiting longer than N days. | Requests aren't forgotten | Low | Medium | 2.x | Proposed | Spec |

## Long-term development: Pro add-on and beyond

| Item | Description and purpose | User benefit | Priority | Size | Milestone | Status | Source |
|---|---|---|---|---|---|---|---|
| Rebuild Pro as an add-on | `unstoppable-cancel-orders-pro` (private repo) extends this plugin through its hooks (`uoca_can_cancel_order`, `uoca_admin_tabs`, …) with Base44 licensing and updates. Replaces the standalone Pro 2.3.0. | Clean upgrade path; free and Pro run together | High | Large | Pro 3.0 | Proposed | Discussed |
| Gateway refunds (Pro) | Optional automatic refund through the payment gateway (full or partial), with a clear result note and a failure fallback. | Saves refund time | Medium | Large | Pro 3.0 | Proposed | Discussed |
| Reports and export (Pro) | Cancellation rate, top reasons, revenue impact, CSV export. | Understand why customers cancel | Medium | Medium | Pro 3.x | Proposed | Discussed |
| Per-product / per-category rules (Pro) | Exclude products or categories, or give them their own time limits. | Fine-grained control | Medium | Large | Pro 3.x | Proposed | Discussed |
| Per-role time limits (Pro) | Different windows for different customer roles. | Tailored policies | Low | Medium | Pro 3.x | Proposed | Discussed |
| Webhooks and integrations (Pro) | Webhook on cancel/request, Slack alert for high-value orders, Zapier. Must be disclosed as an external service and be opt-in. | Fits existing workflows | Low | Medium | Pro 3.x | Proposed | Discussed |
| WooCommerce Subscriptions | Decide how parent and renewal orders behave; avoid cancelling a subscription by accident. | Safe on subscription stores | Low | Large | 3.x | Proposed | Discussed |
| Partial cancellation | Cancel some line items rather than the whole order. | Saves the rest of the sale | Low | Large | 3.x | Proposed | New |
| Retention offer | Optional offer (e.g. a coupon) before the request is submitted. | Fewer lost orders | Low | Large | 3.x or Pro | Proposed | New |
| REST API endpoints | Cancel and request through the Store API for headless stores. | Headless support | Low | Medium | 3.x | Proposed | New |

---

## Ruled out

- **Custom CSS field in settings.** Raised in earlier planning, but not allowed in WordPress.org plugins (August 2026 review). Styling stays with the theme, *Additional CSS*, or template overrides.
- **Upgrade notices, locked "PRO" placeholders, licence checks in the free plugin.** Not allowed under Guideline 5.
