=== GazChap's WooCommerce getAddress.io Postcode Lookup ===
Contributors: gazchap
Tags: woocommerce,ecommerce,address lookup,postcode lookup,uk address lookup,united kingdom,great britain,england,scotland,wales
Requires at least: 4.8.0
Requires PHP: 5.6
Tested up to: 5.4.2
WC tested up to: 4.3.0
License: GNU General Public License v2.0
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: trunk
Donate link: https://paypal.me/gazchap

Adds a postcode lookup tool into WooCommerce's billing and shipping address areas on the Checkout. The postcode lookup tool uses https://getaddress.io to do the lookups, and an API key is therefore required. See installation instructions below.

== Description ==
This plugin enables your customers to complete their billing/shipping address by entering their postcode and selecting their address from the options given.

It utilises getAddress.io (https://getaddress.io) to do the lookup using their Royal Mail Postcode Address File.

An API key is required for the integration to work, and the integration only appears when the customer has selected United Kingdom (or, more specifically, the ISO-3166-2 code "GB") for their country.

It can optionally be turned off for the shipping or billing addresses, and it can be configured to send an email notification to a nominated address if the getAddress.io API key hits its usage limit.

== Testing ==
getAddress.io offers several test postcodes that can be entered to test that it is working without impacting on your usage limits. These are:

`XX2 00X` - Returns a 'successful' response 200. Your request was successful.
`XX4 04X` - Returns 'not found' error 404. No addresses could be found for this postcode.
`XX4 00X` - Returns 'bad request' error 400. Your postcode is not valid.
`XX4 01X` - Returns 'forbidden' error 401. Your api-key is not valid.
`XX4 29X` - Returns 'too many requests' error 429. You have made more requests than your allowed limit.
`XX5 00X` - Returns 'server error' error 500. Server error, you should never see this.

See the getAddress.io documentation (https://getaddress.io/Documentation) for more details.

== Requirements ==

[WordPress](https://wordpress.org). Tested up to version 5.4.2.
[WooCommerce](https://woocommerce.com). Tested with versions up to 4.3.0, minimum version is likely 3.0.0.
[getAddress.io API Key](https://getaddress.io). A number of pricing plans are available.

== Installation ==

Install via the WordPress Plugin Directory, or download a release from this repository and install as you would a normal WordPress plugin.

== Usage ==

Once installed and activated, you need to go to the WooCommerce -> Settings -> General page in the WordPress dashboard. You can enter your API key and set your other options here.

== Filter Reference ==

For developers, I've included a few filters that you can use to customise certain aspects of the plugin. These are:

= Error Messages =

All of these filters take one argument, `$message`, the error message that will be displayed to the user.

`gazchaps-woocommerce-getaddress-io_api_error_400` - shown when the postcode supplied is invalid/empty
`gazchaps-woocommerce-getaddress-io_api_error_401` - shown when the API key provided in the settings is invalid
`gazchaps-woocommerce-getaddress-io_api_error_404` - shown when no addresses were found for the supplied postcode
`gazchaps-woocommerce-getaddress-io_api_error_429` - shown when the API key has hit its usage limit
`gazchaps-woocommerce-getaddress-io_api_error_500` - shown when there is a server error at getAddress.io

Note: By default, the error messages are "customer friendly", i.e. they don't go into much detail about the cause of the error!

= Styling =

`gazchaps-woocommerce-getaddress-io_billing_selector_row_class` - changes the CSS class on the form-row that the billing address selector is placed into
`gazchaps-woocommerce-getaddress-io_shipping_selector_row_class` - changes the CSS class on the form-row that the shipping address selector is placed into
`gazchaps-woocommerce-getaddress-io_clear_additional_fields` - defaults to true, set to '__return_false' to stop the "additional fields" area of the checkout having a clear style applied to it

= Over Usage Email Notification =

`gazchaps-woocommerce-getaddress-io_overusage_email_recipient` - change the recipient passed to wp_mail()
`gazchaps-woocommerce-getaddress-io_overusage_email_subject` - change the subject line passed to wp_mail() for the over-usage email
`gazchaps-woocommerce-getaddress-io_overusage_email_message` - change the message body passed to wp_mail() for the over-usage email

== Changelog ==
= 1.4 (12/06/2020) =

* Uses the postcode returned by getAddress.io to "tidy up" the postcode field after the lookup is completed. Thanks to bootle for the suggestion.

= 1.3 (06/05/2020) =

* Updated JS to trigger WooCommerce's order update Ajax request when an address is selected. Thanks to rfvdan for the report.

= 1.2 (02/05/2019) =

* Fixed a bug that caused the JavaScript to stop running prematurely if a particular address field was not present on the checkout page. Thanks to Max Devlin for the report.

= 1.1 (25/02/2019) =

* Added a caching mechanism to prevent repeated lookups for the same postcode in the same browser session, thus saving API usage

= 1.0 (24/02/2019) =

* Initial release.

== License ==
Licensed under the [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.html)

== Screenshots ==

1. The Find Address button on the default Storefront theme, before the lookup has been completed
2. The address selection drop-down menu on display
3. The administrator settings in the WooCommerce administration dashboard
