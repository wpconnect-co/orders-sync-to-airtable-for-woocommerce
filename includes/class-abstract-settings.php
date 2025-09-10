<?php
/**
 * Abstract Settings class.
 * Base class to define custom settings.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Abstract_Settings
 */
abstract class Abstract_Settings implements \JsonSerializable {
	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructor
	 *
	 * @param array $settings Settings.
	 */
	public function __construct( $settings ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get a setting
	 *
	 * @param string $key Setting key.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		return array_reduce(
			explode( '.', $key ),
			function ( $o, $p ) {
				return is_array( $o ) && array_key_exists( $p, $o ) ? $o[ $p ] : false;
			},
			$this->settings
		);
	}

	/**
	 * Set a setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 *
	 * @return self
	 */
	public function set( $key, $value ) {
		$this->settings[ $key ] = $value;
		return $this;
	}

	/**
	 * Delete a setting
	 *
	 * @param string $key Setting key.
	 *
	 * @return self
	 */
	public function delete( $key ) {
		if ( array_key_exists( $key, $this->settings ) ) {
			unset( $this->settings[ $key ] );
		}
		return $this;
	}

	/**
	 * Get all settings as array
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->settings;
	}

	/**
	 * Retrieves the response data for JSON serialization.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->settings;
	}
}
