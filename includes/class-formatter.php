<?php
/**
 * Manage Airtable Formatter Strategies: register + format.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

use Exception;

/**
 * Formatter class
 */
class Formatter {

	/**
	 * Formatter strategies.
	 *
	 * @var Abstract_Formatter_Strategy[]
	 */
	protected $strategies = array();

	/**
	 * Register strategy.
	 *
	 * @param Abstract_Formatter_Strategy $strategy Formatter strategy to register.
	 *
	 * @return self
	 */
	public function register_strategy( $strategy ) {
		$this->strategies[ $strategy->get_slug() ] = $strategy;
		return $this;
	}

	/**
	 * String supported destinations.
	 *
	 * @var string[]
	 */
	protected $string_supported_destinations = array(
		'barcode.type',
		'barcode.text',
		'email',
		'multilineText',
		'phoneNumber',
		'richText',
		'singleLineText',
		'singleSelect',
		'url',
	);

	/**
	 * Number supported destinations.
	 *
	 * @var string[]
	 */
	protected $number_supported_destinations = array( 'number', 'formula', 'rating', 'percent', 'duration', 'currency' );

	/**
	 * Boolean supported destinations.
	 *
	 * @var string[]
	 */
	protected $boolean_supported_destinations = array( 'checkbox' );

	/**
	 * Datetime supported destinations.
	 *
	 * @var string[]
	 */
	protected $datetime_supported_destinations = array( 'date', 'dateTime' );

	/**
	 * Format value then return result.
	 *
	 * @param string               $strategy_slug Strategy’s slug.
	 * @param mixed                $value The value to format.
	 * @param Export_Field_Context $context The export field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception The strategy’s "%s" is not registered.
	 */
	public function format( $strategy_slug, $value, $context ) {
		$map = false;
		if ( strpos( $strategy_slug, 'map:' ) === 0 ) {
			list($map, $strategy_slug) = explode( ':', $strategy_slug );
		}
		if ( ! isset( $this->strategies[ $strategy_slug ] ) ) {
			throw new \Exception( esc_html( sprintf( 'The strategy’s "%s" is not registered', $strategy_slug ) ), 400 );
		}

		$strategy = $this->strategies[ $strategy_slug ];
		if ( $map ) {
			if ( empty( $value ) ) {
				$value = array();
			}
			if ( ! is_array( $value ) ) {
				throw new \Exception( esc_html( sprintf( '"map:" modifier on strategy slug "%s" is meant to be used on array', $strategy_slug ) ), 400 );
			}
			// Change source field type, we will convert with the map modifier the value from an array to single field value.
			$source_field = $context->get_source_field();
			if ( $source_field && strpos( $source_field->type, 'orders_sync_to_airtable_for_woocommerceProxyRecordLinks|' ) === 0 ) {
				$source_field->type = str_replace( 'orders_sync_to_airtable_for_woocommerceProxyRecordLinks|', '', $source_field->type );
				$context->set_source_field( $source_field );
			}
			$result = array(
				'success'         => true,
				'formatted_value' => array(),
				'messages'        => array(),
			);
			foreach ( $value as $sub_value ) {
				$sub_result                  = $strategy->format( $sub_value, $context );
				$result['success']           = $result['success'] && $sub_result['success'];
				$result['formatted_value'][] = $sub_result['formatted_value'];
				$result['messages']          = array_merge( $result['messages'], $sub_result['messages'] );
			}
		} else {
			$result = $strategy->format( $value, $context );
		}

		if ( ! empty( $result['messages'] ) && $context->has_exporter() ) {
			$log_level = $result['success'] ? 'log' : 'error';
			foreach ( $result['messages'] as $message ) {
				$context->get_exporter()->log( $message, $log_level );
			}
		}

		return $result['formatted_value'];
	}

	/**
	 * Returns string supported destinations from Airtable.
	 *
	 * @return string[]
	 */
	public function get_string_supported_destinations() {
		return $this->string_supported_destinations;
	}

	/**
	 * Returns number supported destinations from Airtable.
	 *
	 * @return string[]
	 */
	public function get_number_supported_destinations() {
		return $this->number_supported_destinations;
	}

	/**
	 * Returns boolean supported destinations from Airtable.
	 *
	 * @return string[]
	 */
	public function get_boolean_supported_destinations() {
		return $this->boolean_supported_destinations;
	}

	/**
	 * Returns datetime supported destinations from Airtable.
	 *
	 * @return string[]
	 */
	public function get_datetime_supported_destinations() {
		return $this->datetime_supported_destinations;
	}
}
