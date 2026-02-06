<?php
if ( ! class_exists( 'WFACP_Class_Register_Third_Party_Fields' ) ) {
    #[AllowDynamicProperties]
    class WFACP_Class_Register_Third_Party_Fields {
        public $new_fields = [];
        public $fields_added = [];
        private $checkout_fields = [];
        public $previous_keys = [];
        private static $instance = null;
        private $wc_fields_under_billing = [];
        private $wc_fields_under_shipping = [];
        private $native_checkout_fields = null;
        private $all_default_fields = [];
        private $third_party_fields_active = [
            'wc_advanced_order_field'  => false,
            'billing_wc_custom_field'  => false,
            'shipping_wc_custom_field' => false
        ];
        private $saved_checkout_fields = [];

        public static function get_instance( $fields = [] ) {
            if ( null == self::$instance ) {
                self::$instance = new self( $fields );
            }

            return self::$instance;
        }

        private function __construct( $fields ) {


            $this->saved_checkout_fields = $fields;
            add_action( 'wfacp_after_template_found', [ $this, 'actions' ] );
            add_action( 'wfacp_after_checkout_page_found', [ $this, 'actions' ], 20 );

            /**
             * Capture Billing and shipping fields
             */
            add_action( 'woocommerce_billing_fields', [ $this, 'capture_billing_fields' ], 99999 );
            add_action( 'woocommerce_shipping_fields', [ $this, 'capture_shipping_fields' ], 99999 );

            add_action( 'wfacp_after_checkout_page_found', [ $this, 'add_wrapper' ] );

            add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'woocommerce_checkout_update_order_meta' ], 9999, 2 );
            add_action( 'wfacp_update_posted_data_vice_versa_keys', [ $this, 'update_posted_data_vice_versa_keys' ] );

        }

        public function capture_billing_fields( $fields ) {
            if ( false === $this->third_party_fields_active['billing_wc_custom_field'] ) {
                return $fields;
            }
            if ( is_array( $fields ) && count( $fields ) > 0 ) {
                $this->wc_fields_under_billing = $fields;
            }


            return $fields;
        }

        public function capture_shipping_fields( $fields ) {
            if ( false === $this->third_party_fields_active['shipping_wc_custom_field'] ) {
                return $fields;
            }
            if ( is_array( $fields ) && count( $fields ) > 0 ) {
                $this->wc_fields_under_shipping = $fields;
            }


            return $fields;
        }

        /**
         * @return void
         * Checking the custom fields active or not and add Default AeroCheckout class using hook
         */
        public function actions() {
            if ( ! $this->is_enabled() ) {
                return;
            }
            $this->native_checkout_fields = WC()->checkout();
            /**
             * Prevent Field to render Custom Registered Fields Billing, Shipping or Advanced Fields
             */
            add_filter( 'wfacp_html_fields_billing_wc_custom_field', '__return_false' );
            add_filter( 'wfacp_html_fields_shipping_wc_custom_field', '__return_false' );
            add_filter( 'wfacp_html_fields_wc_advanced_order_field', '__return_false' );

            /**
             * Process the custom advanced field
             */

            add_action( 'process_wfacp_html', [ $this, 'wc_advanced_order_field' ], 9999, 2 );
            add_filter( 'wfacp_form_section', [ $this, 'detect_extra_fields' ], 10, 3 );
            add_action( 'wfacp_before_form', [ $this, 'find_extra_fields' ] );


            add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 10, 2 );

        }

        public function add_wrapper() {


            /**
             * Add default Hook for billing wrapper
             */
            add_action( 'wfacp_divider_billing', [ $this, 'before_billing_action' ], 9999 );
            add_action( 'wfacp_divider_billing_end', [ $this, 'after_billing_action' ], 99999 );
            /**
             * Add default Hook for Shipping wrapper
             */
            add_action( 'wfacp_divider_shipping', [ $this, 'before_shipping_action' ], 9999 );
            add_action( 'wfacp_divider_shipping_end', [ $this, 'after_shipping_action' ], 99999 );


        }

        /**
         * @return boolean
         * Checking the custom fields for advanced, billing and shipping
         */
        public function is_enabled() {
            $data   = $this->saved_checkout_fields;
            $status = false;
            $fields = [
                'advanced' => 'wc_advanced_order_field',
                'billing'  => 'billing_wc_custom_field',
                'shipping' => 'shipping_wc_custom_field'
            ];
            foreach ( $fields as $type => $field ) {
                if ( isset( $data[ $type ][ $field ] ) ) {
                    $this->third_party_fields_active[ $field ] = true;
                    $status                                    = true;
                }
            }

            return $status;
        }

        /**
         * @param $args
         * @param $key
         *
         * @return void
         *
         * This Function return the advance custom field which are registered on the native checkout
         * but not for Funnelkit checkout
         */
        public function wc_advanced_order_field( $args, $key ) {

            if ( $key != 'wc_advanced_order_field' ) {
                return;
            }

            /**
             * Get Native Checkout Fields
             */
            $checkout = WC()->checkout();

            $fields = apply_filters( 'wfacp_advanced_order_fields', $checkout->get_checkout_fields(), $key );


            /**
             * Get Registered AeroCheckout Fields
             */
            $address_fields = WFACP_Common::get_aero_registered_checkout_fields();

            $instance = wfacp_template();
            $data     = $instance->get_checkout_fields();


            ?>
            <div class="woocommerce-additional-fields" id="wfacp-third-party-fields-wrap">
                <?php
                do_action( 'wfacp_woocommerce_before_order_notes', WC()->checkout() );
                do_action( 'woocommerce_before_order_notes', WC()->checkout() );
                ?>
                <div class="woocommerce-additional-fields__field-wrapper">
                    <?php

                    foreach ( $fields as $key1 => $field1 ) {
                        foreach ( $field1 as $key => $field ) {
                            if ( $key1 == 'billing' || $key1 == 'shipping' ) {
                                continue;
                            }


                            if ( isset( $data[ $key1 ][ $key ] ) ) {
                                $this->fields_added[] = $key;
                                continue;
                            }

                            $field = apply_filters( 'wfacp_print_advanced_custom_fields', $field, $key );

                            if ( ( ! empty( $field ) && ! in_array( $key, $address_fields ) || in_array( $key, $this->fields_added ) ) ) {
                                $this->fields_added[] = $key;
                                if ( ! isset( $instance->already_printed_fields[ $key ] ) ) {
                                    wfacp_form_field( $key, $field, $checkout->get_value( $key ) );
                                    $instance->already_printed_fields[ $key ] = 'yes';
                                }
                            }
                        }
                    }
                    ?>
                </div>
                <?php

                do_action( 'woocommerce_after_order_notes', WC()->checkout() );
                do_action( 'wfacp_woocommerce_after_order_notes', WC()->checkout() );
                ?>
            </div>
            <?php
        }

        /**
         * @return void
         *
         * Add Billing Wrapper and add the default hook of woocommerce woocommerce_before_checkout_billing_form
         */
        public function before_billing_action() {
            do_action( 'woocommerce_before_checkout_billing_form', $this->native_checkout_fields );
        }

        public function after_billing_action() {


            do_action( 'woocommerce_after_checkout_billing_form', $this->native_checkout_fields );

        }

        /**
         * @return void
         *
         * Add Shipping Wrapper and add the default hook of woocommerce woocommerce_before_checkout_shipping_form
         */
        public function before_shipping_action() {
            do_action( 'woocommerce_before_checkout_shipping_form', $this->native_checkout_fields );
        }

        public function after_shipping_action() {
            do_action( 'woocommerce_after_checkout_shipping_form', $this->native_checkout_fields );
        }

        /**
         * Merge value of second array if not present in the first array
         *
         * @param $array1
         * @param $array2
         *
         * @return array
         */
        public function array_merge( $array1, $array2 ) {
            foreach ( $array2 as $key => $value ) {
                if ( isset( $array1[ $key ] ) ) {
                    continue;
                }
                $array1[ $key ] = $value;
            }

            return $array1;
        }

        /**
         * @return void
         *
         * This function use to Find Extra Fields
         */
        public function find_extra_fields() {
            $fields = WC()->checkout()->get_checkout_fields();

            $this->all_default_fields = $fields;
            $our_fields               = WFACP_Common::get_aero_registered_checkout_fields();


            if ( isset( $fields['billing'] ) && ! empty( $fields['billing'] ) ) {

                if ( is_array( $this->wc_fields_under_billing ) && count( $this->wc_fields_under_billing ) > 0 ) {
                    $fields['billing'] = apply_filters( 'wfacp_third_party_billing_fields', $this->array_merge( $fields['billing'], $this->wc_fields_under_billing ) );
                }

                $this->checkout_fields['billing'] = array_filter( $fields['billing'], function ( $v, $key ) use ( $our_fields ) {
                    return ! ( in_array( $key, $our_fields ) || ( isset( $v['id'] ) && in_array( $v['id'], $our_fields ) ) );
                }, ARRAY_FILTER_USE_BOTH );
            }
            if ( isset( $fields['shipping'] ) && ! empty( $fields['shipping'] ) ) {

                if ( is_array( $this->wc_fields_under_shipping ) && count( $this->wc_fields_under_shipping ) > 0 ) {
                    $fields['shipping'] = apply_filters( 'wfacp_third_party_shipping_fields', $this->array_merge( $fields['shipping'], $this->wc_fields_under_shipping ) );
                }

                $this->checkout_fields['shipping'] = array_filter( $fields['shipping'], function ( $v, $key ) use ( $our_fields ) {
                    return ! ( in_array( $key, $our_fields ) || ( isset( $v['id'] ) && in_array( $v['id'], $our_fields ) ) );
                }, ARRAY_FILTER_USE_BOTH );
            }
        }

        public function detect_extra_fields( $section, $section_index, $step ) {
            $offset = 0;
            $fields = $section['fields'];

            $temp = [];


            foreach ( $section['fields'] as $key => $single ) {
                if ( $single['id'] == 'billing_wc_custom_field' && ! empty( $this->checkout_fields['billing'] ) ) {
                    $fields = $this->merge_fields( $fields, $offset, $this->checkout_fields['billing'], $single );
                    $temp[] = $single['id'];
                    break;
                }
                if ( $single['id'] === 'shipping_wc_custom_field' && ! empty( $this->checkout_fields['shipping'] ) ) {
                    $fields = $this->merge_fields( $fields, $offset, $this->checkout_fields['shipping'], $single );
                    $temp[] = $single['id'];
                    break;
                }
                $offset ++;
            }

            $extra_fields = [];
            if ( in_array( 'billing_wc_custom_field', $temp ) && isset( $this->checkout_fields['shipping'] ) ) {
                $extra_fields = $this->checkout_fields['shipping'];

            } elseif ( isset( $this->checkout_fields['billing'] ) ) {
                $extra_fields = $this->checkout_fields['billing'];

            }


            $section['fields'] = apply_filters( 'wfacp_detect_extra_fields', $fields, $extra_fields, $temp );

            return $section;
        }

        public function merge_fields( &$oldArray, $offset, $new_array, $place_fields ) {
            try {
                foreach ( $new_array as $i => $item ) {
                    $new_array[ $i ]['id'] = $i;
                    if ( ! isset( $new_array[ $i ]['class'] ) || ! is_array( $new_array[ $i ]['class'] ) ) {
                        $new_array[ $i ]['class'] = [];
                    }
                    if ( false !== strpos( $i, 'billing_' ) && isset( $this->checkout_fields['billing'][ $i ] ) && isset( $this->checkout_fields['billing'][ $i ]['id'] ) && ( $this->checkout_fields['billing'][ $i ]['id'] != $new_array[ $i ]['id'] ) ) {
                        $new_array[ $i ]['id'] = $this->checkout_fields['billing'][ $i ]['id'];
                    }

                    if ( false !== strpos( $i, 'shipping_' ) && isset( $this->checkout_fields['shipping'][ $i ] ) && isset( $this->checkout_fields['shipping'][ $i ]['id'] ) && ( $this->checkout_fields['shipping'][ $i ]['id'] != $new_array[ $i ]['id'] ) ) {
                        $new_array[ $i ]['id'] = $this->checkout_fields['shipping'][ $i ]['id'];
                    }
                    $new_array[ $i ]['external_field_name'] = $i;
                    $new_array[ $i ]['class']               = $new_array[ $i ]['class'] + $place_fields['class'];
                    $this->fields_added[]                   = $new_array[ $i ]['id'];
                }

                return array_slice( $oldArray, 0, $offset, true ) + $new_array + array_slice( $oldArray, $offset, null, true );
            } catch ( Exception|Error $error ) {
                return $oldArray;
            }

        }

        public function checkout_fields( $fields ) {
            try {
                if ( ! is_array( $fields ) || count( $fields ) == 0 ) {
                    return $fields;
                }
                $other_address_fields = WFACP_Common::get_aero_registered_checkout_fields();
                foreach ( $fields as $field_val_key => $field ) {

                    if ( in_array( $field_val_key, $other_address_fields ) ) {
                        continue;
                    }
                    $field['id'] = $field_val_key;
                    if ( ! isset( $field['priority'] ) ) {
                        $field['priority'] = 100;
                    }
                    $this->new_fields['billing'][ $field_val_key ] = $field;
                    $this->previous_keys[ $field_val_key ]         = $field['priority'];
                }
            } catch ( Exception|Error $error ) {
                return $fields;
            }

            return $fields;
        }

        /**
         * @param $args
         * @param $key
         *
         * @return mixed
         *
         * Add Default AeroCheckout class on the field when class not exists
         */
        public function add_default_wfacp_styling( $args, $key ) {

            $other_address_fields = WFACP_Common::get_aero_registered_checkout_fields();

            if ( ! in_array( $key, $other_address_fields ) && isset( $args['class'] ) && is_array( $args['class'] ) && ! in_array( 'wfacp-col-full', $args['class'] ) ) {
                $args['class'] = array_merge( [ 'wfacp-form-control-wrapper', 'wfacp-col-full' ], $args['class'] );
                if ( false !== strpos( $args['type'], 'hidden' ) ) {
                    $args['class'][] = 'wfacp_type_hidden_field';
                }
            }
            if ( isset( $args['cssready'] ) && is_array( $args['cssready'] ) && ! in_array( 'wfacp-col-full', $args['cssready'] ) ) {
                $args['cssready'] = [ 'wfacp-col-full' ];
            }

            if ( isset( $args['type'] ) && 'checkbox' !== $args['type'] ) {
                if ( isset( $args['input_class'] ) && is_array( $args['input_class'] ) && ! in_array( 'wfacp-form-control', $args['input_class'] ) ) {
                    $args['input_class'] = array_merge( [ 'wfacp-form-control' ], $args['input_class'] );
                }
                if ( isset( $args['label_class'] ) && is_array( $args['label_class'] ) && ! in_array( 'wfacp-form-control-label', $args['label_class'] ) ) {
                    $args['label_class'] = array_merge( [ 'wfacp-form-control-label' ], $args['label_class'] );;
                }
            }

            if ( is_array( $this->wc_fields_under_billing ) && count( $this->wc_fields_under_billing ) > 0 && array_key_exists( $key, $this->wc_fields_under_billing ) && in_array( $key, $this->fields_added ) ) {
                if ( isset( $args['label'] ) && empty( $args['label'] ) && isset( $this->wc_fields_under_billing[ $key ]['label'] ) ) {
                    $args['label'] = $this->wc_fields_under_billing[ $key ]['label'];
                }
                if ( isset( $args['options'] ) && empty( $args['options'] ) && isset( $this->wc_fields_under_billing[ $key ]['options'] ) ) {
                    $args['options'] = $this->wc_fields_under_billing[ $key ]['options'];
                }
            }


            if ( ! isset( $args['is_wfacp_field'] ) && 'select' !== $args['type'] && ( isset( $args['placeholder'] ) && empty( $args['placeholder'] ) ) && isset( $args['label'] ) ) {
                $args['placeholder'] = $args['label'];
            }


            if ( ! in_array( $key, $other_address_fields ) && isset( $args['type'] ) && 'select' === $args['type'] && count( $this->wc_fields_under_billing ) > 0 && array_key_exists( $key, $this->wc_fields_under_billing ) && isset( $this->wc_fields_under_billing[ $key ]['options'] ) ) {
                $args['options'] = $this->wc_fields_under_billing[ $key ]['options'];
            }

            /**
             * Merge Default Classes under billing and shipping address fields
             */

            if ( apply_filters( 'wfacp_merge_default_billing_fields_classes', false, $key ) && isset( $args['class'] ) && is_array( $args['class'] ) && isset( $this->wc_fields_under_billing[ $key ]['class'] ) && is_array( $this->wc_fields_under_billing[ $key ]['class'] ) ) {
                $args['class'] = array_values( array_unique( array_merge( $args['class'], $this->wc_fields_under_billing[ $key ]['class'] ) ) );
            }

            if ( apply_filters( 'wfacp_merge_default_shipping_fields_classes', false, $key ) && isset( $args['class'] ) && is_array( $args['class'] ) && isset( $this->wc_fields_under_shipping[ $key ]['class'] ) && is_array( $this->wc_fields_under_shipping[ $key ]['class'] ) ) {
                $args['class'] = array_values( array_unique( array_merge( $args['class'], $this->wc_fields_under_shipping[ $key ]['class'] ) ) );
            }

            $check_default_classes_for_field = [ 'billing_company' ];


            if ( strpos( $key, 'billing_' ) !== false && in_array( $key, $check_default_classes_for_field ) && isset( $this->all_default_fields['billing'][ $key ]['class'] ) ) {


                if ( is_array( $args['class'] ) && is_array( $this->all_default_fields['billing'][ $key ]['class'] ) ) {
                    $args['class'] = array_unique( array_merge( $args['class'], $this->all_default_fields['billing'][ $key ]['class'] ) );
                }
            }


            return apply_filters( 'wfacp_woocommerce_form_field_args', $args, $key, $this->wc_fields_under_billing, $this->wc_fields_under_shipping );
        }

        public function woocommerce_checkout_update_order_meta( $order_id, $data ) {
            $missingKeys = [];

            if ( ! isset( $_POST['_wfacp_post_id'] ) ) {
                return;
            }
            $order = wc_get_order( $order_id );

            $default_checkout_fields = WC()->checkout()->get_checkout_fields();
            $excludedKeys            = WFACP_Common::get_aero_registered_checkout_fields();

            if ( is_array( $default_checkout_fields ) && count( $default_checkout_fields ) > 0 ) {

                foreach ( $default_checkout_fields as $i => $default_checkout_field ) {

                    if ( $i !== 'billing' && $i != 'shipping' ) {
                        continue;

                    }
                    foreach ( $default_checkout_field as $key => $field ) {
                        if ( ! in_array( $key, $excludedKeys ) ) {
                            $missingKeys[] = $key;
                        }

                    }
                }
            }


            if ( is_array( $missingKeys ) && count( $missingKeys ) > 0 ) {
                foreach ( $missingKeys as $item ) {
                    if ( ! empty( $item ) && isset( $_POST[ $item ] ) ) {
                        $meta_key   = '_' . $item;
                        $meta_value = get_post_meta( $order_id, $meta_key, true );
                        if ( empty( $meta_value ) ) {
                            $order->{$item} = $_POST[ $item ];
                            $order->update_meta_data( '_' . $item, $_POST[ $item ] );
                        }

                    }
                }
                $order->save();
            }


        }


        public function update_posted_data_vice_versa_keys( $keys ) {
            $missingKeys = [];


            $relevantKeys = array_filter( array_keys( $_POST ), function ( $key ) {
                return strpos( $key, 'billing_' ) === 0 || strpos( $key, 'shipping_' ) === 0;
            } );


            $excludedKeys = WFACP_Common::get_aero_registered_checkout_fields();
            foreach ( $relevantKeys as $key ) {
                if ( ! array_key_exists( $key, $keys ) && ! in_array( $key, $excludedKeys ) ) {
                    $missingKeys[] = $key;
                }
            }

            if ( is_array( $missingKeys ) && count( $missingKeys ) > 0 ) {
                foreach ( $missingKeys as $i => $v ) {
                    $keys[ $v ] = $v;
                }
            }

            return $keys;
        }

    }
}
