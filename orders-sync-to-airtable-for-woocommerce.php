<?php
/**
 * Plugin Name: Orders Sync to Airtable for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/orders-sync-to-airtable-for-woocommerce/
 * Description: Easily sync your WooCommerce orders with Airtable
 * Version: 1.0.0
 * Requires at least: 5.7
 * Tested up to: 6.8
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * Author: WP connect
 * Author URI: https://wpconnect.co/
 * License: GPLv2 or later
 * Text Domain: orders-sync-to-airtable-for-woocommerce
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_VERSION', '1.0.0' );
define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_FILE', __FILE__ );
define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_BASENAME', plugin_basename( __FILE__ ) );
define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_LOGDIR', wp_upload_dir( null, false )['basedir'] . '/orders-sync-to-airtable-for-woocommerce-logs/' );
define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_TEMPLATE_URL', 'https://airtable.com/appazTxkrHLhuAAVu/shrCMCfCoDExXhSy0' );
define( 'ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_LATEST_TEMPLATE_VERSION', '1.0.0' );


require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-services.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-abstract-settings.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-logger.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-options.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-site-health.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-export-context.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-export-field-context.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-exporter.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-exporter-settings.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-exporter-state.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-formatter.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-abstract-formatter-strategy.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/formatter-strategies/class-format-to-string-strategy.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/formatter-strategies/class-format-to-datetime-strategy.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/formatter-strategies/class-format-to-number-strategy.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-abstract-source.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bootstrap.php';

require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-action-consumer.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-helper.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/admin/class-admin.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-airtable-api-client.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-template-checker.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-template-structure.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-order-module.php';



register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );


if ( ! function_exists( __NAMESPACE__ . '\deactivate' ) ) {
	/**
	 * The code that runs during plugin deactivation.
	 */
	function deactivate() {
		// flush rewrite rules.
		flush_rewrite_rules();
		// Clear hooks.
		foreach ( _get_cron_array() as $cron ) {
			foreach ( array_keys( $cron ) as $hook ) {
				if ( strpos( $hook, 'orders_sync_to_airtable_for_woocommerce_exporter_' ) === 0 ) {
					wp_clear_scheduled_hook( $hook );
				}
			}
		}
	}
}

register_uninstall_hook( __FILE__, __NAMESPACE__ . '\uninstall' );
if ( ! function_exists( __NAMESPACE__ . '\uninstall' ) ) {
	/**
	 * Uninstall procedure
	 */
	function uninstall() {
		$options = new Exporter_Settings();
		$options->delete_options();

		$state = new Exporter_State();
		$state->delete_options();
	}
}

// Init plugin.
new Bootstrap();
