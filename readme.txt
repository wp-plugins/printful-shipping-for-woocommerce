=== Printful live shipping rates for WooCommerce ===
Contributors: girts_u
Tags: woocommerce, printful, shipping, shipping rates, fulfillment, printing, fedex, carriers, checkout
Requires at least: 3.8
Tested up to: 4.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Calculate live shipping rates based on actual Printful shipping costs

== Description ==

Display actual live shipping rates from carriers like FedEx on your WooCommerce checkout page. This plugin will return a list of available shipping rates specific to the shipping address your customer provides when checking out. These rates are identical to the list you get when you submit an order manually via Printful dashboard.

= Known Limitations =

* Works with WooCommmerce 2.1 and up
* Works only if every item in the order is fulfilled by Printful

== Installation ==
1. Upload 'printful-shipping' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enable rate calculation by adding your Printful API key to WooCommerce->Settings->Shipping->Printful Shipping tab

== Frequently Asked Questions ==

= How do I get Printful API key? =

Go to https://www.theprintful.com/dashboard/store , select your WooCommerce store, click "Edit" and then click
"Enable API Access". Your API key will be generated and displayed there.

== Screenshots ==

1. Settings dialog
2. Shipping rate selection

== Upgrade Notice ==

= 1.0.2 =
Added option to disable SSL

= 1.0.1 =
Minor improvements

= 1.0 =
First release

== Changelog ==

= 1.0.2 =
* Added option to disable SSL for users that do not have a valid CA certificates in their PHP installation

= 1.0.1 =
* Removed CURLOPT_FOLLOWLOCATION that caused problems on some hosting environments
* Added option to display reason status messages if the rate API request has failed

= 1.0 =
* First release
