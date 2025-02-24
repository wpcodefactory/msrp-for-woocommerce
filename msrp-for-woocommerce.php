<?php
/*
Plugin Name: MSRP (RRP) Pricing for WooCommerce
Plugin URI: https://wpfactory.com/item/msrp-for-woocommerce/
Description: Save and display product MSRP in WooCommerce.
Version: 2.0.0
Author: WPFactory
Author URI: https://wpfactory.com/
Text Domain: msrp-for-woocommerce
Domain Path: /langs
WC tested up to: 9.6
Requires Plugins: woocommerce
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

defined( 'ABSPATH' ) || exit;

if ( 'msrp-for-woocommerce.php' === basename( __FILE__ ) ) {
	/**
	 * Check if Pro plugin version is activated.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	$plugin = 'msrp-for-woocommerce-pro/msrp-for-woocommerce-pro.php';
	if (
		in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ||
		(
			is_multisite() &&
			array_key_exists( $plugin, (array) get_site_option( 'active_sitewide_plugins', array() ) )
		)
	) {
		defined( 'ALG_WC_MSRP_FILE_FREE' ) || define( 'ALG_WC_MSRP_FILE_FREE', __FILE__ );
		return;
	}
}

defined( 'ALG_WC_MSRP_VERSION' ) || define( 'ALG_WC_MSRP_VERSION', '2.0.0' );

defined( 'ALG_WC_MSRP_FILE' ) || define( 'ALG_WC_MSRP_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-alg-wc-msrp.php';

if ( ! function_exists( 'alg_wc_msrp' ) ) {
	/**
	 * Returns the main instance of Alg_WC_MSRP to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return  Alg_WC_MSRP
	 */
	function alg_wc_msrp() {
		return Alg_WC_MSRP::instance();
	}
}

/**
 * plugins_loaded.
 *
 * @version 2.0.0
 * @since   2.0.0
 */
add_action( 'plugins_loaded', 'alg_wc_msrp' );
