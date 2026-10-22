=== Unstoppable Cancel Orders for WooCommerce ===
Contributors: paramedic192
Tags: woocommerce, cancel order, order cancellation, my account, cancellation request
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers cancel eligible orders from My Account, or send you a cancellation request to approve or decline.

== Description ==

Unstoppable Cancel Orders adds a cancel button to WooCommerce orders that are still in a status you choose, such as Processing or On hold. You decide whether customers cancel straight away or send a request for you to review.

**Two modes**

* **Request:** the order moves to a new *Pending Cancellation* status. You approve or decline it from the plugin's Dashboard tab or from the Order actions box in the order editor. Declined orders go back to the status they had before.
* **Direct:** the order is cancelled immediately.

**What you can configure**

* Which order statuses can be cancelled.
* Which customer roles see the button (or every registered customer).
* Whether guests can cancel from the order confirmation page.
* A reason picker (off, optional or required) with your own list of reasons.
* An additional notes box (off, optional or required).
* Button labels, popup title and popup message.
* A cancel link in customer order emails while the order can still be cancelled.

**Where customers see it**

* My Account > Orders.
* My Account > View order.
* The order confirmation (thank you) page, including for guests when enabled.

**Emails**

* *Cancellation request (admin)*: tells you a customer asked to cancel.
* *Cancellation request received*: confirms the request to the customer.
* *Cancellation request declined*: tells the customer their order will go ahead.
* When a request is approved, WooCommerce's own *Cancelled order* emails are sent, exactly as they are when an order is cancelled any other way.

All emails can be edited or turned off under WooCommerce > Settings > Emails.

**Stock and refunds**

When an order becomes Cancelled, WooCommerce restores any stock it reduced for that order. The plugin does not create refunds: refunding a payment stays your decision, made in the order editor as usual.

**Built to fit in**

* Works with WooCommerce High-Performance Order Storage (HPOS).
* No external services, no tracking, no remote requests.
* Every feature in the plugin is available to everyone. Nothing is locked.
* Appears under the shared *Options Studio* menu alongside other Unstoppable Solutions plugins.

== External Services ==

This plugin does not connect to any external services.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin and activate it, or install it from Plugins > Add New.
3. Go to Options Studio > Order Cancellation > Settings.
4. Choose the mode, the cancellable statuses and the reasons, then save.

== Frequently Asked Questions ==

= Where are cancellation requests handled? =

On the Dashboard tab under Options Studio > Order Cancellation, and in the Order actions box when you edit an order that is Pending Cancellation. Pending requests are also listed under WooCommerce > Orders with the Pending Cancellation status.

= Does cancelling refund the customer? =

No. The plugin never creates refunds or contacts a payment gateway. Use WooCommerce's Refund button in the order editor when you want to return a payment.

= Is stock put back? =

Yes, by WooCommerce itself, when the order becomes Cancelled and stock had been reduced for it.

= Why does the customer get no email when I approve a request? =

Approved requests use WooCommerce's own *Cancelled order* customer email. Depending on your WooCommerce version it may be off by default. Turn it on under WooCommerce > Settings > Emails.

= My "Pending payment" orders show two cancel buttons. =

WooCommerce already shows its own Cancel button for unpaid orders. If you include Pending payment in the cancellable statuses you will see both; leave that status out to keep only WooCommerce's button.

= Can guests cancel? =

Yes, if you enable guest orders. Guests use the order confirmation page, which is protected by the order's private key. Guests do not get a cancel link in emails, because they have no My Account page.

= I used the earlier "Unstoppable WC Cancel Orders" plugin. =

Your settings are copied automatically the first time this version runs, and the earlier settings are left untouched. Orders that were waiting in Pending Cancellation can be approved or declined here. Deactivate and delete the earlier plugin once you have checked your settings; while it is active this plugin keeps its own buttons hidden so customers never see two.

= Can I change how the popup looks? =

Copy `templates/myaccount/uoca-cancellation-modal.php` to `yourtheme/woocommerce/myaccount/uoca-cancellation-modal.php` and edit it there. For styling, use Appearance > Customize > Additional CSS or the Site Editor.

= What happens to my data if I delete the plugin? =

By default nothing is removed. Tick "Delete this plugin's settings when it is deleted" on the Settings tab if you want the settings removed. Orders, order notes and the cancellation details stored on orders are never deleted.

== Changelog ==

= 2.0.0 =
* New name, slug and text domain: Unstoppable Cancel Orders for WooCommerce (unstoppable-cancel-orders).
* Settings moved to Options Studio > Order Cancellation, with Dashboard and Settings tabs.
* Settings from the earlier plugin are copied automatically; the originals are kept.
* New: approve or decline requests from the Dashboard tab or the order editor's Order actions box.
* New: emails for new requests (admin), request received and request declined (customer).
* New: approved requests trigger WooCommerce's own Cancelled order emails.
* New: editable reason list, and off/optional/required options for reasons and notes.
* New: option to remove settings on uninstall (off by default).
* Changed: cancellations no longer create refund records. Earlier builds recorded a refund automatically without returning money through the payment gateway.
* Changed: stock is restored by WooCommerce rather than by the plugin, so it is never restored twice.
* Fixed: the cancel button in the My Account orders table did nothing.
* Fixed: the order confirmation page showed the cancel button twice.
* Fixed: a declined request now returns the order to the status it had before, instead of always Processing.
* Security: all request data is unslashed, sanitized and validated; every action checks a nonce and the user's permission; guest orders are verified with the order key.
* Removed: licence code, update checker, upgrade page and promotional notices.
* Removed: hidden settings that had no controls in this plugin (time limits, automatic refunds, notification toggles).
* Removed: the "Network" header that prevented per-site activation on multisite.
* Tested with WordPress 7.1 and WooCommerce 11.2.

= 1.1.0 =
* Earlier release under the name "Unstoppable WC Cancel Orders" (wc-order-cancellation).

== Upgrade Notice ==

= 2.0.0 =
New slug and settings location. Settings from the earlier plugin are copied automatically. Deactivate the earlier plugin after checking your settings.
