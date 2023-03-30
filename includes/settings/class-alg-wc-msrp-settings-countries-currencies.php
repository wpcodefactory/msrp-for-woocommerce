<?php
/**
 * MSRP for WooCommerce - Countries & Currencies Section Settings
 *
 * @version 1.3.9
 * @since   1.3.9
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MSRP_Settings_Countries_Currencies' ) ) :

class Alg_WC_MSRP_Settings_Countries_Currencies extends Alg_WC_MSRP_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function __construct() {
		$this->id   = 'countries_currencies';
		$this->desc = __( 'Countries & Currencies', 'msrp-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function get_settings() {

		$all_countries = WC()->countries->get_countries();
		$countries_settings = array(
			array(
				'title'    => __( 'Countries Options', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Fill in this optional section, if you want to save different MSRP values for different countries.', 'msrp-for-woocommerce' ) . ' ' .
					__( 'Country will be detected automatically by visitor\'s IP address.', 'msrp-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_countries_options',
			),
			array(
				'title'    => __( 'MSRP by country', 'msrp-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable section', 'msrp-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_msrp_by_country_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => apply_filters( 'alg_wc_msrp_settings', sprintf( 'You will need %s plugin to enable "MSRP by country" section.',
					'<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">' . 'MSRP for WooCommerce Pro' . '</a>' ) ),
				'custom_attributes' => apply_filters( 'alg_wc_msrp_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'title'    => __( 'Countries', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Save changes to see new options.', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_countries',
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $all_countries,
			),
		);
		$countries = get_option( 'alg_wc_msrp_countries', '' );
		if ( ! empty( $countries ) ) {
			foreach ( $countries as $country_code ) {
				$countries_settings = array_merge( $countries_settings, array(
					array(
						'title'    => ( isset( $all_countries[ $country_code ] ) ? $all_countries[ $country_code ] . ' [' . $country_code . ']' : $country_code ),
						'id'       => 'alg_wc_msrp_countries_currencies[' . $country_code . ']',
						'default'  => get_woocommerce_currency(),
						'type'     => 'select',
						'class'    => 'wc-enhanced-select',
						'options'  => get_woocommerce_currencies(),
					),
				) );
			}
		}
		$countries_settings = array_merge( $countries_settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_countries_options',
			),
		) );

		$currencies_settings = array(
			array(
				'title'    => __( 'Currencies Options', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Fill in this optional section, if you want to save different MSRP values for different currencies.', 'msrp-for-woocommerce' ) . ' ' .
					__( 'Currency can be switched with some external currency switcher plugin.', 'msrp-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_currencies_options',
			),
			array(
				'title'    => __( 'MSRP by currency', 'msrp-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable section', 'msrp-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_msrp_by_currency_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => apply_filters( 'alg_wc_msrp_settings', sprintf( 'You will need %s plugin to enable "MSRP by currency" section.',
					'<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">' . 'MSRP for WooCommerce Pro' . '</a>' ) ),
				'custom_attributes' => apply_filters( 'alg_wc_msrp_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'title'    => __( 'Currencies', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Selected currencies will appear on each product\'s edit page.', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_currencies',
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => get_woocommerce_currencies(),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_currencies_options',
			),
		);

		return array_merge( $countries_settings, $currencies_settings );
	}

}

endif;

return new Alg_WC_MSRP_Settings_Countries_Currencies();
