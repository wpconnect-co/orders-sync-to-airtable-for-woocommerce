<?php
/**
 * Plugin Bootstrap class.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Bootstrap
 */
class Bootstrap {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 100 );
	}

	/**
	 * Init plugin
	 */
	public function init() {
		$this->init_services();
		$this->setup();

		// Admin.
		if ( is_admin() ) {
			new Admin( new Options() );
		}

		/**
		 * Init Sources with Formatters.
		 */
		do_action( 'orders_sync_to_airtable_for_woocommerce/register_source' );

		do_action( 'orders_sync_to_airtable_for_woocommerce/init' );
	}

	/**
	 * Init services.
	 * Services are objects that can be shared across the plugin.
	 * They are stored in a container and can be retrieved using the `get` method.
	 *
	 * @return void
	 * @throws \Exception "{$dependency} not found".
	 */
	public function init_services() {
		$services = Services::get_instance();

		$services->set( 'logger', new Logger() );

		$services->set(
			'airtable_api_client_class_factory',
			static function ( $token ) {
				return new Airtable_Api_Client( $token );
			}
		);

		$services->set(
			'exporter',
			new Exporter(
				new Exporter_Settings(),
				new Exporter_State(),
				$services->get( 'airtable_api_client_class_factory' ),
				$services->get( 'logger' )
			)
		);

		$services->set( 'action_consumer', new Action_Consumer() );

		$services->set( 'template_structure', new Template_Structure() );
		$services->set(
			'template_checker',
			new Template_Checker(
				$services->get( 'airtable_api_client_class_factory' ),
				$services->get( 'template_structure' )
			)
		);

		$services->set( 'formatter', new Formatter() );
		$services->get( 'formatter' )
				->register_strategy( new Format_To_String_Strategy() )
				->register_strategy( new Format_To_Datetime_Strategy() )
				->register_strategy( new Format_To_Number_Strategy() );

		$services->set( 'order_module', new Order_Module() );

		do_action( 'orders_sync_to_airtable_for_woocommerce/init_services', $services );
	}

	/**
	 * Plugin Setup
	 */
	protected function setup() {}
}
