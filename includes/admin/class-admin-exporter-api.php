<?php
/**
 * Connection API: trigger update, get progress, cancel sync.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Admin_Exporter_Api
 */
class Admin_Exporter_Api {

	/**
	 * Exporter instance.
	 *
	 * @var Exporter
	 */
	protected $exporter;

	/**
	 * API client class factory.
	 *
	 * @var callable
	 */
	protected $api_client_class_factory;

	/**
	 * Template checker.
	 *
	 * @var Template_Checker
	 */
	protected $template_checker;

	/**
	 * Register API endpoints.
	 *
	 * @param Exporter         $exporter Exporter instance.
	 * @param callable         $api_client_class_factory The API client class factory.
	 * @param Template_Checker $template_checker Template checker.
	 */
	public function __construct( $exporter, $api_client_class_factory, $template_checker ) {
		$this->exporter                 = $exporter;
		$this->api_client_class_factory = $api_client_class_factory;
		$this->template_checker         = $template_checker;

		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_check_access_token', array( $this, 'check_access_token' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_get_airtable_bases', array( $this, 'get_airtable_bases' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_get_airtable_tables', array( $this, 'get_airtable_tables' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_check_base', array( $this, 'check_base' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_disconnect_base', array( $this, 'disconnect_base' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_get_airtable_fields', array( $this, 'get_airtable_fields' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_check_template', array( $this, 'check_template' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_trigger_update', array( $this, 'trigger_update' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_get_progress', array( $this, 'get_progress' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_cancel_sync', array( $this, 'cancel_sync' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_clear_cache', array( $this, 'clear_cache' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_api_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Check access token
	 */
	public function check_access_token() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_check_access_token', 'nonce' );
		Helper::check_ajax_admin_user_access();

		try {
			$access_token = isset( $_POST['accessToken'] ) ? sanitize_text_field( wp_unslash( $_POST['accessToken'] ) ) : '';
			$result       = $this->template_checker->check_bases_access( $access_token );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message(), 400 );
			}

			wp_send_json_success();
		} catch ( \Throwable $e ) {
			wp_send_json_error();
		}
	}

	/**
	 * Get Airtable bases.
	 *
	 * @return void
	 */
	public function get_airtable_bases() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_bases', 'nonce' );
		Helper::check_ajax_admin_user_access();

		try {
			$offset = null;
			$bases  = array();

			$api_client_class_factory = $this->api_client_class_factory;
			$access_token             = isset( $_POST['accessToken'] ) ? sanitize_text_field( wp_unslash( $_POST['accessToken'] ) ) : '';
			$api_client               = $api_client_class_factory( $access_token );

			do {
				$options = array( 'offset' => $offset );
				$result  = $api_client->list_bases( $options );
				$offset  = $result->offset ?? null;
				$bases   = array_merge( $bases, $result->bases );
			} while ( ! is_null( $offset ) );

			wp_send_json_success(
				array(
					'bases' => $bases,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Get Airtable tables.
	 *
	 * @return void
	 */
	public function get_airtable_tables() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_tables', 'nonce' );
		Helper::check_ajax_admin_user_access();
		try {
			$offset = null;
			$bases  = array();

			$api_client_class_factory = $this->api_client_class_factory;
			$access_token             = isset( $_POST['accessToken'] ) ? sanitize_text_field( wp_unslash( $_POST['accessToken'] ) ) : '';
			$base_id                  = isset( $_POST['baseId'] ) ? sanitize_text_field( wp_unslash( $_POST['baseId'] ) ) : '';
			$api_client               = $api_client_class_factory( $access_token );

			$result = $api_client->get_tables( $base_id );
			$tables = $result->tables;

			wp_send_json_success(
				array(
					'tables' => $tables,
				)
			);

			wp_send_json_success(
				array(
					'bases' => $bases,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}


	/**
	 * Check base the user is trying to connect, if it matches the template, save the base id and the table mapping then return `wp_send_json_success` with the base name and URL.
	 *
	 * @return void
	 */
	public function check_base() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_check_base', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$access_token      = isset( $_POST['accessToken'] ) ? sanitize_text_field( wp_unslash( $_POST['accessToken'] ) ) : '';
		$base              = $this->template_checker->check_base_exists( $access_token );
		$connected_base_id = $this->exporter->config()->get( 'baseId' );
		if ( ! $base ) {
			wp_send_json_error( $connected_base_id ? __( 'We’ve not find the base you’ve previously connected.', 'orders-sync-to-airtable-for-woocommerce' ) : __( 'The base can’t be found.', 'orders-sync-to-airtable-for-woocommerce' ), 404 );
		} elseif ( is_wp_error( $base ) ) {
			wp_send_json_error( $base->get_error_message(), $base->get_error_code() );
		}

		if ( $connected_base_id && $connected_base_id !== $base->id ) {
			wp_send_json_error( __( 'A new base is available but it‘s not the one you have previously connected.', 'orders-sync-to-airtable-for-woocommerce' ), 400 );
		}
		try {
			$check_template_result = $this->template_checker->check_template( $access_token, $base->id );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 400 );
		}

		if ( ! $check_template_result['check_success'] ) {
			wp_send_json_error( array_values( $check_template_result['errors'] ), 400 );
		}

		wp_send_json_success(
			array(
				'message'         => $connected_base_id ? __( 'Your <strong>Airtable base</strong> was created <strong>successfully!</strong>', 'orders-sync-to-airtable-for-woocommerce' ) : __( 'Your new Airtable base has been successfully created!', 'orders-sync-to-airtable-for-woocommerce' ),
				'baseName'        => $base->name,
				'baseURL'         => 'https://airtable.com/' . $base->id,
				'templateVersion' => $check_template_result['template_version'],

				'baseId'          => $base->id,
				'tableId'         => $check_template_result['table_mapping']['orders']['table_id'],
				'mappingInit'     => $this->template_checker->get_default_mapping( $access_token, $base->id ),
				'mappingAuto'     => $check_template_result['table_mapping']['orders']['fields_mapping'],
			)
		);
	}


	/**
	 * Disconnect base.
	 *
	 * @return void
	 */
	public function disconnect_base() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_disconnect_base', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$this->exporter->config()
						->delete( 'baseId' )
						->delete( 'tableId' )
						->delete( 'tableId' )
						->save();

		$this->clear_template_version_transient();

		wp_send_json_success();
	}

	/**
	 * Get Airtable fields based on posted data.
	 *
	 * @return void
	 */
	public function get_airtable_fields() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_fields', 'nonce' );
		Helper::check_ajax_admin_user_access();
		$access_token             = isset( $_POST['accessToken'] ) ? sanitize_text_field( wp_unslash( $_POST['accessToken'] ) ) : '';
		$base_id                  = isset( $_POST['baseId'] ) ? sanitize_text_field( wp_unslash( $_POST['baseId'] ) ) : '';
		$table_id                 = isset( $_POST['tableId'] ) ? sanitize_text_field( wp_unslash( $_POST['tableId'] ) ) : '';
		$api_client_class_factory = $this->api_client_class_factory;
		$api_client               = $api_client_class_factory( $access_token );
		$fields                   = Helper::clean_up_airtable_fields(
			Helper::get_airtable_table_fields(
				$api_client,
				$base_id,
				$table_id
			)
		);
		wp_send_json_success(
			array(
				'fields' => $fields,
			)
		);
	}

	/**
	 * Check template.
	 *
	 * @return void
	 */
	public function check_template() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_check_template', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$access_token = isset( $_POST['accessToken'] ) ? sanitize_text_field( wp_unslash( $_POST['accessToken'] ) ) : '';
		$base_id      = isset( $_POST['baseId'] ) ? sanitize_text_field( wp_unslash( $_POST['baseId'] ) ) : '';
		$fields       = isset( $_POST['fields'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['fields'] ) ) : array();

		try {
			$check_template_result = $this->template_checker->check_template( $access_token, $base_id, $fields );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 400 );
		}

		if ( ! $check_template_result['check_success'] ) {
			wp_send_json_error( $check_template_result, 400 );
		}

		wp_send_json_success( $check_template_result['table_mapping'] );
	}

	/**
	 * Manual sync AJAX function
	 *
	 * @throws \Exception Error from exporter.
	 */
	public function trigger_update() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_trigger_update', 'nonce' );
		Helper::check_ajax_admin_user_access();

		try {
			// Get WooCommerce orders and add theme to queue.
			$result = $this->exporter->run();
			if ( is_wp_error( $result ) ) {
				throw new \Exception( $result->get_error_message() );
			}

			// Unlock Action Scheduler, force queue to start now.
			delete_option( 'action_scheduler_lock_async-request-runner' );

			wp_send_json_success();
		} catch ( \Exception $e ) {
			wp_send_json_error( $this->get_stats( $e->getMessage() ) );
		}
	}

	/**
	 * Get sync progress
	 */
	public function get_progress() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_get_progress', 'nonce' );
		Helper::check_ajax_admin_user_access();

		try {
			if ( $this->exporter->get_run_id() ) {
				$this->exporter->check_sync_state();
				$as_actions_args          = array(
					'run_id' => $this->exporter->get_run_id(),
				);
				$pending_running_statuses = array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING );
				$actions_remaining        = Helper::get_as_actions( 'orders_sync_to_airtable_for_woocommerce_exporter_process_records', $pending_running_statuses, $as_actions_args );

				$all_statuses = array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_COMPLETE, \ActionScheduler_Store::STATUS_CANCELED, \ActionScheduler_Store::STATUS_FAILED );
				$all_actions  = Helper::get_as_actions( 'orders_sync_to_airtable_for_woocommerce_exporter_process_records', $all_statuses, $as_actions_args );
			}

