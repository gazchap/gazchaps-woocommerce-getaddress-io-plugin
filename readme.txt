=== GazChap's WooCommerce getAddress.io Postcode Lookup ===
Contributors: gazchap
Tags: woocommerce,ecommerce,address lookup,postcode lookup,uk address lookup,united kingdom,great britain,england,scotland,wales
Requires at least: 4.8.0
Requires PHP: 5.6
Tested up to: 6.6.1
WC tested up to: 9.1.0
License: GNU General Public License v2.0
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 3.2.2
Donate link: https://ko-fi.com/gazchap

Adds a UK postcode lookup tool into WooCommerce's checkout process.

== Description ==
This plugin enables your customers to complete their billing/shipping addresses by entering their postcode and selecting their address from the options given.

It utilises getAddress.io (https://getaddress.io) to do the lookup using their Royal Mail Postcode Address File.

An API key is required for the integration to work, and the integration only appears when the customer has selected United Kingdom (or, more specifically, the ISO-3166-2 code "GB") for their country.

It can optionally be turned off for the shipping or billing addresses, and can be enabled or disabled in each of the checkout, customer account and WooCommerce admin screens.

Postcodes that are looked up are saved in a custom database table for 24 hours to help reduce your costs for use of the getAddress.io API.

== Requirements ==

[WooCommerce](https://woocommerce.com), at least version 3.0.
[getAddress.io API Key](https://getaddress.io). A number of pricing plans are available.

**Note:** Currently, this plugin does NOT support WooCommerce Checkout Blocks introduced as the default checkout experience in WooCommerce version 8.3, it can only be used with the WooCommerce checkout shortcode. I aim to make this plugin compatible with Checkout Blocks as soon as possible.

== Installation ==

Install via the WordPress Plugin Directory, or download a release from this repository and install as you would a normal WordPress plugin.

== Usage ==

Once installed and activated, you need to go to the WooCommerce -> Settings -> General page in the WordPress dashboard. You can enter your API key and set your other options here.

== Conflicts with other plugins ==

Certain plugins that also modify checkout fields can conflict with this plugin and prevent it from working.

Usually, the symptom of this is that the "Find Address" button does not appear.

Since version 2.1, I've added a Hook Priority setting at the bottom of the settings for the plugin that you can use to try and fix these conflicts.

The default priority is 10, but using a higher number makes my plugin modify the checkout later in the process - you will probably need to experiment to find the priority that makes my plugin's changes happen after the conflicting plugin.

For example, a priority of 1001 seems to fix conflicts with the "Checkout Field Editor" plugin by ThemeHigh.

== Filter Reference ==

For developers, I've included a few filters that you can use to customise certain aspects of the plugin. These are:

= User Interface =

All of these filters take one argument, `$text`.

`gazchaps-woocommerce-getaddress-io_find-address-button-text` - the text shown on the Find Address buttons (default: Find Address)
`gazchaps-woocommerce-getaddress-io_find-address-searching-text` - the text shown when the Find Address button is selected (default: Searching...)
`gazchaps-woocommerce-getaddress-io_enter-address-manually-text` - the text shown for the "Enter an address manually" link (default: Enter an address manually)

Note: Although these two texts are translatable, filters will override any translations.

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

== Changelog ==
= 3.2.2 (04/09/2024) =

* Bugfix - fixed a PHP warning being generated when a lookup fails with a WP_Error. Thanks to jhmaths for the report.

= 3.2.1 (16/04/2024) =

* Bugfix - fixed a PHP warning being generated when errors occurred. Thanks to connectisl for the report.

= 3.2 (26/10/2023) =

* Bugfix - fixed some PHP warnings being generated by the new database class. Thanks to donlee101 for the report.

= 3.1 (19/10/2023) =

* Bugfix - changing country to a non-UK country after performing a postcode lookup now hides the 'Select address' menu. Thanks to Conor for the report.

= 3.0 (08/10/2023) =

* Rewritten to support the new getAddress.io API -- the API used previously is no longer available to new getAddress.io customers.
* Added custom database table to cache postcode results across visitors. Results are cached for 1 day before being purged in a daily cron job.
* Moved settings to their own section (at the top) in the WooCommerce -> Settings -> General tab.
* Removed ability to send an over-usage email, as getAddress.io now does this natively.
* Removed notes about test postcodes from readme and settings screen, they're no longer supported.

= 2.3 (14/06/2023) =

* Declared compatibility with WooCommerce High Performance Order Storage -- thanks benatherton for the information.

= 2.2 (10/02/2022) =

* Stopped using <script> elements to output the Find Address buttons, as this was causing issues with some other checkout-modification plugins like Fluid Checkout.
* Updated event listeners for the Find Address and Enter Address Manually elements so that they work if those elements are first added to the page after load.

= 2.1 (09/12/2021) =

* Added a Hook Priority setting that can be used to try and correct conflicts caused by plugins like Checkout Field Editor Pro. Increase the priority until the lookup button comes back.

= 2.0.4 (15/07/2021) =

* Fixed an issue with the Enter/Return key (or equivalent on mobile) submitting the entire checkout form instead of triggering the postcode lookup. Thanks to donlee101 for the report.

= 2.0.3 (10/06/2021) =

* Fixed a bug with the new hide address fields option not functioning correctly when GB is the only country available to select. Thanks again to prodograw.

= 2.0.2 (10/06/2021) =

* Removed reliance on certain CSS classes in the checkout, as some themes apparently don't use them. Thanks to prodograw for the report.
* Fixed a bug that would stop the "tidy postcode" routine running when looking up a postcode currently in the cache
* Removed some duplicate JS that had crept in

= 2.0.1 (09/06/2021) =

* Somehow the push I did to the WordPress plugin repo missed some files - hopefully this will fix it...

= 2.0 (09/06/2021) =

* Added postcode lookup to the WooCommerce admin when creating and editing orders
* Added option (disabled by default) to hide the address input fields until an address has been selected
* Added option to show an "Enter address manually" button for use in conjunction with the above "hide address fields" option
* Added new filter for changing the "Enter address manually" text
* Added link to getaddress.io in plugin settings
* Updated donation link to Ko-Fi instead of PayPal

= 1.5.1 (27/08/2020) =

* Fix layout issue seemingly introduced by the WooCommerce 4.4 update, that pushed the "Find Address" button on to the next row.

= 1.5 (14/08/2020) =

* The address results selector now works properly in the Account pages on the front-end. Thanks to Ben Wheeler for some additional assistance here.
* Added an additional "Searching..." state when the button is clicked for better user experience
* Added settings in WC admin panel to change the text shown on the Find Address button, and the text shown in the new "Searching..." state
* Added filters for the above texts for developer control
* The JavaScript file is now only enqueued when on the checkout or account pages, which should speed up performance of other pages.

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
4. The address lookup feature within the WooCommerce add/edit order screen
