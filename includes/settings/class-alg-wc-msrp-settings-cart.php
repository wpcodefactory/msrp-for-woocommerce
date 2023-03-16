<?php
/**
 * MSRP for WooCommerce - Cart Section Settings
 *
 * @version 1.3.9
 * @since   1.3.9
 * @author  WPWhale
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MSRP_Settings_Cart' ) ) :

class Alg_WC_MSRP_Settings_Cart extends Alg_WC_MSRP_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function __construct() {
		$this->id   = 'cart';
		$this->desc = __( 'Cart', 'msrp-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 * @todo    [fix] `alg_wc_msrp_display_cart_total_savings_positions`: check which positions are not updated on cart update and remove those positions (with fallback)
	 */
	function get_settings() {

		$cart_settings = $this->get_single_archive_cart_settings( 'cart', __( 'Cart', 'msrp-for-woocommerce' ) );

		$cart_total_savings_settings = array(
			array(
				'title'    => __( 'Cart Total Savings Display Options', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Display total savings in cart.', 'msrp-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_display_cart_total_savings_options',
			),
			array(
				'title'    => __( 'Cart total savings', 'msrp-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable section', 'msrp-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_msrp_display_cart_total_savings_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => apply_filters( 'alg_wc_msrp_settings', sprintf( 'You will need %s plugin to enable "Cart total savings" section.',
					'<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">' . 'MSRP for WooCommerce Pro' . '</a>' ) ),
				'custom_attributes' => apply_filters( 'alg_wc_msrp_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'title'    => __( 'Position(s)', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_display_cart_total_savings_positions',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => array(
					'woocommerce_before_cart'                    => __( 'Before cart', 'msrp-for-woocommerce' ),
					'woocommerce_before_cart_table'              => __( 'Before cart table', 'msrp-for-woocommerce' ),
					'woocommerce_before_cart_contents'           => __( 'Before cart contents', 'msrp-for-woocommerce' ),
					'woocommerce_cart_contents'                  => __( 'Cart contents', 'msrp-for-woocommerce' ),
					'woocommerce_cart_coupon'                    => __( 'Cart coupon', 'msrp-for-woocommerce' ),
					'woocommerce_cart_actions'                   => __( 'Cart actions', 'msrp-for-woocommerce' ),
					'woocommerce_after_cart_contents'            => __( 'After cart contents', 'msrp-for-woocommerce' ),
					'woocommerce_after_cart_table'               => __( 'After cart table', 'msrp-for-woocommerce' ),
					'woocommerce_cart_collaterals'               => __( 'Cart collaterals', 'msrp-for-woocommerce' ),
					'woocommerce_after_cart'                     => __( 'After cart', 'msrp-for-woocommerce' ),

					'woocommerce_before_cart_totals'             => __( 'Before cart totals', 'msrp-for-woocommerce' ),
					'woocommerce_cart_totals_before_shipping'    => __( 'Cart totals: Before shipping', 'msrp-for-woocommerce' ),
					'woocommerce_cart_totals_after_shipping'     => __( 'Cart totals: After shipping', 'msrp-for-woocommerce' ),
					'woocommerce_cart_totals_before_order_total' => __( 'Cart totals: Before order total', 'msrp-for-woocommerce' ),
					'woocommerce_cart_totals_after_order_total'  => __( 'Cart totals: After order total', 'msrp-for-woocommerce' ),
					'woocommerce_proceed_to_checkout'            => __( 'Proceed to checkout', 'msrp-for-woocommerce' ),
					'woocommerce_after_cart_totals'              => __( 'After cart totals', 'msrp-for-woocommerce' ),

					'woocommerce_before_shipping_calculator'     => __( 'Before shipping calculator', 'msrp-for-woocommerce' ),
					'woocommerce_after_shipping_calculator'      => __( 'After shipping calculator', 'msrp-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Template', 'msrp-for-woocommerce' ),
				'desc'     => $this->message_replaced_values( array( '%total_savings%' ) ) . ' ' .
					sprintf( __( 'You can also use shortcodes here, e.g.: %s.', 'msrp-for-woocommerce' ), '<code>[alg_wc_msrp_wpml lang="en"][/alg_wc_msrp_wpml]</code>' ),
				'id'       => 'alg_wc_msrp_display_cart_total_savings_template',
				'default'  => '<div class="price"><label for="alg_wc_msrp_total_savings">You save</label>: <span id="alg_wc_msrp_total_savings">%total_savings%</span></div>',
				'type'     => 'textarea',
				'css'      => 'width:100%;',
				'alg_wc_msrp_raw' => true,
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_display_cart_total_savings_options',
			),
		);

		return array_merge( $cart_settings, $cart_total_savings_settings );
	}

}

endif;

return new Alg_WC_MSRP_Settings_Cart();
