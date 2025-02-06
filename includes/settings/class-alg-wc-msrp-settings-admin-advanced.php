<?php
/**
 * MSRP for WooCommerce - Admin & Advanced Section Settings
 *
 * @version 1.8.0
 * @since   1.3.9
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_MSRP_Settings_Admin_Advanced' ) ) :

class Alg_WC_MSRP_Settings_Admin_Advanced extends Alg_WC_MSRP_Settings_Section {

	/**
	 * Tracks the number of products modified.
	 *
	 * @version 1.8.0
	 * @since   1.8.0
	 */
	public $alg_wc_msrp_action_done;

	/**
	 * Constructor.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 * @todo    [dev] (maybe) add "Tools" to the title?
	 */
	function __construct() {
		$this->id   = 'admin_advanced';
		$this->desc = __( 'Admin & Advanced', 'msrp-for-woocommerce' );
		parent::__construct();
		add_action( 'admin_init',                                     array( $this, 'admin_actions' ), PHP_INT_MAX );
		add_action( 'woocommerce_admin_field_' . 'alg_wc_msrp_tools', array( $this, 'output_tools' ),  PHP_INT_MAX );
	}

	/**
	 * output_tools.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function output_tools( $value ) {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<?php echo $value['desc']; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * admin_actions_notice.
	 *
	 * @version 1.3.4
	 * @since   1.3.4
	 * @todo    [dev] (maybe) better (i.e. more informative) notices
	 */
	function admin_actions_notice() {
		echo '<div class="notice notice-success is-dismissible"><p>' .
			sprintf( __( '%d product(s) modified.', 'msrp-for-woocommerce' ), $this->alg_wc_msrp_action_done ) .
		'</p></div>';
	}

	/**
	 * admin_actions.
	 *
	 * @version 1.3.5
	 * @since   1.3.4
	 * @todo    [dev] (maybe) copy_price_to_msrp: copy to variable product only if at least one variation MSRP is empty
	 */
	function admin_actions() {
		if ( ! empty( $_GET['alg_wc_msrp_action'] ) ) {
			// Security check: nonce
			if ( ! isset( $_GET['alg_wc_msrp_wpnonce'] ) || ! wp_verify_nonce( $_GET['alg_wc_msrp_wpnonce'], $_GET['alg_wc_msrp_action'] ) ) {
				wp_die( __( 'Nonce not verified.', 'msrp-for-woocommerce' ) );
			}
			// Security check: user role
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Wrong user role.', 'msrp-for-woocommerce' ) );
			}
			// Actions
			$result = 0;
			switch ( $_GET['alg_wc_msrp_action'] ) {
				case 'copy_price_to_msrp':
					foreach ( wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'type' => array_merge( array_keys( wc_get_product_types() ), array( 'variation' ) ) ) ) as $product_id ) {
						$product = wc_get_product( $product_id );
						$price   = $product->get_price();
						update_post_meta( $product_id, '_alg_msrp', $price );
						$result++;
					}
					break;
				case 'delete_msrp_meta':
					global $wpdb;
					$plugin_meta = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_alg_msrp%'" );
					foreach ( $plugin_meta as $meta ) {
						delete_post_meta( $meta->post_id, $meta->meta_key );
						$result++;
					}
					break;
			}
			// Add notice
			$this->alg_wc_msrp_action_done = $result;
			add_action( 'admin_notices', array( $this, 'admin_actions_notice' ) );
		}
	}

	/**
	 * get_user_roles.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function get_user_roles() {
		global $wp_roles;
		$user_roles = array_merge( array( 'guest' => array( 'name' => __( 'Guest', 'msrp-for-woocommerce' ), 'capabilities' => array() ) ),
			apply_filters( 'editable_roles', ( ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array() ) ) );
		return wp_list_pluck( $user_roles, 'name' );
	}

	/**
	 * get_tools.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function get_tools() {
		$tools = array();
		foreach ( array(
				'copy_price_to_msrp' => __( 'Copy all products prices to MSRP', 'msrp-for-woocommerce' ),
				'delete_msrp_meta'   => __( 'Delete all products MSRP meta', 'msrp-for-woocommerce' ),
			) as $tool_action => $tool_title
		) {
			$tools[] = '<a class="button"' .
				' href="' . add_query_arg( array( 'alg_wc_msrp_action' => $tool_action, 'alg_wc_msrp_wpnonce' => wp_create_nonce( $tool_action ) ), remove_query_arg( 'alg_wc_msrp_action_done' ) ) . '"' .
				' onclick="return confirm(\'' . __( 'Are you sure?', 'msrp-for-woocommerce' ) . '\')">' . $tool_title . '</a>';
		}
		$tools[] = '<a class="button-primary"' .
					' style="background: green; border-color: green; box-shadow: 0 1px 0 green; "' .
					' href="' . admin_url( 'admin.php?page=bulk-msrp-price-converter-tool' ) . '">' . __( 'Bulk MSRP price converter tool', 'bulk-price-converter-for-woocommerce' ) . '</a>';
		return $tools;
	}

	/**
	 * get_settings.
	 *
	 * @version 1.3.9
	 * @since   1.3.9
	 */
	function get_settings() {

		$tools_settings = array(
			array(
				'title'    => __( 'Tools', 'msrp-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_tools_options',
			),
			array(
				'title'    => __( 'Tools', 'msrp-for-woocommerce' ),
				'desc'     => implode( ' ', $this->get_tools() ),
				'id'       => 'alg_wc_msrp_tools',
				'type'     => 'alg_wc_msrp_tools',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_tools_options',
			),
		);

		$admin_settings = array(
			array(
				'title'    => __( 'Admin Options', 'msrp-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_admin_options',
			),
			array(
				'title'    => __( 'Admin products list', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Adds MSRP column to admin products list.', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Add', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_admin_add_products_column',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Admin quick edit', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Adds MSRP field to admin quick edit.', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Add', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_admin_add_quick_edit',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Admin bulk edit', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Adds MSRP field to admin bulk edit.', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Add', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_admin_add_bulk_edit',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Position in admin quick and bulk edit', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'MSRP field position in admin quick and bulk edit.', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_admin_quick_bulk_edit_position',
				'default'  => 'end',
				'type'     => 'select',
				'options'  => array(
					'start' => __( 'At the start', 'msrp-for-woocommerce' ),
					'end'   => __( 'At the end', 'msrp-for-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'MSRP backend field label', 'msrp-for-woocommerce' ),
				'desc'     => __( 'MSRP backend field label', 'msrp-for-woocommerce' ),
				'type'     => 'text',
				'id'       => 'alg_wc_msrp_admin_field_label',
				'default'  => '',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_admin_options',
			),
		);

		$advanced_settings = array(
			array(
				'title'    => __( 'Advanced Options', 'msrp-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_msrp_advanced_options',
			),
			array(
				'title'    => __( 'Products with empty price', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Hides MSRP for products with empty price.', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Hide', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_advanced_hide_for_empty_price',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Products on sale', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Hides regular price for products on sale and with MSRP.', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Hide', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_hide_regular_price_for_sale_products',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Custom range format', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Enables custom range format for <strong>variable</strong> products when displaying MSRP data.', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Enable', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_custom_range_format_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => $this->message_replaced_values( array( '%from%', '%to%' ) ),
				'desc_tip' => __( 'Custom range format', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_custom_range_format',
				'default'  => __( 'From %from%' ),
				'type'     => 'textarea',
				'css'      => 'width:100%;',
				'alg_wc_msrp_raw' => true,
			),
			array(
				'title'    => __( 'Apply price filter', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Enable this if you want to apply standard WooCommerce price filter to MSRP (e.g. if you are using some currency switcher plugin with price calculation by exchange rates).', 'msrp-for-woocommerce' ),
				'desc'     => __( 'Enable', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_apply_price_filter',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Required user role(s)', 'msrp-for-woocommerce' ),
				'desc_tip' => __( 'Only show MSRP to selected user roles.', 'msrp-for-woocommerce' ) . ' ' .
					__( 'Leave blank to show to all user roles.', 'msrp-for-woocommerce' ),
				'desc'     => apply_filters( 'alg_wc_msrp_settings', '<p>' . sprintf( 'You will need %s plugin to use "User roles" option.',
					'<a target="_blank" href="https://wpfactory.com/item/msrp-for-woocommerce/">' . 'MSRP for WooCommerce Pro' . '</a>' ) . '</p>' ),
				'id'       => 'alg_wc_msrp_required_user_roles',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $this->get_user_roles(),
				'custom_attributes' => apply_filters( 'alg_wc_msrp_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'title'    => __( 'Variable MSRP optimization', 'msrp-for-woocommerce' ),
				'id'       => 'alg_wc_msrp_variable_optimization',
				'default'  => 'none',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'options'  => array(
					'none'          => __( 'None', 'msrp-for-woocommerce' ),
					'in_transients' => __( 'Save in transients', 'msrp-for-woocommerce' ),
					'in_array'      => __( 'Save in array', 'msrp-for-woocommerce' ),
				),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_msrp_advanced_options',
			),
		);

		return array_merge( $tools_settings, $admin_settings, $advanced_settings );
	}

}

endif;

return new Alg_WC_MSRP_Settings_Admin_Advanced();
