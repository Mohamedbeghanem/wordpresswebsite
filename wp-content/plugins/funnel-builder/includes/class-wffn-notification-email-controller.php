<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
if ( ! class_exists( 'WFFN_Notification_Email_Controller' ) ) {
	class WFFN_Notification_Email_Controller {
		private $frequency = '';
		private $id = '';
		private $data = array();
		private $dates = array();

		/**
		 * Constructor.
		 *
		 * @param string $frequency
		 * @param array $data
		 * @param array $dates
		 */
		public function __construct( $frequency, $data = array(), $dates = array() ) {
			$this->frequency = $frequency;
			$this->id        = $frequency . '_report';
			$this->data      = $data;
			$this->dates     = $dates;
		}

	/**
	 * Get promotional campaign content for email
	 * Returns campaign-specific subtitle and theme based on active campaigns
	 * Uses common titles for grouped campaigns from admin_notifications class
	 *
	 * Total 7 campaigns grouped into 3 distinct campaigns for email:
	 * - BF: Pre-BF, BF, Small Business Saturday, BF Extended (all use "BF" title)
	 * - CM: CM, Extended CM (all use "CM" title)
	 * - Green Monday: GM (uses "Green Monday" title)
	 *
	 * @return array|null Campaign content with subtitle and theme, or null if no campaign active
	 */
	private function get_campaign_content() {
		// Only show for non-PRO users
		if ( WFFN_Common::wffn_is_funnel_pro_active() ) {
			return null;
		}

		$campaign_data = null;

		// Check which campaign group is active and fetch common title for that group

		// BF group campaigns (4 campaigns) - All use common "BF" title
		if ( WFFN_Core()->admin_notifications->show_pre_bfcm_header_notification()
			|| WFFN_Core()->admin_notifications->show_bf_header_notification()
			|| WFFN_Core()->admin_notifications->show_small_business_saturday_header_notification()
			|| WFFN_Core()->admin_notifications->show_bfext_header_notification() ) {
			// Use promo_bfcm for common "BF" title
			$campaign_data = WFFN_Core()->admin_notifications->promo_bfcm( false );
		}

		// CM group campaigns (2 campaigns) - All use common "CM" title
		elseif ( WFFN_Core()->admin_notifications->show_cm_header_notification()
			|| WFFN_Core()->admin_notifications->show_cmext_header_notification() ) {
			// Use promo_cmonly for common "CM" title
			$campaign_data = WFFN_Core()->admin_notifications->promo_cmonly( false );
		}

		// Green Monday campaign (1 campaign) - Uses "Green Monday" title
		elseif ( WFFN_Core()->admin_notifications->show_green_monday_header_notification() ) {
			$campaign_data = WFFN_Core()->admin_notifications->promo_gm( false );
		}

		// Return campaign content if available
		if ( ! empty( $campaign_data ) && isset( $campaign_data['title'] ) ) {
			return [
				'subtitle' => $campaign_data['title'],
				'theme'    => 'dark',
			];
		}

		return null;
	}

	/**
	 * Retrieves the email sections for the notification email.
	 *
	 * @return array The array of email sections.
	 */
	public function get_email_sections() {
		$date_range = $this->frequency === 'daily' ? WFFN_Email_Notification::format_date( $this->dates['from_date'] ) : sprintf( '%s - %s', WFFN_Email_Notification::format_date( $this->dates['from_date'] ), WFFN_Email_Notification::format_date( $this->dates['to_date'] ) );

		$highlight_subtitle    = __( 'Gain insights into customer interactions with your store through these statistics', 'Funnelkit' );
		$highlight_button_text = __( 'View Detail Report', 'Funnelkit' );
		$highlight_button_url  = admin_url( 'admin.php?page=bwf' );
		$highlight_theme       = 'light';
		$upgrade_link          = 'https://funnelkit.com/exclusive-offer/';

		if ( ! WFFN_Common::wffn_is_funnel_pro_active() ) {
			$highlight_subtitle    = __( 'Unlock more insights.', 'Funnelkit' );
			$highlight_button_text = __( 'Upgrade To PRO', 'Funnelkit' );
			$highlight_button_url  = add_query_arg( [
				'utm_source' => 'Wordpress',
				'utm_medium' => 'Email+Highlight'
			], $upgrade_link );

			// Check for active promotional campaigns and override content
			$campaign_content = $this->get_campaign_content();
			if ( ! is_null( $campaign_content ) ) {
				$highlight_subtitle = $campaign_content['subtitle'];
				$highlight_theme    = $campaign_content['theme'];
				$highlight_button_url = add_query_arg( [
					'utm_campaign' => 'BFCM'.gmdate('Y')
				], $highlight_button_url );
			}
		}

		$get_total_orders_response = WFFN_REST_API_Dashboard_EndPoint::get_instance()->get_overview_data( [ 'overall' => true ] );
		$get_total_orders          = $get_total_orders_response->get_data();
		$total_revenue             = ! empty( $get_total_orders['data']['revenue'] ) ? floatval( $get_total_orders['data']['revenue'] ) : 0;
		$email_sections            = [
			[
				'type' => 'email_header',
			],
			[
				'type' => 'highlight',
				'data' => [
					'date'        => $date_range,
					'title'       => __( 'Performance Report', 'Funnelkit' ),
					'subtitle'    => $highlight_subtitle,
					'button_text' => $highlight_button_text,
					'button_url'  => $highlight_button_url,
					'theme'       => $highlight_theme,

				],
			],
				[
					'type'     => 'dynamic',
					'callback' => [ $this, 'get_dynamic_content_1' ],
				],
				array(
					'type' => 'bwf_status_section',
					'data' => apply_filters( 'bwf_weekly_mail_status_section', [] ),
				),
				[
					'type' => 'section_header',
					'data' => [
						'title'    => __( 'Key Performance Metrics', 'Funnelkit' ),
						'subtitle' => sprintf( __( 'Change compared to previous %s', 'Funnelkit' ), $this->get_frequency_string( $this->frequency ) ),
					],
				],
			];

			$chunks    = array_chunk( $this->data['metrics'], 2, true );
			$tile_data = [];

			foreach ( $chunks as $chunk ) {
				if ( count( $chunk ) === 2 ) {
					$tile_data[] = [ reset( $chunk ), end( $chunk ) ];
				}
			}

			if ( ! empty( $tile_data ) ) {
				$email_sections[] = [
					'type' => 'metrics',
					'data' => [ 'tile_data' => $tile_data ],
				];
			}

		if ( $total_revenue > 10 ) {
			$cta_content = sprintf( __( "Since installing %1\$s you have captured additional revenue of %2\$s.", 'Funnelkit' ), '<strong>' . __( 'FunnelKit', 'Funnelkit' ) . '</strong>', '<strong>' . wc_price( $total_revenue ) . '</strong>' );

			if ( ! WFFN_Common::wffn_is_funnel_pro_active() ) {
				$cta_content = sprintf( __( "Since installing %1\$s you have captured additional revenue of %2\$s. Upgrade to Pro for even more revenue.", 'Funnelkit' ), '<strong>' . __( 'FunnelKit', 'Funnelkit' ) . '</strong>', '<strong>' . wc_price( $total_revenue ) . '</strong>' );

					$cta_link  = add_query_arg( [
						'utm_source' => 'Wordpress',
						'utm_medium' => 'Total+Revenue'
					], $upgrade_link );

					$email_sections[] = [
						'type' => 'bwf_status_section',
						'data' => [
							'content'           => $cta_content,
							'link'              => $cta_link,
							'link_text'         => __( 'Upgrade To PRO', 'Funnelkit' ),
							'background_color'  => '#FEF7E8',
							'button_color'      => '#FFC65C',
							'button_text_color' => '#000000',
						]
					];
				} else {
					$email_sections[] = [
						'type' => 'bwf_status_w_cta_section',
						'data' => [
							'content'           => $cta_content,
							'background_color'  => '#FEF7E8',
							'button_color'      => '#FFC65C',
							'button_text_color' => '#000000',
						]
					];
				}
			}
			if ( class_exists( 'WooCommerce' ) ) {
				$todos = $this->get_todo_lists();

				if ( ! empty( $todos ) ) {
					$email_sections[] = array(
						'type' => 'section_header',
						'data' => array(
							'title'    => __( 'Get More From FunnelKit', 'Funnelkit' ),
							'subtitle' => __( 'Go through the checklist and watch your sales soar', 'Funnelkit' ),
						),
					);

					$link  = add_query_arg( [
						'utm_source' => 'Wordpress',
						'utm_medium' => 'Todo'
					], $upgrade_link );

					$email_sections[] = array(
						'type' => 'todo_status',
						'data' => array(
							'todolist'     => $todos,
							'upgrade_link' => $link
						),
					);
				}

			}
			$email_sections = array_merge( $email_sections, array(
				array(
					'type'     => 'dynamic',
					'callback' => array( $this, 'get_dynamic_content_2' ),
				),
				array(
					'type' => 'email_footer',
					'data' => array(
						'date'          => $date_range,
						'business_name' => get_bloginfo( 'name' ),
					),
				),
			) );

			return apply_filters( 'bwf_weekly_notification_email_section', $email_sections );
		}

		/**
		 * Returns the HTML content for the email.
		 *
		 * @return string The HTML content of the email.
		 */
		public function get_content_html() {
			$email_sections = $this->get_email_sections();
			ob_start();

			foreach ( $email_sections as $section ) {
				if ( empty( $section['type'] ) ) {
					continue;
				}

				switch ( $section['type'] ) {
					case 'email_header':
						echo WFFN_Email_Notification::get_template_html( 'emails/email-header.php' ); // @codingStandardsIgnoreLine
						break;
					case 'highlight':
						echo WFFN_Email_Notification::get_template_html( 'emails/admin-email-report-highlight.php', $section['data'] ); // @codingStandardsIgnoreLine
						break;
					case 'metrics':
						echo WFFN_Email_Notification::get_template_html( 'emails/admin-email-report-metrics.php', $section['data'] ); // @codingStandardsIgnoreLine
						break;
					case 'section_header':
						echo WFFN_Email_Notification::get_template_html( 'emails/email-section-header.php', $section['data'] ); // @codingStandardsIgnoreLine
						break;
					case 'todo_status':
						echo WFFN_Email_Notification::get_template_html( 'emails/admin-email-report-todo-status.php', $section['data'] ); // @codingStandardsIgnoreLine
						break;
					case 'divider':
						echo WFFN_Email_Notification::get_template_html( 'emails/email-divider.php' );// @codingStandardsIgnoreLine
						break;
					case 'email_footer':
						echo WFFN_Email_Notification::get_template_html( 'emails/email-footer.php', $section['data'] ); // @codingStandardsIgnoreLine
						break;
					case 'dynamic':
						if ( isset( $section['callback'] ) && is_callable( $section['callback'] ) ) {
							call_user_func( $section['callback'], $section['data'] ?? [] );
						}
						break;
					case 'bwf_status_section':
						if ( ! empty( $section['data'] ) ) {
							echo WFFN_Email_Notification::get_template_html( 'emails/email-bwf-status-section.php', $section['data'] );// @codingStandardsIgnoreLine
						}
						break;
					case 'bwf_status_w_cta_section':
						if ( ! empty( $section['data'] ) ) {
							echo WFFN_Email_Notification::get_template_html( 'emails/email-bwf-status-w-btn-section.php', $section['data'] );// @codingStandardsIgnoreLine
						}
						break;
					default:
						do_action( 'bwf_email_section_' . $section['type'], $section['data'] ?? [] );
						break;
				}
			}

			return ob_get_clean();
		}

		/**
		 * Get all todos with their status
		 *
		 * @return array|array[]
		 */
		public function get_todo_lists() {
			$to_dos = array(
				'setup_wizard' => array(
					'title' => __( 'Setup Wizard', 'Funnelkit' ),
					'link'  => esc_url( admin_url( 'admin.php?page=bwf&path=/user-setup' ) ),
				),
				'is_checkout'  => array(
					'title' => __( 'Create Store Checkout', 'Funnelkit' ),
					'link'  => esc_url( admin_url( 'admin.php?page=bwf&path=/store-checkout' ) ),
				),
				'is_orderbump' => array(
					'title' => __( 'Create Order Bump', 'Funnelkit' ),
					'link'  => esc_url( admin_url( 'admin.php?page=bwf&path=/store-checkout' ) ),
				),
				'is_upsells'   => array(
					'title' => __( 'Create One Click Upsell Offer', 'Funnelkit' ),
					'link'  => esc_url( admin_url( 'admin.php?page=bwf&path=/store-checkout' ) ),
				),
				'tracking'     => array(
					'title' => __( 'Enable Pixel Tracking', 'Funnelkit' ),
					'link'  => esc_url( admin_url( 'admin.php?page=bwf&path=/settings/funnelkit_pixel_tracking' ) ),
					'last'  => true,
				),
				'funnels'      => array(
					'title' => __( 'Create Funnel', 'Funnelkit' ),
					'link'  => esc_url( admin_url( 'admin.php?page=bwf&path=/funnels' ) ),
					'last'  => true,
				),
			);

			$incomplete_todo = 0;
			foreach ( $to_dos as $key => $to_do ) {
				$method_name = 'metric_' . $key;
				$status      = method_exists( $this, $method_name ) ? $this->$method_name() : false;

				if ( 'active' !== $status ) {
					$incomplete_todo = 1;
				}

				$to_dos[ $key ]['status'] = $status;
			}

			if ( 0 === intval( $incomplete_todo ) ) {
				return [];
			}

			return $to_dos;
		}

		/**
		 * Metric function to check the setup wizard completion.
		 */
		protected function metric_setup_wizard() {
			$wizard_completed = get_option( '_wffn_onboarding_completed', false );

			return $wizard_completed ? 'active' : 'inactive';
		}

		/**
		 * Metric function to check if checkout funnel exists.
		 */
		protected function metric_is_checkout() {
			$checkout_count = $this->count_funnels_with_step( 'wc_checkout' );

			return $checkout_count > 0 ? 'active' : 'inactive';
		}

		/**
		 * Metric function to check if order bump exists.
		 */
		protected function metric_is_orderbump() {
			if ( ! WFFN_Common::wffn_is_funnel_pro_active() ) {
				return 'pro';
			}
			$order_bump_count = $this->count_funnels_with_step( 'wc_order_bump' );

			return $order_bump_count > 0 ? 'active' : 'inactive';
		}

		/**
		 * Metric function to check if upsells exist.
		 */
		protected function metric_is_upsells() {
			if ( ! WFFN_Common::wffn_is_funnel_pro_active() ) {
				return 'pro';
			}
			$upsells_count = $this->count_funnels_with_step( 'wc_upsells' );

			return $upsells_count > 0 ? 'active' : 'inactive';
		}

		/**
		 * Metric function to check if tracking pixels are enabled.
		 */
		protected function metric_tracking() {
			$tracking_keys = [
				'fb_pixel_key',
				'pint_key',
				'ga_key',
				'gad_key',
				'tiktok_pixel',
				'snapchat_pixel',
			];

			foreach ( $tracking_keys as $key ) {
				if ( ! empty( BWF_Admin_General_Settings::get_instance()->get_option( $key ) ) ) {
					return 'active';
				}
			}

			return 'inactive';
		}

		/**
		 * Metric function to check if at least one funnel exists.
		 */
		protected function metric_funnels() {
			$funnels = WFFN_Core()->admin->get_funnels( [ 'limit' => 1 ] );

			return ! empty( $funnels['items'] ) ? 'active' : 'inactive';
		}

		/**
		 * Helper function to count funnels with specific steps.
		 *
		 * @param string $step
		 *
		 * @return int
		 */
		protected function count_funnels_with_step( $step ) {
			$sql_query = "SELECT COUNT(id) AS count FROM {table_name} WHERE `steps` LIKE '%$step%'";
			$result    = WFFN_Core()->get_dB()->get_results( $sql_query );

			return isset( $result[0]['count'] ) ? absint( $result[0]['count'] ) : 0;
		}


		/**
		 * Dynamic content sections
		 */
		public function get_dynamic_content_1() {
			do_action( 'bwf_email_dynamic_content_1', $this->id, $this->data, $this->dates );
		}

		/**
		 * Dynamic content section 2.
		 */
		public function get_dynamic_content_2() {
			do_action( 'bwf_email_dynamic_content_2', $this->id, $this->data, $this->dates );
		}

		/**
		 * Returns the frequency string based on the given frequency.
		 *
		 * @param string $frequency The frequency value.
		 *
		 * @return string The frequency string.
		 */
		public function get_frequency_string( $frequency ) {
			$frequencies = [
				'weekly'  => __( 'Week', 'Funnelkit' ),
				'monthly' => __( 'Month', 'Funnelkit' ),
			];

			return $frequencies[ $frequency ] ?? '';
		}
	}


}
