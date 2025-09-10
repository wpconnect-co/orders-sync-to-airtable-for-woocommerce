<?php
/**
 * Format to datetime strategy.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Format_To_Datetime_Strategy class
 */
class Format_To_Datetime_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_datetime';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Export_Field_Context $context The export field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => '',
				'messages'        => array(),
			);
		}

		if ( strtotime( $value ) ) {
			$value = new \DateTimeImmutable( $value );
			if ( $value instanceof \DateTimeInterface ) {
				$value = $value->setTimezone( wp_timezone() );
			}
		}

		if ( ! ( $value instanceof \DateTimeInterface ) ) {
			try {
				$str_value = strval( $value );
			} catch ( \Throwable $throwable ) {
				$str_value = gettype( $value );
			}
			return array(
				'success'         => false,
				'formatted_value' => '',
				'messages'        => array(
					sprintf( 'Format_To_Datetime_Strategy->format: The value is not a valid date, "%s" found.', $str_value ),
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
