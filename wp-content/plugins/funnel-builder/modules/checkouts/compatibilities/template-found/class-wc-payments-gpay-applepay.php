<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WFACP_WC_Payments_GPAY_AND_APAY' ) ) {
	#[AllowDynamicProperties]
	class WFACP_WC_Payments_GPAY_AND_APAY {
		private $instance = null;

		public function __construct() {
			add_filter( 'wfacp_smart_buttons', [ $this, 'add_buttons' ] );
			add_action( 'wfacp_smart_button_container_wc_payment_gpay_apay', [ $this, 'add_wc_payment_gpay_apay_buttons' ] );
			add_action( 'wfacp_smart_button_container_wc_payment_woo_pay', [ $this, 'add_wc_payment_woo_pay_buttons' ] );
			add_action( 'wfacp_internal_css', [ $this, 'add_internal_css' ], 11, 2 );

		}

		public function add_buttons( $buttons ) {
			try {


				if ( true == apply_filters( 'wfacp_disabled_google_apple_pay_button_on_desktop', false, $buttons ) ) {
					if ( ! class_exists( 'WFACP_Mobile_Detect' ) ) {
						return $buttons;
					}
					$detect = WFACP_Mobile_Detect::get_instance();
					if ( ! $detect->isMobile() || empty( $detect ) ) {
						return $buttons;
					}
					add_filter( 'wfacp_template_localize_data', [ $this, 'set_local_data' ] );
				}
				if ( class_exists( 'WC_Payments_Express_Checkout_Button_Display_Handler' ) && method_exists( 'WC_Payments_Express_Checkout_Button_Display_Handler', 'display_express_checkout_buttons' ) ) {
					$this->instance = WFACP_Common::remove_actions( 'woocommerce_checkout_before_customer_details', 'WC_Payments_Express_Checkout_Button_Display_Handler', 'display_express_checkout_buttons' );
				} else {
					$this->instance = WFACP_Common::remove_actions( 'woocommerce_checkout_before_customer_details', 'WC_Payments_Payment_Request_Button_Handler', 'display_payment_request_button_html' );
					WFACP_Common::remove_actions( 'woocommerce_checkout_before_customer_details', 'WC_Payments_Payment_Request_Button_Handler', 'display_payment_request_button_separator_html' );
					if ( method_exists( 'WC_Payments', 'display_express_checkout_separator_if_necessary' ) ) {
						WFACP_Common::remove_actions( 'woocommerce_checkout_before_customer_details', 'WC_Payments', 'display_express_checkout_separator_if_necessary' );
					}
				}

				$buttons['wc_payment_gpay_apay'] = [
					'iframe' => true,
					'name'   => __( 'Woocommerce Payment Request', 'woocommerce-payments' ),
				];
				$buttons['wc_payment_woo_pay']   = [
					'iframe' => true,
					'name'   => __( 'Woocommerce Payment Request Woo Pay', 'woocommerce-payments' ),
				];
			} catch ( Exception|Error $e ) {
				wc_get_logger()->error( $e->getMessage(), [ 'source' => 'funnelkit-checkout-render-error' ] );
			}

			return $buttons;
		}

		public function add_wc_payment_gpay_apay_buttons() {
			try {
				$instance = WFACP_Common::remove_actions( 'template_redirect', 'WC_Payments_Express_Checkout_Button_Handler', 'set_session' );
				if ( $instance instanceof WC_Payments_Express_Checkout_Button_Handler && method_exists( 'WC_Payments_Express_Checkout_Button_Handler', 'display_express_checkout_button_html' ) ) {
					$instance->display_express_checkout_button_html();
				} else if ( $this->instance instanceof WC_Payments_Payment_Request_Button_Handler ) {
					$this->instance->display_payment_request_button_html();
				}

			} catch ( Exception|Error $e ) {
				wc_get_logger()->error( $e->getMessage(), [ 'source' => 'funnelkit-checkout-render-error' ] );
			}
		}

		public function add_wc_payment_woo_pay_buttons() {
			try {
				$instance = WFACP_Common::remove_actions( 'wp_ajax_woopay_express_checkout_button_show_error_notice', 'WC_Payments_WooPay_Button_Handler', 'show_error_notice' );

				if ( $instance instanceof WC_Payments_WooPay_Button_Handler && method_exists( 'WC_Payments_WooPay_Button_Handler', 'display_woopay_button_html' ) ) {
					$instance->display_woopay_button_html();
				}
			} catch ( Exception|Error $e ) {
				wc_get_logger()->error( $e->getMessage(), [ 'source' => 'funnelkit-checkout-render-error' ] );
			}
		}

		public function set_local_data( $data ) {
			$data['wc_payment_smart_show_on_desktop'] = 'no';

			return $data;
		}

		public function add_internal_css() {
			?>
            <style>
                #wfacp_smart_button_wc_payment_gpay_apay #wcpay-payment-request-wrapper {
                    padding: 0 !important;
                }

                #wfacp_smart_button_wc_payment_gpay_apay #wcpay-payment-request-button-separator {
                    display: none !important;
                }

                #wfacp_smart_button_wc_payment_gpay_apay #wcpay-express-checkout-button-separator {
                    display: none !important;
                }
            </style>
			<?php
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_WC_Payments_GPAY_AND_APAY(), 'wc-payments-gpay_apay' );
}
