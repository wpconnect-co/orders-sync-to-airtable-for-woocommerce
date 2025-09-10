<?php
/**
 * Order customer source.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Order_Customer_Source.
 */
class Order_Customer_Source extends Abstract_Source {

	/**
	 * Source slug
	 *
	 * @var string
	 */
	protected $module = 'order';

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected $slug = 'order_customer';

	/**
	 * Constructor.
	 *
	 * @param Formatter $formatter Formatter.
	 */
	public function __construct( $formatter ) {
		parent::__construct( $formatter );

		add_filter( 'orders_sync_to_airtable_for_woocommerce/export_order_data', array( $this, 'add_to_order_data' ), 20, 5 );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'label' => __( 'Order Customer', 'orders-sync-to-airtable-for-woocommerce' ),
			'slug'  => 'order_customer',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		$mapping_fields = array(
			array(
				'value'                  => 'email',
				'label'                  => __( 'Customer (email)', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
		);

		return $mapping_fields;
	}

	/**
	 * Add customer field to order data.
	 *
	 * @param array      $order_data Order data.
	 * @param Exporter   $exporter Exporter.
	 * @param array      $fields Fields.
	 * @param \WC_Order  $order WooCommerce order.
	 * @param mixed|null $existing_record_id Airtable record id.
	 *
	 * @return mixed
	 */
	public function add_to_order_data( $order_data, $exporter, $fields, $order, $existing_record_id ) {
		$data       = array();
		$user_order = $order->get_user();
		if ( $user_order ) {
			$custom_email = $user_order->user_email;
		} else {
			$custom_email = $order->get_billing_email();
		}
		$data['order_customer::email'] = $custom_email;
		$fields                        = array_merge( $fields, $data );

		$custom_format_strategies = array();

		$context = new Export_Context();
		$context->set_exporter( $exporter );
		$context->set_existing_record_id( $existing_record_id );

		return $this->format_data( $order_data, $context, $fields, $custom_format_strategies, array_keys( $data ) );
	}
}
