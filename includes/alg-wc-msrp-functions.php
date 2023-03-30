<?php
/**
 * MSRP for WooCommerce - Functions
 *
 * @version 1.3.4
 * @since   1.3.4
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'alg_wc_msrp_get_product_msrp' ) ) {
	/**
	 * alg_wc_msrp_get_product_msrp.
	 *
	 * @version 1.3.4
	 * @since   1.3.4
	 * @todo    [dev] variable products
	 * @todo    [dev] currency
	 * @todo    [dev] add to docs
	 */
	function alg_wc_msrp_get_product_msrp( $product_id = 0, $msrp_option = 'msrp', $discount_percent_round_precision = 0 ) {
		if ( function_exists( 'alg_wc_msrp' ) ) {
			// Get product data
			if ( 0 != $product_id ) {
				$product = wc_get_product( $product_id );
			} else {
				global $product;
				$product_id = $product->get_id();
			}
			// Get MSRP data
			$msrp_data  = alg_wc_msrp()->core->get_msrp( $product_id );
			$msrp       = $msrp_data['msrp'];
			if ( empty( $msrp ) ) {
				return 0;
			}
			// Discount
			if ( in_array( $msrp_option, array( 'msrp_discount', 'msrp_discount_percent' ) ) ) {
				$price = $product->get_price();
				if ( ! is_numeric( $price ) ) {
					$price = 0;
				}
				$diff = $msrp - $price;
			}
			// Return
			switch ( $msrp_option ) {
				case 'msrp':
					return $msrp;
				case 'msrp_discount':
					return $diff;
				case 'msrp_discount_percent':
					return round( $diff / $msrp * 100, $discount_percent_round_precision );
			}
		}
		return 0;
	}
}
