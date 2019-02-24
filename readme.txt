=== GazChap's WooCommerce getAddress.io Plugin ===
Contributors: gazchap
Tags: woocommerce,ecommerce,address lookup,postcode lookup,uk address lookup,united kingdom,great britain,england,scotland,wales
Tested up to: 5.1
License: GNU General Public License v2.0
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: trunk
Donate link: https://paypal.me/gazchap

Adds a postcode lookup tool into WooCommerce's billing and shipping address areas on the Checkout. The postcode lookup tool uses https://getaddress.io to do the lookups, and an API key is therefore required. See installation instructions below.

== Description ==
This plugin enables your customers to complete their billing/shipping address by entering their postcode and selecting their address from the options given.

It utilises getAddress.io (https://getaddress.io) to do the lookup using their Royal Mail Postcode Address File.

An API key is required for the integration to work, and the integration only appears when the customer has selected United Kingdom (or, more specifically, the ISO-3166-2 code "GB") for their country.

== Requirements ==

[WordPress](https://wordpress.org). Tested up to version 5.1.
[WooCommerce](https://woocommerce.com). Tested with versions up to 3.5.5, minimum version is likely 3.0.0.
[getAddress.io API Key](https://getaddress.io). A number of pricing plans are available.

== Installation ==

Install via the WordPress Plugin Directory, or download a release from this repository and install as you would a normal WordPress plugin.

== Usage ==

Once installed and activated, you need to go to the WooCommerce -> Settings -> General page in the WordPress dashboard. You can enter your API key and set your other options here.

== Changelog ==
= 1.0 (24/02/2019) =

* Initial release.

== License ==
Licensed under the [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.html)

