<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Class WFFN_Admin_Notifications
 * Handles All the methods about admin notifications
 */
if ( ! class_exists( 'WFFN_Admin_Notifications' ) ) {
	#[AllowDynamicProperties]
	class WFFN_Admin_Notifications {

		/**
		 * @var WFFN_Admin_Notifications|null
		 */

		private static $ins = null;
		public $notifs      = array();

		public function __construct() {
			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this, 'register_notices' ) );
			}
		}

		/**
		 * @return WFFN_Admin_Notifications|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		public function get_notifications() {
			$this->prepare_notifications();

			return $this->notifs;
		}

		public function get_bf_day_data( $day = 'bf', $return_timestamp = false ) {
			// Get the current year
			$year = gmdate( 'Y' );
			// Create a DateTime object for November 30 of the current year
			// When returning timestamp, work in ET timezone from the start to avoid date shift issues
			$timezone    = new DateTimeZone( $return_timestamp ? 'America/New_York' : 'UTC' );
			$blackFriday = new DateTime( "{$year}-11-30 00:00:00", $timezone );

			// Find the last Friday of November
			while ( $blackFriday->format( 'N' ) !== '5' ) {
				$blackFriday = $blackFriday->modify( '-1 day' );
			}

			// Initialize data variable to store the resulting date
			$data     = '';
			$dateTime = null;

			switch ( $day ) {
				case 'pre':
					// Pre-BFCM: 5 days before (ends 4 days before BF)
					$dateTime = clone $blackFriday;
					$dateTime->modify( $return_timestamp ? '-4 days' : '-5 days' );
					break;
				case 'sbs':
					// Small Business Saturday: 1 day after
					$dateTime = clone $blackFriday;
					$dateTime->modify( '+1 day' );
					break;
				case 'bfext':
					// BF Extended: 2 days after
					$dateTime = clone $blackFriday;
					$dateTime->modify( '+2 days' );
					break;
				case 'cm':
					// CM: 3 days after
					$dateTime = clone $blackFriday;
					$dateTime->modify( '+3 days' );
					break;
				case 'cmext':
					// CM Extended: 7 days after
					$dateTime = clone $blackFriday;
					$dateTime->modify( '+7 days' );
					break;
				default:
					// BF itself
					$dateTime = clone $blackFriday;
					break;
			}

			// Return timestamp or formatted date
			if ( $return_timestamp ) {
				$dateTime->setTime( 23, 59, 59 );
				return $dateTime->getTimestamp();
			} else {
				return $dateTime->format( 'M d' );
			}
		}

		public function show_pre_bfcm_header_notification() {
			// Get the difference in minutes between today and BF
			$blackFridayDifference = $this->get_bf_day_diff();
			// Check if the difference falls within the range for showing the notification
			// (-11 days in minutes to -4 days in minutes)
			if ( $blackFridayDifference >= - ( 11 * 1440 ) && $blackFridayDifference < - ( 4 * 1440 ) ) {
				return true;
			} else {
				return false;
			}
		}

		public function show_bf_header_notification() {
			// Get the difference in minutes between today and BF
			$blackFridayDifference = $this->get_bf_day_diff();

			// Check if the difference falls within the range for showing the notification
			// (-4 days in minutes to the day after BF)
			if ( $blackFridayDifference >= - ( 4 * 1440 ) && $blackFridayDifference < 1440 ) {
				return true;
			} else {
				return false;
			}
		}

		public function show_small_business_saturday_header_notification() {
			// Get the difference in minutes between today and BF
			$blackFridayDifference = $this->get_bf_day_diff();

			// Check if the difference falls within the range for showing the notification
			// (1 day to 2 days after BF)
			if ( $blackFridayDifference >= 1440 && $blackFridayDifference < 2880 ) {
				return true;
			} else {
				return false;
			}
		}


		public function show_bfext_header_notification() {
			// Get the difference in minutes between today and BF
			$blackFridayDifference = $this->get_bf_day_diff();

			// Check if the difference falls within the range for showing the notification
			// (2 days to 3 days after BF)
			if ( $blackFridayDifference >= 2880 && $blackFridayDifference < 4320 ) {
				return true;
			} else {
				return false;
			}
		}

		public function show_cm_header_notification() {
			// Get the difference in minutes between today and BF
			$blackFridayDifference = $this->get_bf_day_diff();

			// Check if the difference falls within the range for showing the notification
			// (3 days to 4 days after BF)
			if ( $blackFridayDifference >= 4320 && $blackFridayDifference < 5760 ) {
				return true;
			} else {
				return false;
			}
		}

		public function show_cmext_header_notification() {
			// Get the difference in minutes between today and BF
			$blackFridayDifference = $this->get_bf_day_diff();

			// Check if the difference falls within the range for showing the notification
			// (4 days to 8 days after BF)
			if ( $blackFridayDifference >= 5760 && $blackFridayDifference < 11520 ) {
				return true;
			} else {
				return false;
			}
		}

		public function show_green_monday_header_notification() {
			// Get the difference in minutes between today and the second Monday of December
			$secondDecMondayDayDiff = $this->get_second_dec_monday_day_diff();

			// Check if the difference falls within the range for showing the notification
			// (0 to 1 day after the second Monday of December)
			if ( $secondDecMondayDayDiff >= 0 && $secondDecMondayDayDiff < 1440 ) {
				return true;
			} else {
				return false;
			}
		}


		public function get_bf_day_diff() {
			// Set the timezone to 'America/New_York'
			$timezone = new DateTimeZone( 'America/New_York' );
			// Create DateTime object for today's date and time in the specified timezone
			$today = new DateTime( 'now', $timezone );

			// Get the current year
			$year = $today->format( 'Y' );
			// Start from November 30 at midnight in ET timezone to avoid date shift issues
			$blackFriday = new DateTime( "{$year}-11-30 00:00:00", $timezone );

			// Find the last Friday of November
			while ( $blackFriday->format( 'N' ) !== '5' ) {
				$blackFriday = $blackFriday->modify( '-1 day' );
			}

			// Calculate the difference in minutes between today and BF
			$differenceInMinutes = $today->getTimestamp() - $blackFriday->getTimestamp();
			$differenceInMinutes = round( $differenceInMinutes / 60 );

			return $differenceInMinutes;
		}

		public function get_second_dec_monday_day_diff( $diff = true, $return_timestamp = false ) {
			// Set the timezone to 'America/New_York'
			$timezone = new DateTimeZone( 'America/New_York' );
			// Get today's date and time in the specified timezone
			$today = new DateTime( 'now', $timezone );

			// Get the current year
			$year = $today->format( 'Y' );

			// Create a DateTime object for November 30 at midnight in ET timezone to avoid date shift
			$dateObj = new DateTime( "{$year}-11-30 00:00:00", $timezone );

			// Move to December 1
			$dateObj->modify( '+1 day' );

			// Get the day of the week (0 = Sunday, 1 = Monday, etc.)
			$dayOfWeek = (int) $dateObj->format( 'w' );

			// Calculate days to add to reach the first Monday of December
			// Monday is 1, so if Dec 1 is Monday (1), add 0 days
			// If Dec 1 is Sunday (0), add 1 day
			// If Dec 1 is any other day, add (8 - dayOfWeek) to reach next Monday
			$daysToAdd = ( 1 === $dayOfWeek ) ? 0 : ( ( 0 === $dayOfWeek ) ? 1 : ( 8 - $dayOfWeek ) );

			// Move to the first Monday of December
			$dateObj->modify( "+{$daysToAdd} days" );

			// Move to the second Monday of December
			$dateObj->modify( '+7 days' );

			if ( $diff ) {
				// Calculate the difference in minutes between today and the second Monday of December
				$differenceInMinutes = round( ( $today->getTimestamp() - $dateObj->getTimestamp() ) / 60 );

				return $differenceInMinutes;
			}
			if ( $return_timestamp ) {
				// Return timestamp at end of day
				$dateObj->setTime( 00, 00, 00 );
				$dateObj->modify( '+1 day' );

				return $dateObj->getTimestamp();
			}

			// Return the formatted date of the second Monday of December only
			return $dateObj->format( 'M d' );
		}

		private function get_notification_buttons( $campaign ) {
			return array(
				array(
					'label'     => __( 'Get FunnelKit PRO', 'funnel-builder' ),
					'href'      => add_query_arg(
						array(
							'utm_source'   => 'WordPress',
							'utm_medium'   => 'Notice+FKFB',
							'utm_campaign' => $campaign,
						),
						'https://funnelkit.com/exclusive-offer/'
					),
					'className' => 'is-primary',
					'target'    => '__blank',
				),
			);
		}

		private function add_notification( $key, $content ) {
			$this->notifs[] = array(
				'key'             => $key,
				'content'         => $content,
				'className'       => 'bwf-notif-bwfcm',
				'customButtons'   => $this->get_notification_buttons( 'BFCM' . gmdate( 'Y' ) ),
				'not_dismissible' => false,
				'index'           => 5,
			);
		}

		private function should_show_memory_limit_notice() {
			$memory_limit = get_option( 'fk_memory_limit', false );

			if ( $memory_limit !== false ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if sticky banner was recently dismissed
		 * Returns true if a transient exists (5-minute cooldown period)
		 *
		 * @return bool True if sticky banner was recently dismissed, false otherwise
		 */
		private function is_sticky_banner_recently_dismissed() {
			$user_id   = get_current_user_id();
			$transient = get_transient( 'wffn_sticky_banner_dismissed_' . $user_id );

			return false !== $transient;
		}

		/**
		 * Check if the Stripe 1.14 update notice should be shown
		 *
		 * @return bool True if notice should be shown, false otherwise
		 */
		private function should_show_stripe_1_14_notice() {
			// Check if user has dismissed this notification
			if ( $this->is_user_dismissed( get_current_user_id(), 'stripe_update_1_14_0' ) ) {
				return false;
			}

			// Check if WooCommerce is active and version >= 10.3.0
			if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '10.3.0', '<' ) ) {
				return false;
			}

			// Check if Stripe plugin is installed and version < 1.14.0
			if ( ! defined( 'FKWCS_VERSION' ) || version_compare( FKWCS_VERSION, '1.14.0', '>=' ) ) {
				return false;
			}

			return true;
		}

		public function memory_limit_notice() {
			$memory_limit    = get_option( 'fk_memory_limit', 0 );
			$memory_limit_mb = round( $memory_limit / 1048576, 2 );
			$admin_instance  = WFFN_Core()->admin;
			$recommended_mb  = round( $admin_instance->fk_memory_limit / 1048576, 2 );

			return '<div class="bwf-notifications-message current">
        <h3 class="bwf-notifications-title">' . __( 'Low PHP Memory Detected!', 'funnel-builder' ) . '</h3>
        <p class="bwf-notifications-content">' . sprintf( __( "We've detected that your site is currently running with only %1\$s MB of PHP memory, which is below the recommended %2\$s MB. This could potentially lead to performance issues or unexpected behavior on your site. To ensure smooth operation, please contact your hosting provider to increase the PHP memory limit.", 'funnel-builder' ), $memory_limit_mb, $recommended_mb ) . '</p>
    </div>';
		}

		public function lang_support_notice() {

			$plugin = WFFN_Plugin_Compatibilities::get_language_compatible_plugin();

			return '<div class="bwf-notifications-message current">
        <h3 class="bwf-notifications-title">' . sprintf( __( 'New Feature: Deep Compatibility with %s Plugin', 'funnel-builder' ), $plugin ) . '</h3>
        <p class="bwf-notifications-content">' . sprintf( __( 'We’ve detected that your site is using %s. You can now configure languages for each funnel step directly within the Languages tab. For optimal performance, we recommend disabling any custom snippets you’ve configured. ', 'funnel-builder' ), $plugin ) . '</p>
    </div>';
		}

		/**
		 * Get campaign end timestamp
		 * Returns the Unix timestamp when the current campaign expires
		 * Uses existing get_bf_day_data() and get_second_dec_monday_day_diff() methods
		 *
		 * @param string $campaign_slug Campaign identifier
		 * @return int Unix timestamp of campaign end time (23:59:59 ET)
		 */
		private function get_campaign_end_timestamp( $campaign_slug ) {
			// Map campaign slug to day parameter for get_bf_day_data()
			$day_map = array(
				'pre_bfcm' => 'pre',
				'bfcm'     => 'bf',
				'sbs'      => 'sbs',
				'bfext'    => 'bfext',
				'cm'       => 'cm',
				'cmext'    => 'cmext',
			);

			// Check if it's a BF related campaign
			if ( isset( $day_map[ $campaign_slug ] ) ) {
				return $this->get_bf_day_data( $day_map[ $campaign_slug ], true );
			}

			// Handle Green Monday separately using existing method
			if ( 'gm' === $campaign_slug ) {
				return $this->get_second_dec_monday_day_diff( false, true );
			}

			// Fallback: return current time + 1 day
			$timezone = new DateTimeZone( 'America/New_York' );
			$endTime  = new DateTime( 'now', $timezone );
			$endTime->modify( '+1 day' );
			$endTime->setTime( 23, 59, 59 );

			return $endTime->getTimestamp();
		}

		/**
		 * Get sticky banner data for promotional campaigns
		 * Returns campaign-specific data with unique key and data attributes
		 *
		 * @return array|null Array with banner data or null if no campaign is active
		 */
		public function get_sticky_banner_data() {
			$campaign_data = null;
			$campaign_key  = '';
			$campaign_slug = '';

			// Check which campaign is active and get the data
			if ( $this->show_pre_bfcm_header_notification() ) {
				$campaign_data = $this->promo_pre_bfcm( false );
				$campaign_slug = 'pre_bfcm';
				$campaign_key  = 'sticky_pre_bfcm_' . gmdate( 'Y' );
			} elseif ( $this->show_bf_header_notification() ) {
				$campaign_data = $this->promo_bfcm( false );
				$campaign_slug = 'bfcm';
				$campaign_key  = 'sticky_bfcm_' . gmdate( 'Y' );
			} elseif ( $this->show_small_business_saturday_header_notification() ) {
				$campaign_data = $this->promo_small_business_saturday( false );
				$campaign_slug = 'sbs';
				$campaign_key  = 'sticky_sbs_' . gmdate( 'Y' );
			} elseif ( $this->show_bfext_header_notification() ) {
				$campaign_data = $this->promo_ext_bfcm( false );
				$campaign_slug = 'bfext';
				$campaign_key  = 'sticky_bfext_' . gmdate( 'Y' );
			} elseif ( $this->show_cm_header_notification() ) {
				$campaign_data = $this->promo_cmonly( false );
				$campaign_slug = 'cm';
				$campaign_key  = 'sticky_cm_' . gmdate( 'Y' );
			} elseif ( $this->show_cmext_header_notification() ) {
				$campaign_data = $this->promo_ext_cmonly( false );
				$campaign_slug = 'cmext';
				$campaign_key  = 'sticky_cmext_' . gmdate( 'Y' );
			} elseif ( $this->show_green_monday_header_notification() ) {
				$campaign_data = $this->promo_gm( false );
				$campaign_slug = 'gm';
				$campaign_key  = 'sticky_gm_' . gmdate( 'Y' );
			}

			// If no campaign is active, return null
			if ( null === $campaign_data ) {
				return null;
			}

			// Get campaign end timestamp for countdown timer
			$end_timestamp     = $this->get_campaign_end_timestamp( $campaign_slug );
			$remaining_seconds = max( 0, $end_timestamp - time() );

			// Return structured data for sticky banner
			// Note: Unix timestamps are always in UTC by definition
			return array(
				'key'               => $campaign_key,
				'title'             => $campaign_data['title'],
				'content'           => $campaign_data['content'],
				'date'              => $campaign_data['date'],
				'campaign'          => $campaign_slug,
				'timestamp'         => $end_timestamp,
				'year'              => gmdate( 'Y' ),
				'remaining_seconds' => $remaining_seconds,
			);
		}

		/**
		 * Get sticky banner HTML (backward compatibility wrapper)
		 * Use get_sticky_banner() for new implementations
		 *
		 * @return string HTML markup or empty string if no campaign is active
		 */
		public function get_sticky_banner_html() {
			$banner = $this->get_sticky_banner();

			return $banner ? $banner['html'] : '';
		}

		/**
		 * Get secondary sticky banner for promotional campaigns
		 * Returns combined object with HTML and metadata for JavaScript countdown timer
		 * Uses a different design and separate dismiss keys from main banner
		 *
		 * Return structure:
		 * - html: Complete HTML markup for the secondary banner
		 * - timestamp_utc: Unix timestamp of campaign end (UTC - all Unix timestamps are UTC by definition)
		 * - remaining_seconds: Total seconds remaining until campaign ends
		 * - key: Unique dismissal key for this campaign (secondary_sticky_*)
		 *
		 * @return array|null Array with banner data or null if no campaign is active or already dismissed
		 */
		public function get_sticky_secondary_banner() {

			if ( defined( 'WFFN_PRO_VERSION' ) ) {
				return null;
			}

			// Get current campaign data
			$banner_data = $this->get_sticky_banner_data();

			// Return null if no campaign is active
			if ( null === $banner_data ) {
				return null;
			}

			// Use secondary key for separate dismissal tracking
			$secondary_key = 'secondary_' . $banner_data['key'];

			// Check if user has already dismissed this specific secondary banner
			if ( $this->is_user_dismissed( get_current_user_id(), $secondary_key ) ) {
				return null;
			}

			// Generate dismiss URL with secondary key
			$current_admin_url = isset( $_SERVER['REQUEST_URI'] ) ? basename( wffn_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$dismiss_url       = admin_url( 'admin-ajax.php?action=wffn_dismiss_notice&nkey=' . $secondary_key . '&nonce=' . wp_create_nonce( 'wp_wffn_dismiss_notice' ) . '&redirect=' . $current_admin_url );

			// Generate CTA URL with UTM parameters
			$cta_url = add_query_arg(
				array(
					'utm_source'   => 'WordPress',
					'utm_medium'   => 'Steps+Listing+Notice+Bar',
					'utm_campaign' => 'BFCM' . $banner_data['year'],
				),
				'https://funnelkit.com/exclusive-offer/'
			);

			// Build HTML with data attributes for countdown timer
			// Note: data-timestamp-utc is Unix timestamp (UTC) for JavaScript Date compatibility
			$html  = '<div class="bwf-lite-noticebar bwf-sale-notice" ';
			$html .= 'data-campaign="' . esc_attr( $banner_data['campaign'] ) . '" ';
			$html .= 'data-timestamp-utc="' . esc_attr( $banner_data['timestamp'] ) . '" ';
			$html .= 'data-key="' . esc_attr( $secondary_key ) . '" ';
			$html .= 'data-year="' . esc_attr( $banner_data['year'] ) . '" ';
			$html .= 'data-remaining-seconds="' . esc_attr( $banner_data['remaining_seconds'] ) . '">';

			// Banner container
			$html .= '<div class="bwf-noticebar-conatiner">';

			// Banner content with dynamic title
			$html .= '<div class="bwf-noticebar-content">';
			$html .= wp_kses_post( $banner_data['title'] );
			$html .= '</div>';

			// CTA Button
			$html .= '<a href="' . esc_url( $cta_url ) . '" class="bwf-noticebar-btn" target="_blank" rel="noreferrer">';
			$html .= esc_html__( 'Claim My Discount', 'funnel-builder' );
			$html .= '</a>';

			$html .= '</div>';

			// Close button will be handled by JavaScript

			$html .= '</div>';

			// Return combined object with HTML and metadata
			return array(
				'html'              => $html,
				'timestamp_utc'     => $banner_data['timestamp'],
				'remaining_seconds' => $banner_data['remaining_seconds'],
				'key'               => $secondary_key,
			);
		}

		/**
		 * Get secondary sticky banner HTML (backward compatibility wrapper)
		 * Use get_sticky_secondary_banner() for new implementations
		 *
		 * @return string HTML markup or empty string if no campaign is active
		 */
		public function get_sticky_secondary_banner_html() {
			$banner = $this->get_sticky_secondary_banner();

			return $banner ? $banner['html'] : '';
		}

		public function get_promo_remaining_time() {
			$campaign_data = $this->get_sticky_banner_data();
			if ( null === $campaign_data ) {
				return 0;
			}
			return $campaign_data['remaining_seconds'];
		}
		/**
		 * Get sticky banner for promotional campaigns
		 * Returns combined object with HTML and metadata for JavaScript countdown timer
		 *
		 * Return structure:
		 * - html: Complete HTML markup for the banner
		 * - timestamp_utc: Unix timestamp of campaign end (UTC - all Unix timestamps are UTC by definition)
		 * - remaining_seconds: Total seconds remaining until campaign ends
		 * - key: Unique dismissal key for this campaign
		 *
		 * @return array|null Array with banner data or null if no campaign is active or already dismissed
		 */
		public function get_sticky_banner() {

			if ( defined( 'WFFN_PRO_VERSION' ) ) {
				return null;
			}

			// Get current campaign data
			$banner_data = $this->get_sticky_banner_data();

			// Return null if no campaign is active
			if ( null === $banner_data ) {
				return null;
			}

			// Check if user has already dismissed this specific campaign banner
			if ( $this->is_user_dismissed( get_current_user_id(), $banner_data['key'] ) ) {
				return null;
			}

			// Generate dismiss URL
			$current_admin_url = isset( $_SERVER['REQUEST_URI'] ) ? basename( wffn_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$dismiss_url       = admin_url( 'admin-ajax.php?action=wffn_dismiss_notice&nkey=' . $banner_data['key'] . '&nonce=' . wp_create_nonce( 'wp_wffn_dismiss_notice' ) . '&redirect=' . $current_admin_url );

			// Generate CTA URL with UTM parameters
			$cta_url = add_query_arg(
				array(
					'utm_source'   => 'WordPress',
					'utm_medium'   => 'Sticky+Banner+Countdown',
					'utm_campaign' => 'BFCM' . $banner_data['year'],
				),
				'https://funnelkit.com/exclusive-offer/'
			);

			// Build HTML with data attributes for countdown timer
			// Note: data-timestamp is Unix timestamp (UTC) for JavaScript Date compatibility
			$html  = '<div class="fk-sale-banner" ';
			$html .= 'data-campaign="' . esc_attr( $banner_data['campaign'] ) . '" ';
			$html .= 'data-timestamp-utc="' . esc_attr( $banner_data['timestamp'] ) . '" ';
			$html .= 'data-key="' . esc_attr( $banner_data['key'] ) . '" ';
			$html .= 'data-year="' . esc_attr( $banner_data['year'] ) . '" ';
			$html .= 'data-remaining-seconds="' . esc_attr( $banner_data['remaining_seconds'] ) . '">';

			// Banner content with dynamic title
			$html .= '<div class="fk-sale-banner-content">';
			$html .= '<span class="fk-sale-banner-text">';
			$html .= wp_kses_post( $banner_data['title'] );
			$html .= '</span>';
			$html .= '</div>';

			// Countdown timer
			$html .= '<div class="fk-countdown-container">';

			// Days
			$html .= '<div class="fk-countdown-item">';
			$html .= '<div class="fk-countdown-item-value-wrapper">';
			$html .= '<div class="fk-countdown-item-value" id="fk-days-value">00</div>';
			$html .= '<div class="fk-countdown-item-label">' . esc_html__( 'Days', 'funnel-builder' ) . '</div>';
			$html .= '</div>';
			$html .= '</div>';

			// Hours
			$html .= '<div class="fk-countdown-item">';
			$html .= '<div class="fk-countdown-item-value-wrapper">';
			$html .= '<div class="fk-countdown-item-value" id="fk-hours-value">00</div>';
			$html .= '<div class="fk-countdown-item-label">' . esc_html__( 'Hrs', 'funnel-builder' ) . '</div>';
			$html .= '</div>';
			$html .= '</div>';

			// Minutes
			$html .= '<div class="fk-countdown-item">';
			$html .= '<div class="fk-countdown-item-value-wrapper">';
			$html .= '<div class="fk-countdown-item-value" id="fk-minutes-value">00</div>';
			$html .= '<div class="fk-countdown-item-label">' . esc_html__( 'Mins', 'funnel-builder' ) . '</div>';
			$html .= '</div>';
			$html .= '</div>';

			// Seconds
			$html .= '<div class="fk-countdown-item">';
			$html .= '<div class="fk-countdown-item-value-wrapper">';
			$html .= '<div class="fk-countdown-item-value" id="fk-seconds-value">00</div>';
			$html .= '<div class="fk-countdown-item-label">' . esc_html__( 'Secs', 'funnel-builder' ) . '</div>';
			$html .= '</div>';
			$html .= '</div>';

			$html .= '</div>';

			// CTA Button
			$html .= '<button class="fk-sale-banner-button" onclick="window.open(\'' . esc_url( $cta_url ) . '\', \'_blank\')">';
			$html .= esc_html__( 'Claim My Discount', 'funnel-builder' );
			$html .= '</button>';

			$html .= '</div>';

			// Return combined object with HTML and metadata
			return array(
				'html'              => $html,
				'timestamp_utc'     => $banner_data['timestamp'],
				'remaining_seconds' => $banner_data['remaining_seconds'],
				'key'               => $banner_data['key'],
			);
		}

		public function prepare_notifications() {

			if ( ! defined( 'WFFN_PRO_VERSION' ) ) {
				// Check if sticky banner is currently being shown
				// If banner is visible, don't show notifications (they should never appear together)
				$sticky_banner     = $this->get_sticky_banner();
				$banner_is_visible = null !== $sticky_banner;

				// Also check if sticky banner was recently dismissed (5-minute cooldown)
				// Notifications only show after banner is dismissed AND cooldown period expires
				if ( ! $banner_is_visible && ! $this->is_sticky_banner_recently_dismissed() ) {
					$year = gmdate( 'Y' );

					// Each campaign has a unique key to allow independent dismissal
					if ( $this->show_pre_bfcm_header_notification() ) {
						$this->add_notification( 'promo_bf_pre_' . $year, $this->promo_pre_bfcm() );
					} elseif ( $this->show_bf_header_notification() ) {
						$this->add_notification( 'promo_bf_' . $year, $this->promo_bfcm() );
					} elseif ( $this->show_small_business_saturday_header_notification() ) {
						$this->add_notification( 'promo_bf_sbs_' . $year, $this->promo_small_business_saturday() );
					} elseif ( $this->show_bfext_header_notification() ) {
						$this->add_notification( 'promo_bf_bfext_' . $year, $this->promo_ext_bfcm() );
					} elseif ( $this->show_cm_header_notification() ) {
						$this->add_notification( 'promo_bf_cm_' . $year, $this->promo_cmonly() );
					} elseif ( $this->show_cmext_header_notification() ) {
						$this->add_notification( 'promo_bf_cmext_' . $year, $this->promo_ext_cmonly() );
					}

					// Show Green Monday notification independently
					if ( $this->show_green_monday_header_notification() ) {
						$this->add_notification( 'promo_bf_gm_' . $year, $this->promo_gm() );
					}
				}
			}

			if ( $this->should_show_stripe_1_14_notice() ) {

				$this->notifs[] = array(
					'key'           => 'stripe_update_1_14_0',
					'content'       => $this->stripe_1_14_notice(),
					'customButtons' => array(
						array(
							'label'     => __( 'Update Now', 'funnel-builder' ),
							'href'      => admin_url( 'plugins.php?s=funnelkit-stripe-woo-payment-gateway' ),
							'className' => 'is-primary',
							'target'    => '__blank',
						),

					),
					'index'         => 1,
				);
			}
			if ( WFFN_Core()->admin->is_update_available() ) {
				$version        = WFFN_Core()->admin->is_update_available();
				$this->notifs[] = array(
					'key'           => 'fb_update_' . str_replace( '.', '_', $version ),
					'content'       => $this->update_available( $version ),
					'customButtons' => array(
						array(
							'label'     => __( 'Update Now', 'funnel-builder' ),
							'href'      => admin_url( 'plugins.php?s=FunnelKit+Funnel+Builder' ),
							'className' => 'is-primary',
							'target'    => '__blank',
						),
						array(
							'label'  => __( 'Learn more', 'funnel-builder' ),
							'href'   => 'https://funnelkit.com/whats-new/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Update+Notice+Bar',
							'target' => '__blank',
						),
					),
					'index'         => 10,
				);
			}

			$state_for_migration = $this->is_conversion_migration_required();
			if ( defined( 'WFFN_PRO_FILE' ) ) {
				if ( 1 === $state_for_migration ) {
					$this->notifs[] = array(
						'key'             => 'conversion_migration',
						'content'         => $this->conversion_migration_content( $state_for_migration ),
						'customButtons'   => array(
							array(
								'label'     => __( 'Upgrade Database', 'funnel-builder' ),
								'action'    => 'api',
								'path'      => '/migrate-conversion/',
								'className' => 'is-primary',
							),

						),
						'not_dismissible' => true,
						'index'           => 15,
					);

				} elseif ( 2 === $state_for_migration ) {
					$this->notifs[] = array(
						'key'             => 'conversion_migration',
						'content'         => $this->conversion_migration_content( $state_for_migration ),
						'customButtons'   => array(),
						'not_dismissible' => true,
						'index'           => 15,
					);
				} elseif ( 3 === $state_for_migration ) {

					$this->notifs[] = array(
						'key'           => 'conversion_migration',
						'content'       => $this->conversion_migration_content( $state_for_migration ),
						'customButtons' => array(
							array(
								'label'     => __( 'Dismiss', 'funnel-builder' ),
								'action'    => 'close_notice',
								'className' => 'is-primary',
							),
						),
						'index'         => 20,
					);
				}
			}

			if ( $this->should_show_memory_limit_notice() ) {
				$this->notifs[] = array(
					'key'           => 'low_memory_limit',
					'content'       => $this->memory_limit_notice(),
					'customButtons' => array(

						array(
							'label'     => __( 'I have already done this', 'funnel-builder' ),
							'action'    => 'api',
							'path'      => '/notifications/memory_notice_dismiss',
							'className' => 'is-primary',
						),
						array(
							'label'  => __( 'Ignore', 'funnel-builder' ),
							'action' => 'close_notice',
						),
					),
					'index'         => 25,

				);
			}

			if ( WFFN_Core()->admin->is_language_support_enabled() ) {
				$this->notifs[] = array(
					'key'           => 'lang_support',
					'content'       => $this->lang_support_notice(),
					'customButtons' => array(
						array(
							'label'     => __( 'Learn more', 'funnel-builder' ),
							'href'      => 'https://funnelkit.com/funnel-builder-3-11-0/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Notice+Bar',
							'className' => 'is-primary',
							'target'    => '__blank',
						),
						array(
							'label'  => __( 'Dismiss', 'funnel-builder' ),
							'action' => 'close_notice',
						),
					),
					'index'         => 30,
				);
			}
		}

		public function stripe_1_14_notice() {
			return '<div class="bwf-notifications-message current">
					<h3 class="bwf-notifications-title">' . __( 'FunnelKit Stripe Update Required for WooCommerce v10.3.0 Compatibility', 'funnel-builder' ) . '</h3>
					<p class="bwf-notifications-content">' . __( 'We’ve detected that your store is running WooCommerce 10.3 or above. To ensure seamless performance, please update FunnelKit WooCommerce Stripe gateway to the latest version (v1.14.0).', 'funnel-builder' ) . '</p>
				</div>';
		}
		public function brandchange() {
			return '<div class="bwf-notifications-message current">
					<h3 class="bwf-notifications-title">' . __( 'Alert! WooFunnels is now FunnelKit', 'funnel-builder' ) . '</h3>
					<p class="bwf-notifications-content">' . __( 'We are proud to announce that WooFunnels is now called FunnelKit. Only the name changes, everything else remains the same.', 'funnel-builder' ) . '</p>
				</div>';
		}

		public function store_checkout_migrated() {
			return '<div class="bwf-notifications-message current">
					<h3 class="bwf-notifications-title">' . __( 'Global Checkout has been migrated to Store Checkout!', 'funnel-builder' ) . '</h3>
					<p class="bwf-notifications-content">' . __( "To make your storefront's more accessible, we have migrated Global Checkout. All the steps of the checkout are available under Store Checkout.", 'funnel-builder' ) . '</p>
				</div>';
		}

		public function pro_update_3_0() {
			return '<div class="bwf-notifications-message current">
					<h3 class="bwf-notifications-title">' . __( 'Update Funnel Builder Pro to version 3.0', 'funnel-builder' ) . '</h3>
					<p class="bwf-notifications-content">' . __( 'It seems that you are running an older version of Funnel Builder Pro. For a smoother experience, update Funnel Builder Pro to version 3.0.', 'funnel-builder' ) . '</p>
				</div>';
		}


		public function promo_pre_bfcm( $html = true ) {
			$title   = __( '🔥 Pre Black Friday Deal: Get FunnelKit Pro for up to 55% OFF 🔥', 'funnel-builder' );
			$content = sprintf( __( 'Get started using FunnelKit to grow your revenue today for up to 55%% OFF! Get access to money-making solutions like Conversion Optimized Checkout, One Click Upsells, Order Bumps, Analytics, A/B Testing and much more! Expires Sunday, %s, at midnight ET.', 'funnel-builder' ), $this->get_bf_day_data( 'pre' ) );

			if ( $html === false ) {
				return array(
					'title'   => $title,
					'content' => $content,
					'date'    => $this->get_bf_day_data( 'pre' ),
				);
			}

			return '<div class="bwf-notifications-message current">
                <h3 class="bwf-notifications-title">' . $title . '</h3>
                <p class="bwf-notifications-content">' . $content . '</p>
            </div>';
		}

		public function promo_bfcm( $html = true ) {
			$title   = __( '🔥 Black Friday Deal: Get FunnelKit Pro for up to 55% OFF 🔥', 'funnel-builder' );
			$content = sprintf( __( 'Get started using FunnelKit to grow your revenue today for up to 55%% OFF! Get access to money-making solutions like Conversion Optimized Checkout, One Click Upsells, Order Bumps, Analytics, A/B Testing and much more! Expires Friday, %s, at midnight ET.', 'funnel-builder' ), $this->get_bf_day_data( 'bf' ) );

			if ( $html === false ) {
				return array(
					'title'   => $title,
					'content' => $content,
					'date'    => $this->get_bf_day_data( 'bf' ),
				);
			}

			return '<div class="bwf-notifications-message current">
                <h3 class="bwf-notifications-title">' . $title . '</h3>
                <p class="bwf-notifications-content">' . $content . '</p>
            </div>';
		}

		public function promo_small_business_saturday( $html = true ) {
			$title   = __( '🔥 Small Business Saturday Deal: Get FunnelKit Pro for up to 55% OFF 🔥', 'funnel-builder' );
			$content = sprintf( __( 'Get started using FunnelKit to grow your revenue today for up to 55%% OFF! Get access to money-making solutions like Conversion Optimized Checkout, One Click Upsells, Order Bumps, Analytics, A/B Testing and much more! Expires Saturday, %s, at midnight ET.', 'funnel-builder' ), $this->get_bf_day_data( 'sbs' ) );

			if ( $html === false ) {
				return array(
					'title'   => $title,
					'content' => $content,
					'date'    => $this->get_bf_day_data( 'sbs' ),
				);
			}

			return '<div class="bwf-notifications-message current">
                <h3 class="bwf-notifications-title">' . $title . '</h3>
                <p class="bwf-notifications-content">' . $content . '</p>
            </div>';
		}

		public function promo_ext_bfcm( $html = true ) {
			$title   = __( '🔥 Black Friday Extended Deal: Get FunnelKit Pro for up to 55% OFF 🔥', 'funnel-builder' );
			$content = sprintf( __( 'Get started using FunnelKit to grow your revenue today for up to 55%% OFF! Get access to money-making solutions like Conversion Optimized Checkout, One Click Upsells, Order Bumps, Analytics, A/B Testing and much more! Expires Sunday, %s, at midnight ET.', 'funnel-builder' ), $this->get_bf_day_data( 'bfext' ) );

			if ( $html === false ) {
				return array(
					'title'   => $title,
					'content' => $content,
					'date'    => $this->get_bf_day_data( 'bfext' ),
				);
			}

			return '<div class="bwf-notifications-message current">
                <h3 class="bwf-notifications-title">' . $title . '</h3>
                <p class="bwf-notifications-content">' . $content . '</p>
            </div>';
		}

		public function promo_cmonly( $html = true ) {
			$title   = __( '🔥 Cyber Monday Deal: Get FunnelKit Pro for up to 55% OFF 🔥', 'funnel-builder' );
			$content = sprintf( __( 'Get started using FunnelKit to grow your revenue today for up to 55%% OFF! Get access to money-making solutions like Conversion Optimized Checkout, One Click Upsells, Order Bumps, Analytics, A/B Testing and much more! Expires Monday, %s, at midnight ET.', 'funnel-builder' ), $this->get_bf_day_data( 'cm' ) );

			if ( $html === false ) {
				return array(
					'title'   => $title,
					'content' => $content,
					'date'    => $this->get_bf_day_data( 'cm' ),
				);
			}

			return '<div class="bwf-notifications-message current">
                <h3 class="bwf-notifications-title">' . $title . '</h3>
                <p class="bwf-notifications-content">' . $content . '</p>
            </div>';
		}

		public function promo_ext_cmonly( $html = true ) {
			$title   = __( '🔥 Cyber Monday Extended Deal: Get FunnelKit Pro for up to 55% OFF 🔥', 'funnel-builder' );
			$content = sprintf( __( 'Get started using FunnelKit to grow your revenue today for up to 55%% OFF! Get access to money-making solutions like Conversion Optimized Checkout, One Click Upsells, Order Bumps, Analytics, A/B Testing and much more! Expires Friday, %s, at midnight ET.', 'funnel-builder' ), $this->get_bf_day_data( 'cmext' ) );

			if ( $html === false ) {
				return array(
					'title'   => $title,
					'content' => $content,
					'date'    => $this->get_bf_day_data( 'cmext' ),
				);
			}

			return '<div class="bwf-notifications-message current">
                <h3 class="bwf-notifications-title">' . $title . '</h3>
                <p class="bwf-notifications-content">' . $content . '</p>
            </div>';
		}

		public function promo_gm( $html = true ) {
			$title   = __( '🔥 Green Monday Deal: Get FunnelKit Pro for up to 55% OFF 🔥', 'funnel-builder' );
			$content = sprintf( __( 'Get started using FunnelKit to grow your revenue today for up to 55%% OFF! Get access to money-making solutions like Conversion Optimized Checkout, One Click Upsells, Order Bumps, Analytics, A/B Testing and much more! Expires Monday, %s, at midnight ET.', 'funnel-builder' ), $this->get_second_dec_monday_day_diff( false ) );

			if ( $html === false ) {
				return array(
					'title'   => $title,
					'content' => $content,
					'date'    => $this->get_second_dec_monday_day_diff( false ),
				);
			}

			return '<div class="bwf-notifications-message current">
                <h3 class="bwf-notifications-title">' . $title . '</h3>
                <p class="bwf-notifications-content">' . $content . '</p>
            </div>';
		}


		public function update_available( $version = '0.0.0' ) {
			return '<div class="bwf-notifications-message current">
					<h3 class="bwf-notifications-title">' . sprintf( 'Alert! New version %s is available for update', $version ) . '</h3>
					<p class="bwf-notifications-content">' . __( "Don't miss out on the latest features, bug fixes & security enhancements! Upgrade to the latest version and do not let an outdated version hold you back.", 'funnel-builder' ) . '</p>
				</div>';
		}

		public function conversion_migration_content( $state ) {

			if ( 1 === $state ) {
				$header = __( 'Funnel Builder requires a Database upgrade', 'funnel-builder' );
			} elseif ( 2 === $state ) {
				$header = __( 'Funnel Builder Database upgrade started', 'funnel-builder' );

				$identifier = 'bwf_conversion_1_migrator_cron';
				if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wffn_conversion_tracking_migrator' ) && ! wp_next_scheduled( $identifier ) ) {
					WFFN_Conversion_Tracking_Migrator::get_instance()->push_to_queue( 'wffn_run_conversion_migrator' );
					WFFN_Conversion_Tracking_Migrator::get_instance()->dispatch();
					WFFN_Conversion_Tracking_Migrator::get_instance()->save();
				}
			} else {
				$header = __( 'Funnel Builder Database upgrade completed', 'funnel-builder' );
			}

			return '<div class="bwf-notifications-message current">
					<h3 class="bwf-notifications-title">' . $header . '</h3>
					<p class="bwf-notifications-content">' . __( "To keep things running smoothly, we have to update the database to the newest version. The database upgrade runs in the background and may take a while depending upon the number of Orders, so please be patient. If you need any help <a target='_blank' href='http://funnelkit.com/support/'>contact support</a>.", 'funnel-builder' ) . '</p>
				</div>';
		}


		public function filter_notifs( $all_registered_notifs, $id ) {
			$userdata = get_user_meta( $id, '_bwf_notifications_close', true ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_get_user_meta
			if ( empty( $userdata ) ) {
				return $all_registered_notifs;
			}

			foreach ( $all_registered_notifs as $k => $notif ) {
				if ( ! in_array( $notif['key'], $userdata, true ) ) {
					continue;
				}
				unset( $all_registered_notifs[ $k ] );
			}

			return $all_registered_notifs;
		}

		public function user_has_notifications( $id ) {
			$all_registered_notifs = $this->get_notifications();

			$filter_notifs = $this->filter_notifs( $all_registered_notifs, $id );

			return count( $filter_notifs ) > 0 ? true : false;
		}

		public function is_user_dismissed( $id, $key ) {
			$userdata = get_user_meta( $id, '_bwf_notifications_close', true );
			$userdata = empty( $userdata ) && ! is_array( $userdata ) ? array() : $userdata;

			return in_array( $key, $userdata, true );
		}

		public function register_notices() {
			$user = WFFN_Role_Capability::get_instance()->user_access( 'menu', 'read' );
			if ( ! $user ) {
				return;
			}

			$this->show_setup_wizard();
			$this->show_stripe_update_notice();
		}


		public function show_stripe_update_notice() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			$allowed_screens = array(
				'woofunnels_page_bwf_funnels',
				'dashboard',
				'plugins',
			);
			if ( ! in_array( $screen_id, $allowed_screens, true ) ) {
				return;
			}

			// Check if the Stripe 1.14 update notice should be shown
			if ( ! $this->should_show_stripe_1_14_notice() ) {
				return;
			}
			$plugin_update_url = admin_url( 'plugins.php?s=funnelkit-stripe-woo-payment-gateway' );
			$current_admin_url = basename( wffn_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$dismiss_url       = admin_url( 'admin-ajax.php?action=wffn_dismiss_notice&nkey=stripe_update_1_14_0&nonce=' . wp_create_nonce( 'wp_wffn_dismiss_notice' ) . '&redirect=' . $current_admin_url );

			if ( true ) { ?>


				<div class="notice notice-warning" style="position: relative;">

				<a class="notice-dismiss" style="
	position: absolute;
	padding: 5px 15px 5px 35px;
	font-size: 13px;
	line-height: 1.2311961000;
	text-decoration: none;
	display: inline-flex;
	top: 12px;
	" href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss' ); ?></a>

<div style="display: flex; align-items: center; margin-bottom: 12px; margin-top:9px;">
	<div class="bwf-notification-icon" style="background: rgba(171,173,191,.3); height: 48px; width: 48px; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; margin-right: 12px; flex-shrink: 0;" bis_skin_checked="1">
		<div style="height: 28px;" bis_skin_checked="1">
			<svg width="23" height="28" viewBox="0 0 23 28" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M20.3699 17.5822C19.0675 16.1609 18.4829 14.7124 18.4829 12.9383L18.4827 10.9309C18.4807 9.71178 18.1594 8.51518 17.5517 7.46305C16.9441 6.41097 16.0717 5.5413 15.0237 4.94245V4.1077C15.0237 2.87853 14.3765 1.74254 13.3258 1.12785C12.2753 0.513374 10.9809 0.513374 9.93047 1.12785C8.87973 1.74254 8.23256 2.87853 8.23256 4.1077V4.99172C6.09647 6.29603 4.78562 8.634 4.77361 11.1611V12.9413C4.77361 14.7154 4.18896 16.161 2.88661 17.5852H2.8864C2.19379 17.6298 1.54391 17.9404 1.06954 18.4538C0.595 18.9671 0.3315 19.6445 0.333014 20.3478V21.0569V21.0567C0.333014 21.7894 0.620151 22.4921 1.13148 23.0102C1.64279 23.5283 2.3362 23.8193 3.05905 23.8193H7.73586C7.88397 25.1206 8.65602 26.2641 9.79947 26.8754C10.943 27.4864 12.3104 27.4864 13.4538 26.8754C14.5971 26.2642 15.3693 25.1206 15.5172 23.8193H20.1972C20.92 23.8193 21.6134 23.5283 22.1247 23.0102C22.6361 22.4921 22.9232 21.7894 22.9232 21.0567V20.3356C22.9224 19.6338 22.6578 18.9588 22.1837 18.4475C21.7094 17.936 21.0609 17.6268 20.3698 17.5823L20.3699 17.5822Z" fill="#353030"></path>
			</svg>
		</div>
	</div>
	<h3 class="bwf-notifications-title" style="margin: 0; flex: 1;"><?php echo esc_html__( 'FunnelKit Stripe Update Required for WooCommerce v10.3.0 Compatibility', 'funnel-builder' ); ?></h3>
</div>

<p><?php esc_html_e( 'We’ve detected that your store is running WooCommerce 10.3 or above. To ensure seamless performance, please update FunnelKit WooCommerce Stripe gateway to the latest version (v1.14.0).', 'funnel-builder' ); ?></p>
<p><a href="<?php echo esc_url( $plugin_update_url ); ?>" class="button button-primary"><?php esc_html_e( 'Update Now', 'funnel-builder' ); ?></a></p>


				</div>

				<?php
			}
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function show_setup_wizard() {

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			$allowed_screens = array(
				'woofunnels_page_bwf_funnels',
				'dashboard',
				'plugins',
			);
			if ( ! in_array( $screen_id, $allowed_screens, true ) ) {
				return;
			}
			$current_admin_url = basename( wffn_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$dismiss_url       = admin_url( 'admin-ajax.php?action=wffn_dismiss_notice&nkey=onboarding_wizard&nonce=' . wp_create_nonce( 'wp_wffn_dismiss_notice' ) . '&redirect=' . $current_admin_url );

			if ( WFFN_Core()->admin->is_wizard_available() ) {
				?>


				<div class="notice notice-warning" style="position: relative;">

					<a class="notice-dismiss" style="
					position: absolute;
					padding: 5px 15px 5px 35px;
					font-size: 13px;
					line-height: 1.2311961000;
					text-decoration: none;
					display: inline-flex;
					top: 12px;
					" href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss' ); ?></a>
					<h3 class="bwf-notifications-title"> <?php echo __( 'Funnel Builder Quick Setup', 'funnel-builder' ); ?></h3> <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<p><?php esc_html_e( 'Thank you for activating Funnel Builder by FunnelKit. Go through a quick setup to ensure most optimal experience.', 'funnel-builder' ); ?></p>
					<p>
						<a href="<?php echo esc_url( WFFN_Core()->admin->wizard_url() ); ?>" class="button button-primary"> <?php esc_html_e( 'Start Wizard', 'funnel-builder' ); ?></a>

					</p>
				</div>

				<?php
			}
		}


		/**
		 * Returns whether conversion migration is required or not
		 *
		 * @return integer
		 */
		public function is_conversion_migration_required() {

			/**
			 * if pro version is not installed, then no need to migrate
			 */
			if ( ! defined( 'WFFN_PRO_VERSION' ) || version_compare( WFFN_PRO_VERSION, '3.0.0', '<' ) ) {
				return 4;
			}
			$upgrade_state = WFFN_Conversion_Tracking_Migrator::get_instance()->get_upgrade_state();

			if ( 0 === $upgrade_state ) {
				if ( ! wffn_is_wc_active() || version_compare( get_option( 'wffn_first_v', '0.0.0' ), '3.0.0', '>=' ) ) {
					WFFN_Conversion_Tracking_Migrator::get_instance()->set_upgrade_state( 4 );
					$upgrade_state = 4;
				} else {
					global $wpdb;
					$count_wc_orders = $wpdb->get_var( "SELECT COUNT(`order_id`) FROM {$wpdb->prefix}wc_order_stats" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( empty( $count_wc_orders ) ) {
						WFFN_Conversion_Tracking_Migrator::get_instance()->set_upgrade_state( 4 );
						$upgrade_state = 4;
					} else {
						WFFN_Conversion_Tracking_Migrator::get_instance()->set_upgrade_state( 1 );
						$upgrade_state = 1;
					}
				}
			}

			return $upgrade_state;
		}
	}


}


if ( class_exists( 'WFFN_Core' ) ) {
	WFFN_Core::register( 'admin_notifications', 'WFFN_Admin_Notifications' );
}
