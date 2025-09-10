<?php
/**
 * Template checker: check Airtable access + base template validity.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Template_Checker.
 */
class Template_Checker {

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
	 * Template structure.
	 *
	 * @var Template_Structure
	 */
	protected $template_stucture;

	/**
	 * Constructor.
	 *
	 * @param callable           $api_client_class_factory The API client class factory.
	 * @param Template_Structure $template_stucture Template structure.
	 */
	public function __construct( $api_client_class_factory, $template_stucture ) {
		$this->api_client_class_factory = $api_client_class_factory;
		$this->template_stucture        = $template_stucture;
	}

	/**
	 * Get or instantiate Airtable API client.
	 *
	 * @param string $access_token Access token.
	 *
	 * @return Airtable_Api_Client
	 */
	public function get_api_client( $access_token ) {
		if ( null === $this->api_client ) {
			$api_client_class_factory = $this->api_client_class_factory;
			$this->api_client         = $api_client_class_factory( $access_token );
		}
		return $this->api_client;
	}

	/**
	 * Check if we can access to the bases.
	 *
	 * @param string $access_token Access token.
	 *
	 * @return true|\WP_Error
	 */
	public function check_bases_access( $access_token ) {
		try {
			$this->get_api_client( $access_token )->list_bases();
		} catch ( \Exception $exception ) {
			return new \WP_Error( 'invalid_access_token', __( 'Invalid Airtable access token', 'orders-sync-to-airtable-for-woocommerce' ) );
		}
		return true;
	}

	/**
	 * Check if base exists, if it exists return the base object.
	 *
	 * @param string $access_token Access token.
	 * @param string $check_field Field to check against.
	 * @param string $field_value Field value.
	 *
	 * @return false|\WP_Error|\stdClass Airtable Base object
	 */
	public function check_base_exists( $access_token, $check_field = 'name', $field_value = 'Orders Sync to Airtable for WooCommerce Template' ) {
		try {
			$response = $this->get_api_client( $access_token )->list_bases();
			foreach ( $response->bases as $base ) {
				if ( $field_value === $base->{$check_field} ) {
					return $base;
				}
			}
		} catch ( \Throwable $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Template_Checker::check_base_exists error: ' . $exception->getMessage() . ' / ' . $exception->getCode() );
			}
			if ( $exception->getCode() === 401 ) {
				return new \WP_Error( $exception->getCode(), $exception->getMessage() );
			}
		}

		return false;
	}

	/**
	 * Get template base information and do some sanity checks.
	 *
	 * @param string $access_token Access token.
	 * @param string $base_id Base id.
	 *
	 * @return array
	 * @throws \Exception There is no table in this base.
	 * @throws \Exception This base does not sound to be an Air WP Sync for WooCommerce template.
	 */
	public function get_template_base( $access_token, $base_id ) {
		$template_base = wp_cache_get( $base_id, 'orders_sync_to_airtable_for_woocommerce_get_template_base' );
		if ( $template_base ) {
			return $template_base;
		}
		$api_client = $this->get_api_client( $access_token );
		$tables     = $api_client->get_tables( $base_id, false );
		if ( count( $tables->tables ) === 0 ) {
			throw new \Exception( esc_html( __( 'There is no table in this base.', 'orders-sync-to-airtable-for-woocommerce' ) ), 400 );
		}
		$welcome_table_records = $api_client->list_records( $base_id, $tables->tables[0]->id );
		$template_version      = false;
		foreach ( $welcome_table_records->records as $record ) {
			if ( isset( $record->fields->Version ) ) {
				$template_version = trim( $record->fields->Version );
				break;
			}
		}
		if ( false === $template_version ) {
			throw new \Exception( esc_html( __( 'This base does not sound to be a Orders Sync to Airtable for WooCommerce template.', 'orders-sync-to-airtable-for-woocommerce' ) ), 400 );
		}

		$table_index = array();
		foreach ( $tables->tables as $table ) {
			$table_index[ $table->id ] = clone $table;
		}
		foreach ( $tables->tables as $table ) {
			foreach ( $table->fields as $field ) {
				if ( 'multipleRecordLinks' === $field->type ) {
					$field->_linked_table_name   = $table_index[ $field->options->linkedTableId ]->name;
					$field->_linked_table_fields = $table_index[ $field->options->linkedTableId ]->fields;
				}
			}
		}
		$template_base = array( $tables, $template_version );
		// Expire quickly so only calls near the request will be in cache.
		wp_cache_set( $base_id, $template_base, 'orders_sync_to_airtable_for_woocommerce_get_template_base', 1 );
		return $template_base;
	}

	/**
	 * Check template against all WordPress fields or only ones in $fields_filter then return the mapping.
	 *
	 * @param string $access_token Access token.
	 * @param string $base_id Base id.
	 * @param array  $fields_filter List of WordPress fields we want to map.
	 *
	 * @return array
	 * @throws \Exception @see Template_Structure::map_template.
	 */
	public function check_template( $access_token, $base_id, $fields_filter = array() ) {
		list($tables, $template_version) = $this->get_template_base( $access_token, $base_id );

		$table_mapping = $this->template_stucture->map_template( $template_version, $tables->tables, $fields_filter );

		return $table_mapping;
	}

	/**
	 * Get default product mapping.
	 *
	 * @param string $access_token Access token.
	 * @param string $base_id Base id.
	 *
	 * @return array|array[]
	 */
	public function get_default_mapping( $access_token, $base_id ) {
		try {
			list($tables, $template_version) = $this->get_template_base( $access_token, $base_id );
			$default_fields_mapping          = $this->template_stucture->get_default_orders_fields_mapping( $template_version );
			$default_fields_mapping          = array_map(
				function ( $fields_mapping ) {
					return array_values(
						array_reduce(
							$fields_mapping,
							function ( $carry, $field_mapping ) {
								list($group_key, $field_name) = explode( '::', $field_mapping['wordpress'] );
								$group_name                   = __( 'Other', 'orders-sync-to-airtable-for-woocommerce' );
								switch ( $group_key ) {
									case 'order':
										$group_name = __( 'Order', 'orders-sync-to-airtable-for-woocommerce' );
										break;
									case 'order_billing':
										$group_name = __( 'Billing', 'orders-sync-to-airtable-for-woocommerce' );
										break;
									case 'order_shipping':
										$group_name = __( 'Shipping', 'orders-sync-to-airtable-for-woocommerce' );
										break;
									case 'order_customer':
										$group_name = __( 'Customer', 'orders-sync-to-airtable-for-woocommerce' );
										break;
								}
								if ( ! isset( $carry[ $group_name ] ) ) {
									$carry[ $group_name ] = array(
										'groupName' => $group_name,
										'mappings'  => array(),
									);
								}
								$carry[ $group_name ]['mappings'][] = $field_mapping;
								return $carry;
							},
							array()
						)
					);
				},
				$default_fields_mapping
			);
			return $default_fields_mapping;
		} catch ( \Exception $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Template_Checker::get_default_mapping error: ' . $exception->getMessage() . ' / ' . $exception->getCode() );
			}
			return array();
		}
	}
}
