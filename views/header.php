<?php
/**
 * Display connection header.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return function () {
	?>

	<div class="orders-sync-to-airtable-for-woocommerce-admin-header">
		<h2>
			<a class="orders-sync-to-airtable-for-woocommerce-admin-header-link" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Orders_Sync_to_Airtable_for_WooCommerce\Admin::TOP_MENU_SLUG ) ); ?>">
				<img class="orders-sync-to-airtable-for-woocommerce-admin-header-logo" width="20" src="<?php echo esc_url( plugins_url( 'assets/images/wpconnect-logo-white.svg', __DIR__ ) ); ?>"/>
				<span><?php esc_html_e( 'Orders Sync to Airtable for WooCommerce', 'orders-sync-to-airtable-for-woocommerce' ); ?></span>
			</a>
		</h2>

		<a class="orders-sync-to-airtable-for-woocommerce-admin-header-wpco" href="https://wpconnect.co/" target="_blank">
			<img width="178" height="24" src="<?php echo esc_url( plugins_url( 'assets/images/logo-wpconnect-v3.svg', __DIR__ ) ); ?>" alt="WP connect"/>
		</a>
	</div>
	<?php
};
