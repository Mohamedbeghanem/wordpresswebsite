<?php
if ( ! class_exists( 'WFFN_Email_Notification' ) ) {
	#[AllowDynamicProperties]
	class WFFN_Email_Notification {
		/**
		 * The single instance of the class.
		 *
		 * @var WFFN_Email_Notification
		 */
		protected static $instance = null;

		/**
		 * Global settings.
		 *
		 * @var array
		 */
		protected static $global_settings = array();
		/**
		 * Last executed notification.
		 *
		 * @var array
		 */
		protected static $executed_last = array();

		/**
		 * Load notification settings.
		 *
		 * @return array
		 */
		public static function load_settings() {
			BWF_Admin_General_Settings::get_instance()->setup_options();
			$bwf_enable_notification = BWF_Admin_General_Settings::get_instance()->get_option( 'bwf_enable_notification' );
			$frequency               = BWF_Admin_General_Settings::get_instance()->get_option( 'bwf_notification_frequency' );
			$users                   = BWF_Admin_General_Settings::get_instance()->get_option( 'bwf_notification_user_selector' );
			$external_users          = BWF_Admin_General_Settings::get_instance()->get_option( 'bwf_external_user' );
			$notification_time       = BWF_Admin_General_Settings::get_instance()->get_option( 'bwf_notification_time' );

			return [
				'bwf_enable_notification'        => $bwf_enable_notification,
				'bwf_notification_frequency'     => $frequency,
				'bwf_notification_user_selector' => $users,
				'bwf_external_user'              => $external_users,
				'bwf_notification_time'          => $notification_time,
			];
		}

		/**
		 * Get the instance of the class.
		 *
		 * @return WFFN_Email_Notification
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Retrieves the HTML content of a template.
		 *
		 * This method includes the specified template file and allows passing arguments to it.
		 *
		 * @param string $template The name of the template file to include.
		 * @param array $args Optional. An array of arguments to pass to the template file. Default is an empty array.
		 *
		 * @return string
		 */
		public static function get_template_html( $template, $args = array() ) {
			if ( ! empty( $args ) && is_array( $args ) ) {
				extract( $args ); // @codingStandardsIgnoreLine
			}

			ob_start();
			include WFFN_PLUGIN_DIR . '/' . $template;

			return ob_get_clean();// phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Create a timestamp from an array of time values.
		 *
		 * @param array $time_array An array of time values.
		 *
		 * @return int The timestamp or false if required keys are missing.
		 */
		public static function create_timestamp_from_array( $time_array ) {
			// Check if required keys exist in the array
			if ( isset( $time_array['hours'], $time_array['minutes'], $time_array['ampm'] ) ) {
				$hours   = intval( $time_array['hours'] );
				$minutes = intval( $time_array['minutes'] );
				$ampm    = strtolower( $time_array['ampm'] );

				if ( $ampm === 'am' && 12 === $hours ) {
					$hours = 0;
				} elseif ( $ampm === 'pm' && $hours < 12 ) {
					// Convert 12-hour format to 24-hour format
					$hours += 12;
				}

				return WFFN_Common::get_store_time( $hours, $minutes, 0 );
			}

			return WFFN_Common::get_store_time( 10 );
		}

		/**
		 * Runs the email notifications.
		 *
		 * @return void
		 */
		public static function run_notifications() {
			/** global settings */
			self::$global_settings = self::load_settings();
			if ( false === self::is_notification_active() ) {
				return;
			}

			$frequencies = self::get_frequencies();

			$frequencies = self::filter_frequencies( $frequencies );

			$frequencies = self::prepare_frequencies( $frequencies );
			if ( empty( $frequencies ) ) {
				return;
			}

			foreach ( $frequencies as $frequency => $dates ) {
				self::send_email( $frequency, $dates );
			}
		}

		/**
		 * Check if email notification is active.
		 *
		 * @return bool
		 */
		protected static function is_notification_active() {
			return isset( self::$global_settings['bwf_enable_notification'] ) && self::$global_settings['bwf_enable_notification'];
		}

		/**
		 * Get the frequencies for email notifications.
		 *
		 * @return array
		 */
		protected static function get_frequencies() {
			if ( isset( self::$global_settings['bwf_notification_frequency'] ) && is_array( self::$global_settings['bwf_notification_frequency'] ) ) {
				return self::$global_settings['bwf_notification_frequency'];
			}

			return array();
		}

		/**
		 * Filter the frequencies based on the last saved option key.
		 *
		 * @param array $frequencies The frequencies to filter.
		 *
		 * @return array The filtered frequencies.
		 */
		protected static function filter_frequencies( $frequencies = array() ) {
			if ( empty( $frequencies ) ) {
				return array();
			}

			/** Filter out the frequencies if an email was already sent */
			return array_filter( $frequencies, function ( $frequency ) {
				return ! self::mail_sent( $frequency );
			} );
		}

		/**
		 * Prepare frequencies
		 *
		 * @param $frequencies
		 *
		 * @return array
		 * @throws DateMalformedStringException
		 */
		public static function prepare_frequencies( $frequencies = [] ) {
			$final = array();

			if ( array_search( 'weekly', $frequencies ) !== false ) { // @codingStandardsIgnoreLine
				$final['weekly'] = WFFN_Common::get_notification_week_range();
			}


			if ( array_search( 'monthly', $frequencies ) !== false ) { // @codingStandardsIgnoreLine
				$final['monthly'] = WFFN_Common::get_notification_month_range();
			}

			return $final;
		}

		/**
		 * Check if the email was sent for the given frequency.
		 *
		 * @param string $frequency The frequency to check.
		 *
		 * @return bool True if the email was sent, false otherwise.
		 */
		public static function mail_sent( $frequency ) {
			$timezone = wp_timezone();
			$today    = new DateTime( 'now', $timezone );
			
			self::$executed_last = get_option( 'wffn_email_notification_updated', array(
				'weekly'  => '',
				'monthly' => '',
			) );

			/** Check if the last execution time for the given frequency is not set */
			if ( ! isset( self::$executed_last[ $frequency ] ) || empty( self::$executed_last[ $frequency ] ) ) {
				return false;
			}

			try {
				$last_sent = new DateTime( self::$executed_last[ $frequency ] );
			} catch ( Exception|Error $e ) {
				WFFN_Core()->logger->log( "Frequency {$frequency} and value " . self::$executed_last[ $frequency ], 'notification-error', true );
				WFFN_Core()->logger->log( "Exception {$e->getMessage()}", 'notification-error', true );

				return false;
			}

			switch ( $frequency ) {
				case 'weekly':
					return ! ( intval( $last_sent->format( 'oW' ) ) < intval( $today->format( 'oW' ) ) );

				case 'monthly':
					return ! ( intval( $last_sent->format( 'Ym' ) ) < intval( $today->format( 'Ym' ) ) );
				default:
					return false;
			}
		}

		/**
		 * Send email notification.
		 *
		 * @param $frequency
		 * @param $dates
		 *
		 * @return bool|mixed|void
		 */
		public static function send_email( $frequency, $dates ) {
			/** Prepare metrics */
			$metrics_controller = new WFFN_Notification_Metrics_Controller( $dates, $frequency );
			$metrics_controller->prepare_data();

			/** Check if email has data */
			if ( ! $metrics_controller->is_valid() ) {
				return;
			}

			$data             = $metrics_controller->get_data();
			$email_controller = new WFFN_Notification_Email_Controller( $frequency, $data, $dates );

			$to      = self::get_recipients();
			$subject = self::get_email_subject( $frequency, $dates );
			$body    = $email_controller->get_content_html();
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			if ( is_array( $to ) && count( $to ) > 0 ) {
				foreach ( $to as $recipient ) {
					$sent = wp_mail( $recipient, $subject, $body, $headers ); // @codingStandardsIgnoreLine
				}
			}

			// Update the last execution time if the email was sent.
			if ( isset( $sent ) && $sent ) {
				/** Fetch the saved notifications data */
				self::$executed_last               = get_option( 'wffn_email_notification_updated', array(
					'weekly'  => '',
					'monthly' => '',
				) );
				// Store current time in site timezone
				$now = new DateTime( 'now', wp_timezone() );
				self::$executed_last[ $frequency ] = $now->format( 'c' );
				update_option( 'wffn_email_notification_updated', self::$executed_last );
			}

		}

		/**
		 * Get the recipients for the email.
		 *
		 * @return array The recipients for the email.
		 */
		private static function get_recipients() {
			$recipients = [];

			if ( isset( self::$global_settings['bwf_notification_user_selector'] ) && is_array( self::$global_settings['bwf_notification_user_selector'] ) ) {
				foreach ( self::$global_settings['bwf_notification_user_selector'] as $user ) {
					if ( isset( $user['id'] ) && ! empty( $user['id'] ) ) {
						$user_data = get_userdata( $user['id'] );
						if ( $user_data ) {
							$recipients[] = $user_data->user_email;
						}
					}
				}
			}

			if ( isset( self::$global_settings['bwf_external_user'] ) && is_array( self::$global_settings['bwf_external_user'] ) ) {
				foreach ( self::$global_settings['bwf_external_user'] as $user ) {
					if ( isset( $user['mail'] ) && ! empty( $user['mail'] ) ) {
						$recipients[] = $user['mail'];
					}
				}
			}

			/** Filter array */
			$recipients = array_filter( $recipients, function ( $email ) {
				return ( strpos( $email, 'support@' ) === false );
			} );
			$recipients = array_unique( $recipients );

			return $recipients;
		}

		/**
		 * Get the email subject.
		 *
		 * @param string $frequency The frequency of the email.
		 * @param array $dates The dates to use in the email subject.
		 *
		 * @return string The email subject.
		 */
		public static function get_email_subject( $frequency, $dates ) {
			$date_string = self::get_date_string( $dates, $frequency );
			switch ( strtolower( $frequency ) ) {
				case 'weekly':
					return sprintf( __( '%s - Weekly Report for %s', 'FunnelKit' ), get_bloginfo( 'name' ), $date_string );//phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
				case 'monthly':
					return sprintf( __( '%s - Monthly Report for %s', 'FunnelKit' ), get_bloginfo( 'name' ), $date_string );//phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText

				default:
					return __( 'Report', 'FunnelKit' );//phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
			}
		}

		/**
		 * Get the date string for the email subject.
		 *
		 * @param array $dates The dates to use in the date string.
		 *
		 * @return string The date string.
		 */
		public static function get_date_string( $dates = array(), $frequency = 'weekly' ) {
			if ( 'daily' === $frequency && isset( $dates['from_date'] ) ) {
				return sprintf( __( '%1$s', 'Funnelkit' ), self::format_date( $dates['from_date'] ) );//phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
			}

			if ( isset( $dates['from_date'] ) && isset( $dates['to_date'] ) ) {
				return sprintf( __( '%1$s - %2$s', 'Funnelkit' ), self::format_date( $dates['from_date'] ), self::format_date( $dates['to_date'] ) );
			}

			return '';
		}

		/**
		 * Formats a date string to the desired format.
		 *
		 * @param string $date_string The date string to format.
		 *
		 * @return string The formatted date string.
		 */
		public static function format_date( $date_string ) {
			// Convert date string to a DateTime object
			$timezone = wp_timezone();
			$date = new DateTime( $date_string, $timezone );

			return $date->format( 'F j, Y' );
		}

		/**
		 * Testing email notification.
		 */
		public static function test_notification_admin() {
			if ( ! current_user_can( 'administrator' ) ) {
				return;
			}
			if ( ! isset( $_GET['wffn_email_preview'] ) ) { // @codingStandardsIgnoreLine
				return;
			}
			/** global settings */
			self::$global_settings = self::load_settings();
			$mode                  = filter_input( INPUT_GET, 'wffn_mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$mode                  = empty( $mode ) ? 'weekly' : $mode;

			switch ( $mode ) {
				case 'monthly':
					$range = WFFN_Common::get_notification_month_range();
					break;
				default:
					$range = WFFN_Common::get_notification_week_range();
					$mode  = 'weekly';
					break;
			}

			$dates = array(
				'from_date'          => $range['from_date'],
				'to_date'            => $range['to_date'],
				'from_date_previous' => $range['from_date_previous'],
				'to_date_previous'   => $range['to_date_previous'],
			);

			// Prepare metrics.
			$metrics_controller = new WFFN_Notification_Metrics_Controller( $dates, $mode );
			$metrics_controller->prepare_data();

			$data             = $metrics_controller->get_data();
			$email_controller = new WFFN_Notification_Email_Controller( $mode, $data, $dates );

			echo $email_controller->get_content_html(); // @codingStandardsIgnoreLine
			exit;
		}

		/**
		 * Save settings for email notification.
		 *
		 * @param array $options The options to save.
		 *
		 * @return array The response status.
		 */
		public static function save_settings( $options ) {
			$resp       = [];
			$get_config = get_option( 'bwf_gen_config', [] );
			foreach ( $options as $key => $value ) {
				$get_config[ $key ] = $value;
			}
			$general_settings = BWF_Admin_General_Settings::get_instance();
			$general_settings->update_global_settings_fields( $get_config );
			$resp['status'] = true;

			return $resp;
		}


		/**
		 * @hook over `fk_fb_every_day`
		 * Sets up the notification schedule if notifications are enabled.
		 *
		 * This method loads the notification settings, filters out frequencies
		 * if an email was already sent, and schedules a single event for the
		 * performance notification if not already scheduled.
		 *
		 * @return bool|void|WP_Error Returns false if no frequencies are left or if the event is already scheduled.
		 */
		public static function maybe_setup_notification_schedule() {
			/** global settings */
			self::$global_settings = self::load_settings();
			if ( empty( self::$global_settings['bwf_enable_notification'] ) ) {
				return;
			}
			$timezone = wp_timezone();
			$today    = new DateTime( 'now', $timezone );
			$frequencies = self::get_frequencies();

			/** Filter out the frequencies if an email was already sent */
			$frequencies = array_filter( $frequencies, function ( $frequency ) use ( $today ) {
				/** Case: weekly. Not Monday */
				if ( 'weekly' === $frequency && 1 === intval( $today->format( 'N' ) ) ) {
					/** 1 means Monday */
					return true;
				}

				/** Case: monthly. Not 1st */
				if ( 'monthly' === $frequency && 1 === intval( $today->format( 'd' ) ) ) {
					return true;
				}

				return false;
			} );
			if ( empty( $frequencies ) ) {
				return;
			}

			$notification_time = self::$global_settings['bwf_notification_time'];
			$desired_timestamp = self::create_timestamp_from_array( $notification_time );

			WFFN_Core()->logger->log( 'Scheduling notification - Current time: ' . current_time( 'Y-m-d H:i:s' ) . ' (' . current_time( 'timestamp' ) . ')', 'notification-debug', true );
			WFFN_Core()->logger->log( 'Scheduling notification - Desired time: ' . current_time( 'Y-m-d H:i:s', true, $desired_timestamp ) . ' (' . $desired_timestamp . ')', 'notification-debug', true );
			WFFN_Core()->logger->log( 'Scheduling notification - Time difference: ' . ( $desired_timestamp - current_time( 'timestamp' ) ) . ' seconds', 'notification-debug', true );

			if ( wp_next_scheduled( 'wffn_performance_notification' ) === false ) {
				$result = wp_schedule_single_event( $desired_timestamp, 'wffn_performance_notification' );
				if(function_exists('bwf_schedule_single_action')) {
					$desired_timestamp = $desired_timestamp + ( 30 * MINUTE_IN_SECONDS );
					bwf_schedule_single_action( $desired_timestamp, 'wffn_performance_notification' );
				}
				WFFN_Core()->logger->log( 'Scheduling result: ' . ( $result ? 'SUCCESS' : 'FAILED' ), 'notification-debug', true );
				return $result;
			}

			return false;
		}


	}
}