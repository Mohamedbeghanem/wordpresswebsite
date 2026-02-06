<?php

/**
 * OptIn manager class is to handle all scenarios occurs for opting the user
 * @author: WooFunnels
 * @since 0.0.1
 * @package WooFunnels
 */
if ( ! class_exists( 'WooFunnels_OptIn_Manager' ) ) {
	#[AllowDynamicProperties]
	class WooFunnels_OptIn_Manager {

		public static $should_show_optin = true;

		/**
		 * Initialization to execute several hooks
		 */
		public static function init() {
			//push notification for optin

			add_action( 'admin_init', array( __CLASS__, 'maybe_push_optin_notice' ), 15 );
			add_action( 'admin_init', array( __CLASS__, 'maybe_clear_optin' ), 15 );
			// track usage user callback
			add_action( 'fk_fb_every_day', array( __CLASS__, 'maybe_track_usage' ), 10, 2 );
			add_action( 'bwf_track_usage_scheduled_single', array( __CLASS__, 'maybe_track_usage' ), 10, 2 );

			//initializing schedules
			add_action( 'admin_footer', array( __CLASS__, 'initiate_schedules' ) );

			// For testing license notices, uncomment this line to force checks on every page load

			/** optin ajax call */
			add_action( 'wp_ajax_woofunnelso_optin_call', array( __CLASS__, 'woofunnelso_optin_call' ) );

			// optin yes track callback
			add_action( 'woofunnels_optin_success_track_scheduled', array( __CLASS__, 'optin_track_usage' ), 10 );

			add_filter( 'cron_schedules', array( __CLASS__, 'register_weekly_schedule' ), 10 );
		}

		/**
		 * Set function to block
		 */
		public static function block_optin() {
			update_option( 'bwf_is_opted', 'no', true );
		}

		public static function maybe_clear_optin() {
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_nonce', FILTER_UNSAFE_RAW ), 'bwf_tools_action' ) && isset( $_GET['woofunnels_tracking'] ) && ( 'reset' === sanitize_text_field( $_GET['woofunnels_tracking'] ) ) ) {
				self::reset_optin();
				wp_safe_redirect( admin_url( 'admin.php?page=woofunnels&tab=tools' ) );
				exit;
			}
		}

		/**
		 * Reset optin
		 */
		public static function reset_optin() {
			$get_action = filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW );
			if ( 'yes' === $get_action ) {
				self::Allow_optin();
			} else {
				delete_option( 'bwf_is_opted' );
			}

		}

		/**
		 * Set function to allow
		 */
		public static function Allow_optin( $pass_jit = false, $product = 'FB' ) {
			if ( 'yes' === self::get_optIn_state() ) {
				return;
			}
			if ( false !== wp_next_scheduled( 'bwf_track_usage_scheduled_single' ) ) {
				wp_clear_scheduled_hook( 'bwf_track_usage_scheduled_single' );
			}
			if ( true === $pass_jit ) {
				wp_schedule_single_event( current_time( 'timestamp' ), 'bwf_track_usage_scheduled_single', array( 'yes', $product ) );
			} else {
				wp_schedule_single_event( current_time( 'timestamp' ), 'bwf_track_usage_scheduled_single', array( false, $product ) );
			}
			update_option( 'bwf_is_opted', 'yes', true );
		}

		/**
		 * Collect some data and let the hook left for our other plugins to add some more info that can be tracked down
		 *
		 * @return array data to track
		 */
		public static function collect_data() {
			global $wpdb, $woocommerce;

			$installed_plugs     = WooFunnels_addons::get_installed_plugins();
			$active_plugins      = get_option( 'active_plugins' );
			$licenses            = WooFunnels_licenses::get_instance()->get_data();
			$theme               = array();
			$get_theme_info      = wp_get_theme();
			$theme['name']       = $get_theme_info->get( 'Name' );
			$theme['uri']        = $get_theme_info->get( 'ThemeURI' );
			$theme['version']    = $get_theme_info->get( 'Version' );
			$theme['author']     = $get_theme_info->get( 'Author' );
			$theme['author_uri'] = $get_theme_info->get( 'AuthorURI' );
			$ref                 = get_option( 'woofunnels_optin_ref', '' );
			$sections            = array();

			if ( class_exists( 'WooCommerce' ) ) {
				$payment_gateways = WC()->payment_gateways->payment_gateways();
				foreach ( $payment_gateways as $gateway_key => $gateway ) {
					if ( 'yes' === $gateway->enabled ) {
						$sections[] = esc_html( $gateway_key );
					}
				}
			}

			/** Product Count */
			$product_count = array();
			if ( class_exists( 'WooCommerce' ) ) {
				$product_count_data     = wp_count_posts( 'product' );
				$product_count['total'] = property_exists( $product_count_data, 'publish' ) ? $product_count_data->publish : 0;

				$product_statuses = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
				if ( is_array( $product_statuses ) && count( $product_statuses ) > 0 ) {
					foreach ( $product_statuses as $product_status ) {
						$product_count[ $product_status->name ] = $product_status->count;
					}
				}
			}

			/** Order Count */
			$order_count = array();
			if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_orders_count' ) ) {
				foreach ( wc_get_order_statuses() as $status_slug => $status_name ) {
					$order_count[ $status_slug ] = wc_orders_count( $status_slug );
				}

			}

			/** Base country slug */
			$base_country = '';
			if ( class_exists( 'WooCommerce' ) ) {
				$base_country = get_option( 'woocommerce_default_country', false );
				if ( ! empty( $base_country ) ) {
					$base_country = substr( $base_country, 0, 2 );
				}
			}

			$return = array(
				'url'                    => home_url(),
				'email'                  => get_option( 'admin_email' ),
				'installed'              => $installed_plugs,
				'active_plugins'         => $active_plugins,
				'license_info'           => $licenses,
				'theme_info'             => $theme,
				'users_count'            => self::get_user_counts(),
				'locale'                 => get_locale(),
				'is_mu'                  => is_multisite() ? 'yes' : 'no',
				'wp'                     => get_bloginfo( 'version' ),
				'php'                    => phpversion(),
				'mysql'                  => $wpdb->db_version(),
				'WooFunnels_version'     => WooFunnel_Loader::$version,
				'notification_ref'       => $ref,
				'date'                   => date( 'd.m.Y H:i:s' ),
				'bwf_order_index_status' => get_option( '_bwf_db_upgrade', '0' ),
				'bwf_version'            => defined( 'BWF_VERSION' ) ? BWF_VERSION : '0.0.0',
			);

			if ( class_exists( 'WooCommerce' ) ) {
				$return['country']        = $base_country;
				$return['currency']       = get_woocommerce_currency();
				$return['wc']             = $woocommerce->version;
				$return['calc_taxes']     = get_option( 'woocommerce_calc_taxes' );
				$return['guest_checkout'] = get_option( 'woocommerce_enable_guest_checkout' );
				$return['product_count']  = $product_count;
				$return['order_count']    = $order_count;
				$return['wc_gateways']    = $sections;
			}

			return apply_filters( 'woofunnels_global_tracking_data', $return );
		}

		/**
		 * Get user totals based on user role.
		 * @return array
		 */
		private static function get_user_counts() {
			$user_count          = array();
			$user_count_data     = count_users();
			$user_count['total'] = $user_count_data['total_users'];

			// Get user count based on user role
			foreach ( $user_count_data['avail_roles'] as $role => $count ) {
				$user_count[ $role ] = $count;
			}

			return $user_count;
		}

		public static function update_optIn_referer( $referer ) {
			update_option( 'woofunnels_optin_ref', $referer, false );
		}

		/**
		 * Checking the opt-in state and if we have scope for notification then push it
		 */
		public static function maybe_push_optin_notice() {
			if ( self::get_optIn_state() === false && apply_filters( 'woofunnels_optin_notif_show', self::$should_show_optin ) ) {
				do_action( 'bwf_maybe_push_optin_notice_state_action' );
			}
		}

		/**
		 * Get current optin status from database
		 *
		 * @return mixed|void
		 */
		public static function get_optIn_state() {
			return get_option( 'bwf_is_opted' );
		}

		/**
		 * Callback function to run on schedule hook
		 */
		public static function maybe_track_usage( $is_jit = false, $product = 'FB' ) {
			//checking optin state
			if ( true === self::is_optin_allowed() && 'yes' === self::get_optIn_state() ) {
				$data = self::collect_data();

				if ( $is_jit === 'yes' ) {
					$data['jit'] = 'yes';
				}
				$data['product'] = $product;
				//posting data to api
				WooFunnels_API::post_tracking_data( $data );
			}
		}

		/**
		 * Initiate schedules in order to start tracking data regularly.
		 *
		 */
		public static function initiate_schedules() {

			// Run only in the admin dashboard

			try {
				if ( ! is_admin() ) {
					return;
				}
				if ( ! current_user_can( 'administrator' ) ) {
					return;
				}
				if ( ! wp_next_scheduled( 'fk_fb_every_day' ) ) {
					wp_schedule_event( self::get_midnight_store_time(), 'daily', 'fk_fb_every_day' );
				} else {
					
					$scheduled_time = wp_next_scheduled( 'fk_fb_every_day' );
					$midnight_time = self::get_midnight_store_time();
					
					if ( $scheduled_time && abs( $scheduled_time - $midnight_time ) > 3600 ) {
						do_action( 'fk_fb_every_day' );
						wp_clear_scheduled_hook( 'fk_fb_every_day' );
					}
				}
				$legacy_schedules = array(
					'wfocu_schedule_mails_for_bacs_and_cheque',
					'wfocu_schedule_pending_emails',
					'wfocu_schedule_normalize_order_statuses',
					'wfocu_schedule_thankyou_action',
					'wfocu_remove_orphaned_transients',
					'wffn_remove_orphaned_transients',
					'bwf_maybe_track_usage_scheduled',
					'woofunnels_license_check',
				);
				foreach ( $legacy_schedules as $schedule ) {

					if ( strpos( $schedule, 'wfocu_' ) === 0 && ! wp_next_scheduled( 'fk_fb_every_4_minute' ) ) {
						continue;
					}
					if ( wp_next_scheduled( $schedule ) ) {
						wp_clear_scheduled_hook( $schedule );
					}
				}
			} catch ( Exception $e ) {

			}


		}


		/**
		 * @return int|void
		 */
		public static function get_midnight_store_time() {

			try {
				$timezone = new DateTimeZone( wp_timezone_string() );
				$date     = new DateTime();
				$date->modify( '+1 days' );
				$date->setTimezone( $timezone );
				$date->setTime( 0, 0, 0 );

				return $date->getTimestamp();
			} catch ( Exception $e ) {

			}
		}

		public static function is_optin_allowed() {
			return apply_filters( 'buildwoofunnels_optin_allowed', true );
		}

		public static function woofunnelso_optin_call() {
			check_ajax_referer( 'bwf_secure_key' );
			if ( is_array( $_POST ) && count( $_POST ) > 0 ) {
				$_POST['domain'] = home_url();
				$_POST['ip']     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
				WooFunnels_API::post_optin_data( $_POST );

				/** scheduling track call when success */
				if ( isset( $_POST['status'] ) && 'yes' === sanitize_text_field( $_POST['status'] ) ) {
					wp_schedule_single_event( time() + 2, 'woofunnels_optin_success_track_scheduled' );
				}
			}
			wp_send_json( array(
				'status' => 'success',
			) );
			exit;
		}

		/**
		 * Callback function to run on schedule hook
		 */
		public static function optin_track_usage() {
			/** update week day for tracking */
			$track_week_day = date( 'w' );
			update_option( 'woofunnels_track_day', $track_week_day, false );

			$data = self::collect_data();

			//posting data to api
			WooFunnels_API::post_tracking_data( $data );
		}

		public static function maybe_default_optin() {
			return;
		}

		public static function register_weekly_schedule( $schedules ) {
			$schedules['weekly_bwf'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Weekly BWF', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			);

			return $schedules;
		}
	}

	// Initialization
	WooFunnels_optIn_Manager::init();
}
