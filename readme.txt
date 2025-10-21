=== Orders Sync to Airtable for WooCommerce ===
Author: WP connect
Author URI: https://wpconnect.co/
Contributors: wpconnectco, staurand
Tags: airtable, woocommerce, orders, api, synchronization
Requires at least: 5.7
Tested up to: 6.8
Tested up to WooCommerce: 10.0
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sync WooCommerce orders to Airtable easily and efficiently.

== Description ==

**Orders Sync to Airtable for WooCommerce** is a free plugin that allows you to synchronize your WooCommerce store's orders to your Airtable base with ease.

Whether you want to manage your order data, automate your reports, or centralize your store activity in Airtable, this plugin helps you streamline the process without manual data entry.

You can use our **predefined Airtable template** or connect to **your own Airtable base** for maximum flexibility.

Looking to sync **products, inventory, and more** from Airtable to WooCommerce? Check out our full-featured plugin [Air WP Sync for WooCommerce](https://wpconnect.co/woocommerce-airtable-integration/). It also includes **[Air WP Sync Pro+](https://wpconnect.co/air-wp-sync-plugin/)** which allows you to sync posts, pages, users, and taxonomy terms.

== Features ==

= Sync your WooCommerce orders to Airtable =
* Send new orders from your WooCommerce store directly to Airtable.
* Map key data like:
  - Order ID, status, date, total
  - Billing and shipping details
  - Customer information

= Flexible synchronization triggers =
* Sync orders **manually** at any time.
* Or enable **automatic syncing when the order status changes**.
* Syncs are queued and processed in a short time interval (~5 minutes), depending on your server's cron and API limits.

= Use a template or your own Airtable base =
* Use our **ready-made Airtable template** for a quick start.
* Or connect to your **own Airtable structure** with custom mappings.

= Built for simplicity =
* Minimal setup required.
* No code or complex configurations.

= Want more power? Sync your full store =
To sync your **products, inventory, custom fields, and SEO data**, upgrade to [Air WP Sync for WooCommerce](https://wpconnect.co/woocommerce-airtable-integration/). With it, you can:
* Import products from Airtable to WooCommerce, including variable and downloadable products.
* Keep inventory automatically in sync.
* Sync WooCommerce custom fields and meta.
* Manage orders from Airtable.
* Get full support for ACF and major SEO plugins.
* Gain access to **Air WP Sync Pro+**, which includes synchronization of:
  - Pages
  - Posts & Custom Posts Type
  - Users
  - Taxonomy terms

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**.
2. Search for "Orders Sync to Airtable for WooCommerce".
3. Click **Install Now**, then **Activate**.
4. Go to **Orders Sync to Airtable for WooCommerce** in the WordPress admin menu to begin setup.

== How to Use Orders Sync to Airtable for WooCommerce ==

1. Go to the **Orders Sync to Airtable for WooCommerce** page in your WordPress dashboard.
2. Click **Get Started**.
3. Enter your **Airtable Access Token** ([get it here](https://airtable.com/create/tokens)).
4. Select the target table for syncing your WooCommerce orders.
5. Map WooCommerce fields to Airtable columns.
6. Choose your synchronization trigger: manual or automatic on order status change.
7. Save the connection and start syncing.

== Frequently Asked Questions ==

= What is Airtable? =
Airtable is a collaborative online database that works like a spreadsheet but with the power of a relational database. It's ideal for organizing and managing order data.

= Do I need an Airtable account? =
Yes. You’ll need an Airtable account and an access token to connect your store. You can start with a free Airtable plan.

= Can I use this plugin with any Airtable base? =
Yes. You can either use the Airtable template we provide or set up your own custom base and column mappings.

= Does it sync orders in real time? =
Syncs are triggered manually or when an order status changes. They are processed via scheduled tasks and may take a few minutes depending on your server’s cron configuration and API limits.

= Can I sync products and stock too? =
Not with this plugin. For full WooCommerce-Airtable integration including products, inventory, custom fields, and more, use [Air WP Sync for WooCommerce](https://wpconnect.co/woocommerce-airtable-integration/), which also includes **Air WP Sync Pro+**.

= Where can I get help? =
You can consult our documentation or contact our support team:
* [Documentation](https://wpconnect.co/orders-sync-to-airtable-for-woocommerce-documentation/)
* [Support Center](https://wordpress.org/support/plugin/orders-sync-to-airtable-for-woocommerce/)

== Screenshots ==

1. Get Started
2. Choose a template
3. Field Mapping
4. Sync Settings

== Changelog ==

[Full changelog](https://wpconnect.co/changelog/changelog-orders-sync-to-airtable-for-woocommerce/)

== Support ==

If you need assistance, please open a ticket at our [Support Center](https://wordpress.org/support/plugin/orders-sync-to-airtable-for-woocommerce/) or check our [documentation](https://wpconnect.co/orders-sync-to-airtable-for-woocommerce-documentation/).

== Troubleshooting ==

* Make sure your Airtable table structure is properly set up.
* Fields must match the expected order data columns.
* Logs can be found in: `/wp-content/uploads/orders-sync-to-airtable-for-woocommerce-logs`

== Source ==

All source code is hosted on [github.com](https://wpconnect.co/changelog/changelog-orders-sync-to-airtable-for-woocommerce/) (JavaScript source files, development tools...)

== External Services ==

This plugin connects to the Airtable API to sync WooCommerce orders into an external Airtable base.

Data sent includes:
- Customer name and email
- Order total and order status
- Product information
- Stock quantity
- Shipping and billing addresses (if configured)

Data is sent to Airtable every time a new WooCommerce order is created or updated. It can be synced manually as well.

The Airtable API service is provided by Airtable Inc. You can find their terms and policies here:

- [Terms of Service](https://www.airtable.com/company/tos)
- [Privacy Policy](https://www.airtable.com/company/privacy)
