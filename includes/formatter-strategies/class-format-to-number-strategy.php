<?php
/**
 * Format to number strategy.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Format_To_Number_Strategy class
 */
class Format_To_Number_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_number';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Export_Field_Context $context The export field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( '' === $value || is_null( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => '',
				'messages'        => array(),
			);
		}

		if ( ! is_numeric( $value ) ) {
			return array(
				'success'         => false,
				'formatted_value' => '',
				'messages'        => array(
					sprintf( 'Format_To_Number_Strategy->format: The value should be numeric, "%s" found.', gettype( $value ) ),
				),
			);
		}

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
