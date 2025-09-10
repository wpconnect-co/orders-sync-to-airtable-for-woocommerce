<?php
/**
 * Order source.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Order_Source.
 */
class Order_Source extends Abstract_Source {

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
	protected $slug = 'order';

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
			'label' => __( 'Order', 'orders-sync-to-airtable-for-woocommerce' ),
			'slug'  => 'order',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		$mapping_fields = array(
			array(
				'value'                  => 'order_number',
				'label'                  => __( 'Order #', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'date_created',
				'label'                  => __( 'Date created', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_datetime_supported_destinations(),
			),
			array(
				'value'                  => 'status',
				'label'                  => __( 'Status', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'total',
				'label'                  => __( 'Total', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_number_supported_destinations(),
			),
			array(
				'value'                  => 'payment_method_title',
				'label'                  => __( 'Payment method', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'transaction_id',
				'label'                  => __( 'Transaction ID', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'customer_note',
				'label'                  => __( 'Customer provided note', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),
			array(
				'value'                  => 'custom_field',
				'label'                  => __( 'Order meta...', 'orders-sync-to-airtable-for-woocommerce' ),
				'enabled'                => true,
				'allow_multiple'         => true,
				'supported_destinations' => $this->formatter->get_string_supported_destinations(),
			),

		);

		return $mapping_fields;
	}

	/**
	 * Add order fields to order data.
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
		$data                                = array();
		$data['order::order_number']         = $order->get_order_number();
		$data['order::date_created']         = $order->get_date_created();
		$data['order::status']               = $order->get_status();
		$data['order::total']                = $order->get_total();
		$data['order::payment_method_title'] = $order->get_payment_method_title();
		$data['order::transaction_id']       = $order->get_transaction_id();
		$data['order::customer_note']        = $order->get_customer_note();
		if ( isset( $fields['order::custom_field'] ) ) {
			$data['order::custom_field'] = '';
			foreach ( $exporter->config()->get( 'mapping' ) as $map ) {
				if ( 'order::custom_field' !== $map['wordpress'] || empty( $map['options']['name'] ) ) {
					continue;
				}
				$data[ $map['options']['name'] ] = $order->get_meta( $map['options']['name'] );
			}
		}
		$fields = array_merge( $fields, $data );

		$custom_format_strategies = array(
			'order::date_created' => array( 'strategies' => array( 'format_to_datetime' ) ),
		);

		$context = new Export_Context();
		$context->set_exporter( $exporter );
		$context->set_existing_record_id( $existing_record_id );

		return $this->format_data( $order_data, $context, $fields, $custom_format_strategies, array_keys( $data ) );
	}
}
