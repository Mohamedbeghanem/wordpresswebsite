<?php
/*
 * Compatibility added with plugin DIGITS: WordPress Mobile Number Signup and Login by UnitedOver v.8.5
 *  Plugin URI: https://digits.unitedover.com
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'WFACP_Compatibility_With_Digits_by_UnitedOver' ) ) {

    #[AllowDynamicProperties]
    class WFACP_Compatibility_With_Digits_by_UnitedOver {

        /**
         * Constructor - sets up hooks and filters
         */
        public function __construct() {
            add_filter( 'wfacp_internal_css', [ $this, 'add_js' ], 50 );
            add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );
            add_action( 'wfacp_before_process_checkout_template_loader', [ $this, 'action' ] );
        }

        /**
         * Integrates DIGITS plugin with WooCommerce checkout
         * Adds DIGITS order button functionality to checkout
         */
        public function action() {
            // Check if DIGITS plugin is active and class exists
            if ( ! class_exists( 'DigitsWCCheckoutHandler' ) ) {
                return;
            }

            try {
                $instance = DigitsWCCheckoutHandler::instance();
                if ( method_exists( $instance, 'place_order_button_html' ) ) {
                    add_filter( 'woocommerce_order_button_html', [ $instance, 'place_order_button_html' ], 999 );
                }
            } catch ( Exception $e ) {
                // Only catching Exception as DIGITS plugin methods may throw standard exceptions.
                // If DIGITS introduces custom exceptions, update this catch block accordingly.
                error_log( 'WFACP DIGITS Compatibility Error: ' . $e->getMessage() );
            }
        }

        /**
         * Adds custom CSS and JavaScript for DIGITS integration
         * Handles country code positioning and RTL/LTR layout support
         */
        public function add_js() {
            ?>
            <style>
                body #wfacp-e-form #wfacp-sec-wrapper  .digcon {
                    position: relative;
                    display: block;
                }

                body #wfacp-e-form #wfacp-sec-wrapper  .digcon .dig_wc_logincountrycodecontainer {
                    position: absolute;
                    top: 0;
                    bottom: 0;
                    padding: 1px;
                    right: auto;
                    left: 0;
                    z-index: 999;
                }

                body #wfacp-e-form #wfacp-sec-wrapper .digcon .dig_wc_logincountrycodecontainer .countrycode {
                    z-index: 1;
                    display: flex;
                    align-items: center;
                    height: 100%;
                    padding: 10px 12px !important;
                    width: auto;
                    margin-right: 0;
                    background: 0 0;
                    position: relative;
                    font-size: 14px;
                    font-weight: 400;
                    border: none;
                }

                body #wfacp-e-form #wfacp-sec-wrapper .digcon .dig_wc_logincountrycodecontainer:after {
                    content: '';
                    display: block;
                    width: 1px;
                    background: #d9d9d9;
                    height: 18px;
                    position: absolute;
                    right: 0;
                    top: 50%;
                    margin-top: -9px;
                }

                body #wfacp-e-form #wfacp-sec-wrapper .dig-cc-search .countrycode_search {
                    line-height: 1.5 !important;
                    padding: 0 12px 0 36px !important;
                    margin: 0 !important;
                    height: 48px;
                    min-height: 48px;
                    box-shadow: none !important;
                }
                body #wfacp-e-form .digits-form_tab_container .lost_password.wfacp-text-align-right {
                    text-align: left;
                }
            </style>

            <script>
                window.addEventListener( 'load', function () {
                    ( function ( $ ) {
                        // Only run if DIGITS country code container exists
                        if ( $( '.dig_wc_logincountrycodecontainer' ).length > 0 ) {
                            // Initial positioning
                            setTimeout( function () {
                                digit_field_position( '.dig_wc_logincountrycodecontainer:visible' );
                            }, 500 );

                            // Handle focus events on country code fields
                            $( document ).on( "focusout focusin", ".countrycode, .countrycode_search", function ( e ) {
                                setTimeout( function () {
                                    digit_field_position( '.dig_wc_logincountrycodecontainer:visible' );
                                }, 500 );
                            } );

                            // Handle flag updates
                            $( document ).on( 'update_flag', '.country_code_flag', function ( e ) {
                                setTimeout( function () {
                                    digit_field_position( '.dig_wc_logincountrycodecontainer:visible' );
                                }, 500 );
                            } );

                            // Handle country selection from dropdown
                            var elem = $( ".digit_cs-list" );
                            elem.on( 'mousedown click', 'li', function ( e ) {
                                setTimeout( function () {
                                    digit_field_position( '.dig_wc_logincountrycodecontainer:visible' );
                                }, 500 );
                            } );

                            /**
                             * Adjusts field positioning based on country code flag width
                             * Supports both RTL and LTR layouts
                             */
                            function digit_field_position( className ) {
                                let flag_w = 0;

                                flag_w = $( className ).innerWidth();
                                if ( typeof flag_w !== "undefined" && '' != flag_w ) {
                                    flag_w = parseInt( flag_w ) + 12;

                                    let username_element=$( '#wfacp_checkout_form .digcon #username' );

                                    // Adjust label positioning if not using top layout
                                    if ( $( '.wfacp-top' ).length == 0 ) {
                                        if ( true === wfacp_frontend.is_rtl || "1" === wfacp_frontend.is_rtl ) {
                                            username_element.parents( '.wfacp-form-control-wrapper' ).find( '.wfacp-form-control-label' ).css( 'right', flag_w + 8 );
                                        } else {
                                            username_element.parents( '.wfacp-form-control-wrapper' ).find( '.wfacp-form-control-label' ).css( 'left', flag_w + 8 );
                                        }
                                    }

                                    // Adjust input field padding
                                    if ( true === wfacp_frontend.is_rtl || "1" === wfacp_frontend.is_rtl ) {
                                        username_element.css( 'cssText', 'padding-right: ' + flag_w + 'px !important' );
                                    } else {
                                        username_element.css( 'cssText', 'padding-left: ' + flag_w + 'px !important' );
                                    }
                                }
                            }
                        }
                    } )( jQuery );
                } );
            </script>
            <?php
        }
    }

    WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Digits_by_UnitedOver(), 'digits' );
}