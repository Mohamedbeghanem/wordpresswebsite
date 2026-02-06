<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Sezzle WooCommerce Payment Gateway Compatibility
 * Plugin URI: https://www.sezzle.com/
 */

if ( ! class_exists( 'WFACP_Plugin_Compatibilities' ) ) {
	return;
}

if ( ! class_exists( 'WFACP_Compatibility_With_Sezzle_Payment_Gateway' ) ) {

	class WFACP_Compatibility_With_Sezzle_Payment_Gateway {

		public function __construct() {
            /**
             * Adds JavaScript to ensure Sezzle modal overlays are present on the page.
             */
            add_action( 'wfacp_internal_css', array( $this, 'add_js' ) );
		}

		public function add_js() {
			if ( ! $this->is_enable() ) {
				return;
			}
			?>
			<script>
				window.addEventListener('load', function () {
					(function ($) {
						function ensureSezzleModalOverlay() {
							if ($('.sezzle-modal-overlay').length === 0) {
								$('body').append('<div style="display:none;" class="sezzle-modal-overlay"></div>');
							}
						}

						ensureSezzleModalOverlay();

						$(document.body).on('updated_checkout', function() {
							ensureSezzleModalOverlay();
						});
					})(jQuery);
				});
			</script>
			<?php
		}

		public function is_enable() {
			return function_exists( 'add_installment_widget_script' );
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Sezzle_Payment_Gateway(), 'sezzle_woocommerce_payment' );
}
