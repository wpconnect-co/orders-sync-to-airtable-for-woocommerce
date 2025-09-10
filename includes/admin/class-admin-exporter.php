<?php
/**
 * Manages admin exporter page.
 *
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Admin Exporter
 */
class Admin_Exporter {

	/**
	 * Exporter instance.
	 *
	 * @var Exporter
	 */
	protected $exporter;

	/**
	 * Template checker.
	 *
	 * @var Template_Checker
	 */
	protected $template_checker;

	/**
	 * Constructor
	 *
	 * @param Exporter         $exporter Exporter.
	 * @param Template_Checker $template_checker Template checker.
	 */
	public function __construct( $exporter, $template_checker ) {
		$this->exporter         = $exporter;
		$this->template_checker = $template_checker;
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
	}


	/**
	 * Register admin styles and scripts.
	 *
	 * @return void
	 */
	public function register_styles_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_orders-sync-to-airtable-for-woocommerce' !== $screen->id ) {
			return;
		}

		wp_enqueue_style( 'orders-sync-to-airtable-for-woocommerce-air-wp-sync-ui', plugins_url( 'assets/js/air-wp-sync-ui/library/index.css', ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_FILE ), false, ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_VERSION );
		wp_add_inline_style(
			'orders-sync-to-airtable-for-woocommerce-air-wp-sync-ui',
			'
/* Override green color by purple */
body.airwpsync-ui--woocommerce {
    --airwpsync--color--green-50: #F2EEF7;
    --airwpsync--color--green-100: #D7CAE7;
    --airwpsync--color--green-200: #C4B0DC;
    --airwpsync--color--green-300: #A98CCC;
    --airwpsync--color--green-400: #9976C2;
    --airwpsync--color--green-500: #7F54B3;
    --airwpsync--color--green-600: #744CA3;
    --airwpsync--color--green-700: #5A3C7F;
    --airwpsync--color--green-800: #462E62;
    --airwpsync--color--green-900: #35234B;
}
'
		);
		// .airwpsync-ui class is required by 'air-wp-sync-ui' style.
		add_filter(
			'admin_body_class',
			function ( $body_class ) {
				$body_class .= ' airwpsync-ui airwpsync-ui--woocommerce';
				return $body_class;
			}
		);
		wp_register_script( 'orders-sync-to-airtable-for-woocommerce-modules', plugins_url( 'assets/js/orders-sync-to-airtable-for-woocommerce-ui/main.js', ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_FILE ), array( 'wp-i18n' ), ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_VERSION, array( 'in_footer' => true ) );
		wp_enqueue_script( 'orders-sync-to-airtable-for-woocommerce-wizard', plugins_url( 'assets/js/wizard.js', ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_FILE ), array( 'orders-sync-to-airtable-for-woocommerce-modules', 'jquery', 'wp-api' ), ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_VERSION, array( 'in_footer' => true ) );
	}


	/**
	 * Display the connection wizard.
	 *
	 * @return void
	 */
	public function view() {

		$view = require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'views/wizard.php';
		$view();

		$exporter_config = $this->exporter->config();
		$exporter_state  = $this->exporter->state();

		$module_config                 = array();
		$module_config['buildPathUri'] = ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_URL . 'assets/js/orders-sync-to-airtable-for-woocommerce-ui';
		$module_config['formOptions']  = array(
			'scheduledSyncTypes' => array(
				array(
					'value'   => 'manual',
					'label'   => __( 'Manual', 'orders-sync-to-airtable-for-woocommerce' ),
					'enabled' => true,
				),
				array(
					'value'   => 'order_state_change',
					'label'   => __( 'Automatic *', 'orders-sync-to-airtable-for-woocommerce' ),
					'enabled' => true,
				),
			),
		);

		$module_config['airtableFields'] = array();

		$module_config['defaultMappingOptions'] = Helper::convert_supported_sources_to_destinations(
			Services::get_instance()->get( 'order_module' )->get_mapping_options()
		);

		$get_airtable_bases_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_bases',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_bases' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$get_airtable_tables_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_tables',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_tables' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$check_access_token_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_check_access_token',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_check_access_token' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$check_base_url      = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_check_base',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_check_base' ),
			),
			admin_url( 'admin-ajax.php' )
		);
		$disconnect_base_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_disconnect_base',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_disconnect_base' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$trigger_update_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_trigger_update',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_trigger_update' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$check_template_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_check_template',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_check_template' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$get_airtable_fields_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_fields',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_get_airtable_fields' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$check_sync_progress_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_get_progress',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_get_progress' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$cancel_sync_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_cancel_sync',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_cancel_sync' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$save_settings_url = add_query_arg(
			array(
				'action'   => 'orders_sync_to_airtable_for_woocommerce_api_save_settings',
				'_wpnonce' => wp_create_nonce( 'orders_sync_to_airtable_for_woocommerce_api_save_settings' ),
			),
			admin_url( 'admin-ajax.php' )
		);

		$module_config['fetchFn'] = array(
			'get-airtable-bases'        => array( 'url' => $get_airtable_bases_url ),
			'get-airtable-tables'       => array( 'url' => $get_airtable_tables_url ),

			'check-access-token'        => array( 'url' => $check_access_token_url ),
			'check-base-template'       => array( 'url' => $check_base_url ),
			'disconnect-base'           => array( 'url' => $disconnect_base_url ),
			'check-template'            => array( 'url' => $check_template_url ),
			'get-airtable-table-fields' => array( 'url' => $get_airtable_fields_url ),
			'trigger-update-url'        => array( 'url' => $trigger_update_url ),
			'check-sync-progress'       => array( 'url' => $check_sync_progress_url ),
			'cancel-sync'               => array( 'url' => $cancel_sync_url ),
			'save-settings'             => array( 'url' => $save_settings_url ),
		);

		$module_config['templateProps'] = array(
			'air_woo_sync_template' => array(
				'templateURL'           => esc_url( ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_TEMPLATE_URL ),
				'latestTemplateVersion' => ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_LATEST_TEMPLATE_VERSION,
			),
		);

		$config           = $exporter_config->to_array();
		$state            = $exporter_state->to_array();
		$config['module'] = 'order';
		$status           = $exporter_state->get( 'status' ) ? $exporter_state->get( 'status' ) : 'idle';
		$is_running       = ( (bool) $this->exporter->get_run_id() );
		if ( $is_running ) {
			$status = 'loading';
		} elseif ( 'success' === $status ) {
			$status = 'idle';
		}
		$config['connection_status']           = $state;
		$config['connection_status']['status'] = $status;
		$config['connection_status']['synced'] = (bool) ( $this->exporter->get_last_updated() );
		if ( isset( $config['connection_status']['errors'] ) ) {
			$config['connection_status']['errors'] = Helper::errors_as_string( $config['connection_status']['errors'] );
		}

		$logs_url = $this->exporter->get_latest_log_file_url();
		if ( ! empty( $logs_url ) ) {
			$config['connection_status']['logs_url'] = $logs_url;
		}
		$last_updated = $this->exporter->get_last_updated();
		if ( ! empty( $last_updated ) ) {
			$config['connection_status']['last_updated_formatted'] = Helper::get_formatted_date_time( $last_updated );
		}

		if ( isset( $config['baseId'] ) ) {
			$base_info = null;
			try {
				if ( isset( $config['template'] ) && 'air_woo_sync_template' === $config['template'] ) {
					$config['mappingInit'] = $this->template_checker->get_default_mapping( $exporter_config->get( 'accessToken' ), $exporter_config->get( 'baseId' ) );
					$check_template_result = $this->template_checker->check_template( $exporter_config->get( 'accessToken' ), $exporter_config->get( 'baseId' ) );
					$config['mappingAuto'] = $check_template_result['table_mapping']['orders']['fields_mapping'];

					$base = $this->template_checker->check_base_exists( $exporter_config->get( 'accessToken' ), 'id', $exporter_config->get( 'baseId' ) );

					if ( ! $base || is_wp_error( $base ) ) {
						$base_info = array(
							'status' => 'base-not-found',
						);
					} else {
						$template_version = get_transient( 'orders_sync_to_airtable_for_woocommerce_current_template_version' );
						if ( ! $template_version ) {
							list($tables, $template_version) = $this->template_checker->get_template_base( $exporter_config->get( 'accessToken' ), $exporter_config->get( 'baseId' ) );
							set_transient( 'orders_sync_to_airtable_for_woocommerce_current_template_version', $template_version, DAY_IN_SECONDS );
						}
						$base_info = array(
							'baseName'        => $base->name,
							'baseURL'         => esc_url( 'https://airtable.com/' . $base->id ),
							'templateVersion' => $template_version,
						);
					}
				} elseif ( isset( $config['template'] ) && 'from_scratch' === $config['template'] ) {
					$bases_results = $this->exporter->get_api_client()->list_bases();
					$base          = false;
					foreach ( $bases_results->bases as $base_result ) {
						if ( $base_result->id === $config['baseId'] ) {
							$base = $base_result;
						}
					}
					if ( ! $base ) {
						$base_info = array(
							'status' => 'base-not-found',
						);
					} else {
						$base_info = array(
							'baseName' => $base->name,
							'baseURL'  => esc_url( 'https://airtable.com/' . $base->id ),
						);
					}
				}
			} catch ( \Throwable $e ) {
				$config['connection_status']['currentStepKey'] = 'authorization';
				$base_info                                     = array(
					'status' => 'base-not-found',
				);
			}
			$config['connection_status']['baseInfo'] = $base_info;
		}

		$module_config_json = wp_json_encode( $module_config );

		$config_json = wp_json_encode( $config );

		wp_add_inline_script(
			'orders-sync-to-airtable-for-woocommerce-wizard',
			"
window.syncWoocommerceOrdersToAirtableWizardModuleConfig = $module_config_json;
window.syncWoocommerceOrdersToAirtableWizardConfig = $config_json;
",
			'before'
		);
	}
}
