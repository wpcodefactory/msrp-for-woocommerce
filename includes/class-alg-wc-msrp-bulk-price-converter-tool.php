<?php
/**
 * Bulk Price Converter - Tool Class
 *
 * @version 1.6.3
 * @since   1.4.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_MSRP_Bulk_Price_Converter_Tool' ) ) :

class Alg_WC_MSRP_Bulk_Price_Converter_Tool {

	/**
	 * Constructor.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'convert_prices' ),                PHP_INT_MAX );
			add_action( 'admin_menu', array( $this, 'add_bulk_price_converter_tool' ), PHP_INT_MAX );
		}
	}

	/**
	 * convert_prices.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function convert_prices() {
		$this->atts   = $this->get_atts();
		$this->result = array();
		if ( ( $this->atts['is_change'] || $this->atts['is_preview'] ) && $this->atts['multiply_prices_by'] > 0 ) {
			if ( ! isset( $_POST['alg_wc_msrp_bpc_nonce'] ) || ! wp_verify_nonce( $_POST['alg_wc_msrp_bpc_nonce'], 'alg_wc_msrp_bpc_action' ) || ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Wrong user role or nonce did not verify.', 'msrp-for-woocommerce' ) );
			}
			$this->result = $this->change_all_products_prices( $this->atts );
			if ( ! $this->atts['is_preview'] ) {
				add_action( 'admin_notices', array( $this, 'add_admin_notice' ) );
				if ( function_exists( 'wc_delete_product_transients' ) ) {
					wc_delete_product_transients();
				}
			}
		}
	}
	
	/**
	 * change_all_products_prices.
	 *
	 * @version 1.5.0
	 * @todo    [dev] (maybe) `sale_prices`: remove `( get_post_meta( $_product_id, '_' . 'price', true ) === get_post_meta( $_product_id, '_' . 'sale_price', true ) )` check
	 * @todo    [dev] (maybe) `regular_prices`: remove `( $price !== $sale_price || $atts['multiply_prices_by'] <= 1 )` check
	 */
	function change_all_products_prices( $atts ) {
		if ( $atts['multiply_prices_by'] <= 0 ) {
			return;
		}
		$offset       = 0;
		$block_size   = 1024;
		$time_limit   = -1;
		$this->result = array();
		$this->atts   = $atts;
		while ( true ) {
			if ( -1 != $time_limit ) {
				set_time_limit( $time_limit );
			}
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			if ( 'any' != $atts['product_cats'] ) {
				$args = apply_filters( 'alg_wc_msrp_bpc_product_query', $args, 'product_cat', $atts['product_cats'] );
			}
			if ( 'any' != $atts['product_tags'] ) {
				$args = apply_filters( 'alg_wc_msrp_bpc_product_query', $args, 'product_tag', $atts['product_tags'] );
			}
			
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $product_id ) {
				// Getting all product IDs (including variations for variable products)
				$product_ids = array( $product_id );
				$product     = wc_get_product( $product_id );
				if ( $product->is_type( 'variable' ) ) {
					$product_ids = array_merge( $product_ids, $product->get_children() );
				}
				// Changing prices
				foreach ( $product_ids as $_product_id ) {

					$this->change_price( $_product_id, $product_id, 'alg_msrp', 0, 0 );
							
				}
			}
			$offset += $block_size;
		}
		return $this->result;
	}
	
	/**
	 * change_price.
	 *
	 * @version 1.5.0
	 */
	function change_price( $product_id, $parent_product_id, $price_type, $min_price = 0, $max_price = 0 ) {
		// Price calculation
		$type = $this->atts['price_types'];
		$price = get_post_meta( $product_id, '_' . $type, true );
		$modified_price = $price;
		// Direct price
		if ( '' !== $this->atts['direct_price'] ) {
			$modified_price = $this->atts['direct_price'];
		}
		if ( '' !== $modified_price ) {
			// Multiplication
			if ( '' !== $this->atts['multiply_prices_by'] ) {
				$modified_price = $modified_price * $this->atts['multiply_prices_by'];
			}
			// Addition
			if ( '' != $this->atts['add_to_price'] ) {
				$modified_price = $modified_price + $this->atts['add_to_price'];
			}
			// Rounding
			if ( 'none' != $this->atts['round_function'] ) {
				$modified_price = apply_filters( 'alg_wc_msrp_bpc_round_price', $modified_price, $this->atts['round_function'], $this->atts['round_coef'] );
			}
			// Final rounding
			$precision          = get_option( 'woocommerce_price_num_decimals', 2 );
			$modified_price     = round( $modified_price, $precision );
			// "Pretty price"
			if ( $this->atts['pretty_prices_threshold'] > 0 ) {
				$modified_price = apply_filters( 'alg_wc_msrp_bpc_pretty_price', $modified_price, $this->atts['pretty_prices_threshold'] );
			}
			// Negative, min & max
			if ( $modified_price < 0 ) {
				$modified_price = 0;
			}
			if ( 0 != $min_price && $modified_price < $min_price ) {
				$modified_price = $min_price;
			}
			if ( 0 != $max_price && $modified_price > $max_price ) {
				$modified_price = $max_price;
			}
			// Maybe update to new price
			if ( ! $this->atts['is_preview'] ) {
				update_post_meta( $product_id, '_' . $price_type, $modified_price );
			}
		}
		// Output
		if ( '' != $price || '' != $modified_price ) {
			$_product_cats = array();
			$product_terms = get_the_terms( $parent_product_id, 'product_cat' );
			if ( ! is_wp_error( $product_terms ) && is_array( $product_terms ) ) {
				foreach ( $product_terms as $term ) {
					$_product_cats[] = esc_html( $term->name );
				}
			}
			$_product_tags = array();
			$product_terms = get_the_terms( $parent_product_id, 'product_tag' );
			if ( ! is_wp_error( $product_terms ) && is_array( $product_terms ) ) {
				foreach ( $product_terms as $term ) {
					$_product_tags[] = esc_html( $term->name );
				}
			}
			
			$this->result[] = array(
				get_the_title( $product_id ) . ' (#' . $product_id . ')',
				implode( ', ', $_product_cats ),
				implode( ', ', $_product_tags ),
				'<code>' . str_replace( 'alg_', ' ', $price_type ) . '</code>',
				( is_numeric( $price ) ? wc_price( $price ) : $price ),
				'<span' . ( $modified_price != $price ? ' style="color:orange;"' : '' ) . '>' . ( is_numeric( $modified_price ) ? wc_price( $modified_price ) : $modified_price ) . '</span>',
			);
		}
	}

	/**
	 * add_admin_notice.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function add_admin_notice() {
		if ( ! empty( $this->result ) ) {
			$message = __( 'Prices updated successfully!', 'msrp-for-woocommerce' );
			$class   = 'success';
		} else {
			$message = __( 'No products.', 'msrp-for-woocommerce' );
			$class   = 'warning';
		}
		echo '<div class="notice notice-' . $class . '"><p><strong>' . $message . '</strong></p></div>';
	}

	/**
	 * add_bulk_price_converter_tool.
	 *
	 * @version 1.4.0
	 */
	function add_bulk_price_converter_tool() {
		add_submenu_page(
			'woocommerce',
			__( 'MSRP Bulk Price Converter Tool', 'msrp-for-woocommerce' ),
			__( 'MSRP Bulk Price Converter', 'msrp-for-woocommerce' ),
			'manage_woocommerce',
			'bulk-msrp-price-converter-tool',
			array( $this, 'create_bulk_price_converter_tool' )
		);
	}

	/**
	 * create_bulk_price_converter_tool.
	 *
	 * @version 1.5.0
	 * @todo    [dev] (maybe) allow "Multiply all product prices by" to be zero
	 * @todo    [dev] rewrite (`get_tool_options()`)
	 * @todo    [dev] (maybe) empty (default) values in "Multiply" and "Add"
	 * @todo    [dev] (maybe) save previously set settings ("Price type to modify", "Products category", "Pretty prices threshold" etc.)
	 * @todo    [feature] add product selection (i.e. products list)
	 * @todo    [feature] `multiple` in "Products category" and in "Products tags"
	 * @todo    [feature] Products: "Product type" (all, simple, variable, variations etc.)
	 * @todo    [feature] Products: "Price range" (min, max)
	 * @todo    [feature] Products: "Sale status" (on sale / not on sale)
	 * @todo    [feature] show/hide Tool per user role (i.e. admin, shop manager etc.)
	 * @todo    [feature] option to use "regular" price, when "sale" price is not available (i.e. force product on sale)
	 * @todo    [feature] option which tool fields to show
	 */
	function create_bulk_price_converter_tool() {
		$step  = 1 / pow( 10, get_option( 'alg_wc_bulk_price_converter_step', 6 ) );
		$html  = '';
		$html .= '<div class="wrap">';
		$html .= '<a style="float: right;" href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_msrp&section=admin_advanced' ) . '">' .
			__( 'MSRP Admin & Advanced settings', 'bulk-price-converter-for-woocommerce' ) . '</a>';
		$html .= '<h1>' . __( 'MSRP Bulk Price Converter Tool', 'msrp-for-woocommerce' ) . '</h1>';
		$html .= '<form method="post" action="">';
		// General Options
		$html .= '<h2>' . __( 'General Options', 'msrp-for-woocommerce' ) . '</h2>';
		$data_table = array();
		$data_table[] = array(
			__( '<strong>Multiply</strong> all product prices by', 'msrp-for-woocommerce' ),
			'<input style="min-width: 200px;" class="" type="number" step="' . $step . '" min="' . $step .
				'" name="alg_wc_msrp_bpc_multiply_prices_by" id="alg_wc_msrp_bpc_multiply_prices_by" value="' . $this->atts['multiply_prices_by'] . '">',
		);
		$data_table[] = array(
			__( '<strong>Add</strong> to all product prices', 'msrp-for-woocommerce' ),
			'<input style="min-width: 200px;" class="" type="number" step="' . $step . '" name="alg_wc_msrp_bpc_add_to_price" id="alg_wc_msrp_bpc_add_to_price" value="' .
				$this->atts['add_to_price'] . '">',
		);
		$data_table[] = array(
			__( 'Base Price', 'msrp-for-woocommerce' ),
			'<select style="min-width: 200px;" name="alg_wc_msrp_bpc_price_types">' .
				'<option value="regular_price"' . selected( 'regular_price', $this->atts['price_types'], false ) . '>' .
					__( 'Regular prices only', 'msrp-for-woocommerce' ) . '</option>' .
				'<option value="sale_price"'    . selected( 'sale_price',    $this->atts['price_types'], false ) . '>' .
					__( 'Sale prices only', 'msrp-for-woocommerce' )    . '</option>' .
			'</select>'
		);
		$html .= $this->get_table_html( $data_table,
			array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical', 'columns_styles' => array( 'width: 200px;' ) ) );
		// Products
		$html .= '<h2>' . __( 'Products', 'msrp-for-woocommerce' ) . '</h2>' . apply_filters( 'alg_wc_msrp_bpc_settings',
			' <p><em>' . sprintf( __( 'You will need %s plugin to change values in this section.', 'msrp-for-woocommerce' ),
				'<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">MSRP (RRP) for WooCommerce Pro</a>' ) . '</em></p>' );
		$data_table = array();
		$data_table[] = array(
			__( 'Products <strong>category</strong>', 'msrp-for-woocommerce' ),
			'<select style="min-width: 200px;" name="alg_wc_msrp_bpc_product_cats" ' . apply_filters( 'alg_wc_msrp_bpc_settings', 'disabled' ) . '>' .
				$this->get_terms_options( 'product_cat', $this->atts['product_cats'] ) .
			'</select>'
		);
		$data_table[] = array(
			__( 'Products <strong>tag</strong>', 'msrp-for-woocommerce' ),
			'<select style="min-width: 200px;" name="alg_wc_msrp_bpc_product_tags" ' . apply_filters( 'alg_wc_msrp_bpc_settings', 'disabled' ) . '>' .
				$this->get_terms_options( 'product_tag', $this->atts['product_tags'] ) .
			'</select>'
		);
		$html .= $this->get_table_html( $data_table,
			array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical', 'columns_styles' => array( 'width: 200px;' ) ) );
		// Final Price Correction
		$html .= '<h2>' . __( 'Final Price Correction', 'msrp-for-woocommerce' ) . '</h2>' . apply_filters( 'alg_wc_msrp_bpc_settings',
			' <p><em>' . sprintf( __( 'You will need %s plugin to change values in this section.', 'msrp-for-woocommerce' ),
				'<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">MSRP (RRP) for WooCommerce Pro</a>' ) . '</em></p>' );
		$data_table = array();
		$data_table[] = array(
			__( '<strong>Rounding function</strong>', 'msrp-for-woocommerce' ),
			'<select style="min-width: 200px;" name="alg_wc_msrp_bpc_round_function" ' . apply_filters( 'alg_wc_msrp_bpc_settings', 'disabled' ) . '>' .
				'<option value="none"' .  selected( 'none',  $this->atts['round_function'], false ) . '>' .
					__( 'None', 'msrp-for-woocommerce' )  . '</option>' .
				'<option value="round"' . selected( 'round', $this->atts['round_function'], false ) . '>' .
					__( 'Round', 'msrp-for-woocommerce' ) . '</option>' .
				'<option value="floor"' . selected( 'floor', $this->atts['round_function'], false ) . '>' .
					__( 'Floor', 'msrp-for-woocommerce' ) . '</option>' .
				'<option value="ceil"'  . selected( 'ceil',  $this->atts['round_function'], false ) . '>' .
					__( 'Ceil', 'msrp-for-woocommerce' )  . '</option>' .
			'</select>'
		);
		$data_table[] = array(
			__( 'Rounding <strong>coefficient</strong>', 'msrp-for-woocommerce' ) .
				$this->tip( __( 'Ignored if "Rounding function" is set to "None".', 'bulk-price-converter-for-woocommerce' ) ),
			'<input style="min-width: 200px;" class="" type="number" step="' . $step . '" min="' . $step . '" name="alg_wc_msrp_bpc_round_coef" ' .
				'id="alg_wc_msrp_bpc_round_coef" value="' . $this->atts['round_coef'] . '"' . apply_filters( 'alg_wc_msrp_bpc_settings', 'disabled' ) . '>'
		);
		$data_table[] = array(
			__( '<strong>"Pretty prices"</strong> threshold', 'msrp-for-woocommerce' ) .
				$this->tip( __( 'Ignored if set to zero.', 'msrp-for-woocommerce' ) ),
			'<input style="min-width: 200px;" class="" type="number" step="' . $step .
				'" min="0" name="alg_wc_msrp_bpc_pretty_prices_threshold" id="alg_wc_msrp_bpc_pretty_prices_threshold" value="' .
				$this->atts['pretty_prices_threshold'] . '"' . apply_filters( 'alg_wc_msrp_bpc_settings', 'disabled' ) . '>'
		);
		$html .= $this->get_table_html( $data_table,
			array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical', 'columns_styles' => array( 'width: 200px;' ) ) );
		// Buttons
		$html .= '<p>';
		$html .= '<input class="button-primary" type="submit" name="alg_wc_msrp_bpc_preview_prices" id="alg_wc_msrp_bpc_preview_prices" value="' .
			__( 'Preview prices', 'msrp-for-woocommerce' ) . '">';
		$html .= ' <input class="button-primary" type="submit" name="alg_wc_msrp_bpc_change_prices" id="alg_wc_msrp_bpc_change_prices"' .
			' style="background: red; border-color: red; box-shadow: 0 1px 0 red; text-shadow: 0 -1px 1px #a00,1px 0 1px #a00,0 1px 1px #a00,-1px 0 1px #a00;"' .
			' value="' . __( 'Change prices', 'msrp-for-woocommerce' ) . '"' .
			' onclick="return confirm(\'' . __( 'There is no undo for this action.', 'msrp-for-woocommerce' ) . ' ' .
				__( 'Are you sure?', 'msrp-for-woocommerce' ) . '\')">';
		$html .= ' <a class="button" href="">' . __( 'Reset', 'msrp-for-woocommerce' ) . '</a>';
		$html .= '</p>';
		$html .= wp_nonce_field( 'alg_wc_msrp_bpc_action', 'alg_wc_msrp_bpc_nonce', true, false );
		$html .= '</form>';
		// Results table
		if ( $this->atts['is_change'] || $this->atts['is_preview'] ) {
			$html .= '<h2>' . __( 'Results', 'msrp-for-woocommerce' ) . '</h2>';
			if ( ! empty( $this->result ) ) {
				$data_table = array_merge( array(
					array(
						__( 'Product', 'msrp-for-woocommerce' ),
						__( 'Categories', 'msrp-for-woocommerce' ),
						__( 'Tags', 'msrp-for-woocommerce' ),
						__( 'Price Type', 'msrp-for-woocommerce' ),
						__( 'Original Price', 'msrp-for-woocommerce' ),
						__( 'Modified Price', 'msrp-for-woocommerce' ),
					) ),
					$this->result
				);
				$html .= $this->get_table_html( $data_table, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'horizontal' ) );
			} else {
				$html .= '<p>' . '<em>' . __( 'No products.', 'msrp-for-woocommerce' ) . '</em>' . '</p>';
			}
		}
		$html .= '</div>';
		echo $html;
	}

	/**
	 * get_atts.
	 *
	 * @version 1.6.3
	 * @since   1.4.0
	 */
	function get_atts() {
		return array(
			'direct_price'              => isset( $_POST['alg_wc_msrp_bpc_direct_price'] )            ? sanitize_text_field( $_POST['alg_wc_msrp_bpc_direct_price'] )   : '',
			'multiply_prices_by'        => isset( $_POST['alg_wc_msrp_bpc_multiply_prices_by'] )      ? floatval( $_POST['alg_wc_msrp_bpc_multiply_prices_by'] )        : 1,
			'add_to_price'              => isset( $_POST['alg_wc_msrp_bpc_add_to_price'] )            ? floatval( $_POST['alg_wc_msrp_bpc_add_to_price'] )              : 0,
			'product_cats'              => isset( $_POST['alg_wc_msrp_bpc_product_cats'] )            ? sanitize_text_field( $_POST['alg_wc_msrp_bpc_product_cats'] )   : 'any',
			'product_tags'              => isset( $_POST['alg_wc_msrp_bpc_product_tags'] )            ? sanitize_text_field( $_POST['alg_wc_msrp_bpc_product_tags'] )   : 'any',
			'price_types'               => isset( $_POST['alg_wc_msrp_bpc_price_types'] )             ? sanitize_text_field( $_POST['alg_wc_msrp_bpc_price_types'] )    : 'regular_prices',
			'round_function'            => isset( $_POST['alg_wc_msrp_bpc_round_function'] )          ? sanitize_text_field( $_POST['alg_wc_msrp_bpc_round_function'] ) : 'none',
			'round_coef'                => isset( $_POST['alg_wc_msrp_bpc_round_coef'] )              ? floatval( $_POST['alg_wc_msrp_bpc_round_coef'] )                : 0.05,
			'pretty_prices_threshold'   => isset( $_POST['alg_wc_msrp_bpc_pretty_prices_threshold'] ) ? floatval( $_POST['alg_wc_msrp_bpc_pretty_prices_threshold'] )   : 0,
			'is_preview'                => isset( $_POST['alg_wc_msrp_bpc_preview_prices'] ),
			'is_change'                 => isset( $_POST['alg_wc_msrp_bpc_change_prices'] ),
		);
	}

	/**
	 * get_terms_options.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function get_terms_options( $taxonomy, $terms ) {
		$terms_html          = '';
		$terms_html         .= '<option value="any"'  . selected( 'any', $terms, false )  . '>' . __( 'Any', 'msrp-for-woocommerce' ) . '</option>';
		global $wp_version;
		$product_terms       = ( version_compare( $wp_version, '4.5.0', '<' ) ?
			get_terms( $taxonomy, array( 'orderby' => 'name', 'hide_empty' => 0 ) ) : get_terms( array( 'taxonomy' => $taxonomy, 'orderby' => 'name', 'hide_empty' => 0 ) ) );
		if ( ! empty( $product_terms ) && ! is_wp_error( $product_terms ) ){
			foreach ( $product_terms as $product_term ) {
				$terms_html .= '<option value="' . $product_term->slug . '"' . selected( $product_term->slug, $terms, false ) . '>' . $product_term->name . '</option>';
			}
		}
		$terms_html         .= '<option value="none"' . selected( 'none', $terms, false ) . '>' . __( 'None', 'msrp-for-woocommerce' ) . '</option>';
		return $terms_html;
	}

	/**
	 * tip.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 * @todo    [dev] (maybe) make it nicer
	 */
	function tip( $message ) {
		return '<span style="cursor: pointer; float: right; line-height: 15px; width: 15px; border-radius: 50%; background-color: gray; color: white; font-weight: bold; font-size: x-small; text-align: center;" ' .
			'title="' . esc_html( $message ) . '">&quest;</span>';
	}

	/**
	 * get_table_html.
	 *
	 * @version 1.4.0
	 */
	function get_table_html( $data, $args = array() ) {
		$args  = array_merge( array(
			'table_class'        => '',
			'table_style'        => '',
			'table_heading_type' => 'horizontal',
			'columns_classes'    => array(),
			'columns_styles'     => array(),
		), $args );
		$html  = '';
		$html .= '<table' . ( '' == $args['table_class'] ? '' : ' class="' . $args['table_class'] . '"' ) . ( '' == $args['table_style'] ? '' : ' style="' . $args['table_style'] . '"' ) . '>';
		$html .= '<tbody>';
		foreach( $data as $row_number => $row ) {
			$html .= '<tr>';
			foreach( $row as $column_number => $value ) {
				$th_or_td      = ( ( 0 === $row_number && 'horizontal' === $args['table_heading_type'] ) || ( 0 === $column_number && 'vertical' === $args['table_heading_type'] ) ? 'th' : 'td' );
				$column_class  = ( ! empty( $args['columns_classes'] ) && isset( $args['columns_classes'][ $column_number ] ) ? ' class="' . $args['columns_classes'][ $column_number ] . '"' : '' );
				$column_style  = ( ! empty( $args['columns_styles'] )  && isset( $args['columns_styles'][ $column_number ] )  ? ' style="' . $args['columns_styles'][ $column_number ]  . '"' : '' );
				$html         .= '<' . $th_or_td . $column_class . $column_style . '>';
				$html         .= $value;
				$html         .= '</' . $th_or_td . '>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		$html .= '</table>';
		return $html;
	}

}

endif;

return new Alg_WC_MSRP_Bulk_Price_Converter_Tool();
