<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WFACP_Compatibility_With_Theme_Flatsome' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Theme_Flatsome {

		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_actions' ] );
			add_action( 'init', [ $this, 'builder_post_type' ] );
			add_action( 'wfacp_template_load', [ $this, 'add_terms_condition' ] );
			add_action( 'wfacp_internal_css', [ $this, 'add_internal_css' ] );
		}

		public function remove_actions() {
			if ( function_exists( 'flatsome_checkout_scripts' ) ) {
				remove_action( 'wp_enqueue_scripts', 'flatsome_checkout_scripts', 100 );
			}
			if ( function_exists( 'flatsome_viewport_meta' ) ) {
				remove_action( 'wp_head', 'flatsome_viewport_meta', 1 );
			}
		}

		public function builder_post_type() {
			if ( function_exists( 'add_ux_builder_post_type' ) ) {
				add_ux_builder_post_type( WFACP_Common::get_post_type_slug() );
			}
		}

		public function add_terms_condition() {
			if ( function_exists( 'flatsome_fix_policy_text' ) ) {
				add_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 21 );
			}

			if ( function_exists( 'flatsome_fix_policy_text' ) ) {
				remove_action( 'woocommerce_checkout_after_order_review', 'wc_checkout_privacy_policy_text', 1 );
			}
		}


		public function add_internal_css() {
			if ( ! function_exists( 'wfacp_template' ) ) {
				return;
			}


			$instance = wfacp_template();
			if ( ! $instance instanceof WFACP_Template_Common ) {
				return;
			}
			$bodyClass = "body ";


			if ( 'pre_built' !== $instance->get_template_type() ) {

				$bodyClass = "body #wfacp-e-form ";
			}

			echo "<style>";
			echo $bodyClass . '.wfacp_form #payment select {-webkit-appearance: menulist;-moz-appearance: menulist;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' ul.woocommerce-error li .container {padding: 0;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' #payment div.payment_box p {position: relative;font-weight: normal;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' .woocommerce-error .medium-text-center {text-align: left !important;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' .wfacp-coupon-page .message-container.container.medium-text-center { text-align: left !important;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' .wfacp_notice_dismise_link.demo_store a:before {display: none;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' .wfacp_main_form .woocommerce-error {color: #ff0000 !important;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' .wfacp_main_form.woocommerce .woocommerce-checkout #payment ul.payment_methods li input[type=radio] {  margin: 0 10px 0 0 !important;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' .wfacp-row.wfacp_coupon_field_box.wfacp_coupon_collapsed{ margin-top: 10px;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' .wfacp_main_form .wfacp-coupon-section .wfacp-coupon-page .wfacp_coupon_field_box { margin-top: 10px;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' button.button.button-primary:after{   display: none;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $bodyClass . ' button.button.button-primary:before{   display: none;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "</style>";

            if ( !function_exists('flatsome_scripts') ) {
                return;
            }

			?>
			<script>
                window.addEventListener('load', function () {


                    (function ($) {

                        var checkoutAjaxFlag = false;

                        // Before AJAX handler - set flag
                        var ajaxBeforeHandler = function(event, jqxhr, settings) {
                            try {
                                if (settings && settings.url && settings.url.indexOf('wc-ajax=checkout') !== -1) {
                                    checkoutAjaxFlag = true;
                                }
                            } catch (error) {
                                console.error('Error in ajaxBeforeHandler:', error);
                            }
                        };

                    // After AJAX handler
                        var ajaxHandler = function (event, jqxhr, settings) {
                            try {
                                // Condition 1: Flag set -> add class and return (ALWAYS)
                                if (checkoutAjaxFlag === true) {
                                    $("#wfacp_checkout_form").addClass("processing");

                                }

								if (settings && settings.url && settings.url.indexOf('wc-ajax=checkout') !== -1) {
                                    if(jqxhr.responseJSON){
                                    	if (jqxhr.responseJSON.result === 'failure') {
                                       	$("#wfacp_checkout_form").removeClass("processing");
                                       	checkoutAjaxFlag = false;
                                    	} else if (jqxhr.responseJSON.result === 'success') {
                                       	$("#wfacp_checkout_form").addClass("processing");
                                       	checkoutAjaxFlag = false;
                                   	}
                               		}
                                }


                            } catch (error) {
                                console.error('Error in ajaxHandler:', error);
                            }
                        };

                        $(document).ajaxSend(ajaxBeforeHandler);
                        $(document).ajaxComplete(ajaxHandler);

                    })(jQuery);
                });
			</script>

<?php
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Theme_Flatsome(), 'flatsome' );
}
