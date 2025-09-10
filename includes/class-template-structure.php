<?php
/**
 * Template structure: allow to load and check template from the resources/templates folder
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Template_Structure.
 */
class Template_Structure {

	/**
	 * Keep some cache locally.
	 *
	 * @var array
	 */
	protected $cache = array();

	/**
	 * Load template from resources/templates folder, throw an Exception if it can't be loaded.
	 *
	 * @param string $version Template version.
	 *
	 * @return array
	 * @throws \Exception Template version %s not found.
	 * @throws \Exception Template version %s could not be loaded.
	 */
	public function get_template( $version ) {
		if ( isset( $this->cache[ $version ] ) ) {
			return $this->cache[ $version ];
		}
		$template_file = ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'resources/templates/' . $version . '.json';
		if ( ! is_file( $template_file ) ) {
			// translators: %s template version.
			throw new \Exception( esc_html( sprintf( __( 'Template version %s not found.', 'orders-sync-to-airtable-for-woocommerce' ), $version ) ), 404 );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template      = file_get_contents( $template_file );
		$template_json = json_decode( $template, true );
		if ( ! $template_json ) {
			// translators: %s template version.
			throw new \Exception( esc_html( sprintf( __( 'Template version %s could not be loaded.', 'orders-sync-to-airtable-for-woocommerce' ), $version ) ), 500 );
		}
		$this->cache[ $version ] = $template_json;
		return $template_json;
	}

	/**
	 * Get table template.
	 *
	 * @param string $version Template version.
	 * @param string $table_key Table key.
	 *
	 * @return mixed
	 * @throws \Exception Table key %s not found.
	 */
	public function get_table( $version, $table_key ) {
		$template = $this->get_template( $version );
		if ( ! isset( $template['table_templates'][ $table_key ] ) ) {
			// translators: %s table key.
			throw new \Exception( esc_html( sprintf( __( 'Table key %s not found.', 'orders-sync-to-airtable-for-woocommerce' ), $table_key ) ), 404 );
		}
		return $template['table_templates'][ $table_key ];
	}

	/**
	 * Get mapping from template.
	 *
	 * @param string $version Template version.
	 * @param array  $airtable_tables Airtable tables.
	 * @param array  $fields_filter Filter by WordPress fields.
	 *
	 * @return array
	 * @throws \Exception @see `get_template`.
	 */
	public function map_template( $version, $airtable_tables, $fields_filter = array() ) {
		// Generic errors.
		$errors = array();
		// Fields mapping error.
		$fields_mapping_errors = array();
		// Remove first table we don't need it.
		array_shift( $airtable_tables );
		$template       = $this->get_template( $version );
		$template_keys  = array_keys( $template['table_templates'] );
		$tables_by_name = array_reduce(
			$template_keys,
			function ( $carry, $table_key ) use ( $template ) {
				$table                   = $template['table_templates'][ $table_key ];
				$table['table_key']      = $table_key;
				$carry[ $table['name'] ] = $table;
				return $carry;
			},
			array()
		);
		$table_mapping  = array();

		foreach ( $airtable_tables as $table ) {
			if ( ! isset( $tables_by_name[ $table->name ] ) ) {
				continue;
			}
			$table_structure      = $tables_by_name[ $table->name ];
			$fields_mapping       = array();
			$table_fields_by_name = array_reduce(
				$table->fields,
				function ( $carry, $field ) {
					$carry[ $field->name ] = $field;
					return $carry;
				},
				array()
			);
			foreach ( $table_structure['fields'] as $field_name => $wp_key ) {
				if ( ! empty( $fields_filter ) && ! in_array( $wp_key, $fields_filter, true ) ) {
					continue;
				}
				$field_name_parts = explode( '::', $field_name );
				$airtable_field   = false;
				if ( isset( $table_fields_by_name[ $field_name_parts[0] ] ) ) {
					// => multipleRecordLinks
					if ( count( $field_name_parts ) > 1 ) {
						$airtable_field = $table_fields_by_name[ $field_name_parts[0] ];
						$sub_field_name = $field_name_parts[1];
						if ( ! is_array( $airtable_field->_linked_table_fields ) ) {
							$airtable_field = false;
						} else {
							$sub_airtable_field = array_values(
								array_filter(
									$airtable_field->_linked_table_fields,
									function ( $field ) use ( $sub_field_name ) {
										return $field->name === $sub_field_name;
									}
								)
							);
							if ( count( $sub_airtable_field ) > 0 ) {
								$airtable_field            = $sub_airtable_field[0];
								$fields_mapping[ $wp_key ] = '__rel__' . $table_fields_by_name[ $field_name_parts[0] ]->id . '|__proxy__|' . $airtable_field->id;
							} else {
								$airtable_field = false;
							}
						}
					} else {
						$airtable_field            = $table_fields_by_name[ $field_name ];
						$fields_mapping[ $wp_key ] = $airtable_field->id;
					}
				}
				if ( false === $airtable_field ) {
					// translators: %1$s field name, %2$s table name.
					$fields_mapping_errors[ $table_structure['table_key'] ]['fields_mapping'][ $wp_key ] = sprintf( __( 'The field "%1$s" could not be found in the table "%2$s".', 'orders-sync-to-airtable-for-woocommerce' ), $field_name, $table->name );
					if ( isset( $errors['template_field_not_found'] ) ) {
						$errors['template_field_not_found'] = __( 'Some fields are missing in the template.', 'orders-sync-to-airtable-for-woocommerce' );
					} else {
						$errors['template_field_not_found'] = __( 'A field is missing in the template.', 'orders-sync-to-airtable-for-woocommerce' );
					}
				}
			}
			$table_mapping[ $table_structure['table_key'] ] = array(
				'table_id'       => $table->id,
				'fields_mapping' => $fields_mapping,
			);
		}

		if ( empty( $fields_filter ) ) {
			$missing_tables = array_diff( $template_keys, array_keys( $table_mapping ) );
			if ( count( $missing_tables ) > 0 ) {
				$missing_table_names = array_map(
					function ( $table_key ) use ( $template ) {
						return $template['table_templates'][ $table_key ]['name'];
					},
					$missing_tables
				);
				// translators: %1$d count missing tables, %2$s missing table name list.
				$errors['missing_table'] = sprintf( __( '%1$d missing table(s): %2$s.', 'orders-sync-to-airtable-for-woocommerce' ), count( $missing_tables ), implode( ', ', $missing_table_names ) );
			}
		}

		return array_merge(
			array(
				'check_success'    => count( $errors ) === 0,
				'errors'           => $errors,
				'table_mapping'    => $table_mapping,
				'template_version' => $version,
			),
			$fields_mapping_errors
		);
	}

	/**
	 * Returns default orders fields mapping.
	 *
	 * @param string $version Template version.
	 *
	 * @return array|false
	 * @throws \Exception @see `get_template`.
	 */
	public function get_default_orders_fields_mapping( $version ) {
		$template                      = $this->get_template( $version );
		$default_fields                = $template['default_fields'];
		$default_fields_required       = $template['default_fields_required'];
		$orders_mapping                = $template['table_templates']['orders']['fields'];
		$default_orders_fields_mapping = array_map(
			function ( $type ) use ( $default_fields, $default_fields_required, $orders_mapping ) {
				$fields_name          = $default_fields[ $type ];
				$fields_name_required = $default_fields_required[ $type ];
				return array_map(
					function ( $field_name ) use ( $orders_mapping, $fields_name_required ) {
						return array(
							'wordpress'         => $orders_mapping[ $field_name ],
							'airtableFieldName' => $field_name,
							'readonly'          => in_array( $field_name, $fields_name_required, true ),
						);
					},
					$fields_name
				);
			},
			array_keys( $default_fields )
		);
		return array_combine( array_keys( $default_fields ), $default_orders_fields_mapping );
	}
}
