<?php
if ( ! class_exists( 'WFACP_Compatibility_WC_Coderockz_Delivery' ) ) {
	/**
	 * WooCommerce Delivery & Pickup Date Time Pro v.1.3.80 by CodeRockz
	 *  class WFACP_Compatibility_WC_Coderockz_Delivery
	 */
	#[AllowDynamicProperties]
	class WFACP_Compatibility_WC_Coderockz_Delivery {

		private $coderockz_woo_delivery = null;

		public function __construct() {

			add_filter( 'wfacp_advanced_fields', [ $this, 'add_field' ], 20 );
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'actions' ] );
			add_filter( 'wfacp_html_fields_coderockz_woo_delivery', '__return_false' );
			add_action( 'process_wfacp_html', [ $this, 'call_fields_hook' ], 999, 3 );
			add_action( 'wfacp_internal_css', [ $this, 'internal_css' ] );

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );
		}

		/**
		 * Register Add field with Funnelkit Checkout
		 *
		 * @param $fields
		 *
		 * @return mixed
		 */
		public function add_field( $fields ) {
			$fields['coderockz_woo_delivery'] = [
				'type'       => 'wfacp_html',
				'class'      => [ 'wfacp-col-full', 'wfacp-form-control-wrapper', 'wfacp_anim_wrap', 'coderockz_woo_delivery' ],
				'id'         => 'coderockz_woo_delivery',
				'field_type' => 'coderockz_woo_delivery',
				'label'      => __( 'Coderockz Woo Delivery', 'woofunnels-aero-checkout' ),
			];


			return $fields;
		}

		/**
		 * @return void
		 *
		 * Remove action when Our checkot page found and add Checkout classes
		 */

		public function actions() {

			if ( class_exists( 'Coderockz_Woo_Delivery_Public' ) ) {
				if ( defined( 'CODEROCKZ_WOO_DELIVERY_DIR' ) && defined( 'CODEROCKZ_WOO_DELIVERY_VERSION' ) ) {
					$this->coderockz_woo_delivery = new Coderockz_Woo_Delivery_Public( plugin_basename( CODEROCKZ_WOO_DELIVERY_DIR ), CODEROCKZ_WOO_DELIVERY_VERSION );
				}
			}
			add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 50, 2 );
		}

		/**
		 * @param $field
		 * @param $key
		 * @param $args
		 *
		 * @return void
		 *
		 * Process Our checkout field and replaced with Plugin Fields
		 */

		public function call_fields_hook( $field, $key, $args ) {
			if ( ! empty( $key ) && 'coderockz_woo_delivery' === $key ) {
				if ( $this->coderockz_woo_delivery instanceof Coderockz_Woo_Delivery_Public ) {
					echo "<div class='wfacp_coderockz_woo_delivery'>";
					$this->coderockz_woo_delivery->coderockz_woo_delivery_add_custom_field();
					echo "</div>";
				}
			}
		}

		/**
		 * @param $args
		 * @param $key
		 *
		 * @return mixed
		 *
		 * Add Default Checkout field class for coderockz field
		 */

		public function add_default_wfacp_styling( $args, $key ) {


			if ( strpos( $key, 'coderockz' ) === false ) {
				return $args;
			}

			if ( isset( $args['type'] ) && ( 'checkbox' !== $args['type'] && 'radio' !== $args['type'] && 'wfacp_radio' !== $args['type'] ) ) {
				$args['input_class'] = array_merge( [ 'wfacp-form-control' ], $args['input_class'] );
				$args['label_class'] = array_merge( [ 'wfacp-form-control-label' ], $args['label_class'] );
				$args['class']       = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full ' ], $args['class'] );
				$args['cssready']    = [ 'wfacp-col-left-half' ];

			} else {
				$args['class']    = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full ' ], $args['class'] );
				$args['cssready'] = [ 'wfacp-col-full' ];
			}


			return $args;
		}

		/**
		 * @return void
		 *
		 * Add Own Custom Css and JS which will be run on the checkout page with plugin related
		 */

		public function internal_css() {

			if ( ! function_exists( 'wfacp_template' ) ) {
				return;
			}

			$instance = wfacp_template();
			if ( ! $instance instanceof WFACP_Template_Common ) {
				return;
			}
			$px = $instance->get_template_type_px() . "px";
			if ( 'pre_built' !== $instance->get_template_type() ) {
				$px = "7px";
			}

			echo "<style>";
			if ( $px != '' ) {
				echo "#wfacp-sec-wrapper .wfacp_coderockz_woo_delivery{padding:0 $px" . 'px' . "}";
			}
			echo "</style>";

			?>
            <script>
                window.addEventListener('load', function () {
                    (function ($) {

                        setTimeout(function () {
                            add_anim_class();
                        }, 1000);

                        $(document.body).on('updated_checkout', function () {
                            add_anim_class();
                        });

                        $(document.body).on('wfacp_step_switching', function (e, v) {
                            add_anim_class();
                        });

                        function add_anim_class() {
                            if ($('.coderockz_woo_delivery_pickup_date_field').length > 0 && !$('.coderockz_woo_delivery_pickup_date_field').hasClass('wfacp-anim-wrap')) {
                                $('.coderockz_woo_delivery_pickup_date_field').addClass('wfacp-anim-wrap')
                            }
                        }

                    })(jQuery);
                });
            </script>
			<?php
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_WC_Coderockz_Delivery(), 'wc-codrockz-delivery' );
}
