<?php
/**
 * Options class.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Class Options
 */
class Options extends Abstract_Settings {
	/**
	 * WP option slug
	 *
	 * @var string
	 */
	protected $option_slug = 'orders_sync_to_airtable_for_woocommerce_exporter_options';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( $this->load_options() );
	}

	/**
	 * Save settings to DB
	 */
	public function save() {
		update_option( $this->option_slug, $this->settings );
	}

	/**
	 * Load options from DB
	 */
	protected function load_options() {
		return get_option( $this->option_slug );
	}

	/**
	 * Delete options from DB
	 */
	public function delete_options() {
		return delete_option( $this->option_slug );
	}
}
