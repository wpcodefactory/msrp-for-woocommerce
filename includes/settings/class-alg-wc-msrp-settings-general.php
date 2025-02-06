<?php
/**
 * MSRP for WooCommerce - General Section Settings
 *
 * @version 1.3.9
 * @since   1.0.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_MSRP_Settings_General' ) ) :

class Alg_WC_MSRP_Settings_General extends Alg_WC_MSRP_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.3.5
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id   = '';
		$this->desc = __( 'General', 'msrp-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 1.3.9
	 * @since   1.0.0
	 */
	function get_settings() {

		$plugin_settings = array(
			array(
				'title'    => __( 'MSRP Options', 'msrp-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_plugin_options',
				'desc'     => __( 'The <strong>manufacturer\'s suggested retail price</strong> (<strong>MSRP</strong>), also known as the <strong>list price</strong>, or the <strong>recommended retail price</strong> (<strong>RRP</strong>), or the <strong>suggested retail price</strong> (<strong>SRP</strong>), of a product is the price at which the manufacturer recommends that the retailer sell the product.', 'msrp-for-woocommerce' ) . '<br>' .
					sprintf( __( 'Plugin stores MSRP as product meta with %s key.', 'msrp-for-woocommerce' ), '<code>_alg_msrp</code>' ),
			),
			array(
				'title'    => __( 'MSRP for WooCommerce', 'msrp-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable plugin', 'msrp-for-woocommerce' ) . '</strong>',
				'desc_tip' => __( 'Save and display product MSRP in WooCommerce.', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_plugin_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_plugin_options',
			),
		);

		$single_product_settings = $this->get_single_archive_cart_settings( 'single', __( 'Single Product Page', 'msrp-for-woocommerce' ) );

		$archive_settings = $this->get_single_archive_cart_settings( 'archives', __( 'Archives', 'msrp-for-woocommerce' ) );

		return array_merge( $plugin_settings, $single_product_settings, $archive_settings );
	}

}

endif;

return new Alg_WC_MSRP_Settings_General();
