<?php
if ( ! class_exists( 'WFACP_Compatibility_WoodMart_Theme' ) ) {
    #[AllowDynamicProperties]
    class WFACP_Compatibility_WoodMart_Theme {
        public function __construct() {
            add_action( 'init', [ $this, 'register_elementor_widget' ], 150 );
            add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ], 20 );
            add_action( 'wfacp_template_load', [ $this, 'remove_action' ] );
            add_action( 'wfacp_internal_css', [ $this, 'internal_css' ] );
        }


        public function remove_action() {
            if ( function_exists( 'woodmart_section_negative_gap' ) ) {
                remove_action( 'wp', 'woodmart_section_negative_gap' );
            }

            /* Remove Action of the order table at checkout */
            if ( class_exists( 'XTS\Modules\Checkout_Order_Table' ) ) {
                WFACP_Common::remove_actions( 'woocommerce_review_order_before_cart_contents', 'XTS\Modules\Checkout_Order_Table', 'checkout_table_content_replacement' );
            }

            if ( class_exists( 'XTS\Modules\Show_Single_Variations\Query' ) && woodmart_get_opt( 'show_single_variation' ) ) {
                WFACP_Common::remove_actions( 'posts_clauses', 'XTS\Modules\Show_Single_Variations\Query', 'posts_clauses' );
            }

            if ( class_exists( 'XTS\Modules\Layouts\Checkout' ) ) {
                WFACP_Common::remove_actions( 'template_include', 'XTS\Modules\Layouts\Checkout', 'override_template' );
            }

            if ( class_exists( 'XTS\Modules\Estimate_Delivery\Frontend' ) ) {
                WFACP_Common::remove_actions( 'woocommerce_review_order_after_order_total', 'XTS\Modules\Estimate_Delivery\Frontend', 'render_overall' );
            }

            add_action( 'woocommerce_review_order_after_order_total', array( $this, 'render_overall' ) );


            add_action( 'wp_enqueue_scripts', function () {

                wp_deregister_style( 'wd-select2' );
                wp_dequeue_style( 'wd-select2' );

                wp_deregister_style( 'woo-lib-select2' );
                wp_dequeue_style( 'woo-lib-select2' );


            }, 101000 );

        }

        public function action() {
            $this->clear_cache();
            add_filter( 'body_class', [ $this, 'remove_class' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_style' ], 9999999 );

            /* Dequeue hook where flex slider dequeue on the page in theme  */
            remove_action( 'wp_enqueue_scripts', 'woodmart_dequeue_scripts', 2000 );
        }

        public function clear_cache() {
            $is_clear_cached = get_post_meta( WFACP_Common::get_id(), 'wfacp_woodmart_clear_cached', true );
            if ( 'yes' === $is_clear_cached ) {
                return;
            }
            if ( class_exists( 'Elementor\Plugin' ) ) {
                Elementor\Plugin::$instance->files_manager->clear_cache();
                update_post_meta( WFACP_Common::get_id(), 'wfacp_woodmart_clear_cached', 'yes' );
            }
        }

        public function dequeue_style() {
            wp_deregister_style( 'wd-page-checkout' );
            wp_dequeue_style( 'wd-page-checkout' );

        }

        public function remove_class( $body_class ) {

            $notification_key = array_search( "notifications-sticky", $body_class );
            if ( isset( $body_class[ $notification_key ] ) ) {
                unset( $body_class[ $notification_key ] );
            }


            return $body_class;
        }

        public function internal_css() {
            if ( ! $this->enable() ) {
                return;
            }


            ?>
            <style>

                body #wfacp-e-form   .select2-container--default .select2-selection--single .select2-selection__rendered{padding-right: 12px !important;padding-left: 12px !important;}
                body #wfacp-e-form  .checkout_coupon {width: 100% !important;max-width: 100%;text-align: left;}

                body td.product-remove a, body .woocommerce-remove-coupon{display: inline-block;align-items: inherit;justify-content: inherit;width: auto;height: auto;}
                body td.product-remove a:before, body .woocommerce-remove-coupon:before{display:none;}
                body .select2-container--default .select2-results>.select2-results__options { color: initial;}
                body .responsive-table{
                    width: 100%;
                    overflow: unset;
                    margin: 0;
                }

                .wd-del-overall {
                    width: 100% !important;
                }
                .wd-del-overall td {
                    display: table-cell !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    text-align: left !important;
                    padding: 15px 0 !important;
                    border-top: 1px solid #e0e0e0;
                    background-color: #f9f9f9;
                    box-sizing: border-box !important;
                }
                .wd-product-info.wd-est-del {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 10px;
                    background-color: #fff;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                    line-height: 1.4;
                }
                /* Icon styling */
                .wd-info-icon {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    background-color: #007cba;
                    border-radius: 50%;
                    flex-shrink: 0;
                    position: relative;
                }
                .wd-info-icon:before {
                    content: "i";
                    color: white;
                    font-size: 12px;
                    font-weight: bold;
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    font-style: normal;
                }
                /* Force the delivery info to use full available width */
                .wd-del-overall .wd-product-info {
                    width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                }
                /* Message text styling */
                .wd-info-msg {
                    flex: 1;
                    color: #333;
                }
                .wd-info-msg strong {
                    color: #000;
                    font-weight: 600;
                }
                .wd-notice, div.wpcf7-response-output, .mc4wp-alert, :is(.woocommerce-error,.woocommerce-message,.woocommerce-info) {
                    padding: 0 !important;
                    margin: 0;
                    color: #000;
                }

                body #wfacp-sec-wrapper .wfacp-success, body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_coupon_field_msg>.wfacp_single_coupon_msg, body #wfacp-sec-wrapper .wfacp_main_form.woocommerce .woocommerce-message, body #wfacp-sec-wrapper .wfacp_success, body #wfacp-sec-wrapper .wfacp_sucuss, body #wfacp-sec-wrapper .woocommerce-message, body .wfacp-coupon-page .wfacp_coupon_remove_msg {

                    padding: 5px 10px 5px 24px !important;

                }
                body .wfacp_main_form.woocommerce span.optional {
                    top: auto;
                    color: #969595;
                }
                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .wd-del-overall td {
                        padding: 12px 8px !important;
                    }
                    .wd-info-msg {
                        font-size: 13px;
                    }
                }
            </style>

            <script>
                window.addEventListener('bwf_checkout_load', function () {
                    try {
                        (function ($) {

                            // Ensure the woodmartThemeModule is defined before triggering

                            if (typeof woodmartThemeModule !== 'undefined' && woodmartThemeModule.$document) {

                                $(document.body).on('wfacp_quick_view_open', function () {
                                    woodmartThemeModule.$document.trigger('wood-images-loaded');
                                });

                                setTimeout(function () {
                                    woodmartThemeModule.$document.trigger('wood-images-loaded');
                                }, 500);

                            }
                        })(jQuery);
                    } catch (e) {
                    }
                });
            </script>

            <?php

        }

        public function enable() {
            if ( ! defined( 'WOODMART_THEME_DIR' ) ) {
                return false;
            }

            return true;
        }

        public function register_elementor_widget() {
            if ( class_exists( 'Elementor\Plugin' ) ) {
                $instance = WFACP_Elementor::get_instance();
                $instance->initialize_widgets();
            }
        }

        public function render_overall() {

            if ( ! woodmart_get_opt( 'estimate_delivery_show_overall' ) || ! isset( WC()->cart ) ) {
                return;
            }

            $cart_items = WC()->cart->get_cart();
            $products   = array();

            foreach ( $cart_items as $cart_item ) {
                $products[] = $cart_item['data'];
            }
            $delivery_date_string='';
            if(class_exists('XTS\Modules\Estimate_Delivery\Overal_Delivery_Date')){
                $overal_dates         = new XTS\Modules\Estimate_Delivery\Overal_Delivery_Date( $products );
                $delivery_date_string = $overal_dates->get_date_string();
            }


            if ( empty( $delivery_date_string ) ) {
                return;
            }
            ?>
            <tr class="wd-del-overall">
                <td colspan="2">
                    <div class="wd-product-info wd-est-del">
                        <span class="wd-info-icon"></span><span class="wd-info-msg"><?php echo wp_kses( $delivery_date_string, 'strong' ); ?></span>

                        <div class="wd-loader-overlay wd-fill"></div>
                    </div>
                </td>
            </tr>
            <?php
        }
    }

    WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_WoodMart_Theme(), 'woodmart' );
}
