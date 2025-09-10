<?php
/**
 * Action Scheduler Action Consumer.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Action_Consumer
 */
class Action_Consumer {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'orders_sync_to_airtable_for_woocommerce_exporter_process_records', array( $this, 'consume' ), 10, 2 );
		add_action( 'orders_sync_to_airtable_for_woocommerce_exporter_single_order', array( $this, 'consume_single_order' ), 10, 1 );
	}

	/**
	 * Consume a record from queue
	 *
	 * @param string $run_id Importer run id.
	 * @param string $item_id Records chunk id.
	 */
	public function consume( $run_id, $item_id ) {
		// Get orders saved as a temporary option.
		$orders = get_option( $item_id );
		if ( ! $orders ) {
			return;
		}

		$exporter = Services::get_instance()->get( 'exporter' );
		foreach ( $orders as $order ) {
			// Import record.
			list($order_id, $error) = $exporter->process_woocommerce_order( $order );
			// Temporarily save created or saved content ID.
			if ( ! empty( $order_id ) ) {
				$exporter->append_run_result( $order_id, $run_id );
			}
			if ( is_wp_error( $error ) ) {
				$exporter->add_error( $error );
			}
		}

		// Delete temporary option.
		delete_option( $item_id );
	}

	/**
	 * Consume "orders_sync_to_airtable_for_woocommerce_exporter_single_order" action.
	 *
	 * @param int $order_id Order id.
	 *
	 * @return void
	 */
	public function consume_single_order( $order_id ) {
		$exporter = Services::get_instance()->get( 'exporter' );
		$order    = \WC_Order_Factory::get_order( $order_id );
		if ( ! $order ) {
			$exporter->log( __( 'The order does not exist, it may have been deleted?', 'orders-sync-to-airtable-for-woocommerce' ), Logger::WARNING, $order_id );
			return;
		}
		$exporter->process_woocommerce_order( $order );
	}
}
