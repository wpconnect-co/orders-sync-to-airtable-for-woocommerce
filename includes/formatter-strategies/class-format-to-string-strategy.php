<?php
/**
 * Format to string strategy.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Format_To_String_Strategy class
 */
class Format_To_String_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_string';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Export_Field_Context $context The export field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( is_array( $value ) ) {
			// Multiple values.
			$value = implode( ', ', $value );
		} else {
			// Default string.
			$value = strval( $value );
		}

		return array(
			'success'         => is_string( $value ),
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