			if ( ! $this->exporter->get_run_id() ) {
				if ( $this->exporter->state()->get( 'status' ) === 'error' ) {
					wp_send_json_error(
						array_merge(
							$this->get_stats(),
							array(
								'ratio'             => 1,
								'remaining_minutes' => 0,
							)
						)
					);
				} else {
					wp_send_json_success(
						array_merge(
							$this->get_stats(),
							array(
								'ratio'             => 1,
								'remaining_minutes' => 0,
							)
						)
					);
				}
			} else {
				$finished_actions = count( $all_actions ) - count( $actions_remaining );
				$progress_ratio   = count( $all_actions ) > 0 ? $finished_actions / count( $all_actions ) : 1;

				$extra = array(
					'ratio'             => min( 0.99, $progress_ratio ), // it can't be completed here because we have a run id.
					'remaining_minutes' => $progress_ratio < 1 ? count( $actions_remaining ) : 0, // let's assume 1 min per chunk.
				);

				$start_date = $this->exporter->get_run_option( $this->exporter->get_run_id(), 'start-date' );

				$count_other_pending_actions = 0;
				if ( $start_date ) {
					$time_spent          = human_time_diff( strtotime( $start_date ), time() );
					$extra['time_spent'] = $time_spent;
					// Override ratio based on time spent.
					if ( count( $all_actions ) > 0 ) {
						$total_minutes  = count( $all_actions );
						$extra['ratio'] = ( time() - strtotime( $start_date ) ) / 60 / $total_minutes;
					}

					// We try to check if they are long pending actions that was planned before we start the sync.
					$all_pending_actions_before_sync_started = Helper::get_as_pending_actions(
						array(
							'date'         => new \DateTime( $start_date ),
							'date_compare' => '<=',
						)
					);
					$other_pending_actions                   = array_diff( $all_pending_actions_before_sync_started, $all_actions );
					$other_pending_actions                   = array_values( $other_pending_actions );
					$count_other_pending_actions             = count( $other_pending_actions );
				}
				$extra['as_other_pending_actions'] = $count_other_pending_actions;
				wp_send_json_success( array_merge( $this->get_stats(), $extra ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( $this->get_stats( $e->getMessage() ) );
		}
	}

	/**
	 * Cancel sync
	 */
	public function cancel_sync() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_cancel_sync', 'nonce' );
		Helper::check_ajax_admin_user_access();

		try {
			$this->exporter->end_run( 'cancel' );
			wp_send_json_success( $this->get_stats() );
		} catch ( \Exception $e ) {
			wp_send_json_error( $this->get_stats( $e->getMessage() ) );
		}
	}
	/**
	 * Get exporter statistics
	 *
	 * @param null|string $forced_error Error to be displayed.
	 *
	 * @return array
	 */
	protected function get_stats( $forced_error = null ) {
		$status       = $forced_error ? 'error' : ( $this->exporter->state()->get( 'status' ) );
		$last_updated = $this->exporter->get_last_updated();
		if ( ! is_string( $last_updated ) ) {
			$last_updated = '';
		}
		$errors = array();
		if ( $forced_error ) {
			$errors [] = $forced_error;
		} else {
			$errors = $this->exporter->get_errors();
		}
		// Make sure we have only error as string here.
		$errors = Helper::errors_as_string( $errors );

		$time_spent      = '';
		$count_processed = -1;
		$start_date      = null;
		if ( 'success' === $status ) {
			$start_date      = $this->exporter->state()->get( 'last_start_date' );
			$count_processed = $this->exporter->state()->get( 'count_processed' );
			if ( ! is_numeric( $count_processed ) ) {
				$count_processed = -1;
			}
		}
		if ( $start_date && $last_updated ) {
			$time_spent = human_time_diff( strtotime( $start_date ), strtotime( $last_updated ) );
		}

		return array(
			'status'                 => $status,
			'errors'                 => $errors,
			'last_updated'           => $last_updated,
			'last_updated_formatted' => ! empty( $last_updated ) ? Helper::get_formatted_date_time( $last_updated ) : '',
			'time_spent'             => $time_spent,
			'count_processed'        => (int) $count_processed,
			'logs_url'               => $this->exporter->get_latest_log_file_url(),
		);
	}

