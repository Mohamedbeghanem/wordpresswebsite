<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WFFN_WooFunnels_Support' ) ) {
	class WFFN_WooFunnels_Support {

		public static $_instance = null;

		protected $encoded_basename = '';

		public function __construct() {


			$this->encoded_basename = sha1( WFFN_PLUGIN_BASENAME );

			add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 85 );
			if ( ! WFFN_Common::skip_automation_page() ) {
				add_action( 'admin_menu', array( $this, 'register_automations_menu' ), 901 );
			}
			add_action( 'admin_menu', array( $this, 'register_menu_for_pro' ), 999 );
			add_action( 'admin_menu', array( $this, 'add_menus' ), 81 );


		}

		/**
		 * @return null|WFFN_WooFunnels_Support
		 */
		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}


		public function woofunnels_page() {

			if ( null === filter_input( INPUT_GET, 'tab' ) ) {
				if ( class_exists( 'WFFN_Pro_Core' ) ) {
					WooFunnels_dashboard::$selected = 'licenses';
				} else {
					WooFunnels_dashboard::$selected = 'support';
				}
			}
			if ( class_exists( 'WFFN_Header' ) ) {
				$header_ins = new WFFN_Header();
				$header_ins->set_level_1_navigation_active( 'licenses' );
				$header_ins->set_level_2_side_navigation( WFFN_Header::level_2_navigation_licenses() );
				$header_ins->set_level_2_side_navigation_active( WooFunnels_dashboard::$selected );
				echo $header_ins->render();//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
            <div class="woofunnels_licenses_wrapper">
				<?php WooFunnels_dashboard::load_page(); ?>
            </div>
			<?php

		}

		/**
		 * Adding WooCommerce sub-menu for global options
		 */
		public function add_menus() {

			$user = WFFN_Role_Capability::get_instance()->user_access( 'menu', 'read' );

			if ( ! WooFunnels_dashboard::$is_core_menu && false !== $user ) {
				add_menu_page( 'WooFunnels', 'WooFunnels', $user, 'woofunnels', array(
					$this,
					'woofunnels_page',
				), '', 59 );
				add_submenu_page( 'woofunnels', __( 'Licenses', 'funnel-builder' ), __( 'License', 'funnel-builder' ), $user, 'woofunnels' );
				WooFunnels_dashboard::$is_core_menu = true;
			}
		}

		public function register_admin_menu() {
			WFFN_Core()->admin->register_admin_menu();
		}

		public function register_automations_menu() {
			WFFN_Core()->admin->add_automations_menu();
		}


		private function get_campaign_url( $campaign ) {
			$url = "https://funnelkit.com/exclusive-offer/";

			return add_query_arg( [
				'utm_source'   => 'WordPress',
				'utm_medium'   => 'Admin+Menu+FB',
				'utm_campaign' => $campaign
			], $url );
		}

		private function add_admin_submenu( $title, $link ) {
			add_submenu_page( 'woofunnels', '', '<a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $title ) . '</a>', 'manage_options', 'upgrade_pro', function () {
			}, 100 );
		}

		public function register_menu_for_pro() {

			if ( ! defined( 'WFFN_PRO_VERSION' ) ) {
				$link = add_query_arg( [
					'utm_source'   => 'WordPress',
					'utm_medium'   => 'Menu',
					'utm_campaign' => 'FB+Lite+Plugin',
				], WFFN_Core()->admin->get_pro_link() );
				add_submenu_page( 'woofunnels', null, '<a href="' . $link . '" style="background-color:#1DA867; color:white;" target="_blank"><strong>' . __( 'Upgrade to Pro', 'funnel-builder' ) . '</strong></a>', 'manage_options', 'upgrade_pro', function () {
				}, 99 );


			$year = gmdate( 'Y' );

			if ( WFFN_Core()->admin_notifications->show_pre_bfcm_header_notification()
				|| WFFN_Core()->admin_notifications->show_bf_header_notification()
				|| WFFN_Core()->admin_notifications->show_small_business_saturday_header_notification()
				|| WFFN_Core()->admin_notifications->show_bfext_header_notification() ) {
				$campaign = 'BF' . $year;
				$title    = "Black Friday 🔥";
				$link     = $this->get_campaign_url( $campaign );
				$this->add_admin_submenu( $title, $link );

			} elseif ( WFFN_Core()->admin_notifications->show_cm_header_notification()
				|| WFFN_Core()->admin_notifications->show_cmext_header_notification() ) {
				$campaign = 'CM' . $year;
				$title    = "Cyber Monday 🔥";
				$link     = $this->get_campaign_url( $campaign );
				$this->add_admin_submenu( $title, $link );

			} elseif ( WFFN_Core()->admin_notifications->show_green_monday_header_notification() ) {
				$campaign = 'GM' . $year;
				$title    = "Green Monday 🔥";
				$link     = $this->get_campaign_url( $campaign );
				$this->add_admin_submenu( $title, $link );
			}


			} else {
				$License = WooFunnels_licenses::get_instance();
				$License->get_plugins_list();
				$current = new DateTime( current_time( 'mysql', true ) );

				$a = WFFn_Core()->admin->get_license_config( true );

				if ( ! empty( $a['f']['ed'] ) ) {

					$expiry = new DateTime( $a['f']['ed'] );

					/**
					 * the expiry should always be less than on current utc
					 */
					if ( $expiry->getTimestamp() < $current->getTimestamp() ) {
						$link = add_query_arg( [
							'utm_source'   => 'WordPress',
							'utm_medium'   => 'Admin+Menu',
							'utm_campaign' => 'FB+Lite+Plugin',
						], 'https://funnelkit.com/exclusive-offer/' );
						add_submenu_page( 'woofunnels', null, '<a href="' . $link . '" style="background-color:#e15334; color:white;" target="_blank"><strong>' . __( 'License Expired', 'funnel-builder' ) . '</strong></a>', 'manage_options', 'upgrade_pro', function () {
						}, 99 );
					}

				}


			}


		}


	}

	if ( class_exists( 'WFFN_WooFunnels_Support' ) ) {
		WFFN_Core::register( 'support', 'WFFN_WooFunnels_Support' );
	}
}

/**new WFFN_WooFunnels_Support();*/
