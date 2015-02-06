=== Printful Integration for WooCommerce ===
Contributors: girts_u
Tags: woocommerce, printful, drop shipping, shipping, shipping rates, fulfillment, printing, fedex, carriers, checkout, t-shirts
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Calculate live shipping rates and tax rates based on actual Printful shipping costs

== Description ==

Display actual live shipping rates from carriers like FedEx on your WooCommerce checkout page. This plugin will return a list of available shipping rates specific to the shipping address your customer provides when checking out. These rates are identical to the list you get when you submit an order manually via Printful dashboard.

This plugin will also automatically calculate taxes where it is required for Printful so that your originally intended profit margin stays intact.

= Known Limitations =

* Works with WooCommmerce 2.1 and up
* Works only if every item in the order is fulfilled by Printful

== Installation ==
1. Upload 'printful-shipping' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add your Printful API key to WooCommerce->Settings->Integration->Integration tab
1. Enable shipping rate calculation in WooCommerce->Settings->Shipping->Printful Shipping tab
1. To automatically calculate taxes please check 'Enable taxes and tax calculations' under WooCommerce Tax settings.
1. Then go to 'Integration' tab and check 'Calculate sales tax for locations where it is required for Printful orders'.

== Frequently Asked Questions ==

= How do I get Printful API key? =

Go to https://www.theprintful.com/dashboard/store , select your WooCommerce store, click "Edit" and then click "Enable API Access". Your API key will be generated and displayed there.

== Screenshots ==

1. Plugin settings dialog
2. Shipping rate dialog
3. Shipping rate selection

== Upgrade Notice ==

= 1.1.1 =
Ignore virtual and downloadable products when calculating shipping rates

= 1.1 =
Added tax rate calculation

= 1.0.2 =
Added option to disable SSL

= 1.0.1 =
Minor improvements

= 1.0 =
First release

== Changelog ==

= 1.1.1 =
* Ignore virtual and downloadable products when calculating shipping rates

= 1.1 =
* Added option to calculate sales tax rates for locations where it is required for Printful orders
* Added automatic conversion of shipping rates to the currency used by Woocommerce
* Printful API client library updated to use Wordpress internal wp_remote_get method instead of CURL directly
* Changed plugin code structure for easier implementation of new features in the future

= 1.0.2 =
* Added option to disable SSL for users that do not have a valid CA certificates in their PHP installation

= 1.0.1 =
* Removed CURLOPT_FOLLOWLOCATION that caused problems on some hosting environments
* Added option to display reason status messages if the rate API request has failed

= 1.0 =
* First release