	/**
	 * Clear Airtable cache.
	 *
	 * @return void
	 */
	public function clear_cache() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_clear_cache', 'nonce' );
		Helper::check_ajax_admin_user_access();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transient_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT options.option_name FROM {$wpdb->options} options
			WHERE options.option_name LIKE %s",
				$wpdb->esc_like( '_transient_orders_sync_to_airtable_for_woocommerce_tables_' ) . '%'
			)
		);
		foreach ( $transient_names as $transient_name ) {
			delete_transient( str_replace( '_transient_', '', $transient_name ) );
		}

		$this->clear_template_version_transient();

		do_action( 'orders_sync_to_airtable_for_woocommerce/cache_cleared' );

		wp_send_json_success();
	}

	/**
	 * Clear template version transient.
	 *
	 * @return void
	 */
	public function clear_template_version_transient() {
		delete_transient( 'orders_sync_to_airtable_for_woocommerce_current_template_version' );
	}

	/**
	 * Save settings
	 */
	public function save_settings() {
		// Nonce check.
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_api_save_settings', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$settings = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '';
		$settings = json_decode( $settings, true );
		if ( ! $settings ) {
			wp_send_json_error( __( 'An error has occured, please double check your settings', 'orders-sync-to-airtable-for-woocommerce' ) );
		}

		$state = $settings['connection_status'];
		unset( $settings['connection_status'] );
		$old_value = get_option( 'orders_sync_to_airtable_for_woocommerce_exporter_state' );
		if ( maybe_serialize( $state ) !== maybe_serialize( $old_value ) ) {
			if ( ! update_option( 'orders_sync_to_airtable_for_woocommerce_exporter_state', $state ) ) {
				wp_send_json_error( __( 'An error has occurred, please double check your settings', 'orders-sync-to-airtable-for-woocommerce' ) );
			}
		}
		$old_value = get_option( 'orders_sync_to_airtable_for_woocommerce_exporter_settings' );
		if ( maybe_serialize( $settings ) === maybe_serialize( $old_value ) ) {
			wp_send_json_success( __( 'Settings saved', 'orders-sync-to-airtable-for-woocommerce' ) );
		}

		if ( ! update_option( 'orders_sync_to_airtable_for_woocommerce_exporter_settings', $settings ) ) {
			wp_send_json_error( __( 'An error has occurred, please double check your settings', 'orders-sync-to-airtable-for-woocommerce' ) );
		}
		wp_send_json_success( __( 'Settings saved', 'orders-sync-to-airtable-for-woocommerce' ) );
	}
}
