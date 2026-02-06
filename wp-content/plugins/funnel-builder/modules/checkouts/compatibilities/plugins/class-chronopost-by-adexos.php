<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: Chronopost by Adexos version 2.0.0
 * Plugin URI:  https://www.chronopost.fr/
 *
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Chronopost_by_Adexos' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Chronopost_by_Adexos {
		public function __construct() {
			add_action( 'wfacp_before_shipping_calculator_field', function () {
				echo "<div id='order_review'>";
			} );
			add_action( 'wfacp_after_shipping_calculator_field', function () {
				echo "</div>";
			} );
			add_action( 'wfacp_internal_css', [ $this, 'js' ] );
		}

		public function js() {
			?>
			<script>
				window.addEventListener('DOMContentLoaded', function () {
					(function ($) {


						var shipping = $('#shipping_postcode');
						var billing = $('#billing_postcode');
						
						// Check if elements exist before binding events
						if (shipping.length && billing.length) {
							shipping.on('change', function () {
								billing.val($(this).val());
							});
							billing.val(shipping.val());
						}

						$(document.body).on('wfacp_gmap_address_selected', function () {
							setTimeout(function () {
								var shipping = $('#shipping_postcode');
								var billing = $('#billing_postcode');
								if (shipping.length && billing.length) {
									billing.val(shipping.val());
									check_for_relay_link();
								}
							}, 200);
						});

						$(document.body).on("chronomap:pickuprelay_change", function () {
							check_for_relay_link();
						});

						$(document.body).on('updated_checkout', function () {
							check_for_relay_link();
						});

						function check_for_relay_link() {
							if ($('#shipping_method').length > 0) {
								if ($('#shipping_method li:first').find('.shipping_method').is(':checked')) {
									$('.pickup-relay-link').show();
								} else {
									$('.pickup-relay-link').hide();
								}
							}
						}

					})(jQuery);
				});
			</script>
			<?php
		}
	}


	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Chronopost_by_Adexos(), 'chronopost-by-adexos' );

}
