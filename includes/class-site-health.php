<?php
/**
 * Site health checks
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

/**
 * Site_Health class.
 */
class Site_Health {
	/**
	 * Site_Health instance
	 *
	 * @var Site_Health $instance
	 */
	private static $instance;

	/**
	 * Returns Site_Health instance
	 *
	 * @return Site_Health
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return site health checks for the plugin.
	 *
	 * @return array
	 */
	public function check() {
		$checks    = array( 'can_perform_loopback', 'cron', 'check_db_charset' );
		$check_md5 = md5( implode( ',', $checks ) );
		$result    = get_transient( 'orders_sync_to_airtable_for_woocommerce_site_health_check_' . $check_md5 );
		if ( $result ) {
			return $result;
		}
		if ( ! class_exists( '\WP_Site_Health' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
		}

		$result = array();

		$health_check_site_status       = \WP_Site_Health::get_instance();
		$result['can_perform_loopback'] = true;
		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			$can_perform_loopback           = $health_check_site_status->can_perform_loopback();
			$result['can_perform_loopback'] = 'good' !== $can_perform_loopback->status ? $can_perform_loopback->message : true;
		}
		$cron           = $health_check_site_status->get_test_scheduled_events();
		$result['cron'] = 'good' !== $cron['status'] ? $cron['label'] : true;

		$chars_sample = 'Try to store some special chars → 😀';
		update_option( 'orders_sync_to_airtable_for_woocommerce_check_chars', $chars_sample );
		global $wpdb;
		// Get it directly from DB so we are sure it does not come from cache.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$check_chars_result         = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'orders_sync_to_airtable_for_woocommerce_check_chars' ), ARRAY_A );
		$result['check_db_charset'] = $check_chars_result['option_value'] === $chars_sample;

		$result['all_good'] = array_reduce(
			$result,
			function ( $carry, $check_result ) {
				return $carry && true === $check_result;
			},
			true
		);

		set_transient( 'orders_sync_to_airtable_for_woocommerce_site_health_check_' . $check_md5, $result, DAY_IN_SECONDS );
		return $result;
	}
}
