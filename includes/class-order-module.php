<?php
/**
 * Class to define WooCommerce order’s source fields.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/sources/class-order-source.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/sources/class-order-billing-source.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/sources/class-order-shipping-source.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/sources/class-order-customer-source.php';

/**
 * Class Order_Module.
 */
class Order_Module {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'orders_sync_to_airtable_for_woocommerce/register_source', array( $this, 'register_sources' ) );
	}

	/**
	 * Get mapping options.
	 *
	 * @return array
	 */
	public function get_mapping_options() {
		return apply_filters( 'orders_sync_to_airtable_for_woocommerce/get_wp_fields', array(), 'order' );
	}

	/**
	 * Register sources
	 */
	public function register_sources() {
		new Order_Source( Services::get_instance()->get( 'formatter' ) );
		new Order_Billing_Source( Services::get_instance()->get( 'formatter' ) );
		new Order_Shipping_Source( Services::get_instance()->get( 'formatter' ) );
		new Order_Customer_Source( Services::get_instance()->get( 'formatter' ) );
	}
}
