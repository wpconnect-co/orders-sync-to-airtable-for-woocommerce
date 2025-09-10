<?php
/**
 * Order Shipping source.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Order_Shipping_Source.
 */
class Order_Shipping_Source extends Abstract_Source {

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
	protected $slug = 'order_shipping';

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
			'label' => __( 'Order Shipping', 'orders-sync-to-airtable-for-woocommerce' ),
			'slug'  => 'order_shipping',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		$mapping_fields = array(
			array(
				'value'                  => 'shipping_first_name',
				'label'                  => __( 'Shipping First name', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_last_name',
				'label'                  => __( 'Shipping Last name', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_company',
				'label'                  => __( 'Shipping Company', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_address_1',
				'label'                  => __( 'Shipping Address line 1', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_address_2',
				'label'                  => __( 'Shipping Address line 2', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_city',
				'label'                  => __( 'Shipping City', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_postcode',
				'label'                  => __( 'Shipping Postcode / ZIP', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_country',
				'label'                  => __( 'Shipping Country / Region', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_state',
				'label'                  => __( 'Shipping State / County', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'shipping_phone',
				'label'                  => __( 'Shipping Phone', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
		);

		return $mapping_fields;
	}

	/**
	 * Add shipping fields to order data.
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
		$data                                        = array();
		$data['order_shipping::shipping_first_name'] = $order->get_shipping_first_name();
		$data['order_shipping::shipping_last_name']  = $order->get_shipping_last_name();
		$data['order_shipping::shipping_company']    = $order->get_shipping_company();
		$data['order_shipping::shipping_address_1']  = $order->get_shipping_address_1();
		$data['order_shipping::shipping_address_2']  = $order->get_shipping_address_2();
		$data['order_shipping::shipping_city']       = $order->get_shipping_city();
		$data['order_shipping::shipping_postcode']   = $order->get_shipping_postcode();
		$data['order_shipping::shipping_country']    = $order->get_shipping_country();
		$data['order_shipping::shipping_state']      = $order->get_shipping_state();
		$data['order_shipping::shipping_phone']      = $order->get_shipping_phone();
		$fields                                      = array_merge( $fields, $data );

		$custom_format_strategies = array();

		$context = new Export_Context();
		$context->set_exporter( $exporter );
		$context->set_existing_record_id( $existing_record_id );

		return $this->format_data( $order_data, $context, $fields, $custom_format_strategies, array_keys( $data ) );
	}
}
