<?php
/**
 * Abstract Source.
 * Base class to define export source.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Abstract_Source
 */
abstract class Abstract_Source {
	/**
	 * Source slug
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected $module;

	/**
	 * Formatter.
	 *
	 * @var Formatter
	 */
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param Formatter $formatter Formatter.
	 */
	public function __construct( $formatter ) {
		$this->formatter = $formatter;
		add_filter( 'orders_sync_to_airtable_for_woocommerce/get_wp_fields', array( $this, 'add_fields' ), 10, 2 );
	}

	/**
	 * Assign fields to mapping group
	 *
	 * @return array
	 */
	abstract protected function get_group();

	/**
	 * Get mapping fields
	 *
	 * @return array
	 */
	abstract protected function get_mapping_fields();

	/**
	 * Add fields to mapping options
	 *
	 * @param array  $fields Fields.
	 * @param string $module Module's slug.
	 *
	 * @return array|mixed
	 */
	public function add_fields( $fields, $module ) {
		if ( $module === $this->module ) {
			$group          = $this->get_group();
			$new_fields     = $this->get_mapping_fields();
			$options        = array();
			$default_option = array(
				'enabled'        => true,
				'allow_multiple' => false,
			);
			foreach ( $new_fields as $field ) {
				$options[] = array_merge( $default_option, $field, array( 'value' => $this->slug . '::' . $field['value'] ) );
			}

			$fields = array_merge_recursive(
				$fields,
				array(
					$group['slug'] => array(
						'options' => $options,
					),
				)
			);
			if ( ! empty( $group['label'] ) ) {
				$fields[ $group['slug'] ]['label'] = $group['label'];
			}
		}
		return $fields;
	}

	/**
	 * Get mapped fields for our source specifically
	 *
	 * @param Exporter $exporter Exporter.
	 *
	 * @return array
	 */
	protected function get_source_mapping( $exporter ) {
		$mapping        = ! empty( $exporter->config()->get( 'mapping' ) ) ? $exporter->config()->get( 'mapping' ) : array();
		$source_mapping = array();
		$module         = $exporter->config()->get( 'module' );

		if ( $module !== $this->module ) {
			return $source_mapping;
		}

		foreach ( $mapping as $mapping_pair ) {
			$wp_field_parts = explode( '::', $mapping_pair['wordpress'] );
			$wp_field_group = $wp_field_parts[0] ? $wp_field_parts[0] : '';
			$wp_field       = $wp_field_parts[1] ? $wp_field_parts[1] : '';

			if ( $wp_field_group === $this->slug ) {
				$source_mapping[] = $mapping_pair;
			}
		}
		return $source_mapping;
	}

	/**
	 * Format data: for each mapped field filtered by $filter_fields run formatting strategies defined in $strategies_conf or use $default_format_strategies if there is no configuration for the field.
	 *
	 * @param array          $data Data for format (associative array wp field key => value).
	 * @param Export_Context $context Export context.
	 * @param array          $fields Fields.
	 * @param array          $strategies_conf {
	 *               A list of custom strategies.
	 *      @type callable $pre_format Optional: A function to pre-format the value before applying formatting strategies on it. Accepts 1 or 2 parameters: $value & $context.
	 *      @type string[] $strategies A list of strategies’ slugs.
	 *      @type callable $pre_format Optional: A function to post-format the value after the formatting strategies has been applied on it. One parameter: $value.
	 * }
	 * @param array          $filter_fields List of WordPress field key to apply format on. Empty array = all fields.
	 * @param array          $default_format_strategies List of default format strategies to apply on values without custom strategies.
	 *
	 * @return mixed
	 * @throws \ReflectionException Exception regarding the reflection.
	 */
	protected function format_data( $data, $context, $fields, $strategies_conf, $filter_fields = array(), $default_format_strategies = array( 'format_to_string' ) ) {
		$exporter      = $context->get_exporter();
		$mapped_fields = $this->get_source_mapping( $exporter );

		foreach ( $mapped_fields as $mapped_field ) {
			$wordpress_field_key = $mapped_field['wordpress'];
			if ( ! empty( $mapped_field['options']['name'] ) ) {
				$wordpress_field_key = $mapped_field['options']['name'];
			}
			if ( ! empty( $filter_fields ) && ! in_array( $wordpress_field_key, $filter_fields, true ) ) {
				continue;
			}

			$destination_field = $exporter->get_field_by_id( $mapped_field['airtable'] );
			$field_context     = Export_Field_Context::from_export_context( $context );
			$field_context->set_destination_field( ! empty( $destination_field ) ? $destination_field : null );

			$value                   = $fields[ $wordpress_field_key ];
			$field_format_strategies = array( 'strategies' => $default_format_strategies );

			if ( isset( $strategies_conf[ $wordpress_field_key ] ) ) {
				$field_format_strategies = $strategies_conf[ $wordpress_field_key ];
			}
			if ( isset( $field_format_strategies['pre_format'] ) ) {
				$rf = new \ReflectionFunction( $field_format_strategies['pre_format'] );
				if ( $rf->getNumberOfRequiredParameters() === 1 ) {
					$value = $field_format_strategies['pre_format']( $value );
				} elseif ( $rf->getNumberOfRequiredParameters() === 2 ) {
					$value = $field_format_strategies['pre_format']( $value, $field_context );
				}
			}

			foreach ( $field_format_strategies['strategies'] as $field_format_strategy ) {
				$value = $this->formatter->format( $field_format_strategy, $value, $field_context );
			}
			if ( isset( $field_format_strategies['post_format'] ) ) {
				$value = $field_format_strategies['post_format']( $value );
			}

			$data[ $wordpress_field_key ] = $value;
		}
		return $data;
	}
}
