<?php
/**
 * MSRP for WooCommerce - Section Settings
 *
 * @version 1.3.9
 * @since   1.0.0
 * @author  WPWhale
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MSRP_Settings_Section' ) ) :

class Alg_WC_MSRP_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 */
	function __construct() {
		add_filter( 'woocommerce_get_sections_alg_wc_msrp',              array( $this, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_alg_wc_msrp_' . $this->id, array( $this, 'get_settings' ), PHP_INT_MAX );
	}

	/**
	 * settings_section.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = $this->desc;
		return $sections;
	}

	/**
	 * message_replaced_values.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function message_replaced_values( $values ) {
		$message_template = ( 1 == count( $values ) ? __( 'Replaced value: %s.', 'msrp-for-woocommerce' ) : __( 'Replaced values: %s.', 'msrp-for-woocommerce' ) );
		return sprintf( $message_template, '<code>' . implode( '</code>, <code>', $values ) . '</code>' );
	}

	/**
	 * get_single_archive_cart_settings.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function get_single_archive_cart_settings( $section_id, $section_title ) {
		return array(
			array(
				'title'    => sprintf( __( '%s Display Options', 'msrp-for-woocommerce' ), $section_title ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_display_on_' . $section_id . '_options',
			),
			array(
				'title'    => __( 'Display', 'msrp-for-woocommerce' ),
				'type'     => 'select',
				'id'       => 'alg_wc_msrp_display_on_' . $section_id,
				'default'  => ( 'cart' == $section_id ? 'hide' : 'show' ),
				'options'  => array(
					'hide'           => __( 'Do not show', 'msrp-for-woocommerce' ),
					'show'           => __( 'Show', 'msrp-for-woocommerce' ),
					'show_if_higher' => __( 'Only show if MSRP is higher than the standard price', 'msrp-for-woocommerce' ),
					'show_if_diff'   => __( 'Only show if MSRP differs from the standard price', 'msrp-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Position', 'msrp-for-woocommerce' ),
				'type'     => 'select',
				'id'       => 'alg_wc_msrp_display_on_' . $section_id . '_position',
				'default'  => 'after_price',
				'options'  => array(
					'before_price'     => __( 'Before the standard price', 'msrp-for-woocommerce' ),
					'after_price'      => __( 'After the standard price', 'msrp-for-woocommerce' ),
					'instead_of_price' => __( 'Instead of the standard price', 'msrp-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Savings', 'msrp-for-woocommerce' ),
				'desc'     => sprintf( __( 'Savings amount. To display this, use %s in "Final Template"', 'msrp-for-woocommerce' ), '<code>' . '%you_save%' . '</code>' ) . ' ' .
					$this->message_replaced_values( array( '%you_save_raw%' ) ),
				'type'     => 'textarea',
				'id'       => 'alg_wc_msrp_display_on_' . $section_id . '_you_save',
				'default'  => ' (%you_save_raw%)',
				'alg_wc_msrp_raw' => true,
			),
			array(
				'desc'     => sprintf( __( 'Savings amount in percent. To display this, use %s in "Final Template"', 'msrp-for-woocommerce' ), '<code>' . '%you_save_percent%' . '</code>' ) . ' ' .
					$this->message_replaced_values( array( '%you_save_percent_raw%' ) ),
				'type'     => 'textarea',
				'id'       => 'alg_wc_msrp_display_on_' . $section_id . '_you_save_percent',
				'default'  => ' (%you_save_percent_raw% %)',
				'alg_wc_msrp_raw' => true,
			),
			array(
				'desc'     => __( 'Savings amount in percent - rounding precision', 'msrp-for-woocommerce' ),
				'type'     => 'number',
				'id'       => 'alg_wc_msrp_display_on_' . $section_id . '_you_save_percent_round',
				'default'  => 0,
				'custom_attributes' => array( 'min' => 0 ),
			),
			array(
				'title'    => __( 'Final Template', 'msrp-for-woocommerce' ),
				'desc'     => $this->message_replaced_values( array( '%msrp%', '%you_save%', '%you_save_percent%', '%price%' ) ) . ' ' .
					sprintf( __( 'You can also use shortcodes here, e.g.: %s.', 'msrp-for-woocommerce' ), '<code>[alg_wc_msrp_wpml lang="en"][/alg_wc_msrp_wpml]</code>' ) .
					apply_filters( 'alg_wc_msrp_settings', sprintf( '<br>' . 'You will need %s plugin to change the template.',
						'<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">' . 'MSRP for WooCommerce Pro' . '</a>' ) ),
				'type'     => 'textarea',
				'id'       => 'alg_wc_msrp_display_on_' . $section_id . '_template',
				'default'  => '<div class="price"><label for="alg_wc_msrp">MSRP</label>: <span id="alg_wc_msrp"><del>%msrp%</del>%you_save%</span></div>',
				'css'      => 'width:100%;',
				'alg_wc_msrp_raw' => true,
				'custom_attributes' => apply_filters( 'alg_wc_msrp_settings', array( 'readonly' => 'readonly' ) ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_display_' . $section_id . '_options',
			),
		);
	}

}

endif;
