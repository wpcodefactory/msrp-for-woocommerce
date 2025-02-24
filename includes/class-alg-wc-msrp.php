<?php
/**
 * MSRP for WooCommerce - Main Class
 *
 * @version 2.0.0
 * @since   1.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_MSRP' ) ) :

final class Alg_WC_MSRP {

	/**
	 * Plugin version.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $version = ALG_WC_MSRP_VERSION;

	/**
	 * Core object.
	 *
	 * @version 1.8.0
	 * @since   1.8.0
	 *
	 * @var     Alg_WC_MSRP_Core class instance
	 */
	public $core = null;

	/**
	 * Instance.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @var     Alg_WC_MSRP The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main Alg_WC_MSRP Instance.
	 *
	 * Ensures only one instance of Alg_WC_MSRP is loaded or can be loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
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
	 * @version 2.0.0
	 * @since   1.0.0
	 *
	 * @access  public
	 */
	function __construct() {

		// Check for active WooCommerce plugin
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// Load libs
		if ( is_admin() ) {
			require_once plugin_dir_path( ALG_WC_MSRP_FILE ) . 'vendor/autoload.php';
		}

		// Set up localisation
		add_action( 'init', array( $this, 'localize' ) );

		// Declare compatibility with custom order tables for WooCommerce
		add_action( 'before_woocommerce_init', array( $this, 'wc_declare_compatibility' ) );

		// Pro
		if ( 'msrp-for-woocommerce-pro.php' === basename( ALG_WC_MSRP_FILE ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'pro/class-alg-wc-msrp-pro.php';
		}

		// Include required files
		$this->includes();

		// Admin
		if ( is_admin() ) {
			$this->admin();
		}
	}

	/**
	 * localize.
	 *
	 * @version 2.0.0
	 * @since   1.8.0
	 */
	function localize() {
		load_plugin_textdomain(
			'msrp-for-woocommerce',
			false,
			dirname( plugin_basename( ALG_WC_MSRP_FILE ) ) . '/langs/'
		);
	}

	/**
	 * wc_declare_compatibility.
	 *
	 * @version 2.0.0
	 * @since   1.7.8
	 *
	 * @see     https://developer.woocommerce.com/docs/hpos-extension-recipe-book/
	 */
	function wc_declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			$files = (
				defined( 'ALG_WC_MSRP_FILE_FREE' ) ?
				array( ALG_WC_MSRP_FILE, ALG_WC_MSRP_FILE_FREE ) :
				array( ALG_WC_MSRP_FILE )
			);
			foreach ( $files as $file ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
					'custom_order_tables',
					$file,
					true
				);
			}
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function includes() {

		// Core
		$this->core = require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-msrp-core.php';

		// Functions
		require_once plugin_dir_path( __FILE__ ) . 'alg-wc-msrp-functions.php';

		// Bulk price converter tool
		require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-msrp-bulk-price-converter-tool.php';

	}

	/**
	 * admin.
	 *
	 * @version 2.0.0
	 * @since   1.3.2
	 */
	function admin() {

		// Action links
		add_filter(
			'plugin_action_links_' . plugin_basename( ALG_WC_MSRP_FILE ),
			array( $this, 'action_links' )
		);

		// "Recommendations" page
		$this->add_cross_selling_library();

		// WC Settings tab as WPFactory submenu item
		$this->move_wc_settings_tab_to_wpfactory_menu();

		// Settings
		add_filter(
			'woocommerce_get_settings_pages',
			array( $this, 'add_woocommerce_settings_tab' )
		);

		// Version update
		if ( get_option( 'alg_wc_msrp_version', '' ) !== $this->version ) {
			add_action( 'admin_init', array( $this, 'version_updated' ) );
		}

	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 *
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links = array();

		$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_msrp' ) . '">' .
			__( 'Settings', 'msrp-for-woocommerce' ) .
		'</a>';

		if ( 'msrp-for-woocommerce.php' === basename( ALG_WC_MSRP_FILE ) ) {
			$custom_links[] = '<a target="_blank" style="font-weight: bold; color: green;" href="https://wpfactory.com/item/msrp-for-woocommerce/">' .
				__( 'Go Pro', 'msrp-for-woocommerce' ) .
			'</a>';
		}

		return array_merge( $custom_links, $links );
	}

	/**
	 * add_cross_selling_library.
	 *
	 * @version 2.0.0
	 * @since   1.8.0
	 */
	function add_cross_selling_library() {

		if ( ! class_exists( '\WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling' ) ) {
			return;
		}

		$cross_selling = new \WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling();
		$cross_selling->setup( array( 'plugin_file_path' => ALG_WC_MSRP_FILE ) );
		$cross_selling->init();

	}

	/**
	 * move_wc_settings_tab_to_wpfactory_menu.
	 *
	 * @version 1.8.0
	 * @since   1.8.0
	 */
	function move_wc_settings_tab_to_wpfactory_menu() {

		if ( ! class_exists( '\WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu' ) ) {
			return;
		}

		$wpfactory_admin_menu = \WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu::get_instance();

		if ( ! method_exists( $wpfactory_admin_menu, 'move_wc_settings_tab_to_wpfactory_menu' ) ) {
			return;
		}

		$wpfactory_admin_menu->move_wc_settings_tab_to_wpfactory_menu( array(
			'wc_settings_tab_id' => 'alg_wc_msrp',
			'menu_title'         => __( 'MSRP', 'msrp-for-woocommerce' ),
			'page_title'         => __( 'MSRP', 'msrp-for-woocommerce' ),
		) );

	}

	/**
	 * Add MSRP settings tab to WooCommerce settings.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function add_woocommerce_settings_tab( $settings ) {
		$settings[] = require_once plugin_dir_path( __FILE__ ) . 'settings/class-alg-wc-settings-msrp.php';
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
	 * @version 2.0.0
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( ALG_WC_MSRP_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( ALG_WC_MSRP_FILE ) );
	}

}

endif;
