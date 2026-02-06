<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WFACP_Analytics_loader' ) ) {
	class WFACP_Analytics_loader {
		private static $self = null;
		private $pixel = null;
		private $ga = null;
		private $gads = null;
		private $pint = null;
		private $tiktok = null;
		private $snapchat = null;

		private function __construct() {
			$this->include_class();
			$this->init_classes();

			if ( wp_doing_ajax() && isset( $_REQUEST['wc-ajax'] ) ) {
				$this->prepare_analytics_data();
			} else {
				add_action( 'wfacp_after_checkout_page_found', [ $this, 'prepare_analytics_data' ] );
				add_action( 'wfacp_after_native_checkout_page_found', [ $this, 'prepare_analytics_data' ] );
			}

		}

		public static function get_instance() {
			if ( is_null( self::$self ) ) {
				self::$self = new self;
			}

			return self::$self;
		}

		private function include_class() {
			include __DIR__ . '/tracking/class-abstract-tracking.php';
			include __DIR__ . '/tracking/class-facebook.php';
			include __DIR__ . '/tracking/class-google-analytics.php';
			include __DIR__ . '/tracking/class-google-ads.php';
			include __DIR__ . '/tracking/class-pinterest.php';
			include __DIR__ . '/tracking/class-tiktok.php';
			include __DIR__ . '/tracking/class-snapchat.php';
		}

		private function init_classes() {
			$this->pixel    = WFACP_Analytics_Pixel::get_instance(); // Facebook Pixel
			$this->ga       = WFACP_Analytics_GA::get_instance(); //Google Analytics
			$this->gads     = WFACP_Analytics_GADS::get_instance(); // Google Ads
			$this->pint     = WFACP_Analytics_Pint::get_instance();// Pinterest
			$this->tiktok   = WFACP_Analytics_TikTok::get_instance();// Tiktok
			$this->snapchat = WFACP_Analytics_Snapchat::get_instance();// SnapChat
		}

		public function prepare_analytics_data() {
			$this->pixel->prepare_data();
			$this->ga->prepare_data();
			$this->gads->prepare_data();
			$this->pint->prepare_data();
			$this->tiktok->prepare_data();
			$this->snapchat->prepare_data();
		}

	}

	WFACP_Analytics_loader::get_instance();
}
