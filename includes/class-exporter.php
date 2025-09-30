<?php
/**
 * Exporter.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

use Exception, TypeError;
use WP_Error, WP_CLI;

/**
 * Exporter
 */
class Exporter {
	/**
	 * Config
	 *
	 * @var Exporter_Settings
	 */
	public $config;

	/**
	 * State
	 *
	 * @var Exporter_State
	 */
	public $state;

	/**
	 * Api Client
	 *
	 * @var Airtable_Api_Client
	 */
	protected $api_client;

	/**
	 * API client class factory.
	 *
	 * @var callable
	 */
	protected $api_client_class_factory;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	protected $logger;


	const STATE_INIT              = 'STATE_INIT';
	const STATE_PROCESSING_ORDERS = 'STATE_PROCESSING_ORDERS';

	const STATE_COMPLETED = 'STATE_COMPLETED';

	/**
	 * Constructor
	 *
	 * @param Exporter_Settings $exporter_settings Config object.
	 * @param Exporter_State    $exporter_state Config object.
	 * @param callable          $api_client_class_factory The API client class factory.
	 * @param Logger            $logger Logger.
	 */
	public function __construct( $exporter_settings, $exporter_state, $api_client_class_factory, $logger ) {
		$this->config                   = $exporter_settings;
		$this->state                    = $exporter_state;
		$this->api_client_class_factory = $api_client_class_factory;
		$this->logger                   = $logger;
		add_action( 'action_scheduler_after_process_queue', array( $this, 'check_sync_state' ) );
		if ( 'order_state_change' === $this->get_schedule_type() ) {
			add_action( 'woocommerce_order_status_changed', array( $this, 'update_airtable_orders_on_status_change' ), 10, 3 );
		}
	}

	/**
	 * Config getter
	 *
	 * @return Exporter_Settings
	 */
	public function config() {
		return $this->config;
	}

	/**
	 * State getter
	 *
	 * @return Exporter_State
	 */
	public function state() {
		return $this->state;
	}

	/**
	 * Get schedule type.
	 *
	 * @return string
	 */
	public function get_schedule_type() {
		return $this->config->get( 'scheduled_sync' ) && $this->config->get( 'scheduled_sync' )['type'] ? $this->config->get( 'scheduled_sync' )['type'] : 'manual';
	}

	/**
	 * Return last updated date as string (gmdate + format 'Y-m-d H:i:s') or false if there were no successful sync yet.
	 *
	 * @return string|false
	 */
	public function get_last_updated() {
		return $this->state()->get( 'last_updated' );
	}

	/**
	 * Fields getter
	 *
	 * @return array|false|null
	 */
	public function get_airtable_fields() {
		if ( ! $this->get_run_id() && 'order_state_change' === $this->get_schedule_type() ) {
			return $this->get_table_fields();
		}
		return $this->get_run_option( $this->get_run_id(), 'table_fields', array() );
	}

	/**
	 * Run ID getter
	 *
	 * @return string|false|null
	 */
	public function get_run_id() {
		return $this->state()->get( 'run_id' );
	}

	/**
	 * Get run option key.
	 *
	 * @param string $run_id Run id.
	 * @param string $suffix Key suffix.
	 *
	 * @return string
	 */
	protected function get_run_option_key( $run_id, $suffix ) {
		return 'orders_sync_to_airtable_for_woocommerce_exporter_run-' . $run_id . '-' . $suffix;
	}

	/**
	 * Update run option.
	 *
	 * @param string $run_id Run id.
	 * @param string $suffix Key suffix.
	 * @param mixed  $value Option value.
	 *
	 * @return void
	 */
	protected function update_run_option( $run_id, $suffix, $value ) {
		update_option( $this->get_run_option_key( $run_id, $suffix ), $value, false );
	}

	/**
	 * Get run option.
	 *
	 * @param string $run_id Run id.
	 * @param string $suffix Key suffix.
	 * @param mixed  $default_value Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed Value of the option.
	 */
	public function get_run_option( $run_id, $suffix, $default_value = false ) {
		return get_option( $this->get_run_option_key( $run_id, $suffix ), $default_value );
	}

	/**
	 * Cron action
	 *
	 * @return bool|WP_Error
	 */
	public function cron() {
		return $this->run();
	}

