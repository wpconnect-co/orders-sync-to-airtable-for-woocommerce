<?php
/**
 * Airtable Export context.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

use Exception;

/**
 * Export_Context class
 */
class Export_Context {
	/**
	 * Exporter.
	 *
	 * @var Exporter|null
	 */
	protected $exporter = null;

	/**
	 * Existing record id.
	 *
	 * @var mixed|null
	 */
	protected $existing_record_id = null;


	/**
	 * Get Exporter.
	 *
	 * @return Exporter|null
	 */
	public function get_exporter(): Exporter {
		return $this->exporter;
	}

	/**
	 * Set exporter.
	 *
	 * @param Exporter|null $exporter Exporter.
	 */
	public function set_exporter( Exporter $exporter ) {
		$this->exporter = $exporter;
		return $this;
	}


	/**
	 * Get existing record id if any.
	 *
	 * @return mixed|null
	 */
	public function get_existing_record_id() {
		return $this->existing_record_id;
	}

	/**
	 * Set existing record id.
	 *
	 * @param mixed|null $existing_record_id Existing record id.
	 */
	public function set_existing_record_id( $existing_record_id ) {
		$this->existing_record_id = $existing_record_id;
		return $this;
	}

	/**
	 * Returns true if the context has an exporter set.
	 *
	 * @return bool
	 */
	public function has_exporter() {
		return $this->exporter instanceof Exporter;
	}

	/**
	 * Returns true if the context has an existing record set.
	 *
	 * @return bool
	 */
	public function has_existing_record_id() {
		return null !== $this->existing_record_id;
	}
}
