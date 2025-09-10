<?php
/**
 * Airtable export field context.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

use Exception;

/**
 * Export_Field_Context class
 */
class Export_Field_Context extends Export_Context {

	/**
	 * Airtable destination field.
	 *
	 * @var \stdClass|null
	 */
	protected $destination_field = null;

	/**
	 * Airtable destination field type.
	 *
	 * @var string|null
	 */
	protected $destination_type = null;

	/**
	 * Field args.
	 *
	 * @var array
	 */
	protected $args = array();


	/**
	 * Get source field.
	 *
	 * @return \stdClass|null
	 */
	public function get_destination_field() {
		return $this->destination_field;
	}

	/**
	 * Set destination field.
	 *
	 * @param \stdClass|null $destination_field Destination field.
	 */
	public function set_destination_field( $destination_field ) {
		$this->destination_field = $destination_field;
		return $this;
	}

	/**
	 * Returns true if the context has a destination field set.
	 *
	 * @return bool
	 */
	public function has_destination_field() {
		return null !== $this->destination_field;
	}

	/**
	 * Returns destination type.
	 *
	 * @return string|mixed
	 */
	public function get_destination_type() {
		// Use result type for computed fields (formula, lookup).
		return $this->destination_field && ! empty( $this->destination_field->options->result->type ) ? $this->destination_field->options->result->type : $this->destination_field->type;
	}

	/**
	 * Returns source type.
	 *
	 * @return array
	 */
	public function get_args(): array {
		return $this->args;
	}

	/**
	 * Set field import args.
	 *
	 * @param array $args Args.
	 *
	 * @return void
	 */
	public function set_args( array $args ) {
		$this->args = $args;
	}

	/**
	 * Get field import arg value.
	 *
	 * @param string $arg_name Field import arg name.
	 *
	 * @return mixed
	 */
	public function get_arg( $arg_name ) {
		return $this->args[ $arg_name ];
	}

	/**
	 * Returns true if the arg with the name $arg_name exists.
	 *
	 * @param string $arg_name Field import arg name.
	 *
	 * @return bool
	 */
	public function has_arg( $arg_name ) {
		return isset( $this->args[ $arg_name ] );
	}

	/**
	 * Builds export field context from export context.
	 *
	 * @param Export_Context $export_context Export context.
	 *
	 * @return Export_Field_Context
	 */
	public static function from_export_context( Export_Context $export_context ) {
		$export_field_context = new Export_Field_Context();
		$export_field_context->set_exporter( $export_context->get_exporter() );
		$export_field_context->set_existing_record_id( $export_context->get_existing_record_id() );
		return $export_field_context;
	}
}