	/**
	 * Run importer
	 *
	 * @return bool|WP_Error
	 */
	public function run() {
		if ( $this->get_run_id() ) {
			return new WP_Error( 'orders-sync-to-airtable-for-woocommerce-run-error', __( 'A sync is already running.', 'orders-sync-to-airtable-for-woocommerce' ) );
		}

		$this->reset_errors();

		try {
			// Define a unique id for this run.
			$run_id = uniqid();
			// Save run.
			$this->state()->set( 'run_id', $run_id )->save();

			$this->log( sprintf( 'Starting exporter...' ) );

			$this->update_run_option( $run_id, 'start-date', gmdate( 'Y-m-d H:i:s' ) );

			$this->update_run_option( $run_id, 'state', self::STATE_INIT );

			// Save table schema.
			$this->update_run_option( $run_id, 'table_fields', $this->get_table_fields() );

			// Loop through all orders.
			$offset = null;
			do {
				$offset = $this->save_orders( $run_id, $offset );
			} while ( ! empty( $offset ) && $this->get_run_id() );

			$this->update_run_option( $run_id, 'state', self::STATE_PROCESSING_ORDERS );

			return true;
		} catch ( \Throwable $e ) {
			$this->log( $e->getMessage() );
			$this->end_run( 'error', $e->getMessage() );
			return new WP_Error( 'orders-sync-to-airtable-for-woocommerce-run-error', $e->getMessage() );
		}
	}

	/**
	 * Log message to file and WPCLI output
	 *
	 * @param string|\WP_Error|\Throwable $message Message to log.
	 * @param string                      $level Log level ("log", "error", "warning").
	 * @param string|false                $run_id Run id.
	 */
	public function log( $message, $level = 'log', $run_id = false ) {
		if ( ! $run_id ) {
			$run_id = $this->get_run_id();
			if ( empty( $run_id ) && 'manual' !== $this->get_schedule_type() ) {
				$run_id = $this->get_schedule_type();
			}
		}
		$this->logger->log( $message, gmdate( 'Y-m-d' ) . '-' . $run_id, $level );
	}

	/**
	 * Returns the latest log file URL if any.
	 *
	 * @return string|false
	 */
	public function get_latest_log_file_url() {
		$log_files = $this->logger->get_log_files();
		if ( count( $log_files ) === 0 ) {
			return false;
		}
		$latest_log_file = array_shift( $log_files );
		$log_dir_url     = $this->logger->get_log_dir_url();

		return str_replace( $this->logger->get_log_dir(), $log_dir_url, $latest_log_file );
	}

	/**
	 * Reset errors.
	 *
	 * @return void
	 */
	public function reset_errors() {
		$this->state()->delete( 'errors' )->save();
	}

	/**
	 * Add an error (string or WP_Error object ) to the importer error list.
	 *
	 * @param string|WP_Error $error Error to add to the importer error list.
	 *
	 * @return void
	 */
	public function add_error( $error ) {
		$errors   = $this->get_errors();
		$errors[] = $error;
		$this->state()->set( 'errors', $errors )->save();
	}

	/**
	 * Return errors.
	 *
	 * @return array
	 */
	public function get_errors() {
		$errors = $this->state()->get( 'errors' );
		if ( ! is_array( $errors ) ) {
			$errors = array();
		}
		return $errors;
	}

	/**
	 * Process a WooCommerce order.
	 * Return:
	 * - on success an array with the record id created on Airtable as first element and null as second element.
	 * - on failure  an array with null as first element and a WP_Error object as second element.
	 *
	 * @param \WC_Order $order WooCommerce order.
	 *
	 * @return array
	 * @throws \Exception Error message from get_order_number_field method.
	 */
	public function process_woocommerce_order( $order ) {
		$this->log( sprintf( 'Order ID %s', $order->get_id() ) );

		try {
			if ( $order->get_meta( 'orders_sync_to_airtable_for_woocommerce_hash' ) && ! $this->needs_update( $order->get_id(), (object) $order->get_data() ) ) {
				$this->log( '- No update needed' );
				return array( $order->get_meta( 'orders_sync_to_airtable_for_woocommerce_record_id' ), null );
			}

			$airtable_order_number_field = $this->get_order_number_field();
			if ( is_wp_error( $airtable_order_number_field ) ) {
				throw new \Exception( $airtable_order_number_field->get_error_message() );
			}

			$record_id    = null;
			$search_order = $this->get_api_client()->list_records(
				$this->config()->get( 'baseId' ),
				$this->config()->get( 'tableId' ),
				array(
					'filterByFormula'       => '{' . $airtable_order_number_field->name . '}="' . $order->get_order_number() . '"',
					'returnFieldsByFieldId' => true,
				)
			);
			if ( count( $search_order->records ) > 0 ) {
				$record_id = $search_order->records[0]->id;
				$this->log( sprintf( '- Found matching record, ID %s', $record_id ) );
			}

			$fields = $this->get_mapped_fields();
			$this->log( sprintf( $record_id ? '- Update order %s' : '- Create order %s', $order->get_id() ) );
			$record_id = $this->export_order( $order, $fields, $record_id );
			return array( $record_id, null );
		} catch ( \Throwable $e ) {
			$this->log( $e );
			return array( false, new WP_Error( 'orders-sync-to-airtable-for-woocommerce-run-error', $e->getMessage() ) );
		}
	}


