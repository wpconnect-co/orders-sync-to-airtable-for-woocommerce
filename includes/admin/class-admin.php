<?php
/**
 * Manages admin pages registration.
 *
 * @package Orders_Sync_to_Airtable_for_WooCommerce
 */

namespace Orders_Sync_to_Airtable_for_WooCommerce;

require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/admin/class-admin-exporter-api.php';
require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'includes/admin/class-admin-exporter.php';

/**
 * Class Admin
 */
class Admin {

	const TOP_MENU_SLUG = 'orders-sync-to-airtable-for-woocommerce';

	/**
	 * Plugin settings.
	 *
	 * @var Options
	 */
	protected $options;

	/**
	 * Admin exporter admin page.
	 *
	 * @var Admin_Exporter
	 */
	protected $admin_exporter_page;

	/**
	 * Constructor
	 *
	 * @param Options $options Plugin settings.
	 */
	public function __construct( $options ) {
		$this->options = $options;

		add_action( 'admin_menu', array( $this, 'add_menu' ), 100 );
		add_action( 'in_admin_header', array( $this, 'in_admin_header' ) );
		add_filter( 'orders_sync_to_airtable_for_woocommerce/should_display_admin_header', array( $this, 'should_display_admin_header' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
		add_action( 'current_screen', array( $this, 'add_notices' ) );
		add_action( 'wp_ajax_orders_sync_to_airtable_for_woocommerce_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );

		add_filter( 'plugin_action_links_' . ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_BASENAME, array( $this, 'plugin_action_links' ) );

		$services = Services::get_instance();
		new Admin_Exporter_Api( $services->get( 'exporter' ), $services->get( 'airtable_api_client_class_factory' ), $services->get( 'template_checker' ) );
		$this->admin_exporter_page = new Admin_Exporter( $services->get( 'exporter' ), $services->get( 'template_checker' ) );
	}

	/**
	 * Add menu
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Orders Sync to Airtable for WooCommerce', 'orders-sync-to-airtable-for-woocommerce' ),
			__( 'WooCommerce Orders to Airtable', 'orders-sync-to-airtable-for-woocommerce' ),
			apply_filters( 'orders_sync_to_airtable_for_woocommerce/manage_options_capability', 'manage_options' ),
			self::TOP_MENU_SLUG,
			array( $this->admin_exporter_page, 'view' ),
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODMuOTkgMTE4Ij48cGF0aCBkPSJNNTAuNjYgOTAuMjQgMjUuMDEgNzUuNThjLS42Mi0uMzYtLjk5LTEuMDEtLjk5LTEuNzJWNDQuMTNjMC0uNzEuMzgtMS4zNi45OS0xLjcybDI2Ljk4LTE1LjQ0Uzk3LjEgNTMuODMgMTMxLjA2LjkzYy0zOC43MyAzNi44My03MS42OSA1LjgzLTc4LS4zNi0uNjQtLjYyLTEuNjItLjc1LTIuMzktLjMxTC45OSAyOC42N2MtLjYyLjM2LS45OSAxLjAxLS45OSAxLjcydjU3LjJjMCAuNzEuMzggMS4zNi45OSAxLjcybDQ5LjM2IDI4LjIzYy45Mi41MyAyLjA5LjI0IDIuNjctLjY0Qzg2Ljg2IDY2LjA4IDEzMS45OCA5MSAxMzEuOTggOTFjLTM5LjAyLTM1Ljc1LTcyLjY2LTcuMDctNzguOTQtMS4wNS0uNjQuNjEtMS42MS43NC0yLjM4LjI5WiIgZmlsbD0iI0ZGRiIgb3BhY2l0eT0iMC44Ii8+PHBhdGggZD0iTTEzMC45NiAxLjA4Qzk3LjEyIDUxLjkgNTIgMjYuOTggNTIgMjYuOThjMzkuMDIgMzUuNzUgNzIuNjYgNy4wNyA3OC45NCAxLjA1LjY0LS42MSAxLjYxLS43NCAyLjM4LS4yOWwyNS42NSAxNC42NmMuNjIuMzYuOTkgMS4wMS45OSAxLjcydjI5LjczYzAgLjcxLS4zOCAxLjM2LS45OSAxLjcybC0yNi45OCAxNS40NHMtNDUtMjcuMDEtNzguOTYgMjUuOWMzOC43My0zNi44MyA3MS41OS01LjY5IDc3LjkxLjUxLjY0LjYyIDEuNjIuNzUgMi4zOS4zMWw0OS42NS0yOC40Yy42Mi0uMzYuOTktMS4wMS45OS0xLjcyVjMwLjM4YzAtLjcxLS4zOC0xLjM2LS45OS0xLjcyTDEzMy42My40M2MtLjkyLS41My0yLjA5LS4yNC0yLjY3LjY0WiIgZmlsbD0iI0ZGRiIgLz48L3N2Zz4K'
		);
		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'Orders Sync to Airtable for WooCommerce', 'orders-sync-to-airtable-for-woocommerce' ),
			__( 'Set up', 'orders-sync-to-airtable-for-woocommerce' ),
			apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
			self::TOP_MENU_SLUG
		);
		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'Support', 'orders-sync-to-airtable-for-woocommerce' ),
			__( 'Support', 'orders-sync-to-airtable-for-woocommerce' ),
			apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
			'https://wordpress.org/support/plugin/orders-sync-to-airtable-for-woocommerce/'
		);
		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'Documentation', 'orders-sync-to-airtable-for-woocommerce' ),
			__( 'Documentation', 'orders-sync-to-airtable-for-woocommerce' ),
			apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
			'https://wpconnect.co/orders-sync-to-airtable-for-woocommerce-documentation/'
		);
	}

	/**
	 * Redirect top menu to sub menu page (default to connection list).
	 *
	 * @return void
	 */
	public function redirect_top_menu() {
		$view = require_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'views/wizard.php';
		$view();
	}

	/**
	 * Display plugin header
	 */
	public function in_admin_header() {
		if ( apply_filters( 'orders_sync_to_airtable_for_woocommerce/should_display_admin_header', false ) ) {
			$view = include_once ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_DIR . 'views/header.php';
			$view();
		}
	}

	/**
	 * Display admin header if we are on the wizard screen.
	 *
	 * @param bool $display_admin_header Display admin header.
	 *
	 * @return mixed|true
	 */
	public function should_display_admin_header( $display_admin_header ) {
		$current_screen = get_current_screen();
		if ( strpos( $current_screen->base, self::TOP_MENU_SLUG ) !== false ) {
			$display_admin_header = true;
		}
		return $display_admin_header;
	}

	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		wp_enqueue_style( 'orders-sync-to-airtable-for-woocommerce-admin', plugins_url( 'assets/css/admin-page.css', ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_PLUGIN_FILE ), false, ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_VERSION );
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param string[] $actions An array of plugin action links. By default this can include
	 *                              'activate', 'deactivate', and 'delete'. With Multisite active
	 *                              this can also include 'network_active' and 'network_only' items.
	 */
	public function plugin_action_links( $actions ) {
		return array_merge(
			$actions,
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=orders-sync-to-airtable-for-woocommerce' ) . '">' . esc_html__( 'Settings', 'orders-sync-to-airtable-for-woocommerce' ) . '</a>',
			)
		);
	}

	/**
	 * Add admin notices
	 */
	public function add_notices() {
		// Display site health alert if required.
		$this->site_heath_notice();
	}

	/**
	 * Display site health notice if required.
	 *
	 * @return void
	 */
	protected function site_heath_notice() {
		$current_screen = get_current_screen();
		if ( strpos( $current_screen->base, self::TOP_MENU_SLUG ) !== false ) {
			$site_health_check = Site_Health::get_instance()->check();
			if ( ! $site_health_check['all_good'] ) {
				$messages  = array();
				$notice_id = array( 'site_health' );
				foreach ( $site_health_check as $check_key => $check_result ) {
					if ( 'all_good' === $check_key ) {
						continue;
					}
					if ( true !== $check_result ) {
						$messages [] = '<p>' . $check_result . '</p>';
						$notice_id[] = $check_key;
					}
				}
				$notice_id  = implode( '_', $notice_id );
				$notice_id .= '_' . wp_date( 'Y-m' );

				$dismissed_notices = $this->options->get( 'dismissed_notices' );
				if ( ! $dismissed_notices ) {
					$dismissed_notices = array();
				}
				if ( isset( $dismissed_notices[ $notice_id ] ) ) {
					return;
				}

				if ( true !== $site_health_check['cron'] || true !== $site_health_check['can_perform_loopback'] ) {
					/* Translators: %s Site health page URL */
					$messages [] = '<p>' . sprintf( __( 'WP Cron does not sound to work correctly and it‘s required by our plugin. Please check <a href="%s">Site Health page</a> and <a href="https://wpconnect.co/blog/setup-cron-wordpress/" target="_blank">how to set up WP Cron correctly</a>.', 'orders-sync-to-airtable-for-woocommerce' ), admin_url( 'site-health.php' ) ) . '</p>';
				}
				if ( true !== $site_health_check['check_db_charset'] ) {
					/* Translators: %s Site health page URL */
					$messages [] = '<p>' . esc_html__( 'There is a problem with your database tables charset, our plugin will not work properly if you are not using utf8mb4. Please contact your hosting provider to fix it.', 'orders-sync-to-airtable-for-woocommerce' ) . '</p>';
				}

				$message            = implode( '', $messages );
				$dismiss_action_url = add_query_arg(
					array(
						'action'    => 'orders_sync_to_airtable_for_woocommerce_dismiss_notice',
						'notice_id' => $notice_id,
					),
					wp_nonce_url( admin_url( 'admin-ajax.php' ), 'orders_sync_to_airtable_for_woocommerce_dismiss_notice' )
				);
				add_action(
					'admin_notices',
					function () use ( $message, $dismiss_action_url ) {
						echo wp_kses(
							sprintf(
								"<div class='notice notice-warning is-dismissible js-orders-sync-to-airtable-for-woocommerce-dismissible-notice' data-orders-sync-to-airtable-for-woocommerce-dismissible-action='%s'>%s</div>",
								esc_attr( $dismiss_action_url ),
								$message
							),
							array(
								'div' => array(
									'class' => array(),
									'data-orders-sync-to-airtable-for-woocommerce-dismissible-action' => array(),
								),
								'p'   => array(),
								'br'  => array(),
								'a'   => array(
									'href'   => array(),
									'target' => array(),
								),
							)
						);
					}
				);
				add_action( 'admin_enqueue_scripts', array( $this, 'dismissible_notice_script' ) );
			}
		}
	}

	/**
	 * Output dismiss notice script.
	 *
	 * @return void
	 */
	public function dismissible_notice_script() {
		wp_register_script( 'orders-sync-to-airtable-for-woocommerce-dismiss-notice', false, array( 'jquery' ), ORDERS_SYNC_TO_AIRTABLE_FOR_WOOCOMMERCE_VERSION, true );
		wp_enqueue_script( 'orders-sync-to-airtable-for-woocommerce-dismiss-notice' );
		wp_add_inline_script(
			'orders-sync-to-airtable-for-woocommerce-dismiss-notice',
			"
			jQuery(function () {
				const dismissibleNotices = document.querySelectorAll('.js-orders-sync-to-airtable-for-woocommerce-dismissible-notice');
				dismissibleNotices.forEach(function (dismissibleNotice) {
					dismissibleNotice.addEventListener('click', function (e) {
						if (e.target && e.target.classList.contains('notice-dismiss')) {
							fetch(dismissibleNotice.getAttribute('data-orders-sync-to-airtable-for-woocommerce-dismissible-action'));
						}
					})
				});
			});
		"
		);
	}

	/**
	 * Handle Ajax request to dismiss a notice.
	 *
	 * @return void
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'orders_sync_to_airtable_for_woocommerce_dismiss_notice' );

		$notice_id = isset( $_GET['notice_id'] ) ? sanitize_key( $_GET['notice_id'] ) : false;
		if ( ! $notice_id ) {
			wp_send_json_error();
		}

		$dismissed_notices = $this->options->get( 'dismissed_notices' );
		if ( ! $dismissed_notices ) {
			$dismissed_notices = array();
		}
		$dismissed_notices[ $notice_id ] = true;
		$this->options->set( 'dismissed_notices', $dismissed_notices );
		$this->options->save();
		wp_send_json_success();
	}
}
