<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme: Savoy
 * Theme URI: http://themeforest.net/item/savoy-minimalist-ajax-woocommerce-theme/12537825
 * class WFACP_Compatibility_With_Active_Savoy
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Active_Savoy' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Active_Savoy {

		public function __construct() {
			/* checkout page */
			add_action( 'wfacp_before_coupon_apply', [ $this, 'remove_actions' ] );

			add_action( 'wfacp_internal_css', [ $this, 'internal_css_js' ] );
			add_filter( 'wfacp_disable_wc_dropdown_variation_attribute_options', '__return_false' );

			/**
			 * Dequeue css and js on the checkot page
			 */
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );
		}

		public function remove_actions() {
			remove_action( 'woocommerce_applied_coupon', 'wc_coupon_yu' );
		}

		public function internal_css_js() {

			?>
			<style>

                body #wfacp_qr_model_wrap .single_variation {
                    border: none;
                    padding: 0 !important;
                    line-height: 1.5;
                }

                body .mfp-ready {
                    display: none;
                }

			</style>

			<script>
                window.addEventListener('load', function () {
                    (function ($) {
                        $(document).on('wfacp_quick_view_open', function () {
                            var $container = $('.wfacp-product-variations');

                            if ($container.length > 0 && typeof $.nmThemeInstance !== 'undefined' && typeof $.nmThemeInstance.singleProductVariationsInit === 'function') {

                                $.nmThemeInstance.singleProductVariationsInit($container);
                            }

                        });
                    })(jQuery);
                });
			</script>
			<?php
		}

		public function action() {

			add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_scripts' ], 9999 );
		}

		public function dequeue_scripts() {

			wp_dequeue_script( 'nm-shop' );

		}

	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Active_Savoy(), 'Savoy' );
}