	/**
	 * Export order to Airtable record and return the Airtable record id created / updated.
	 *
	 * @param \WC_Order  $order WooCommerce order.
	 * @param array      $fields Order mapped fields.
	 * @param mixed|null $existing_record_id Airtable record id.
	 *
	 * @return mixed
	 * @throws \Exception Error message from get_order_number_field method.
	 */
	public function export_order( $order, $fields, $existing_record_id ) {
		$record_data = array(
			'fields' => array(),
		);

		/**
		 * Filters order data that will be passed to Airtable API.
		 *
		 * @param array $order_data Record data.
		 * @param Exporter $exporter Exporter.
		 * @param array $fields Fields.
		 * @param \WC_Order $order WooCommerce order.
		 * @param mixed|null $existing_record_id Airtable record id.
		 */
		$order_data = apply_filters( 'orders_sync_to_airtable_for_woocommerce/export_order_data', array(), $this, $fields, $order, $existing_record_id );

		$field_formatters = $this->get_field_formatters();
		foreach ( $this->config()->get( 'mapping' ) as $map ) {
			$airtable_field = $this->get_field_by_id( $map['airtable'] );
			if ( empty( $airtable_field ) ) {
				continue;
			}
			$wordpress_field = $map['wordpress'];
			if ( ! empty( $map['options']['name'] ) ) {
				$wordpress_field = $map['options']['name'];
			}
			$field_type = $airtable_field->type;

			$field_value = $order_data[ $wordpress_field ] ?? null;
			if ( is_null( $field_value ) || ! isset( $field_formatters[ $field_type ] ) ) {
				continue;
			}
			$field_value                                  = $field_formatters[ $field_type ]( $field_value );
			$record_data['fields'][ $airtable_field->id ] = $field_value;
		}

		$airtable_order_number_field = $this->get_order_number_field();
		if ( is_wp_error( $airtable_order_number_field ) ) {
			throw new Exception( esc_html( $airtable_order_number_field->get_error_message() ) );
		}

		if ( $existing_record_id ) {
			$update_records_response = $this->get_api_client()->update_records(
				$this->config()->get( 'baseId' ),
				$this->config()->get( 'tableId' ),
				array( $record_data ),
				array(
					'fieldsToMergeOn'       => array( $airtable_order_number_field->id ),
					'returnFieldsByFieldId' => true,
				)
			);
		} else {
			$create_records_response = $this->get_api_client()->create_records( $this->config()->get( 'baseId' ), $this->config()->get( 'tableId' ), array( $record_data ) );
			$existing_record_id      = $create_records_response->records[0]->id;
		}

		$order->update_meta_data( 'orders_sync_to_airtable_for_woocommerce_record_id', $existing_record_id );
		$order->save_meta_data();

		$order->update_meta_data( 'orders_sync_to_airtable_for_woocommerce_hash', $this->generate_hash( (object) $order->get_data() ) );
		$order->save_meta_data();

		return $existing_record_id;
	}

	/**
	 * Get airtable field definition by id
	 *
	 * @param string $airtable_id Airtable field id.
	 */
	public function get_field_by_id( $airtable_id ) {
		foreach ( $this->get_airtable_fields() as $field ) {
			if ( $field->id === $airtable_id ) {
				return $field;
			}
		}
		return '';
	}

