<?php
/**
 * Abstract Formatter Strategy.
 * Base class to define formatter strategy.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Abstract_Formatter_Strategy
 */
abstract class Abstract_Formatter_Strategy {
	/**
	 * Formatter strategy slug
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Constructor.
	 * Checks if the slug has been defined.
	 *
	 * @throws \Exception Formatter strategy slug must be defined.
	 */
	public function __construct() {
		if ( ! $this->slug ) {
			throw new \Exception( 'Formatter strategy slug must be defined' );
		}
	}

	/**
	 * Return formatter strategy slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Format value then check formatted value, return [ 'success' => true ] if the format match, return [ 'success' => false ] if it does not.
	 * Add messages to the result to explain why it failed or warning if it succeeded with comments.
	 *
	 * @param mixed                $value The value to format.
	 * @param Export_Field_Context $context The export field context for the formatter.
	 *
	 * @return mixed
	 */
	abstract public function format( $value, $context );
}
