<?php
/*
Plugin Name: MSRP for WooCommerce
Plugin URI: https://wpfactory.com/item/msrp-for-woocommerce/
Description: Save and display product MSRP in WooCommerce.
Version: 1.7.6-dev
Author: WPFactory
Author URI: https://wpfactory.com/
Text Domain: msrp-for-woocommerce
Domain Path: /langs
Copyright: © 2023 WPFactory
WC tested up to: 7.3
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MSRP' ) ) :

/**
 * Main Alg_WC_MSRP Class
 *
 * @class   Alg_WC_MSRP
 * @version 1.3.9
 * @since   1.0.0
 */
final class Alg_WC_MSRP {

	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $version = '1.7.6-dev-20230330-1451';

	/**
	 * @var   Alg_WC_MSRP The single instance of the class
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main Alg_WC_MSRP Instance
	 *
	 * Ensures only one instance of Alg_WC_MSRP is loaded or can be loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @static
	 * @return  Alg_WC_MSRP - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Alg_WC_MSRP Constructor.
	 *
	 * @version 1.3.9
	 * @since   1.0.0
	 * @access  public
	 */
	function __construct() {

		// Check for active plugins
		if (
			! $this->is_plugin_active( 'woocommerce/woocommerce.php' ) ||
			( 'msrp-for-woocommerce.php' === basename( __FILE__ ) && $this->is_plugin_active( 'msrp-for-woocommerce-pro/msrp-for-woocommerce-pro.php' ) )
		) {
			return;
		}

		// Set up localisation
		load_plugin_textdomain( 'msrp-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );

		// Pro
		if ( 'msrp-for-woocommerce-pro.php' === basename( __FILE__ ) ) {
			require_once( 'includes/pro/class-alg-wc-msrp-pro.php' );
		}

		// Include required files
		$this->includes();

		// Admin
		if ( is_admin() ) {
			$this->admin();
		}
	}

	/**
	 * is_plugin_active.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function is_plugin_active( $plugin ) {
		return ( function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) :
			(
				in_array( $plugin, apply_filters( 'active_plugins', ( array ) get_option( 'active_plugins', array() ) ) ) ||
				( is_multisite() && array_key_exists( $plugin, ( array ) get_site_option( 'active_sitewide_plugins', array() ) ) )
			)
		);
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 1.3.4
	 * @since   1.0.0
	 */
	function includes() {
		// Core
		$this->core = require_once( 'includes/class-alg-wc-msrp-core.php' );
		// Functions
		require_once( 'includes/alg-wc-msrp-functions.php' );
		
		// Tool
		require_once( 'includes/class-alg-wc-msrp-bulk-price-converter-tool.php' );
	}

	/**
	 * admin.
	 *
	 * @version 1.3.9
	 * @since   1.3.2
	 */
	function admin() {
		// Action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		// Settings
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
		require_once( 'includes/settings/class-alg-wc-msrp-settings-section.php' );
		$this->settings = array();
		$this->settings['general']                = require_once( 'includes/settings/class-alg-wc-msrp-settings-general.php' );
		$this->settings['cart']                   = require_once( 'includes/settings/class-alg-wc-msrp-settings-cart.php' );
		$this->settings['countries_currencies']   = require_once( 'includes/settings/class-alg-wc-msrp-settings-countries-currencies.php' );
		$this->settings['admin_advanced']         = require_once( 'includes/settings/class-alg-wc-msrp-settings-admin-advanced.php' );
		// Version update
		if ( get_option( 'alg_wc_msrp_version', '' ) !== $this->version ) {
			add_action( 'admin_init', array( $this, 'version_updated' ) );
		}
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links = array();
		$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_msrp' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
		if ( 'msrp-for-woocommerce.php' === basename( __FILE__ ) ) {
			$custom_links[] = '<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">' .
				__( 'Unlock All', 'msrp-for-woocommerce' ) . '</a>';
		}
		return array_merge( $custom_links, $links );
	}

	/**
	 * Add MSRP settings tab to WooCommerce settings.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 */
	function add_woocommerce_settings_tab( $settings ) {
		$settings[] = require_once( 'includes/settings/class-alg-wc-settings-msrp.php' );
		return $settings;
	}

	/**
	 * version_updated.
	 *
	 * @version 1.3.2
	 * @since   1.2.0
	 */
	function version_updated() {
		update_option( 'alg_wc_msrp_version', $this->version );
	}

	/**
	 * Get the plugin url.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

}

endif;

if ( ! function_exists( 'alg_wc_msrp' ) ) {
	/**
	 * Returns the main instance of Alg_WC_MSRP to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  Alg_WC_MSRP
	 */
	function alg_wc_msrp() {
		return Alg_WC_MSRP::instance();
	}
}

alg_wc_msrp();