	/**
	 * Return Airtable field mapped to "order::order_number" Order field.
	 *
	 * @return \stdClass|WP_Error
	 */
	public function get_order_number_field() {
		foreach ( $this->config()->get( 'mapping' ) as $map ) {
			if ( 'order::order_number' !== $map['wordpress'] ) {
				continue;
			}
			$order_number_field = $this->get_field_by_id( $map['airtable'] );
			if ( empty( $order_number_field ) ) {
				return new WP_Error( 'order_number_is_missing', 'The "Order #" Airtable column is missing' );
			}
			// Disallow {} symbols to avoid issues with the Formulas.
			if ( strpos( $order_number_field->name, '}' ) !== false || strpos( $order_number_field->name, '{' ) !== false ) {
				return new WP_Error( 'order_number_is_missing', 'The "Order #" Airtable column’s name contains invalid characters: {}' );
			}
			return $order_number_field;
		}
		return new WP_Error( 'order_number_is_missing', 'The "Order #" Airtable column is missing' );
	}


	/**
	 * Return formatters to update Airtable fields.
	 *
	 * @return \Closure[]
	 */
	protected function get_field_formatters() {
		return array(
			'singleLineText' => function ( $field_value ) {
				if ( empty( $field_value ) && '0' !== $field_value ) {
					return null;
				}
				return ( (string) $field_value );
			},
			'email'          => function ( $field_value ) {
				if ( empty( $field_value ) || strpos( $field_value, '@' ) === false ) {
					return null;
				}
				return (string) $field_value;
			},
			'currency'       => function ( $field_value ) {
				if ( '' === $field_value ) {
					return null;
				}
				return (float) $field_value;
			},
			'number'         => function ( $field_value ) {
				if ( '' === $field_value ) {
					return null;
				}
				return (float) $field_value;
			},
			'dateTime'       => function ( $field_value ) {
				if ( ! ( $field_value instanceof \DateTimeInterface ) ) {
					return false;
				}
				$airtable_date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $field_value->format( 'Y-m-d H:i:s' ), wp_timezone() );
				$airtable_date->setTimezone( new \DateTimeZone( 'UTC' ) );
				return $airtable_date->format( 'Y-m-d\TH:i:s\Z' );
			},
			'singleSelect'   => function ( $field_value ) {
				if ( empty( $field_value ) && '0' !== $field_value ) {
					return null;
				}
				return ( (string) $field_value );
			},
			'multilineText'  => function ( $field_value ) {
				if ( empty( $field_value ) && '0' !== $field_value ) {
					return null;
				}
				return ( (string) $field_value );
			},
		);
	}

	/**
	 * End run
	 *
	 * @param string      $status Import status ("success", "cancel" or "error").
	 * @param string|null $error Error message.
	 */
	public function end_run( $status = 'success', $error = null ) {
		global $wpdb;
		$run_id = $this->get_run_id();

		$processed_order_ids = $this->get_run_option( $run_id, 'order_ids', array() );
		$count_processed     = is_array( $processed_order_ids ) ? count( $processed_order_ids ) : 0;

		// Delete any remaining AS actions.
		$as_deletion_args = array(
			'run_id' => $run_id,
		);

		Helper::delete_as_actions( 'orders_sync_to_airtable_for_woocommerce_exporter_process_orders', \ActionScheduler_Store::STATUS_PENDING, $as_deletion_args );

		$start_date = $this->get_run_option( $run_id, 'start-date' );

		// Delete temporary options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $this->get_run_option_key( $run_id, '' ) ) . '%'
			)
		);

		$action_ids = \ActionScheduler::store()->query_actions(
			array(
				'hook'                  => 'orders_sync_to_airtable_for_woocommerce_exporter_process_orders',
				'status'                => \ActionScheduler_Store::STATUS_FAILED,
				'partial_args_matching' => 'like',
				'args'                  => array(
					'run_id' => $run_id,
				),
				'per_page'              => -1,
			)
		);
		if ( count( $action_ids ) > 0 ) {
			$this->add_error(
				sprintf(
				// translators: %1$s: start link tag, %2$s end link tag.
					__( 'Some tasks have been interrupted, %1$smore details%2$s', 'orders-sync-to-airtable-for-woocommerce' ),
					'<a href="' . esc_url( admin_url( 'tools.php?page=action-scheduler&status=failed&s=' . $run_id . '&action=-1&action2=-1&paged=1' ) ) . '">',
					'</a>'
				)
			);
		}
		// Remove temporary metas.
		$this->state()
			->delete( 'run_id' )
			->delete( 'table_fields' )
			->save();

		$importer_errors = $this->get_errors();
		if ( $importer_errors && 'success' === $status ) {
			$status = 'error';
		}
		// Update status and error.
		$this->state()
			->set( 'status', $status )
			->set( 'last_error', $error )
			->save();

		// Save date if success.
		if ( 'success' === $status ) {
			$this->state()
				->set( 'last_updated', gmdate( 'Y-m-d H:i:s' ) )
				->set( 'count_processed', $count_processed )
				->save();

			if ( $start_date ) {
				$this->state()->set( 'last_start_date', $start_date );
			}

			$this->state()->save();
		}

		$this->log( 'Import ends', 'log', $run_id );
	}

	/**
	 * Get table fields from API.
	 */
	public function get_table_fields() {
		return Helper::get_airtable_table_fields(
			$this->get_api_client(),
			$this->config()->get( 'baseId' ),
			$this->config()->get( 'tableId' ),
			$this->get_export_fields_options()
		);
	}

	/**
	 * Get and save AT records from API.
	 *
	 * @param string $run_id Import run id.
	 * @param int    $offset Pagination offset.
	 *
	 * @return string|false
	 */
	protected function save_orders( $run_id, $offset = 0 ) {
		$data = $this->get_orders( $offset );
		// Loop through all orders.
		$chunks = array_chunk( $data->orders, 30 );
		foreach ( $chunks as $chunk ) {
			// Save orders as a temporary option.
			$item_id = uniqid( $this->get_run_option_key( $run_id, 'item' ) . '-' );
			update_option( $item_id, $chunk );
			// Add it to queue.
			as_enqueue_async_action(
				'orders_sync_to_airtable_for_woocommerce_exporter_process_records',
				array(
					'run_id'  => $run_id,
					'item_id' => $item_id,
				)
			);
		}

		return ! empty( $data->offset ) ? $data->offset : false;
	}

	/**
	 * Get orders from WooCommerce.
	 *
	 * @param string|null $offset Pagination offset.
	 *
	 * @return object   { orders: [], offset: int|false }
	 */
	public function get_orders( $offset = 0 ) {
		$wc_order_query_params = array(
			'type'    => 'shop_order',
			'limit'   => 300,
			'orderby' => 'date',
			'order'   => 'ASC',
			'offset'  => $offset,
		);
		$wc_order_query_params = apply_filters( 'orders_sync_to_airtable_for_woocommerce/get_orders_wc_order_query_params', $wc_order_query_params, $this, $offset );
		// Remove empty values.
		$wc_order_query_params = array_filter( $wc_order_query_params );

		$query  = new \WC_Order_Query( $wc_order_query_params );
		$orders = $query->get_orders();

		return (object) array(
			'orders' => apply_filters( 'orders_sync_to_airtable_for_woocommerce/get_orders', $orders, $wc_order_query_params, $this, $offset ),
			'offset' => count( $orders ) > 0 && count( $orders ) < $wc_order_query_params['limit'] ? $offset + $wc_order_query_params['limit'] : false,
		);
	}

	/**
	 * Get mapped fields
	 */
	protected function get_mapped_fields() {
		$mapping        = ! empty( $this->config()->get( 'mapping' ) ) ? $this->config()->get( 'mapping' ) : array();
		$wordpress_keys = array_map(
			function ( $mapping_pair ) {
				if ( preg_match( '/(.+)\.(.+)/', $mapping_pair['wordpress'], $matches ) ) {
					$wordpress_id = $matches[1];
				} else {
					$wordpress_id = $mapping_pair['wordpress'];
				}
				return $wordpress_id;
			},
			$mapping
		);

		$fields = array();
		foreach ( $wordpress_keys as $wordpress_key ) {
			$fields[ $wordpress_key ] = '';
		}

		return apply_filters( 'orders_sync_to_airtable_for_woocommerce/export_order_fields', $fields, $this );
	}

	/**
	 * Update import state based on the action scheduler tasks state.
	 * 1. STATE_INIT
	 * 2. STATE_PROCESSING_ORDERS: process orders saved in `get_orders`
	 * 4. STATE_COMPLETED: once the import is completed.
	 *
	 * @return void
	 */
	public function check_sync_state() {
		// Not needed if there is no import running.
		if ( ! $this->get_run_id() ) {
			return;
		}

		$run_id                   = $this->get_run_id();
		$as_actions_args          = array(
			'run_id' => $run_id,
		);
		$current_import_state     = $this->get_run_option( $this->get_run_id(), 'state' );
		$pending_running_statuses = array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING );

		$is_processing_record = count( Helper::get_as_actions( 'orders_sync_to_airtable_for_woocommerce_exporter_process_records', $pending_running_statuses, $as_actions_args ) ) > 0;
		if ( self::STATE_PROCESSING_ORDERS === $current_import_state && ! $is_processing_record ) {
			$this->update_run_option( $run_id, 'state', self::STATE_COMPLETED );
		}

		$new_state = $this->get_run_option( $run_id, 'state' );
		if ( self::STATE_COMPLETED === $new_state ) {
			// All jobs done, end import.
			$this->end_run();
		}
	}

	/**
	 * Append new order_id to the run result
	 *
	 * @param mixed  $order_id WordPress object id.
	 * @param string $run_id Run id.
	 */
	public function append_run_result( $order_id, $run_id ) {
		$order_ids = $this->get_run_option( $run_id, 'order_ids', array() );
		if ( ! is_array( $order_ids ) ) {
			$order_ids = array();
		}
		$order_ids[] = $order_id;
		$this->update_run_option( $run_id, 'order_ids', $order_ids );
	}

	/**
	 * Get or instantiate Airtable API client.
	 *
	 * @return Airtable_Api_Client
	 */
	public function get_api_client() {
		if ( null === $this->api_client ) {
			$api_client_class_factory = $this->api_client_class_factory;
			$this->api_client         = $api_client_class_factory( $this->config()->get( 'accessToken' ) );
		}
		return $this->api_client;
	}

	/**
	 * Returns the export fields options.
	 *
	 * @return array Export field's options
	 */
	public function get_export_fields_options() {
		$options = array();

		return $options;
	}

	/**
	 * Trigger Airtable orders table update on order status change.
	 *
	 * @param int    $order_id Order id.
	 * @param string $from Previous order status.
	 * @param string $to New order status.
	 *
	 * @return void
	 */
	public function update_airtable_orders_on_status_change( $order_id, $from, $to ) {
		if ( $from === $to ) {
			return;
		}

		// Add it to queue.
		as_enqueue_async_action(
			'orders_sync_to_airtable_for_woocommerce_exporter_single_order',
			array(
				'order_id' => $order_id,
			),
			'orders_sync_to_airtable_for_woocommerce_export_order_' . $order_id . '_' . $to,
			true,
			0
		);
	}

	/**
	 * Compare hashes to check if Airtable needs update
	 *
	 * @param mixed  $content_id WordPress object id.
	 * @param object $object_to_export Object to export record.
	 *
	 * @return bool
	 */
	protected function needs_update( $content_id, $object_to_export ) {
		if ( defined( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE' ) && ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE ) {
			return true;
		}
		return $this->generate_hash( $object_to_export ) !== $this->get_existing_content_hash( $content_id );
	}

	/**
	 * Generate hash for given object
	 *
	 * @param object $object_to_hash Object.
	 *
	 * @return string
	 */
	protected function generate_hash( $object_to_hash ) {
		// Exclude hash... from hash...
		if ( isset( $object_to_hash->meta_data ) && is_array( $object_to_hash->meta_data ) ) {
			$object_to_hash->meta_data = array_filter(
				$object_to_hash->meta_data,
				function ( $meta_data ) {
					return 'orders_sync_to_airtable_for_woocommerce_hash' !== $meta_data->get_data()['key'];
				}
			);
			$object_to_hash->meta_data = array_values( $object_to_hash->meta_data );
		}
		// Exclude date_modified (it's updated right after we change the hash...
		$object_to_hash->date_modified = null;
		// Exclude changing props from config.
		$config_array = $this->config()->to_array();
		return Helper::generate_hash( $object_to_hash, $config_array );
	}

	/**
	 * Get existing content hash
	 *
	 * @param mixed $content_id WordPress object id.
	 */
	protected function get_existing_content_hash( $content_id ) {
		$order = wc_get_order( $content_id );
		return $order->get_meta( 'orders_sync_to_airtable_for_woocommerce_hash' );
	}
}
