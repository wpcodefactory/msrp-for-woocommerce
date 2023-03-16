<?php
/**
 * MSRP for WooCommerce - Core Class
 *
 * @version 1.3.9
 * @since   1.0.0
 * @author  WPWhale
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MSRP_Core' ) ) :

class Alg_WC_MSRP_Core {

	/**
	 * Constructor.
	 *
	 * @version 1.3.6
	 * @since   1.0.0
	 * @todo    [dev] (maybe) recheck if `woocommerce_delete_product_transients` should be hooked in admin only
	 * @todo    [dev] split file into `...admin.php` and `...core.php`
	 * @todo    [dev] split settings into separate settings sections
	 * @todo    [feature] option to add custom filters in "Apply price filter"
	 * @todo    [feature] grouped products
	 * @todo    [feature] option to change `_alg_msrp` meta key
	 * @todo    [feature] `[alg_wc_msrp]` shortcode
	 * @todo    [feature] REST API
	 * @todo    [feature] composite products
	 * @todo    [feature] MSRP per user role (i.e. same as per country / currency)
	 */
	function __construct() {
		if ( 'yes' === get_option( 'alg_wc_msrp_plugin_enabled', 'yes' ) ) {
			// Init options
			$this->init_options();
			// Core
			if ( is_admin() ) {
				// MSRP input on admin product page (simple product)
				add_action( 'woocommerce_product_options_pricing', array( $this, 'add_msrp_input' ) );
				add_action( 'save_post_product',                   array( $this, 'save_msrp_input' ), PHP_INT_MAX, 2 );
				// MSRP input on admin product page (variable product)
				add_action( 'woocommerce_variation_options_pricing',            array( $this, 'add_msrp_input_variation' ), 10, 3 );
				add_action( 'woocommerce_save_product_variation',               array( $this, 'save_msrp_input_variation' ), PHP_INT_MAX, 2 );
				add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_msrp_input_variable' ), PHP_INT_MAX );
				// Products columns
				if ( $this->options['do_add_admin_products_column'] ) {
					add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),    PHP_INT_MAX );
					add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_columns' ), PHP_INT_MAX );
				}
				// Quick and bulk edit
				if ( $this->options['do_add_admin_quick_edit'] || $this->options['do_add_admin_bulk_edit'] ) {
					if ( $this->options['do_add_admin_quick_edit'] ) {
						add_action( 'woocommerce_product_quick_edit_' . $this->options['admin_quick_bulk_edit_position'],
							array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
					}
					if ( $this->options['do_add_admin_bulk_edit'] ) {
						add_action( 'woocommerce_product_bulk_edit_'  . $this->options['admin_quick_bulk_edit_position'],
							array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
					}
					add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'save_bulk_and_quick_edit_fields' ), PHP_INT_MAX, 2 );
				}
			} else {
				// Hide regular price for products on sale
				if ( $this->options['hide_regular_price_for_sale_products'] ) {
					add_filter( 'woocommerce_get_price_html', array( $this, 'hide_regular_price_for_sale_products' ), 9, 2 );
				}
				// Display
				add_filter( 'woocommerce_get_price_html', array( $this, 'display' ), PHP_INT_MAX, 2 );
				// Cart display
				add_filter( 'woocommerce_cart_item_price', array( $this, 'display_in_cart' ), PHP_INT_MAX, 3 );
				// Cart total savings
				if ( $this->options['cart_total_savings_enabled'] && ! empty( $this->options['cart_total_savings_positions'] ) ) {
					foreach ( $this->options['cart_total_savings_positions'] as $position ) {
						add_action( $position, array( $this, 'display_totals_in_cart' ), PHP_INT_MAX );
					}
				}
				// WPML shortcode
				add_shortcode( 'alg_wc_msrp_wpml', array( $this, 'alg_wc_msrp_wpml' ) );
			}
			// Transients
			if ( 'in_transients' === $this->options['variable_optimization'] ) {
				add_action( 'woocommerce_delete_product_transients', array( $this, 'delete_product_transient_variable' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * init_options.
	 *
	 * @version 1.3.9
	 * @since   1.1.0
	 * @todo    [dev] (maybe) load only required options on back/front end
	 */
	function init_options() {
		// General
		$this->options['is_wc_version_below_3_0_0']        = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		$this->options['do_hide_msrp_for_empty_price']     = ( 'yes' === get_option( 'alg_wc_msrp_advanced_hide_for_empty_price', 'no' ) );
		$this->options['do_add_admin_products_column']     = ( 'yes' === get_option( 'alg_wc_msrp_admin_add_products_column', 'no' ) );
		$this->options['do_add_admin_quick_edit']          = ( 'yes' === get_option( 'alg_wc_msrp_admin_add_quick_edit', 'no' ) );
		$this->options['do_add_admin_bulk_edit']           = ( 'yes' === get_option( 'alg_wc_msrp_admin_add_bulk_edit', 'no' ) );
		$this->options['admin_quick_bulk_edit_position']   = get_option( 'alg_wc_msrp_admin_quick_bulk_edit_position', 'end' );
		// MSRP by country
		$this->options['is_msrp_by_country_enabled']    = ( 'yes' === apply_filters( 'alg_wc_msrp_by_country', 'no' ) );
		$this->options['msrp_countries']                = ( $this->options['is_msrp_by_country_enabled'] ? get_option( 'alg_wc_msrp_countries', '' ) : '' );
		if ( ! empty( $this->options['msrp_countries'] ) ) {
			$this->options['msrp_countries_currencies'] = get_option( 'alg_wc_msrp_countries_currencies', '' );
			$this->options['default_wc_country']        = get_option( 'woocommerce_default_country' );
		}
		// MSRP by currency
		$this->options['is_msrp_by_currency_enabled'] = ( 'yes' === apply_filters( 'alg_wc_msrp_by_currency', 'no' ) );
		$this->options['msrp_currencies']             = ( $this->options['is_msrp_by_currency_enabled'] ? get_option( 'alg_wc_msrp_currencies', '' ) : '' );
		// Apply filter
		$this->options['is_msrp_apply_price_filter_enabled'] = ( 'yes' === get_option( 'alg_wc_msrp_apply_price_filter', 'no' ) );
		if ( $this->options['is_msrp_apply_price_filter_enabled'] ) {
			$this->options['wc_price_filter']                = ( $this->options['is_wc_version_below_3_0_0'] ? 'woocommerce_get_price' : 'woocommerce_product_get_price' );
		}
		// Display
		$template = '<div class="price"><label for="alg_wc_msrp">MSRP</label>: <span id="alg_wc_msrp"><del>%msrp%</del>%you_save%</span></div>';
		$sections = array( 'single', 'archives', 'cart' );
		foreach ( $sections as $section_id ) {
			$option_id = 'alg_wc_msrp_display_on_' . $section_id;
			$this->options['msrp_display'][ $section_id ]['display']          = get_option( $option_id, 'show' );
			$this->options['msrp_display'][ $section_id ]['position']         = get_option( $option_id . '_position', 'after_price' );
			$this->options['msrp_display'][ $section_id ]['template']         = apply_filters( 'alg_wc_msrp_template', $template, $section_id );
			$this->options['msrp_display'][ $section_id ]['you_save']         = get_option( $option_id . '_you_save',         ' (%you_save_raw%)' );
			$this->options['msrp_display'][ $section_id ]['you_save_percent'] = get_option( $option_id . '_you_save_percent', ' (%you_save_percent_raw% %)' );
			$this->options['msrp_display'][ $section_id ]['you_save_round']   = get_option( $option_id . '_you_save_percent_round', 0 );
		}
		// Cart total savings
		$this->options['cart_total_savings_enabled']   = ( 'yes' === apply_filters( 'alg_wc_msrp_cart_total_savings', 'no' ) );
		$this->options['cart_total_savings_positions'] = get_option( 'alg_wc_msrp_display_cart_total_savings_positions', array() );
		$this->options['cart_total_savings_template']  = get_option( 'alg_wc_msrp_display_cart_total_savings_template',
			'<div class="price"><label for="alg_wc_msrp_total_savings">You save</label>: <span id="alg_wc_msrp_total_savings">%total_savings%</span></div>' );
		// Optimization type
		$this->options['variable_optimization'] = get_option( 'alg_wc_msrp_variable_optimization', 'none' );
		// Hide regular price for products on sale
		$this->options['hide_regular_price_for_sale_products'] = ( 'yes' === get_option( 'alg_wc_msrp_hide_regular_price_for_sale_products', 'no' ) );
		// Custom range format
		$this->options['custom_range_format_enabled'] = ( 'yes' === get_option( 'alg_wc_msrp_custom_range_format_enabled', 'no' ) );
		$this->options['custom_range_format']         = get_option( 'alg_wc_msrp_custom_range_format', __( 'From %from%' ) );
	}

	/**
	 * is_msrp_set_for_product.
	 *
	 * @version 1.3.6
	 * @since   1.3.6
	 */
	function is_msrp_set_for_product( $product ) {
		if ( $product->is_type( 'variable' ) ) {
			$msrp = $this->get_variable_msrp( $product );
			return ( ! empty( $msrp ) );
		} else {
			$msrp = $this->get_msrp( $this->get_product_id( $product ) );
			return ( ! empty( $msrp['msrp'] ) );
		}
	}

	/**
	 * hide_regular_price_for_sale_products.
	 *
	 * @version 1.3.6
	 * @since   1.3.6
	 * @todo    [fix] (important) "Sale" tag is still displayed: maybe use `woocommerce_product_is_on_sale` filter to hide it (optionally)
	 * @todo    [dev] add custom template (i.e. `'%sale_price%'`) (#13210)
	 */
	function hide_regular_price_for_sale_products( $price, $product ) {
		if ( $product->is_on_sale() && $this->is_msrp_set_for_product( $product ) ) {
			if ( $product->is_type( 'variable' ) ) {
				// Variable products
				$prices = $product->get_variation_prices( true );
				if ( ! empty( $prices['price'] ) ) {
					$min_price     = current( $prices['price'] );
					$max_price     = end( $prices['price'] );
					$min_reg_price = current( $prices['regular_price'] );
					$max_reg_price = end( $prices['regular_price'] );
					if ( $min_price === $max_price && $min_reg_price === $max_reg_price ) {
						return wc_price( $min_price ) . $product->get_price_suffix();
					}
				}
			} elseif ( ! $product->is_type( 'grouped' ) ) {
				// Simple etc. products (i.e. all except grouped)
				return wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
			}
		}
		// No changes
		return $price;
	}

	/**
	 * display_in_cart.
	 *
	 * @version 1.3.0
	 * @since   1.3.0
	 * @todo    [feature] add option enable/disable info in mini-cart and cart separately
	 * @todo    [feature] add option to use `woocommerce_cart_item_name` instead of `woocommerce_cart_item_price`
	 */
	function display_in_cart( $price_html, $cart_item, $cart_item_key ) {
		return $this->display( $price_html, $cart_item['data'], 'cart' );
	}

	/**
	 * display_totals_in_cart.
	 *
	 * @version 1.3.8
	 * @since   1.3.0
	 * @todo    [dev] handle multicurrency (`$msrp_data['currency']`)
	 * @todo    [feature] add option to customize "position" priority
	 */
	function display_totals_in_cart() {
		if ( function_exists( 'WC' ) && isset( WC()->cart ) ) {
			$savings = 0;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$msrp_data = $this->get_msrp( ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'] );
				if ( ! empty( $msrp_data['msrp'] ) ) {
					$savings += ( $cart_item['data']->get_price() - $msrp_data['msrp'] ) * $cart_item['quantity'];
				}
			}
			if ( ! empty( $savings ) ) {
				echo str_replace( '%total_savings%', wc_price( -$savings ), do_shortcode( $this->options['cart_total_savings_template'] ) );
			}
		}
	}

	/**
	 * add_bulk_and_quick_edit_fields.
	 *
	 * @version 1.3.9
	 * @since   1.2.0
	 * @todo    [dev] recheck variable products
	 * @todo    [dev] (maybe) reposition this (to the `price_fields` section)
	 * @todo    [dev] (maybe) actual value (instead of "No change" placeholder) (probably need to add value to `woocommerce_inline_`)
	 * @todo    [feature] add `_alg_msrp_by_country` and `_alg_msrp_by_currency`
	 */
	function add_bulk_and_quick_edit_fields() {
		$current_filter = current_filter();
		if ( 'end' === $this->options['admin_quick_bulk_edit_position'] && 'woocommerce_product_quick_edit_end' === $current_filter ) {
			echo '<br class="clear" />';
		}
		?><label>
			<span class="title"><?php echo __( 'MSRP', 'msrp-for-woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<input type="text" name="_alg_msrp" class="text wc_input_price" placeholder="<?php echo __( '- No change -', 'msrp-for-woocommerce' ); ?>" value="">
			</span>
		</label><?php
		if ( 'start' === $this->options['admin_quick_bulk_edit_position'] && 'woocommerce_product_quick_edit_start' === $current_filter ) {
			echo '<br class="clear" />';
		}
	}

	/**
	 * save_bulk_and_quick_edit_fields.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function save_bulk_and_quick_edit_fields( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		// Check nonce.
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) { // WPCS: input var ok, sanitization ok.
			return $post_id;
		}
		// Check bulk or quick edit.
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) { // WPCS: input var ok.
			if ( ! $this->options['do_add_admin_quick_edit'] ) {
				return $post_id;
			}
		} else {
			if ( ! $this->options['do_add_admin_bulk_edit'] ) {
				return $post_id;
			}
		}
		// Save.
		if ( isset( $_REQUEST['_alg_msrp'] ) && '' !== $_REQUEST['_alg_msrp'] ) {
			update_post_meta( $post_id, '_alg_msrp', $_REQUEST['_alg_msrp'] );
		}
		return $post_id;
	}

	/**
	 * add_product_columns.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function add_product_columns( $columns ) {
		$_columns = array();
		foreach ( $columns as $column_key => $column_title ) {
			$_columns[ $column_key ] = $column_title;
			if ( 'price' === $column_key ) {
				$_columns['msrp'] = __( 'MSRP', 'msrp-for-woocommerce' );
			}
		}
		return $_columns;
	}

	/**
	 * render_product_columns.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function render_product_columns( $column ) {
		if ( 'msrp' === $column ) {
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );
			if ( $product->is_type( 'variable' ) ) {
				echo $this->get_variable_msrp( $product );
			} else {
				$msrp_data = $this->get_msrp( $product_id );
				if ( '' != $msrp_data['msrp'] ) {
					echo wc_price( $msrp_data['msrp'], array( 'currency' => $msrp_data['currency'] ) );
				}
			}
		}
	}

	/**
	 * alg_wc_msrp_wpml.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function alg_wc_msrp_wpml( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'lang'     => '',
			'not_lang' => ''
		), $atts, 'alg_wc_msrp_wpml' );
		// Check if language is ok (lang in ...)
		if ( '' != $atts['lang'] ) {
			if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
				return '';
			}
			if ( ! in_array( ICL_LANGUAGE_CODE, array_map( 'trim', explode( ',', $atts['lang'] ) ) ) ) {
				return '';
			}
		}
		// Check if language is ok (lang not in...)
		if ( '' != $atts['not_lang'] ) {
			if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				if ( in_array( ICL_LANGUAGE_CODE, array_map( 'trim', explode( ',', $atts['not_lang'] ) ) ) ) {
					return '';
				}
			}
		}
		return $content;
	}

	/**
	 * add_msrp_input_variation.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function add_msrp_input_variation( $loop, $variation_data, $variation ) {
		woocommerce_wp_text_input( array(
			'id'            => "variable_alg_msrp_{$loop}",
			'name'          => "variable_alg_msrp[{$loop}]",
			'value'         => wc_format_localized_price( isset( $variation_data['_alg_msrp'][0] ) ? $variation_data['_alg_msrp'][0] : '' ),
			'label'         => __( 'MSRP', 'msrp-for-woocommerce' ) . ' (' . $this->get_full_woocommerce_currency_symbol() . ')',
			'data_type'     => 'price',
			'wrapper_class' => 'form-row form-row-full',
		) );
		if ( ! empty( $this->options['msrp_countries'] ) ) {
			$values = ( isset( $variation_data['_alg_msrp_by_country'][0] ) && is_serialized( $variation_data['_alg_msrp_by_country'][0] ) ?
				unserialize( $variation_data['_alg_msrp_by_country'][0] ) : array() );
			foreach ( $this->options['msrp_countries'] as $country_code ) {
				$currency = ( isset( $this->options['msrp_countries_currencies'][ $country_code ] ) ? $this->options['msrp_countries_currencies'][ $country_code ] : '' );
				$value    = ( isset( $values[ $country_code ] )                          ? $values[ $country_code ]                          : '' );
				woocommerce_wp_text_input( array(
					'id'            => "variable_alg_msrp_by_country_{$loop}_{$country_code}",
					'name'          => "variable_alg_msrp_by_country[{$loop}][{$country_code}]",
					'value'         => wc_format_localized_price( $value ),
					'label'         => __( 'MSRP', 'msrp-for-woocommerce' ) . ' [' . $country_code . '] (' . $this->get_full_woocommerce_currency_symbol( $currency ) . ')',
					'data_type'     => 'price',
					'wrapper_class' => 'form-row form-row-full',
				) );
			}
		}
		if ( ! empty( $this->options['msrp_currencies'] ) ) {
			$values = ( isset( $variation_data['_alg_msrp_by_currency'][0] ) && is_serialized( $variation_data['_alg_msrp_by_currency'][0] ) ?
				unserialize( $variation_data['_alg_msrp_by_currency'][0] ) : array() );
			foreach ( $this->options['msrp_currencies'] as $currency ) {
				$value = ( isset( $values[ $currency ] ) ? $values[ $currency ] : '' );
				woocommerce_wp_text_input( array(
					'id'            => "variable_alg_msrp_by_currency_{$loop}_{$currency}",
					'name'          => "variable_alg_msrp_by_currency[{$loop}][{$currency}]",
					'value'         => wc_format_localized_price( $value ),
					'label'         => __( 'MSRP', 'msrp-for-woocommerce' ) . ' (' . $this->get_full_woocommerce_currency_symbol( $currency ) . ')',
					'data_type'     => 'price',
					'wrapper_class' => 'form-row form-row-full',
				) );
			}
		}
	}

	/**
	 * save_msrp_input_variation.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function save_msrp_input_variation( $variation_id, $i ) {
		if ( isset( $_POST['variable_alg_msrp'][ $i ] ) ) {
			update_post_meta( $variation_id, '_alg_msrp', wc_clean( $_POST['variable_alg_msrp'][ $i ] ) );
			if ( isset( $_POST['variable_alg_msrp_by_country'][ $i ] ) ) {
				update_post_meta( $variation_id, '_alg_msrp_by_country', $_POST['variable_alg_msrp_by_country'][ $i ] );
			}
			if ( isset( $_POST['variable_alg_msrp_by_currency'][ $i ] ) ) {
				update_post_meta( $variation_id, '_alg_msrp_by_currency', $_POST['variable_alg_msrp_by_currency'][ $i ] );
			}
		}
	}

	/**
	 * add_msrp_input_variable.
	 *
	 * @version 1.1.2
	 * @since   1.1.0
	 */
	function add_msrp_input_variable() {
		if ( ( $product = wc_get_product() ) && $product->is_type( 'variable' ) ) {
			echo '<div class="options_group show_if_variable">';
			$this->add_msrp_input();
			echo '</div>';
		}
	}

	/**
	 * add_msrp_input.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 * @todo    [dev] (maybe) rethink `$product_id`
	 */
	function add_msrp_input() {
		$product_id = get_the_ID();
		woocommerce_wp_text_input( array(
			'id'          => '_alg_msrp',
			'value'       => get_post_meta( $product_id, '_' . 'alg_msrp', true ),
			'data_type'   => 'price',
			'label'       => __( 'MSRP', 'msrp-for-woocommerce' ) . ' (' . $this->get_full_woocommerce_currency_symbol() . ')',
		) );
		if ( ! empty( $this->options['msrp_countries'] ) ) {
			$values = get_post_meta( $product_id, '_alg_msrp_by_country', true );
			foreach ( $this->options['msrp_countries'] as $country_code ) {
				$currency = ( isset( $this->options['msrp_countries_currencies'][ $country_code ] ) ? $this->options['msrp_countries_currencies'][ $country_code ] : '' );
				$value    = ( isset( $values[ $country_code ] )                          ? $values[ $country_code ]                          : '' );
				woocommerce_wp_text_input( array(
					'id'          => '_alg_msrp_by_country_' . $country_code,
					'name'        => '_alg_msrp_by_country[' . $country_code . ']',
					'value'       => $value,
					'data_type'   => 'price',
					'label'       => __( 'MSRP', 'msrp-for-woocommerce' ) . ' [' . $country_code . '] (' . $this->get_full_woocommerce_currency_symbol( $currency ) . ')',
				) );
			}
		}
		if ( ! empty( $this->options['msrp_currencies'] ) ) {
			$values = get_post_meta( $product_id, '_alg_msrp_by_currency', true );
			foreach ( $this->options['msrp_currencies'] as $currency ) {
				$value = ( isset( $values[ $currency ] ) ? $values[ $currency ] : '' );
				woocommerce_wp_text_input( array(
					'id'          => '_alg_msrp_by_currency_' . $currency,
					'name'        => '_alg_msrp_by_currency[' . $currency . ']',
					'value'       => $value,
					'data_type'   => 'price',
					'label'       => __( 'MSRP', 'msrp-for-woocommerce' ) . ' (' . $this->get_full_woocommerce_currency_symbol( $currency ) . ')',
				) );
			}
		}
	}

	/**
	 * save_msrp_input.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 */
	function save_msrp_input( $post_id, $__post ) {
		if ( isset( $_POST['_alg_msrp'] ) && empty( $_REQUEST['woocommerce_quick_edit'] ) ) {
			update_post_meta( $post_id, '_alg_msrp', $_POST['_alg_msrp'] );
			if ( isset( $_POST['_alg_msrp_by_country'] ) ) {
				update_post_meta( $post_id, '_alg_msrp_by_country', $_POST['_alg_msrp_by_country'] );
			}
			if ( isset( $_POST['_alg_msrp_by_currency'] ) ) {
				update_post_meta( $post_id, '_alg_msrp_by_currency', $_POST['_alg_msrp_by_currency'] );
			}
		}
	}

	/**
	 * get_full_woocommerce_currency_symbol.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function get_full_woocommerce_currency_symbol( $currency = '' ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency();
		}
		return ( ( $symbol = get_woocommerce_currency_symbol( $currency ) ) != $currency ? $currency . $symbol : $currency );
	}

	/**
	 * get_product_id.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function get_product_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		return ( $this->options['is_wc_version_below_3_0_0'] ? ( isset( $_product->variation_id ) ? $_product->variation_id : $_product->id ) : $_product->get_id() );
	}

	/**
	 * get_product_parent_id.
	 *
	 * @version 1.3.4
	 * @since   1.3.4
	 */
	function get_product_parent_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		return ( $this->options['is_wc_version_below_3_0_0'] ? $_product->id : ( $_product->is_type( 'variation' ) ? $_product->get_parent_id() : $_product->get_id() ) );
	}

	/**
	 * get_visitors_country_by_ip.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function get_visitors_country_by_ip() {
		if ( isset( $this->visitors_country_by_ip ) ) {
			return $this->visitors_country_by_ip;
		}
		// Get the country by IP
		$location = ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::geolocate_ip() : array( 'country' => '' ) );
		// Base fallback
		if ( empty( $location['country'] ) ) {
			$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', $this->options['default_wc_country'] ) );
		}
		$this->visitors_country_by_ip = ( isset( $location['country'] ) ? $location['country'] : '' );
		return $this->visitors_country_by_ip;
	}

	/**
	 * get_post_or_parent_meta.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function get_post_or_parent_meta( $product_id, $meta_key ) {
		if ( '' == ( $msrp = get_post_meta( $product_id, $meta_key, true ) ) ) {
			$product = wc_get_product( $product_id );
			$msrp    = get_post_meta( $product->get_parent_id(), $meta_key, true );
		}
		return $msrp;
	}

	/**
	 * check_user_role.
	 *
	 * @version 1.3.9
	 * @since   1.3.3
	 */
	function check_user_role() {
		return apply_filters( 'alg_wc_msrp_check_user_role', true, $this );
	}

	/**
	 * get_msrp.
	 *
	 * @version 1.3.7
	 * @since   1.1.0
	 * @todo    [dev] automatic currency conversion (i.e. exchange rates) (#13113)
	 */
	function get_msrp( $product_id ) {
		if ( ! $this->check_user_role() ) {
			return array( 'msrp' => '', 'currency' => '' );
		}
		$msrp       = '';
		$currency   = '';
		if ( ! empty( $this->options['msrp_countries'] ) ) {
			$msrp_by_country  = $this->get_post_or_parent_meta( $product_id, '_alg_msrp_by_country' );
			$country_code     = $this->get_visitors_country_by_ip();
			if ( isset( $msrp_by_country[ $country_code ] ) ) {
				$currency   = ( isset( $this->options['msrp_countries_currencies'][ $country_code ] ) ? $this->options['msrp_countries_currencies'][ $country_code ] : '' );
				$msrp       = apply_filters( 'alg_wc_msrp_by_country', $msrp_by_country[ $country_code ], $product_id, $country_code, $currency );
			}
		}
		if ( ( '' == $msrp || 0 == $msrp ) && ! empty( $this->options['msrp_currencies'] ) ) {
			$msrp_by_currency = $this->get_post_or_parent_meta( $product_id, '_alg_msrp_by_currency' );
			$currency_code    = get_woocommerce_currency();
			if ( isset( $msrp_by_currency[ $currency_code ] ) ) {
				$currency   = $currency_code;
				$msrp       = apply_filters( 'alg_wc_msrp_by_currency', $msrp_by_currency[ $currency ], $product_id, $currency );
			}
		}
		if ( '' == $msrp || 0 == $msrp ) {
			$msrp = apply_filters( 'alg_wc_msrp', $this->get_post_or_parent_meta( $product_id, '_alg_msrp' ), $product_id );
		}
		if ( $this->options['is_msrp_apply_price_filter_enabled'] ) {
			$msrp = apply_filters( $this->options['wc_price_filter'], $msrp, wc_get_product( $product_id ) );
		}
		if ( ! empty( $msrp ) ) {
			$msrp = str_replace( ',', '.', $msrp );
			$msrp = floatval( $msrp );
		}
		return apply_filters( 'alg_wc_get_msrp', array( 'msrp' => $msrp, 'currency' => $currency ), $product_id );
	}

	/**
	 * Format a price range for display.
	 *
	 * @version 1.3.6
	 * @since   1.1.0
	 *
	 * @param   string $from Price from.
	 * @param   string $to   Price to.
	 * @return  string
	 */
	function wc_format_price_range( $from, $to, $currency_from, $currency_to ) {
		$from = ( is_numeric( $from ) ? wc_price( $from, array( 'currency' => $currency_from ) ) : $from );
		$to   = ( is_numeric( $to )   ? wc_price( $to,   array( 'currency' => $currency_to ) )   : $to );
		return ( $this->options['custom_range_format_enabled'] ?
			str_replace( array( '%from%', '%to%' ), array( $from, $to ), $this->options['custom_range_format'] ) :
			apply_filters( 'woocommerce_format_price_range', sprintf( _x( '%1$s &ndash; %2$s', 'Price range: from-to', 'woocommerce' ), $from, $to ), $from, $to )
		);
	}

	/**
	 * Format a range for display.
	 *
	 * @version 1.3.6
	 * @since   1.1.0
	 *
	 * @param   string $from Value from.
	 * @param   string $to   Value to.
	 * @return  string
	 */
	function format_range( $from, $to ) {
		return ( $this->options['custom_range_format_enabled'] ?
			str_replace( array( '%from%', '%to%' ), array( $from, $to ), $this->options['custom_range_format'] ) :
			sprintf( '%1$s &ndash; %2$s', $from, $to )
		);
	}

	/**
	 * sort_by_msrp.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function sort_by_msrp( $a, $b ) {
		if ( $a['msrp'] == $b['msrp'] ) {
			return 0;
		}
		return ( $a['msrp'] < $b['msrp'] ) ? -1 : 1;
	}

	/**
	 * get_available_variations_ids.
	 *
	 * @version 1.3.3
	 * @since   1.3.3
	 * @see     `WC_Product_Variable::get_available_variations()`
	 * @todo    [dev] (maybe) optionally return in `$child_id => $child_object` format
	 */
	function get_available_variations_ids( $product ) {
		$available_variations_ids = array();

		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );

			// Hide out of stock variations if 'Hide out of stock items from the catalog' is checked.
			if ( ! $variation || ! $variation->exists() || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $variation->is_in_stock() ) ) {
				continue;
			}

			// Filter 'woocommerce_hide_invisible_variations' to optionally hide invisible variations (disabled variations and variations with empty price).
			if ( apply_filters( 'woocommerce_hide_invisible_variations', true, $product->get_id(), $variation ) && ! $variation->variation_is_visible() ) {
				continue;
			}

			$available_variations_ids[] = $child_id;
		}
		$available_variations_ids = array_values( array_filter( $available_variations_ids ) );

		return $available_variations_ids;
	}

	/**
	 * delete_product_transient_variable.
	 *
	 * @version 1.3.3
	 * @since   1.3.3
	 */
	function delete_product_transient_variable( $post_id ) {
		$you_save_round = 0;
		foreach ( array( 'msrp', 'you_save', 'you_save_percent' ) as $type ) {
			delete_transient( 'alg_wc_msrp_var_' . $type . '_' . $post_id );
		}
	}

	/**
	 * get_saved_value_variable_hash.
	 *
	 * @version 1.3.3
	 * @since   1.3.3
	 * @todo    [dev] rethink `currency`, `country` and maybe `you_save_round`
	 */
	function get_saved_value_variable_hash( $type, $you_save_round ) {
		return apply_filters( 'alg_wc_msrp_variable_hash', md5( json_encode( array(
			'you_save_round'       => ( 'you_save_percent' == $type ? $you_save_round : 0 ),
			'country'              => ( ! empty( $this->options['msrp_countries'] ) ? $this->get_visitors_country_by_ip() : '' ),
			'currency'             => ( ! empty( $this->options['msrp_currencies'] ) ? get_woocommerce_currency() : '' ),
			'price_filter_enabled' => $this->options['is_msrp_apply_price_filter_enabled'],
		) ) ) );
	}

	/**
	 * get_saved_value_variable.
	 *
	 * @version 1.3.3
	 * @since   1.3.3
	 * @todo    [dev] set to `in_transients` by default (and probably remove other options)
	 */
	function get_saved_value_variable( $product, $type, $you_save_round ) {
		switch ( $this->options['variable_optimization'] ) {
			case 'in_transients':
				$transient_value = get_transient( 'alg_wc_msrp_var_' . $type . '_' . $this->get_product_id( $product ) );
				$transient_hash  = $this->get_saved_value_variable_hash( $type, $you_save_round );
				return ( isset( $transient_value[ $transient_hash ] ) ? $transient_value[ $transient_hash ] : false );
			case 'in_array':
				$product_id = $this->get_product_id( $product );
				return ( isset( $this->saved_get_variable_msrp[ $product_id ][ $type ][ $you_save_round ] ) ?
					$this->saved_get_variable_msrp[ $product_id ][ $type ][ $you_save_round ] : false );
		}
		return false; // `none`
	}

	/**
	 * save_value_variable.
	 *
	 * @version 1.3.3
	 * @since   1.3.3
	 */
	function save_value_variable( $value, $product, $type, $you_save_round ) {
		switch ( $this->options['variable_optimization'] ) {
			case 'in_transients':
				$transient_value = get_transient( 'alg_wc_msrp_var_' . $type . '_' . $this->get_product_id( $product ) );
				$transient_hash  = $this->get_saved_value_variable_hash( $type, $you_save_round );
				$transient_value[ $transient_hash ] = $value;
				set_transient( 'alg_wc_msrp_var_' . $type . '_' . $this->get_product_id( $product ), $transient_value, DAY_IN_SECONDS * 30 );
				break;
			case 'in_array':
				$this->saved_get_variable_msrp[ $this->get_product_id( $product ) ][ $type ][ $you_save_round ] = $value;
				break;
		}
	}

	/**
	 * get_variable_msrp.
	 *
	 * @version 1.3.3
	 * @since   1.1.0
	 */
	function get_variable_msrp( $product, $type = 'msrp', $you_save_round = 0 ) {
		if ( false !== ( $saved_value = $this->get_saved_value_variable( $product, $type, $you_save_round ) ) ) {
			return $saved_value;
		}
		if ( ! $this->check_user_role() ) {
			$this->save_value_variable( '', $product, $type, $you_save_round );
			return '';
		}
		$data = array();
		foreach ( $this->get_available_variations_ids( $product ) as $variation_id ) {
			$msrp = $this->get_msrp( $variation_id );
			if ( '' != $msrp['msrp'] ) {
				if ( 'you_save' === $type ) {
					$variation_product = wc_get_product( $variation_id );
					$msrp['msrp']     -= $variation_product->get_price();
					if ( $msrp['msrp'] > 0 ) {
						$data[ $variation_id ] = $msrp;
					}
				} elseif ( 'you_save_percent' === $type ) {
					$variation_product = wc_get_product( $variation_id );
					$diff              = $msrp['msrp'] - $variation_product->get_price();
					$you_save_percent  = round( $diff / $msrp['msrp'] * 100, $you_save_round );
					if ( $you_save_percent > 0 ) {
						$msrp['msrp']     = $you_save_percent;
						$msrp['currency'] = '';
						$data[ $variation_id ] = $msrp;
					}
				} else {
					$data[ $variation_id ] = $msrp;
				}
			}
		}
		if ( empty( $data ) ) {
			$this->save_value_variable( '', $product, $type, $you_save_round );
			return '';
		} else {
			uasort( $data, array( $this, 'sort_by_msrp' ) );
			$min = current( $data );
			$max = end( $data );
			if ( $min['msrp'] !== $max['msrp'] ) {
				$html = ( 'you_save_percent' === $type ?
					$this->format_range( $min['msrp'], $max['msrp'] ) : $this->wc_format_price_range( $min['msrp'], $max['msrp'], $min['currency'], $max['currency'] ) );
			} else {
				$html = ( 'you_save_percent' === $type ?
					$min['msrp'] : wc_price( $min['msrp'], $min['currency'] ) );
			}
		}
		$this->save_value_variable( $html, $product, $type, $you_save_round );
		return $html;
	}

	/**
	 * compare_variable_prices_and_msrp.
	 *
	 * Returns `false` if *at least one* variation is not `$cmp`
	 *
	 * @version 1.3.3
	 * @since   1.1.0
	 */
	function compare_variable_prices_and_msrp( $product, $cmp ) {
		foreach ( $this->get_available_variations_ids( $product ) as $variation_id ) {
			$msrp              = $this->get_msrp( $variation_id );
			$variation_product = wc_get_product( $variation_id );
			$variation_price   = $variation_product->get_price();
			switch ( $cmp ) {
				case 'is_equal':
					if ( $variation_price != $msrp['msrp'] ) {
						return false;
					}
					break;
				case 'is_lower_or_equal':
					if ( $variation_price > $msrp['msrp'] ) {
						return false;
					}
					break;
			}
		}
		return true;
	}

	/**
	 * get_product_price_html.
	 *
	 * @version 1.3.7
	 * @since   1.3.7
	 */
	function get_product_price_html( $product ) {
		remove_filter( 'woocommerce_get_price_html', array( $this, 'display' ), PHP_INT_MAX, 2 );
		$price = $product->get_price_html();
		add_filter(    'woocommerce_get_price_html', array( $this, 'display' ), PHP_INT_MAX, 2 );
		return $price;
	}

	/**
	 * display.
	 *
	 * @version 1.3.7
	 * @since   1.0.0
	 * @todo    [fix] (important) add `%product_id%` placholder to the template (and replace `id="alg_wc_msrp"` with `id="alg_wc_msrp-%product_id%" class="alg_wc_msrp"`)
	 * @todo    [dev] (important) calculate placeholders only if search value is present in the template (e.g. search for `%price%` before calling `get_product_price_html()`)
	 * @todo    [dev] (maybe) add `%raw_msrp%` placeholder
	 * @todo    [dev] (maybe) add `%regular_price%` and `%sale_price%` placeholders
	 */
	function display( $price_html, $product, $section_id = false ) {
		if ( '' === $price_html && $this->options['do_hide_msrp_for_empty_price'] ) {
			return $price_html;
		}
		if ( ! $section_id ) {
			$section_id = ( is_product() && is_single( $this->get_product_parent_id( $product ) ) ? 'single' : 'archives' );
		}
		$display = $this->options['msrp_display'][ $section_id ]['display'];
		if ( 'hide' == $display ) {
			return $price_html;
		}
		if ( $product->is_type( 'variable' ) ) {
			$msrp_variable_html = $this->get_variable_msrp( $product );
			if ( '' == $msrp_variable_html ) {
				return $price_html;
			}
			if (
				( 'show_if_diff'   == $display && $this->compare_variable_prices_and_msrp( $product, 'is_equal' ) ) ||
				( 'show_if_higher' == $display && $this->compare_variable_prices_and_msrp( $product, 'is_lower_or_equal' ) ) ) {
				return $price_html;
			}
			$you_save_variable_html         = $this->get_variable_msrp( $product, 'you_save' );
			$you_save_percent_variable_html = $this->get_variable_msrp( $product, 'you_save_percent', $this->options['msrp_display'][ $section_id ]['you_save_round'] );
			$replaced_values = array(
				'%price%'            => $this->get_product_price_html( $product ),
				'%msrp%'             => $msrp_variable_html,
				'%you_save%'         => str_replace( '%you_save_raw%',
					$you_save_variable_html,
					( '' != $you_save_variable_html ? $this->options['msrp_display'][ $section_id ]['you_save'] : '' ) ),
				'%you_save_percent%' => str_replace( '%you_save_percent_raw%',
					$you_save_percent_variable_html,
					( '' != $you_save_percent_variable_html ? $this->options['msrp_display'][ $section_id ]['you_save_percent'] : '' ) ),
			);
		} else {
			$product_id = $this->get_product_id( $product );
			$msrp_data  = $this->get_msrp( $product_id );
			$msrp       = $msrp_data['msrp'];
			$currency   = $msrp_data['currency'];
			if ( '' == $msrp || 0 == $msrp ) {
				return $price_html;
			}
			$price = $product->get_price();
			if ( ! is_numeric( $price ) ) {
				$price = 0;
			}
			if ( ( 'show_if_diff' == $display && $msrp == $price ) || ( 'show_if_higher' == $display && $msrp <= $price ) ) {
				return $price_html;
			}
			$diff = $msrp - $price;
			$replaced_values = array(
				'%price%'            => wc_price( $product->get_price() ),
				'%msrp%'             => wc_price( $msrp, array( 'currency' => $currency ) ),
				'%you_save%'         => str_replace( '%you_save_raw%',
					wc_price( $diff, array( 'currency' => $currency ) ),
					( $diff > 0 ? $this->options['msrp_display'][ $section_id ]['you_save'] : '' ) ),
				'%you_save_percent%' => str_replace( '%you_save_percent_raw%',
					round( $diff / $msrp * 100, $this->options['msrp_display'][ $section_id ]['you_save_round'] ),
					( $diff > 0 ? $this->options['msrp_display'][ $section_id ]['you_save_percent'] : '' ) ),
			);
		}
		$msrp_html = str_replace( array_keys( $replaced_values ), $replaced_values, do_shortcode( $this->options['msrp_display'][ $section_id ]['template'] ) );
		switch ( $this->options['msrp_display'][ $section_id ]['position'] ) {
			case 'before_price':
				return $msrp_html . $price_html;
			case 'after_price':
				return $price_html . $msrp_html;
			case 'instead_of_price':
				return $msrp_html;
		}
	}

}

endif;

return new Alg_WC_MSRP_Core();
