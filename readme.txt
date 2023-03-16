=== MSRP for WooCommerce ===
Contributors: omardabbas
Tags: woocommerce, product, msrp, list price, rrp, srp, woo commerce
Requires at least: 4.4
Tested up to: 5.3
Stable tag: 1.4.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Save and display product MSRP in WooCommerce.

== Description ==

**MSRP for WooCommerce** plugin lets you save and display product MSRP (manufacturer's suggested retail price) in WooCommerce.

You can set **display options** separately for *single product*, *archives* and *cart* pages:

* Display: Do not show (i.e. visible to admin only); Show; Only show if MSRP is higher than the standard price; Only show if MSRP differs from the standard price.
* Position: Before the standard price; After the standard price; Instead of the standard price.
* Savings amount (and percent) templates.

Additional options include:

* Hide MSRP for products with empty price.
* Hide regular price for products on sale.
* Set custom range format for variable products.
* Apply price filter.
* Add MSRP column to admin products list.
* Add MSRP field to admin quick edit.
* Add MSRP field to admin bulk edit.

[MSRP for WooCommerce Pro](https://wpfactory.com/item/msrp-for-woocommerce/) plugin version also has options to:

* Set **MSRP by country** (country will be detected automatically by visitor's IP address).
* Set **MSRP by currency** (for currency switcher plugins).
* **Customize** final template.
* Display **total savings in cart**.
* Show MSRP on frontend for selected **user roles** only.

= Feedback =

* We are open to your suggestions and feedback. Thank you for using or trying out one of our plugins!
* [Visit plugin site](https://wpfactory.com/item/msrp-for-woocommerce/).

== Installation ==

1. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Start by visiting plugin settings at "WooCommerce > Settings > MSRP".

== Changelog ==

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
