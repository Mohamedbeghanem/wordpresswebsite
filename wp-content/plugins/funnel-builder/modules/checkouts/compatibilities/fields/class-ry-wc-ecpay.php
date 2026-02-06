<?php
if ( ! class_exists( 'WFACP_RY_WC_Ecpay' ) ) {
	/**
	 * RY WooCommerce ECPay Invoice by Yang
	 * Plugin URI: https://richer.tw/ry-woocommerce-ecpay-invoice/
	 */
	#[AllowDynamicProperties]
	class WFACP_RY_WC_Ecpay {
		public $instance = null;

		public function __construct() {
			/* Register Add field */
			add_filter( 'wfacp_advanced_fields', [ $this, 'add_field' ], 20 );
			add_filter( 'wfacp_html_fields_rc_wc_ecpay', '__return_false' );
			add_action( 'process_wfacp_html', [ $this, 'display_field' ], 999, 2 );
			/* Assign Object */
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ], 99 );
			/* default classes */
			add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 10, 2 );
			/* internal css for plugin */
			add_action( 'wfacp_internal_css', [ $this, 'internal_css' ] );

			add_filter( 'wfacp_show_shipping_options', '__return_true' );


			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );
			add_filter( 'wfacp_print_advanced_custom_fields', [ $this, 'print_third_party' ], 99, 2 );

		}

		public function add_field( $fields ) {
			$fields['rc_wc_ecpay'] = [
				'type'       => 'wfacp_html',
				'class'      => [ 'wfacp-col-full', 'wfacp-form-control-wrapper', 'rc_wc_ecpay' ],
				'id'         => 'rc_wc_ecpay',
				'field_type' => 'rc_wc_ecpay',
				'label'      => __( 'RC WC Ecpay', 'woofunnels-aero-checkout' ),
			];

			return $fields;
		}

		public function action() {

			$this->instance = WFACP_Common::remove_actions( 'woocommerce_after_checkout_billing_form', 'RY_WEI_Invoice', 'show_invoice_form' );

		}


		public function display_field( $field, $key ) {
			if ( empty( $key ) || 'rc_wc_ecpay' !== $key ) {
				return '';
			}

			?>
            <div class="wfacp_rc_wc_ecpay" id="wfacp_rc_wc_ecpay">
				<?php
				if ( method_exists( 'RY_WEI_Invoice', 'show_invoice_form' ) ) {
					RY_WEI_Invoice::show_invoice_form( WC()->checkout() );
				} elseif ( method_exists( 'RY_WEI_Invoice_Basic', 'show_invoice_form' ) ) {
					RY_WEI_Invoice_Basic::show_invoice_form( WC()->checkout() );
				}
				?>
            </div>
			<?php
		}

		public function add_default_wfacp_styling( $args, $key ) {

			if ( strpos( $key, 'invoice_' ) === false ) {
				return $args;
			}
			if ( isset( $args['type'] ) && 'checkbox' !== $args['type'] ) {
				$args['input_class'] = array_merge( [ 'wfacp-form-control' ], $args['input_class'] );
				$args['label_class'] = array_merge( [ 'wfacp-form-control-label' ], $args['label_class'] );
				$args['class']       = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full' ], $args['class'] );
				$args['cssready']    = [ 'wfacp-col-full' ];
			} else {
				$args['class']    = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full ' ], $args['class'] );
				$args['cssready'] = [ 'wfacp-col-full' ];
			}

			return $args;
		}

		public function internal_css() {
			$instance = wfacp_template();
			if ( ! $instance instanceof WFACP_Template_Common ) {
				return;
			}
			$bodyClass = "body";
			if ( 'pre_built' !== $instance->get_template_type() ) {
				$bodyClass = "body #wfacp-e-form ";
			}
			$cssHtml = "<style>";
			$cssHtml .= $bodyClass . "#wfacp_rc_wc_ecpay {clear:both;}";
			$cssHtml .= "</style>";
			echo $cssHtml;
		}

		public function print_third_party( $field, $key ) {
			if ( strpos( $key, 'invoice_' ) !== false ) {
				return [];
			}


			return $field;
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_RY_WC_Ecpay(), 'wfacp-ecpay' );
}
