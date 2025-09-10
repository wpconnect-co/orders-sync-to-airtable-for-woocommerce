<?php
/**
 * Order Billing source.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Order_Billing_Source.
 */
class Order_Billing_Source extends Abstract_Source {

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
	protected $slug = 'order_billing';

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
			'label' => __( 'Order Billing', 'orders-sync-to-airtable-for-woocommerce' ),
			'slug'  => 'order_billing',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		$mapping_fields = array(
			array(
				'value'                  => 'billing_first_name',
				'label'                  => __( 'Billing First name', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_last_name',
				'label'                  => __( 'Billing Last name', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_company',
				'label'                  => __( 'Billing Company', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_address_1',
				'label'                  => __( 'Billing Address line 1', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_address_2',
				'label'                  => __( 'Billing Address line 2', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_city',
				'label'                  => __( 'Billing City', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_postcode',
				'label'                  => __( 'Billing Postcode / ZIP', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_country',
				'label'                  => __( 'Billing Country / Region', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_state',
				'label'                  => __( 'Billing State / County', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_email',
				'label'                  => __( 'Billing Email address', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'billing_phone',
				'label'                  => __( 'Billing Phone', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
		);

		return $mapping_fields;
	}

	/**
	 * Add billing fields to order data.
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
		$data                                      = array();
		$data['order_billing::billing_first_name'] = $order->get_billing_first_name();
		$data['order_billing::billing_last_name']  = $order->get_billing_last_name();
		$data['order_billing::billing_company']    = $order->get_billing_company();
		$data['order_billing::billing_address_1']  = $order->get_billing_address_1();
		$data['order_billing::billing_address_2']  = $order->get_billing_address_2();
		$data['order_billing::billing_city']       = $order->get_billing_city();
		$data['order_billing::billing_postcode']   = $order->get_billing_postcode();
		$data['order_billing::billing_country']    = $order->get_billing_country();
		$data['order_billing::billing_state']      = $order->get_billing_state();
		$data['order_billing::billing_email']      = $order->get_billing_email();
		$data['order_billing::billing_phone']      = $order->get_billing_phone();
		$fields                                    = array_merge( $fields, $data );

		$custom_format_strategies = array();

		$context = new Export_Context();
		$context->set_exporter( $exporter );
		$context->set_existing_record_id( $existing_record_id );

		return $this->format_data( $order_data, $context, $fields, $custom_format_strategies, array_keys( $data ) );
	}
}
