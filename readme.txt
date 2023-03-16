=== MSRP (List Price/RRP) for WooCommerce ===
Contributors: omardabbas
Tags: woocommerce, product, msrp, list price, rrp, srp
Requires at least: 4.4
Tested up to: 6.1
Stable tag: 1.7.5
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Save and display product MSRP (RRP / SRP / List Price) for all products in WooCommerce to encourage users to purchase with your competitive prices

== Description ==

**MSRP for WooCommerce** plugin lets you create and display product MSRP (manufacturer's suggested retail price) in WooCommerce.

With this MSRP plugin, you will be able to define different MSRPs for products, decide when to show or hide these prices (based on conditions), you can also customize the name, style, and text of the price.

Plugin main features:

* Hide MSRP for products with empty price.
* Hide regular price for products on sale.
* Set custom range format for variable products.
* Apply price filter.
* Add MSRP column to admin products list.
* Add MSRP field to admin quick edit.
* Add MSRP field to admin bulk edit.

You can set **display options** separately for *single product*, *archives* and *cart* pages:

* Display: Do not show (i.e. visible to admin only); Show; Only show if MSRP is higher than the standard price; Only show if MSRP differs from the standard price.
* Position: Before the standard price; After the standard price; Instead of the standard price.
* Savings amount (and percent) templates.

If you want to to further customize the plugin to meet your needs, you may need to give [MSRP for WooCommerce Pro](https://wpfactory.com/item/msrp-for-woocommerce/) a try, our premium plugin version has options to:

* Set **MSRP by country** (country will be detected automatically by visitor's IP address).
* Set **MSRP by currency** (for currency switcher plugins).
* **Customize** final template.
* Display **total savings in cart**.
* Show MSRP on frontend for selected **user roles** only.

= Demo Store =

If you want to try the plugin features, play around with its settings before installing it on your live website, feel free to do so on this demo store:
URL: https://wpwhale.com/demo/wp-admin/
User: demo
Password: G6_32e!r@

= Feedback =

* We are open to your suggestions and feedback. Thank you for using or trying out one of our plugins!
* [Visit plugin site](https://wpfactory.com/item/msrp-for-woocommerce/).

== Installation ==

1. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Start by visiting plugin settings at "WooCommerce > Settings > MSRP".

== Changelog ==

= 1.7.5 - 06/02/2023 =
* Verified compatibility with WooCommerce 7.3

= 1.7.4 - 04/11/2022 =
* Verified compatibility with WordPress 6.1 & WooCommerce 7.0

= 1.7.3 - 24/08/2022 =
* Fixed a bug showing MSRP on variations without MSRP
* Verified compatibility with WooCommerce 6.8

= 1.7.2 - 12/06/2022 =
* Verified compatibility with WordPress 6.0 & WooCommerce 6.5

= 1.7.1 - 19/03/2022 =
* Verified compatibility with WooCommerce 6.3

= 1.7 - 28/01/2022 =
* Verified compatibility with WordPress 5.9 & WooCommerce 6.1
* New Feature: You can bulk edit MSRP prices for all your products at once (or by category)

= 1.6.4 - 26/08/2021 =
* Verified compatibility with WooCommerce 5.9

= 1.6.3 - 26/08/2021 =
* Checked & verified compatibility with Woo 5.6

= 1.6.2 - 25/07/2021 =
* Verified compatibilty with WC 5.5 & WP 5.8

= 1.6.1 - 17/05/2021 =
* Verified compatibility with WooCommerce 5.3

= 1.6 - 03/05/2021 =
* Added a new template for products without MSRP defined
* Allowed changing the field label (product backend) that defines the MSRP price

= 1.5.6 - 20/04/2021 =
* Tested compatibilty with WC 5.2 & WP 5.7

= 1.5.5 - 28/02/2021 =
* Tested compatibilty with WC 5.0

= 1.5.4 - 07/02/2021 =
* Enhanced %price% tag to work with tax-included prices

= 1.5.3 - 27/01/2021 =
* Tested compatibility with WC 4.9 & WP 5.6

= 1.5.2 - 21/11/2020 =
* Tested compatibility with WC 4.7

= 1.5.1 - 24/08/2020 =
* Added a new dropdown menu that allows choosing MSRP text format: Bold / Italic / Underlined / Strike-through
* Fixed a bug in variable MSRP pricing when selecting "Only show MSRP if higher than Regular Price"

= 1.5 - 15/08/2020 =
* Changed MSRP field to read tax as per WC settings (if prices include / exclude tax)
* Tested compatibility with WP 5.5 & WC 4.3

= 1.4.3 - 28/04/2020 =
* Tested compatibility with WP 5.4 & WC 4.0

= 1.4.2 - 15/03/2020 =
* Fix: Geolocate pricing was not working when using multiple countries and/or currencies

= 1.4.1 - 02/01/2020 =
* Text updates over the plugin pages.
* Copyrights Update
* Added a section to review the plugin

= 1.4.0 - 23/12/2019 =
* Dev - Plugin author updated.

= 1.3.9 - 06/12/2019 =
* Dev - Admin settings split into sections.
* Dev - Admin settings descriptions updated.
* Dev - Code refactoring.
* Tested up to: 5.3.
* WC tested up to: 3.8.

= 1.3.8 - 24/07/2019 =
* Fix - Cart Total Savings Display - "Enable section" option fixed.
* Dev - Cart Total Savings Display - `%total_savings%` placeholder is now outputted without minus sign.

= 1.3.7 - 22/07/2019 =
* Fix - `%price%` placeholder is now available for variable products also.
* Dev - `alg_wc_get_msrp` filter added.
* Dev - Currency added to the `alg_wc_msrp_by_country` filter.
* Dev - Code refactoring.

= 1.3.6 - 14/07/2019 =
* Dev - Advanced Options - "Hide regular price for products on sale" option added.
* Dev - Advanced Options - "Custom range format" options added.

= 1.3.5 - 02/07/2019 =
* Fix - Advanced Options - Apply price filter - Second hook parameter added.
* Dev - `uninstall.php` removed.
* Dev - Admin tools notice - Code refactoring.
* Dev - Code refactoring.
* WC tested up to: 3.6.
* Tested up to: 5.2.

= 1.3.4 - 04/04/2019 =
* Fix - Single Product Page / Archives recognized properly now in all cases.
* Dev - Tools - "Copy all products prices to MSRP" tool added.
* Dev - Tools - "Delete all products MSRP meta" tool added.
* Dev - Functions - `alg_wc_msrp_get_product_msrp()` function added.
* Dev - Plugin's data (i.e. options and meta) is now deleted on uninstall (i.e. `uninstall.php` added).

= 1.3.3 - 26/02/2019 =
* Dev - Position - "Instead of the standard price" option (and `%price%` replaced value) added.
* Dev - Advanced Options - "Variable MSRP optimization" option added.
* Dev - Advanced Options - "Required user role(s)" option added.
* Dev - "Your settings have been reset" admin notice  added.
* Dev - Code refactoring.

= 1.3.2 - 31/10/2018 =
* Fix - Comma decimal separator in price parsed correctly now.
* Fix - "Reset settings" fixed for serialized values.
* Dev - Code refactoring.

= 1.3.1 - 30/10/2018 =
* Dev - `is_numeric()` check added for the saved MSRP value.

= 1.3.0 - 02/10/2018 =
* Dev - "Cart Display" section added.
* Dev - "Cart Total Savings Display" section added.

= 1.2.1 - 25/09/2018 =
* Dev - Admin Options - "Advanced: MSRP field position in admin quick and bulk edit" option added.

= 1.2.0 - 10/09/2018 =
* Dev - Admin Options - "Add MSRP field to admin quick edit" and "Add MSRP field to admin bulk edit" options added.
* Dev - Code refactoring.
* Dev - Plugin URI updated.

= 1.1.2 - 17/05/2018 =
* Fix - MSRP not saved for simple products - bug fixed.

= 1.1.1 - 17/05/2018 =
* Dev - Advanced Options - Apply price filter - Moved to free plugin.

= 1.1.0 - 16/05/2018 =
* Dev - Admin Options - "Add MSRP column to admin products list" option added.
* Dev - Advanced Options - "Apply price filter" option added.
* Dev - Advanced Options - "Hide MSRP for products with empty price" option added.
* Dev - "Countries Options" section added.
* Dev - "Currencies Options" section added.
* Dev - Variable products - Fallback MSRP added.
* Dev - Variable products - Proper MSRP display implemented.
* Dev - Code refactoring.

= 1.0.0 - 06/05/2018 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
This is the first release of the plugin.
