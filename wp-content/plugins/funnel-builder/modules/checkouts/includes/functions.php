<?php
function wfacp_is_elementor() {

	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		if ( version_compare( ELEMENTOR_VERSION, '3.2.0', '<=' ) ) {
			return \Elementor\Plugin::$instance->db->is_built_with_elementor( WFACP_Common::get_id() );
		} else {
			return \Elementor\Plugin::$instance->documents->get( WFACP_Common::get_id() )->is_built_with_elementor();
		}
	}

	return false;
}


/**
 * Return instance of Current Template Class
 * @return WFACP_Template_Common
 */
function wfacp_template() {
	if ( is_null( WFACP_Core()->template_loader ) ) {
		return null;
	}

	return WFACP_Core()->template_loader->get_template_ins();
}

function wfacp_elementor_edit_mode() {
	$status = false;
	if ( isset( $_REQUEST['elementor-preview'] ) || ( isset( $_REQUEST['action'] ) && ( 'elementor' == $_REQUEST['action'] || 'elementor_ajax' == $_REQUEST['action'] ) ) ) {
		$status = true;

	}
	if ( ( isset( $_REQUEST['preview_id'] ) && isset( $_REQUEST['preview_nonce'] ) ) || isset( $_REQUEST['elementor-preview'] ) ) {
		$status = true;
	}

	return $status;
}

function wfacp_check_nonce() {
	$rsp = [
		'status' => 'false',
		'msg'    => 'Invalid Call',
	];
	if ( isset( $_POST['post_data'] ) ) {
		$post_data   = [];
		$t_post_data = filter_input( INPUT_POST, 'post_data', FILTER_UNSAFE_RAW );
		parse_str( $t_post_data, $post_data );
		if ( ! empty( $post_data ) ) {
			WFACP_Common::$post_data = $post_data;
		}
	}
	$wfacp_nonce = filter_input( INPUT_POST, 'wfacp_nonce', FILTER_UNSAFE_RAW );

	if ( is_null( $wfacp_nonce ) || ! wp_verify_nonce( $wfacp_nonce, 'wfacp_secure_key' ) ) {
		wp_send_json( $rsp );
	}
}

if ( ! function_exists( 'wfacp_is_hpos_enabled' ) ) {
	function wfacp_is_hpos_enabled() {
		return ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() );
	}
}

if ( ! function_exists( 'wfacp_get_order_meta' ) ) {
	function wfacp_get_order_meta( $order, $key = '' ) {
		if ( empty( $key ) ) {
			return '';
		}
		if ( ! $order instanceof WC_Abstract_Order ) {
			return '';
		}

		$meta_value = $order->get_meta( $key );
		if ( ! empty( $meta_value ) ) {
			return $meta_value;
		}

		if ( true === wfacp_is_hpos_enabled() ) {
			global $wpdb;
			$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM `{$wpdb->prefix}wc_orders_meta` WHERE `meta_key`=%s AND `order_id`=%d", $key, $order->get_id() ) );
		}

		if ( ! empty( $meta_value ) ) {
			return maybe_unserialize( $meta_value );
		}

		return get_post_meta( $order->get_id(), $key, true );
	}
}

/** wfacp_form_field Checkout Fields  */

if ( ! function_exists( 'wfacp_form_field' ) ) {

	/**
	 * Outputs a checkout/address form field.
	 *
	 * @param string $key Key.
	 * @param mixed $args Arguments.
	 * @param string $value (default: null).
	 *
	 * @return string
	 */
	function wfacp_form_field( $key, $args, $value = null ) {
		$defaults = array(
			'type'              => 'text',
			'label'             => '',
			'description'       => '',
			'placeholder'       => '',
			'maxlength'         => false,
			'required'          => false,
			'autocomplete'      => false,
			'id'                => $key,
			'class'             => array(),
			'label_class'       => array(),
			'input_class'       => array(),
			'return'            => false,
			'options'           => array(),
			'custom_attributes' => array(),
			'validate'          => array(),
			'default'           => '',
			'autofocus'         => '',
			'priority'          => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$key  = apply_filters( 'wfacp_form_field_key', $key, $args, $value );
		$args = apply_filters( 'woocommerce_form_field_args', $args, $key, $value );

		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
		} else {
			$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
		}

		if ( is_string( $args['label_class'] ) ) {
			$args['label_class'] = array( $args['label_class'] );
		}

		if ( is_null( $value ) ) {
			$value = $args['default'];
		}

		// Custom attribute handling.
		$custom_attributes         = array();
		$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

		if ( $args['maxlength'] ) {
			$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
		}

		if ( ! empty( $args['autocomplete'] ) ) {
			$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
		}

		if ( true === $args['autofocus'] ) {
			$args['custom_attributes']['autofocus'] = 'autofocus';
		}

		if (isset($args['description']) &&  $args['description'] ) {
			$args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
		}

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( ! empty( $args['validate'] ) ) {
			foreach ( $args['validate'] as $validate ) {
				$args['class'][] = 'validate-' . $validate;
			}
		}

		$field           = '';
		$label_id        = $args['id'];
		$sort            = $args['priority'] ? $args['priority'] : '';
		$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s <span class="wfacp_inline_error" data-key="%2$s"></span></p>';

		switch ( $args['type'] ) {
			case 'country':
				$countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();

				if ( 1 === count( $countries ) ) {

					$field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';

					$field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';

				} else {
					$data_label = ! empty( $args['label'] ) ? 'data-label="' . esc_attr( $args['label'] ) . '"' : '';

					$field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="country_to_state country_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ? $args['placeholder'] : esc_attr__( 'Select a country / region&hellip;', 'woocommerce' ) ) . '" ' . $data_label . '><option value="">' . esc_html__( 'Select a country / region&hellip;', 'woocommerce' ) . '</option>';

					foreach ( $countries as $ckey => $cvalue ) {
						$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
					}

					$field .= '</select>';

					$field .= '<noscript><button type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__( 'Update country / region', 'woocommerce' ) . '">' . esc_html__( 'Update country / region', 'woocommerce' ) . '</button></noscript>';

				}

				break;
			case 'state':
				/* Get country this state field is representing */ $for_country = isset( $args['country'] ) ? $args['country'] : WC()->checkout->get_value( 'billing_state' === $key ? 'billing_country' : 'shipping_country' );
				$states                                                         = WC()->countries->get_states( $for_country );

				if ( is_array( $states ) && empty( $states ) ) {

					$field_container = '<p class="form-row %1$s" id="%2$s" style="display: none">%3$s</p>';

					$field .= '<input type="hidden" class="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '" readonly="readonly" data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"/>';

				} elseif ( ! is_null( $for_country ) && is_array( $states ) ) {
					$data_label = ! empty( $args['label'] ) ? 'data-label="' . esc_attr( $args['label'] ) . '"' : '';

					$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="state_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ? $args['placeholder'] : esc_html__( 'Select an option&hellip;', 'woocommerce' ) ) . '"  data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . $data_label . '>
						<option value="">' . esc_html__( 'Select an option&hellip;', 'woocommerce' ) . '</option>';

					foreach ( $states as $ckey => $cvalue ) {
						$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
					}

					$field .= '</select>';

				} else {

					$field .= '<input type="text" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $value ) . '"  placeholder="' . esc_attr( $args['placeholder'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . implode( ' ', $custom_attributes ) . ' data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"/>';

				}

				break;
			case 'textarea':
				$field .= '<textarea name="' . esc_attr( $key ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '' ) . ( empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '' ) . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $value ) . '</textarea>';

				break;
			case 'checkbox':
				$field = '<label class="checkbox ' . implode( ' ', $args['label_class'] ) . '" ' . implode( ' ', $custom_attributes ) . '>
						<input type="' . esc_attr( $args['type'] ) . '" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( $value, 1, false ) . ' /> ' . $args['label'] . $required . '</label>';

				break;
			case 'text':
			case 'password':
			case 'datetime':
			case 'datetime-local':
			case 'date':
			case 'month':
			case 'time':
			case 'week':
			case 'number':
			case 'email':
			case 'url':
			case 'tel':
				$field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

				break;
			case 'hidden':
				$field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-hidden ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

				break;
			case 'select':
				$field   = '';
				$options = '';

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						if ( '' === $option_key ) {
							// If we have a blank option, select2 needs a placeholder.
							if ( empty( $args['placeholder'] ) ) {
								$args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'woocommerce' );
							}
							$custom_attributes[] = 'data-allow_clear="true"';
						}
						$options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_html( $option_text ) . '</option>';
					}

					$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
							' . $options . '
						</select>';
				}

				break;
			case 'radio':
				$label_id .= '_' . current( array_keys( $args['options'] ) );

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						$field .= '<input type="radio" class="input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" ' . implode( ' ', $custom_attributes ) . ' id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
						$field .= '<label for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" class="radio ' . implode( ' ', $args['label_class'] ) . '">' . esc_html( $option_text ) . '</label>';
					}
				}

				break;
		}

		if ( ! empty( $field ) ) {
			$field_html = '';

			if ( $args['label'] && 'checkbox' !== $args['type'] ) {

				$field_html .= apply_filters( 'wfacp_before_checkout_label', '', $args );

				$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . wp_kses_post( $args['label'] ) . $required . '</label>';
				$field_html .= apply_filters( 'wfacp_after_checkout_label', '', $args );
			}

			$field_html .= '<span class="woocommerce-input-wrapper">' . $field;

			if (isset($args['description']) &&  $args['description'] ) {
				$field_html .= '<span class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</span>';
			}

			$field_html .= '</span>';

			$container_class = esc_attr( implode( ' ', $args['class'] ) );
			$container_id    = esc_attr( $args['id'] ) . '_field';
			$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
		}

		/**
		 * Filter by type.
		 */
		$field = apply_filters( 'woocommerce_form_field_' . $args['type'], $field, $key, $args, $value );

		/**
		 * General filter on form fields.
		 *
		 * @since 3.4.0
		 */
		$field = apply_filters( 'woocommerce_form_field', $field, $key, $args, $value );

		if ( $args['return'] ) {
			return $field;
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $field;
		}
	}
}


if ( ! function_exists( 'wfacp_get_translation' ) ) {
	function wfacp_get_translation() {
		return [
			'de_DE' => [
				'Your Payment Information'                                                                           => 'Ihre Zahlungsinformationen',
				'Your payment information'                                                                           => 'Ihre Zahlungsinformationen',
				'Payment Information'                                                                                => 'Zahlungsinformationen',
				'Shipping Information'                                                                               => 'Informationen zum Versand',
				'Select Payment Method'                                                                              => 'Zahlungsmethode auswählen',
				'All transactions are secured and encrypted'                                                         => 'Alle Transaktionen sind gesichert und verschlüsselt',
				'We Respect Your Privacy & Information'                                                              => 'Wir respektieren Ihre Privatsphäre und Informationen',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Alle Transaktionen sind sicher und verschlüsselt. Kreditkarteninformationen werden niemals auf unseren Servern gespeichert.',
				'Customer Information'                                                                               => 'Informationen für Kunden',
				'Your Products'                                                                                      => 'Ihre Produkte',
				'Billing Details'                                                                                    => 'Details zur Rechnungsstellung',
				'Shipping'                                                                                           => 'Versand',
				'Payment'                                                                                            => 'Zahlung',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'WAS IST IN IHREM PLAN ENTHALTEN?',
				'Best Value'                                                                                         => 'Bester Wert',
				'Your Plans'                                                                                         => 'Ihre Pläne',
				'Select Your Plan'                                                                                   => 'Wählen Sie Ihren Plan',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* 100 % sicher und sicher Sichere Zahlungen *',
				'Use a different Billing address'                                                                    => 'Verwenden Sie eine andere Rechnungsadresse',
				'Use a different billing address'                                                                    => 'Verwenden Sie eine andere Rechnungsadresse',
				'Choose Your Product'                                                                                => 'Wählen Sie Ihr Produkt',
				'Use a different shipping address'                                                                   => 'Verwenden Sie eine andere Lieferadresse',
				'Use a different Shipping address'                                                                   => 'Verwenden Sie eine andere Versandadresse',
				'Your Billing Address'                                                                               => 'Ihre Rechnungsadresse',
				'Enter Customer Information'                                                                         => 'Kundeninformationen eingeben',
				'All transactions are secure and encrypted.'                                                         => 'Alle Transaktionen sind sicher und verschlüsselt.',
				'Your Cart'                                                                                          => 'Ihr Warenkorb',
				'Country'                                                                                            => 'Land',
				'Order Summary'                                                                                      => 'Zusammenfassung der Bestellung',
				'Shipping Address'                                                                                   => 'Lieferadresse',
				'Billing Address'                                                                                    => 'Rechnungsadresse',
				'Your Shipping Address'                                                                              => 'Ihre Lieferadresse',
				'Your Information'                                                                                   => 'Ihre Informationen',
				'show order summary'                                                                                 => 'Bestellübersicht anzeigen',
				'Show Order Summary'                                                                                 => 'Bestellung anzeigen Zusammenfassung',
				'Hide Order Summary'                                                                                 => 'Zusammenfassung der Bestellung ausblenden',
				'Shipping Phone'                                                                                     => 'Versand Telefon',
				'Confirm Your Order'                                                                                 => 'Bestätigen Sie Ihre Bestellung',
				'Confirm your order'                                                                                 => 'Bestätigen Sie Ihre Bestellung',
				'Select Shipping Method'                                                                             => 'Versandart auswählen',
				'COMPLETE PURCHASE'                                                                                  => 'KOMPLETTKAUF',
				'INFORMATION'                                                                                        => 'INFORMATIONEN',
				'Information'                                                                                        => 'Informationen',
				'Complete Your Order Now'                                                                            => 'Schließen Sie Ihre Bestellung jetzt ab',
				'Payment method'                                                                                     => 'Zahlungsmethode',
				'Payment Methods'                                                                                    => 'Zahlungsarten',
				'Payment Method'                                                                                     => 'Zahlungsmethode',
				'PLACE ORDER NOW'                                                                                    => 'BESTELLUNG JETZT AUFGEBEN',
				'Place Order Now'                                                                                    => 'Jetzt bestellen',
				'Method'                                                                                             => 'Methode',
				'Hide'                                                                                               => 'Ausblenden',
				'Show'                                                                                               => 'anzeigen',
				'Place Your Order Now'                                                                               => 'Bestellen Sie jetzt',
				'Apply'                                                                                              => 'Bewerbung',
				'Review Order Summary'                                                                               => 'Zusammenfassung der Bestellung',
				'Apartment, suite, unit, etc.'                                                                       => 'Appartement, suite, eenheid, enz.',
				'Proceed to Final Step'                                                                              => 'Weiter zum letzten Schritt',
				'Contact Information'                                                                                => "Kontaktinformationen",
			],
			'es_ES' => [
				'Your Payment Information'                                                                           => 'Su información de pago',
				'Your payment information'                                                                           => 'Su información de pago',
				'Payment Information'                                                                                => 'Información de pago',
				'Shipping Information'                                                                               => 'Información de envío',
				'Select Payment Method'                                                                              => 'Seleccionar método de pago',
				'All transactions are secured and encrypted'                                                         => 'Todas las transacciones están aseguradas y cifradas',
				'We Respect Your Privacy & Information'                                                              => 'Respetamos su privacidad e información',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Todas las transacciones son seguras y están cifradas. La información de tarjetas de crédito nunca se almacena en nuestros servidores.',
				'Customer Information'                                                                               => 'Información del cliente',
				'Your Products'                                                                                      => 'Sus productos',
				'Billing Details'                                                                                    => 'Detalles de facturación',
				'Shipping'                                                                                           => 'Envío',
				'Payment'                                                                                            => 'Pago',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => '¿QUÉ INCLUYE SU PLAN?',
				'Best Value'                                                                                         => 'Mejor valor',
				'Your Plans'                                                                                         => 'Sus planes',
				'Select Your Plan'                                                                                   => 'Seleccione su plan',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* Pagos 100% seguros y protegidos *',
				'Use a different Billing address'                                                                    => 'Usar una dirección de facturación diferente',
				'Use a different billing address'                                                                    => 'Usar una dirección de facturación diferente',
				'Choose Your Product'                                                                                => 'Elija su producto',
				'Use a different shipping address'                                                                   => 'Usar una dirección de envío diferente',
				'Use a different Shipping address'                                                                   => 'Usar una dirección de envío diferente',
				'Your Billing Address'                                                                               => 'Su dirección de facturación',
				'Enter Customer Information'                                                                         => 'Introducir información del cliente',
				'All transactions are secure and encrypted.'                                                         => 'Todas las transacciones son seguras y están cifradas.',
				'Your Cart'                                                                                          => 'Su carrito',
				'Country'                                                                                            => 'País',
				'Order Summary'                                                                                      => 'Resumen del pedido',
				'Shipping Address'                                                                                   => 'Dirección de envío',
				'Billing Address'                                                                                    => 'Dirección de facturación',
				'Your Shipping Address'                                                                              => 'Su dirección de envío',
				'Your Information'                                                                                   => 'Su información',
				'show order summary'                                                                                 => 'mostrar resumen del pedido',
				'Show Order Summary'                                                                                 => 'Mostrar resumen del pedido',
				'Hide Order Summary'                                                                                 => 'Ocultar resumen del pedido',
				'Shipping Phone'                                                                                     => 'Teléfono de envío',
				'Confirm Your Order'                                                                                 => 'Confirme su pedido',
				'Confirm your order'                                                                                 => 'Confirme su pedido',
				'Select Shipping Method'                                                                             => 'Seleccionar método de envío',
				'COMPLETE PURCHASE'                                                                                  => 'COMPLETAR COMPRA',
				'INFORMATION'                                                                                        => 'INFORMACIÓN',
				'Information'                                                                                        => 'Información',
				'Complete Your Order Now'                                                                            => 'Complete su pedido ahora',
				'Payment method'                                                                                     => 'Método de pago',
				'Payment Methods'                                                                                    => 'Métodos de pago',
				'Payment Method'                                                                                     => 'Método de pago',
				'PLACE ORDER NOW'                                                                                    => 'REALIZAR PEDIDO AHORA',
				'Place Order Now'                                                                                    => 'Realizar pedido ahora',
				'Method'                                                                                             => 'Método',
				'Hide'                                                                                               => 'Ocultar',
				'Show'                                                                                               => 'Mostrar',
				'Place Your Order Now'                                                                               => 'Realice su pedido ahora',
				'Apply'                                                                                              => 'Aplicar',
				'Review Order Summary'                                                                               => 'Revisar resumen del pedido',
				'Apartment, suite, unit, etc.'                                                                       => 'Apartamento, suite, unidad, etc.',
				'Proceed to Final Step'                                                                              => 'Proceder al paso final',
				'Contact Information'                                                                                => 'Información de contacto',
			],
			'bg_BG' => [
				'Your Payment Information'                                                                           => 'Вашата информация за плащане',
				'Your payment information'                                                                           => 'Вашата информация за плащане',
				'Payment Information'                                                                                => 'Информация за плащане',
				'Shipping Information'                                                                               => 'Информация за доставката',
				'Select Payment Method'                                                                              => 'Изберете метод на плащане',
				'All transactions are secured and encrypted'                                                         => 'Всички транзакции са защитени и криптирани',
				'We Respect Your Privacy & Information'                                                              => 'Уважаваме Вашата поверителност и информация',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Всички транзакции са сигурни и криптирани. Информацията за кредитните карти никога не се съхранява на нашите сървъри.',
				'Customer Information'                                                                               => 'Информация за клиентите',
				'Your Products'                                                                                      => 'Вашите продукти',
				'Billing Details'                                                                                    => 'Данни за фактуриране',
				'Shipping'                                                                                           => 'Доставка',
				'Payment'                                                                                            => 'Плащане',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'КАКВО Е ВКЛЮЧЕНО ВЪВ ВАШИЯ ПЛАН?',
				'Best Value'                                                                                         => 'Най-добра стойност',
				'Your Plans'                                                                                         => 'Вашите планове',
				'Select Your Plan'                                                                                   => 'Изберете своя план',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* 100% Secure &amp; Безопасни плащания *',
				'Use a different Billing address'                                                                    => 'Използване на различен адрес за фактуриране',
				'Use a different billing address'                                                                    => 'Използване на различен адрес за фактуриране',
				'Choose Your Product'                                                                                => 'Изберете своя продукт',
				'Use a different shipping address'                                                                   => 'Използване на различен адрес за доставка',
				'Use a different Shipping address'                                                                   => 'Използване на различен адрес за доставка',
				'Your Billing Address'                                                                               => 'Вашият адрес за фактуриране',
				'Enter Customer Information'                                                                         => 'Въвеждане на информация за клиента',
				'All transactions are secure and encrypted.'                                                         => 'Всички транзакции са сигурни и криптирани.',
				'Your Cart'                                                                                          => 'Вашата количка',
				'Country'                                                                                            => 'Държава',
				'Order Summary'                                                                                      => 'Резюме на поръчката',
				'Shipping Address'                                                                                   => 'Адрес за доставка',
				'Billing Address'                                                                                    => 'Адрес за фактуриране',
				'Your Shipping Address'                                                                              => 'Вашият адрес за доставка',
				'Your Information'                                                                                   => 'Вашата информация',
				'show order summary'                                                                                 => 'Покажи резюме на поръчката',
				'Show Order Summary'                                                                                 => 'Покажи резюме на поръчката',
				'Hide Order Summary'                                                                                 => 'Скриване на резюмето на поръчката',
				'Shipping Phone'                                                                                     => 'Телефон за доставка',
				'Confirm Your Order'                                                                                 => 'Потвърдете поръчката си',
				'Confirm your order'                                                                                 => 'Потвърдете поръчката си',
				'Select Shipping Method'                                                                             => 'Изберете метод за доставка',
				'COMPLETE PURCHASE'                                                                                  => 'ЗАВЪРШЕТЕ ПОКУПКАТА',
				'INFORMATION'                                                                                        => 'ИНФОРМАЦИЯ',
				'Information'                                                                                        => 'Информация',
				'Complete Your Order Now'                                                                            => 'Завършете поръчката си сега',
				'Payment method'                                                                                     => 'Метод на плащане',
				'Payment Methods'                                                                                    => 'Методи на плащане',
				'Payment Method'                                                                                     => 'Метод на плащане',
				'PLACE ORDER NOW'                                                                                    => 'НАПРАВЕТЕ ПОРЪЧКА СЕГА',
				'Place Order Now'                                                                                    => 'Направете поръчка сега',
				'Method'                                                                                             => 'Метод',
				'Hide'                                                                                               => 'Скрий',
				'Show'                                                                                               => 'Покажи',
				'Place Your Order Now'                                                                               => 'Направете поръчката си сега',
				'Apply'                                                                                              => 'Приложи',
				'Review Order Summary'                                                                               => 'Преглед на резюмето на поръчката',
				'Apartment, suite, unit, etc.'                                                                       => 'Апартамент, апартамент, помещение и т.н.',
				'Proceed to Final Step'                                                                              => 'Преминете към последната стъпка',
				'Contact Information'                                                                                => 'Информация за контакт',
			],
			'ar' => [
				'Your Payment Information'                                                                           => 'معلومات الدفع الخاصة بك',
				'Your payment information'                                                                           => 'معلومات الدفع الخاصة بك',
				'Payment Information'                                                                                => 'معلومات الدفع',
				'Shipping Information'                                                                               => 'معلومات الشحن',
				'Select Payment Method'                                                                              => 'اختر طريقة الدفع',
				'All transactions are secured and encrypted'                                                         => 'جميع المعاملات آمنة ومشفرة',
				'We Respect Your Privacy & Information'                                                              => 'نحن نحترم خصوصيتك ومعلوماتك',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'جميع المعاملات آمنة ومشفرة. لا يتم تخزين معلومات بطاقة الائتمان أبدًا على خوادمنا.',
				'Customer Information'                                                                               => 'معلومات العميل',
				'Your Products'                                                                                      => 'منتجاتك',
				'Billing Details'                                                                                    => 'تفاصيل الفواتير',
				'Shipping'                                                                                           => 'الشحن',
				'Payment'                                                                                            => 'الدفع',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'ما الذي تتضمنه خطتك؟',
				'Best Value'                                                                                         => 'أفضل قيمة',
				'Your Plans'                                                                                         => 'خططك',
				'Select Your Plan'                                                                                   => 'اختر خطتك',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* مدفوعات آمنة ومضمونة بنسبة 100% *',
				'Use a different Billing address'                                                                    => 'استخدام عنوان فواتير مختلف',
				'Use a different billing address'                                                                    => 'استخدام عنوان فواتير مختلف',
				'Choose Your Product'                                                                                => 'اختر منتجك',
				'Use a different shipping address'                                                                   => 'استخدام عنوان شحن مختلف',
				'Use a different Shipping address'                                                                   => 'استخدام عنوان شحن مختلف',
				'Your Billing Address'                                                                               => 'عنوان إرسال الفواتير الخاص بك',
				'Enter Customer Information'                                                                         => 'إدخال معلومات العميل',
				'All transactions are secure and encrypted.'                                                         => 'جميع المعاملات آمنة ومشفرة.',
				'Your Cart'                                                                                          => 'عربة التسوق الخاصة بك',
				'Country'                                                                                            => 'البلد',
				'Order Summary'                                                                                      => 'ملخص الطلب',
				'Shipping Address'                                                                                   => 'عنوان الشحن',
				'Billing Address'                                                                                    => 'عنوان الفواتير',
				'Your Shipping Address'                                                                              => 'عنوان الشحن الخاص بك',
				'Your Information'                                                                                   => 'معلوماتك',
				'show order summary'                                                                                 => 'عرض ملخص الطلب',
				'Show Order Summary'                                                                                 => 'عرض ملخص الطلب',
				'Hide Order Summary'                                                                                 => 'إخفاء ملخص الطلب',
				'Shipping Phone'                                                                                     => 'هاتف الشحن',
				'Confirm Your Order'                                                                                 => 'تأكيد طلبك',
				'Confirm your order'                                                                                 => 'تأكيد طلبك',
				'Select Shipping Method'                                                                             => 'اختر طريقة الشحن',
				'COMPLETE PURCHASE'                                                                                  => 'إتمام الشراء',
				'INFORMATION'                                                                                        => 'المعلومات',
				'Information'                                                                                        => 'المعلومات',
				'Complete Your Order Now'                                                                            => 'أكمل طلبك الآن',
				'Payment method'                                                                                     => 'طريقة الدفع',
				'Payment Methods'                                                                                    => 'طرق الدفع',
				'Payment Method'                                                                                     => 'طريقة الدفع',
				'PLACE ORDER NOW'                                                                                    => 'إرسال الطلب الآن',
				'Place Order Now'                                                                                    => 'إرسال الطلب الآن',
				'Method'                                                                                             => 'طريقة',
				'Hide'                                                                                               => 'إخفاء',
				'Show'                                                                                               => 'عرض',
				'Place Your Order Now'                                                                               => 'قم بإرسال طلبك الآن',
				'Apply'                                                                                              => 'تطبيق',
				'Review Order Summary'                                                                               => 'مراجعة ملخص الطلب',
				'Apartment, suite, unit, etc.'                                                                       => 'شقة، جناح، وحدة، إلخ.',
				'Proceed to Final Step'                                                                              => 'المتابعة إلى الخطوة النهائية',
				'Contact Information'                                                                                => 'معلومات الاتصال',
			],
			'fr_FR' => [
				'Your Payment Information'                                                                           => 'Vos informations de paiement',
				'Your payment information'                                                                           => 'Vos informations de paiement',
				'Payment Information'                                                                                => 'Informations de paiement',
				'Shipping Information'                                                                               => 'Informations de livraison',
				'Select Payment Method'                                                                              => 'Sélectionner un moyen de paiement',
				'All transactions are secured and encrypted'                                                         => 'Toutes les transactions sont sécurisées et cryptées',
				'We Respect Your Privacy & Information'                                                              => 'Nous respectons votre vie privée et vos informations',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Toutes les transactions sont sécurisées et cryptées. Les informations de carte de crédit ne sont jamais stockées sur nos serveurs.',
				'Customer Information'                                                                               => 'Informations client',
				'Your Products'                                                                                      => 'Vos produits',
				'Billing Details'                                                                                    => 'Détails de facturation',
				'Shipping'                                                                                           => 'Livraison',
				'Payment'                                                                                            => 'Paiement',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'QU\'EST-CE QUI EST INCLUS DANS VOTRE FORFAIT?',
				'Best Value'                                                                                         => 'Meilleur rapport qualité-prix',
				'Your Plans'                                                                                         => 'Vos forfaits',
				'Select Your Plan'                                                                                   => 'Sélectionnez votre forfait',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* Paiements 100% sécurisés et fiables *',
				'Use a different Billing address'                                                                    => 'Utiliser une adresse de facturation différente',
				'Use a different billing address'                                                                    => 'Utiliser une adresse de facturation différente',
				'Choose Your Product'                                                                                => 'Choisissez votre produit',
				'Use a different shipping address'                                                                   => 'Utiliser une adresse de livraison différente',
				'Use a different Shipping address'                                                                   => 'Utiliser une adresse de livraison différente',
				'Your Billing Address'                                                                               => 'Votre adresse de facturation',
				'Enter Customer Information'                                                                         => 'Saisir les informations client',
				'All transactions are secure and encrypted.'                                                         => 'Toutes les transactions sont sécurisées et cryptées.',
				'Your Cart'                                                                                          => 'Votre panier',
				'Country'                                                                                            => 'Pays',
				'Order Summary'                                                                                      => 'Récapitulatif de la commande',
				'Shipping Address'                                                                                   => 'Adresse de livraison',
				'Billing Address'                                                                                    => 'Adresse de facturation',
				'Your Shipping Address'                                                                              => 'Votre adresse de livraison',
				'Your Information'                                                                                   => 'Vos informations',
				'show order summary'                                                                                 => 'afficher le récapitulatif de commande',
				'Show Order Summary'                                                                                 => 'Afficher le récapitulatif de commande',
				'Hide Order Summary'                                                                                 => 'Masquer le récapitulatif de commande',
				'Shipping Phone'                                                                                     => 'Téléphone de livraison',
				'Confirm Your Order'                                                                                 => 'Confirmer votre commande',
				'Confirm your order'                                                                                 => 'Confirmer votre commande',
				'Select Shipping Method'                                                                             => 'Sélectionner le mode de livraison',
				'COMPLETE PURCHASE'                                                                                  => 'FINALISER L\'ACHAT',
				'INFORMATION'                                                                                        => 'INFORMATION',
				'Information'                                                                                        => 'Information',
				'Complete Your Order Now'                                                                            => 'Complétez votre commande maintenant',
				'Payment method'                                                                                     => 'Moyen de paiement',
				'Payment Methods'                                                                                    => 'Moyens de paiement',
				'Payment Method'                                                                                     => 'Moyen de paiement',
				'PLACE ORDER NOW'                                                                                    => 'PASSER LA COMMANDE MAINTENANT',
				'Place Order Now'                                                                                    => 'Passer la commande maintenant',
				'Method'                                                                                             => 'Méthode',
				'Hide'                                                                                               => 'Masquer',
				'Show'                                                                                               => 'Afficher',
				'Place Your Order Now'                                                                               => 'Passez votre commande maintenant',
				'Apply'                                                                                              => 'Appliquer',
				'Review Order Summary'                                                                               => 'Vérifier le récapitulatif de commande',
				'Apartment, suite, unit, etc.'                                                                       => 'Appartement, suite, unité, etc.',
				'Proceed to Final Step'                                                                              => 'Passer à l\'étape finale',
				'Contact Information'                                                                                => 'Informations de contact',
			],
			'zh_CN' => [
				'Your Payment Information'                                                                           => '您的支付信息',
				'Your payment information'                                                                           => '您的支付信息',
				'Payment Information'                                                                                => '支付信息',
				'Shipping Information'                                                                               => '配送信息',
				'Select Payment Method'                                                                              => '选择支付方式',
				'All transactions are secured and encrypted'                                                         => '所有交易均安全加密',
				'We Respect Your Privacy & Information'                                                              => '我们尊重您的隐私和信息',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => '所有交易均安全加密。信用卡信息绝不会存储在我们的服务器上。',
				'Customer Information'                                                                               => '客户信息',
				'Your Products'                                                                                      => '您的产品',
				'Billing Details'                                                                                    => '账单详情',
				'Shipping'                                                                                           => '配送',
				'Payment'                                                                                            => '支付',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => '您的计划包含什么？',
				'Best Value'                                                                                         => '最佳价值',
				'Your Plans'                                                                                         => '您的计划',
				'Select Your Plan'                                                                                   => '选择您的计划',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* 100% 安全可靠的支付 *',
				'Use a different Billing address'                                                                    => '使用不同的账单地址',
				'Use a different billing address'                                                                    => '使用不同的账单地址',
				'Choose Your Product'                                                                                => '选择您的产品',
				'Use a different shipping address'                                                                   => '使用不同的配送地址',
				'Use a different Shipping address'                                                                   => '使用不同的配送地址',
				'Your Billing Address'                                                                               => '您的账单地址',
				'Enter Customer Information'                                                                         => '输入客户信息',
				'All transactions are secure and encrypted.'                                                         => '所有交易均安全加密。',
				'Your Cart'                                                                                          => '您的购物车',
				'Country'                                                                                            => '国家',
				'Order Summary'                                                                                      => '订单摘要',
				'Shipping Address'                                                                                   => '配送地址',
				'Billing Address'                                                                                    => '账单地址',
				'Your Shipping Address'                                                                              => '您的配送地址',
				'Your Information'                                                                                   => '您的信息',
				'show order summary'                                                                                 => '显示订单摘要',
				'Show Order Summary'                                                                                 => '显示订单摘要',
				'Hide Order Summary'                                                                                 => '隐藏订单摘要',
				'Shipping Phone'                                                                                     => '配送电话',
				'Confirm Your Order'                                                                                 => '确认您的订单',
				'Confirm your order'                                                                                 => '确认您的订单',
				'Select Shipping Method'                                                                             => '选择配送方式',
				'COMPLETE PURCHASE'                                                                                  => '完成购买',
				'INFORMATION'                                                                                        => '信息',
				'Information'                                                                                        => '信息',
				'Complete Your Order Now'                                                                            => '立即完成您的订单',
				'Payment method'                                                                                     => '支付方式',
				'Payment Methods'                                                                                    => '支付方式',
				'Payment Method'                                                                                     => '支付方式',
				'PLACE ORDER NOW'                                                                                    => '立即下单',
				'Place Order Now'                                                                                    => '立即下单',
				'Method'                                                                                             => '方式',
				'Hide'                                                                                               => '隐藏',
				'Show'                                                                                               => '显示',
				'Place Your Order Now'                                                                               => '立即下单',
				'Apply'                                                                                              => '应用',
				'Review Order Summary'                                                                               => '查看订单摘要',
				'Apartment, suite, unit, etc.'                                                                       => '公寓、套房、单元等',
				'Proceed to Final Step'                                                                              => '进入最后步骤',
				'Contact Information'                                                                                => '联系信息',
			],
			'it_IT' => [
				'Your Payment Information'                                                                           => 'Le tue informazioni di pagamento',
				'Your payment information'                                                                           => 'Le tue informazioni di pagamento',
				'Payment Information'                                                                                => 'Informazioni di pagamento',
				'Shipping Information'                                                                               => 'Informazioni di spedizione',
				'Select Payment Method'                                                                              => 'Seleziona metodo di pagamento',
				'All transactions are secured and encrypted'                                                         => 'Tutte le transazioni sono sicure e criptate',
				'We Respect Your Privacy & Information'                                                              => 'Rispettiamo la tua privacy e le tue informazioni',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Tutte le transazioni sono sicure e criptate. I dati della carta di credito non vengono mai memorizzati sui nostri server.',
				'Customer Information'                                                                               => 'Informazioni cliente',
				'Your Products'                                                                                      => 'I tuoi prodotti',
				'Billing Details'                                                                                    => 'Dettagli di fatturazione',
				'Shipping'                                                                                           => 'Spedizione',
				'Payment'                                                                                            => 'Pagamento',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'COSA È INCLUSO NEL TUO PIANO?',
				'Best Value'                                                                                         => 'Miglior valore',
				'Your Plans'                                                                                         => 'I tuoi piani',
				'Select Your Plan'                                                                                   => 'Seleziona il tuo piano',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* Pagamenti 100% sicuri e protetti *',
				'Use a different Billing address'                                                                    => 'Usa un indirizzo di fatturazione diverso',
				'Use a different billing address'                                                                    => 'Usa un indirizzo di fatturazione diverso',
				'Choose Your Product'                                                                                => 'Scegli il tuo prodotto',
				'Use a different shipping address'                                                                   => 'Usa un indirizzo di spedizione diverso',
				'Use a different Shipping address'                                                                   => 'Usa un indirizzo di spedizione diverso',
				'Your Billing Address'                                                                               => 'Il tuo indirizzo di fatturazione',
				'Enter Customer Information'                                                                         => 'Inserisci i dati del cliente',
				'All transactions are secure and encrypted.'                                                         => 'Tutte le transazioni sono sicure e criptate.',
				'Your Cart'                                                                                          => 'Il tuo carrello',
				'Country'                                                                                            => 'Paese',
				'Order Summary'                                                                                      => 'Riepilogo ordine',
				'Shipping Address'                                                                                   => 'Indirizzo di spedizione',
				'Billing Address'                                                                                    => 'Indirizzo di fatturazione',
				'Your Shipping Address'                                                                              => 'Il tuo indirizzo di spedizione',
				'Your Information'                                                                                   => 'Le tue informazioni',
				'show order summary'                                                                                 => 'mostra riepilogo ordine',
				'Show Order Summary'                                                                                 => 'Mostra riepilogo ordine',
				'Hide Order Summary'                                                                                 => 'Nascondi riepilogo ordine',
				'Shipping Phone'                                                                                     => 'Telefono per la spedizione',
				'Confirm Your Order'                                                                                 => 'Conferma il tuo ordine',
				'Confirm your order'                                                                                 => 'Conferma il tuo ordine',
				'Select Shipping Method'                                                                             => 'Seleziona metodo di spedizione',
				'COMPLETE PURCHASE'                                                                                  => 'COMPLETA ACQUISTO',
				'INFORMATION'                                                                                        => 'INFORMAZIONI',
				'Information'                                                                                        => 'Informazioni',
				'Complete Your Order Now'                                                                            => 'Completa il tuo ordine ora',
				'Payment method'                                                                                     => 'Metodo di pagamento',
				'Payment Methods'                                                                                    => 'Metodi di pagamento',
				'Payment Method'                                                                                     => 'Metodo di pagamento',
				'PLACE ORDER NOW'                                                                                    => 'EFFETTUA ORDINE ORA',
				'Place Order Now'                                                                                    => 'Effettua ordine ora',
				'Method'                                                                                             => 'Metodo',
				'Hide'                                                                                               => 'Nascondi',
				'Show'                                                                                               => 'Mostra',
				'Place Your Order Now'                                                                               => 'Effettua il tuo ordine ora',
				'Apply'                                                                                              => 'Applica',
				'Review Order Summary'                                                                               => 'Rivedi riepilogo ordine',
				'Apartment, suite, unit, etc.'                                                                       => 'Appartamento, suite, unità, ecc.',
				'Proceed to Final Step'                                                                              => 'Procedi all\'ultima fase',
				'Contact Information'                                                                                => 'Informazioni di contatto',
			],

			'he_IL' => [
				'Your Payment Information'                                                                           => 'פרטי התשלום שלך',
				'Your payment information'                                                                           => 'פרטי התשלום שלך',
				'Payment Information'                                                                                => 'פרטי תשלום',
				'Shipping Information'                                                                               => 'פרטי משלוח',
				'Select Payment Method'                                                                              => 'בחר אמצעי תשלום',
				'All transactions are secured and encrypted'                                                         => 'כל העסקאות מאובטחות ומוצפנות',
				'We Respect Your Privacy & Information'                                                              => 'אנו מכבדים את הפרטיות והמידע שלך',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'כל העסקאות מאובטחות ומוצפנות. פרטי כרטיס האשראי לעולם אינם מאוחסנים בשרתים שלנו.',
				'Customer Information'                                                                               => 'פרטי לקוח',
				'Your Products'                                                                                      => 'המוצרים שלך',
				'Billing Details'                                                                                    => 'פרטי חיוב',
				'Shipping'                                                                                           => 'משלוח',
				'Payment'                                                                                            => 'תשלום',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'מה כלול בתוכנית שלך?',
				'Best Value'                                                                                         => 'הערך הטוב ביותר',
				'Your Plans'                                                                                         => 'התוכניות שלך',
				'Select Your Plan'                                                                                   => 'בחר את התוכנית שלך',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* תשלומים מאובטחים ובטוחים 100% *',
				'Use a different Billing address'                                                                    => 'השתמש בכתובת חיוב אחרת',
				'Use a different billing address'                                                                    => 'השתמש בכתובת חיוב אחרת',
				'Choose Your Product'                                                                                => 'בחר את המוצר שלך',
				'Use a different shipping address'                                                                   => 'השתמש בכתובת משלוח אחרת',
				'Use a different Shipping address'                                                                   => 'השתמש בכתובת משלוח אחרת',
				'Your Billing Address'                                                                               => 'כתובת החיוב שלך',
				'Enter Customer Information'                                                                         => 'הזן פרטי לקוח',
				'All transactions are secure and encrypted.'                                                         => 'כל העסקאות מאובטחות ומוצפנות.',
				'Your Cart'                                                                                          => 'העגלה שלך',
				'Country'                                                                                            => 'מדינה',
				'Order Summary'                                                                                      => 'סיכום הזמנה',
				'Shipping Address'                                                                                   => 'כתובת למשלוח',
				'Billing Address'                                                                                    => 'כתובת לחיוב',
				'Your Shipping Address'                                                                              => 'כתובת המשלוח שלך',
				'Your Information'                                                                                   => 'המידע שלך',
				'show order summary'                                                                                 => 'הצג סיכום הזמנה',
				'Show Order Summary'                                                                                 => 'הצג סיכום הזמנה',
				'Hide Order Summary'                                                                                 => 'הסתר סיכום הזמנה',
				'Shipping Phone'                                                                                     => 'טלפון למשלוח',
				'Confirm Your Order'                                                                                 => 'אשר את ההזמנה שלך',
				'Confirm your order'                                                                                 => 'אשר את ההזמנה שלך',
				'Select Shipping Method'                                                                             => 'בחר שיטת משלוח',
				'COMPLETE PURCHASE'                                                                                  => 'השלם רכישה',
				'INFORMATION'                                                                                        => 'מידע',
				'Information'                                                                                        => 'מידע',
				'Complete Your Order Now'                                                                            => 'השלם את ההזמנה שלך עכשיו',
				'Payment method'                                                                                     => 'אמצעי תשלום',
				'Payment Methods'                                                                                    => 'אמצעי תשלום',
				'Payment Method'                                                                                     => 'אמצעי תשלום',
				'PLACE ORDER NOW'                                                                                    => 'בצע הזמנה עכשיו',
				'Place Order Now'                                                                                    => 'בצע הזמנה עכשיו',
				'Method'                                                                                             => 'שיטה',
				'Hide'                                                                                               => 'הסתר',
				'Show'                                                                                               => 'הצג',
				'Place Your Order Now'                                                                               => 'בצע את ההזמנה שלך עכשיו',
				'Apply'                                                                                              => 'החל',
				'Review Order Summary'                                                                               => 'סקור סיכום הזמנה',
				'Apartment, suite, unit, etc.'                                                                       => 'דירה, סוויטה, יחידה וכו\'',
				'Proceed to Final Step'                                                                              => 'המשך לשלב הסופי',
				'Contact Information'                                                                                => 'פרטי התקשרות',
			],
			'pt_PT' => [
				'Your Payment Information'                                                                           => 'Suas informações de pagamento',
				'Your payment information'                                                                           => 'Suas informações de pagamento',
				'Payment Information'                                                                                => 'Informações de pagamento',
				'Shipping Information'                                                                               => 'Informações de envio',
				'Select Payment Method'                                                                              => 'Selecionar método de pagamento',
				'All transactions are secured and encrypted'                                                         => 'Todas as transações são seguras e criptografadas',
				'We Respect Your Privacy & Information'                                                              => 'Respeitamos sua privacidade e informações',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Todas as transações são seguras e criptografadas. As informações do cartão de crédito nunca são armazenadas em nossos servidores.',
				'Customer Information'                                                                               => 'Informações do cliente',
				'Your Products'                                                                                      => 'Seus produtos',
				'Billing Details'                                                                                    => 'Detalhes de faturamento',
				'Shipping'                                                                                           => 'Envio',
				'Payment'                                                                                            => 'Pagamento',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'O QUE ESTÁ INCLUÍDO NO SEU PLANO?',
				'Best Value'                                                                                         => 'Melhor valor',
				'Your Plans'                                                                                         => 'Seus planos',
				'Select Your Plan'                                                                                   => 'Selecione seu plano',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* Pagamentos 100% seguros e protegidos *',
				'Use a different Billing address'                                                                    => 'Usar um endereço de faturamento diferente',
				'Use a different billing address'                                                                    => 'Usar um endereço de faturamento diferente',
				'Choose Your Product'                                                                                => 'Escolha seu produto',
				'Use a different shipping address'                                                                   => 'Usar um endereço de envio diferente',
				'Use a different Shipping address'                                                                   => 'Usar um endereço de envio diferente',
				'Your Billing Address'                                                                               => 'Seu endereço de faturamento',
				'Enter Customer Information'                                                                         => 'Inserir informações do cliente',
				'All transactions are secure and encrypted.'                                                         => 'Todas as transações são seguras e criptografadas.',
				'Your Cart'                                                                                          => 'Seu carrinho',
				'Country'                                                                                            => 'País',
				'Order Summary'                                                                                      => 'Resumo do pedido',
				'Shipping Address'                                                                                   => 'Endereço de envio',
				'Billing Address'                                                                                    => 'Endereço de faturamento',
				'Your Shipping Address'                                                                              => 'Seu endereço de envio',
				'Your Information'                                                                                   => 'Suas informações',
				'show order summary'                                                                                 => 'mostrar resumo do pedido',
				'Show Order Summary'                                                                                 => 'Mostrar resumo do pedido',
				'Hide Order Summary'                                                                                 => 'Ocultar resumo do pedido',
				'Shipping Phone'                                                                                     => 'Telefone para envio',
				'Confirm Your Order'                                                                                 => 'Confirme seu pedido',
				'Confirm your order'                                                                                 => 'Confirme seu pedido',
				'Select Shipping Method'                                                                             => 'Selecionar método de envio',
				'COMPLETE PURCHASE'                                                                                  => 'CONCLUIR COMPRA',
				'INFORMATION'                                                                                        => 'INFORMAÇÃO',
				'Information'                                                                                        => 'Informação',
				'Complete Your Order Now'                                                                            => 'Complete seu pedido agora',
				'Payment method'                                                                                     => 'Método de pagamento',
				'Payment Methods'                                                                                    => 'Métodos de pagamento',
				'Payment Method'                                                                                     => 'Método de pagamento',
				'PLACE ORDER NOW'                                                                                    => 'FAZER PEDIDO AGORA',
				'Place Order Now'                                                                                    => 'Fazer pedido agora',
				'Method'                                                                                             => 'Método',
				'Hide'                                                                                               => 'Ocultar',
				'Show'                                                                                               => 'Mostrar',
				'Place Your Order Now'                                                                               => 'Faça seu pedido agora',
				'Apply'                                                                                              => 'Aplicar',
				'Review Order Summary'                                                                               => 'Revisar resumo do pedido',
				'Apartment, suite, unit, etc.'                                                                       => 'Apartamento, suite, unidade, etc.',
				'Proceed to Final Step'                                                                              => 'Prosseguir para a etapa final',
				'Contact Information'                                                                                => 'Informações de contato',
			],
			'pl_PL' => [
				'Your Payment Information'                                                                           => 'Twoje informacje płatności',
				'Your payment information'                                                                           => 'Twoje informacje płatności',
				'Payment Information'                                                                                => 'Informacje o płatności',
				'Shipping Information'                                                                               => 'Informacje o wysyłce',
				'Select Payment Method'                                                                              => 'Wybierz metodę płatności',
				'All transactions are secured and encrypted'                                                         => 'Wszystkie transakcje są zabezpieczone i szyfrowane',
				'We Respect Your Privacy & Information'                                                              => 'Szanujemy Twoją prywatność i informacje',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Wszystkie transakcje są bezpieczne i szyfrowane. Informacje o kartach kredytowych nigdy nie są przechowywane na naszych serwerach.',
				'Customer Information'                                                                               => 'Informacje o kliencie',
				'Your Products'                                                                                      => 'Twoje produkty',
				'Billing Details'                                                                                    => 'Szczegóły płatności',
				'Shipping'                                                                                           => 'Wysyłka',
				'Payment'                                                                                            => 'Płatność',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'CO ZAWIERA TWÓJ PLAN?',
				'Best Value'                                                                                         => 'Najlepsza wartość',
				'Your Plans'                                                                                         => 'Twoje plany',
				'Select Your Plan'                                                                                   => 'Wybierz swój plan',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* Płatności 100% bezpieczne i chronione *',
				'Use a different Billing address'                                                                    => 'Użyj innego adresu rozliczeniowego',
				'Use a different billing address'                                                                    => 'Użyj innego adresu rozliczeniowego',
				'Choose Your Product'                                                                                => 'Wybierz swój produkt',
				'Use a different shipping address'                                                                   => 'Użyj innego adresu wysyłki',
				'Use a different Shipping address'                                                                   => 'Użyj innego adresu wysyłki',
				'Your Billing Address'                                                                               => 'Twój adres rozliczeniowy',
				'Enter Customer Information'                                                                         => 'Wprowadź informacje o kliencie',
				'All transactions are secure and encrypted.'                                                         => 'Wszystkie transakcje są bezpieczne i szyfrowane.',
				'Your Cart'                                                                                          => 'Twój koszyk',
				'Country'                                                                                            => 'Kraj',
				'Order Summary'                                                                                      => 'Podsumowanie zamówienia',
				'Shipping Address'                                                                                   => 'Adres wysyłki',
				'Billing Address'                                                                                    => 'Adres rozliczeniowy',
				'Your Shipping Address'                                                                              => 'Twój adres wysyłki',
				'Your Information'                                                                                   => 'Twoje informacje',
				'show order summary'                                                                                 => 'pokaż podsumowanie zamówienia',
				'Show Order Summary'                                                                                 => 'Pokaż podsumowanie zamówienia',
				'Hide Order Summary'                                                                                 => 'Ukryj podsumowanie zamówienia',
				'Shipping Phone'                                                                                     => 'Telefon do wysyłki',
				'Confirm Your Order'                                                                                 => 'Potwierdź swoje zamówienie',
				'Confirm your order'                                                                                 => 'Potwierdź swoje zamówienie',
				'Select Shipping Method'                                                                             => 'Wybierz metodę wysyłki',
				'COMPLETE PURCHASE'                                                                                  => 'ZAKOŃCZ ZAKUP',
				'INFORMATION'                                                                                        => 'INFORMACJE',
				'Information'                                                                                        => 'Informacje',
				'Complete Your Order Now'                                                                            => 'Dokończ swoje zamówienie teraz',
				'Payment method'                                                                                     => 'Metoda płatności',
				'Payment Methods'                                                                                    => 'Metody płatności',
				'Payment Method'                                                                                     => 'Metoda płatności',
				'PLACE ORDER NOW'                                                                                    => 'ZŁÓŻ ZAMÓWIENIE TERAZ',
				'Place Order Now'                                                                                    => 'Złóż zamówienie teraz',
				'Method'                                                                                             => 'Metoda',
				'Hide'                                                                                               => 'Ukryj',
				'Show'                                                                                               => 'Pokaż',
				'Place Your Order Now'                                                                               => 'Złóż swoje zamówienie teraz',
				'Apply'                                                                                              => 'Zastosuj',
				'Review Order Summary'                                                                               => 'Przejrzyj podsumowanie zamówienia',
				'Apartment, suite, unit, etc.'                                                                       => 'Mieszkanie, apartament, lokal, itp.',
				'Proceed to Final Step'                                                                              => 'Przejdź do ostatniego kroku',
				'Contact Information'                                                                                => 'Informacje kontaktowe',
			],
			'ru_RU' => [
				'Your Payment Information'                                                                           => 'Ваша платежная информация',
				'Your payment information'                                                                           => 'Ваша платежная информация',
				'Payment Information'                                                                                => 'Информация об оплате',
				'Shipping Information'                                                                               => 'Информация о доставке',
				'Select Payment Method'                                                                              => 'Выберите способ оплаты',
				'All transactions are secured and encrypted'                                                         => 'Все транзакции защищены и зашифрованы',
				'We Respect Your Privacy & Information'                                                              => 'Мы уважаем вашу конфиденциальность и информацию',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Все транзакции безопасны и зашифрованы. Информация о кредитной карте никогда не хранится на наших серверах.',
				'Customer Information'                                                                               => 'Информация о клиенте',
				'Your Products'                                                                                      => 'Ваши товары',
				'Billing Details'                                                                                    => 'Платежные реквизиты',
				'Shipping'                                                                                           => 'Доставка',
				'Payment'                                                                                            => 'Оплата',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'ЧТО ВКЛЮЧЕНО В ВАШ ПЛАН?',
				'Best Value'                                                                                         => 'Лучшее предложение',
				'Your Plans'                                                                                         => 'Ваши планы',
				'Select Your Plan'                                                                                   => 'Выберите ваш план',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* 100% Безопасные и надежные платежи *',
				'Use a different Billing address'                                                                    => 'Использовать другой платежный адрес',
				'Use a different billing address'                                                                    => 'Использовать другой платежный адрес',
				'Choose Your Product'                                                                                => 'Выберите ваш товар',
				'Use a different shipping address'                                                                   => 'Использовать другой адрес доставки',
				'Use a different Shipping address'                                                                   => 'Использовать другой адрес доставки',
				'Your Billing Address'                                                                               => 'Ваш платежный адрес',
				'Enter Customer Information'                                                                         => 'Введите информацию о клиенте',
				'All transactions are secure and encrypted.'                                                         => 'Все транзакции безопасны и зашифрованы.',
				'Your Cart'                                                                                          => 'Ваша корзина',
				'Country'                                                                                            => 'Страна',
				'Order Summary'                                                                                      => 'Сводка заказа',
				'Shipping Address'                                                                                   => 'Адрес доставки',
				'Billing Address'                                                                                    => 'Платежный адрес',
				'Your Shipping Address'                                                                              => 'Ваш адрес доставки',
				'Your Information'                                                                                   => 'Ваша информация',
				'show order summary'                                                                                 => 'показать сводку заказа',
				'Show Order Summary'                                                                                 => 'Показать сводку заказа',
				'Hide Order Summary'                                                                                 => 'Скрыть сводку заказа',
				'Shipping Phone'                                                                                     => 'Телефон для доставки',
				'Confirm Your Order'                                                                                 => 'Подтвердите ваш заказ',
				'Confirm your order'                                                                                 => 'Подтвердите ваш заказ',
				'Select Shipping Method'                                                                             => 'Выберите способ доставки',
				'COMPLETE PURCHASE'                                                                                  => 'ЗАВЕРШИТЬ ПОКУПКУ',
				'INFORMATION'                                                                                        => 'ИНФОРМАЦИЯ',
				'Information'                                                                                        => 'Информация',
				'Complete Your Order Now'                                                                            => 'Завершите ваш заказ сейчас',
				'Payment method'                                                                                     => 'Способ оплаты',
				'Payment Methods'                                                                                    => 'Способы оплаты',
				'Payment Method'                                                                                     => 'Способ оплаты',
				'PLACE ORDER NOW'                                                                                    => 'РАЗМЕСТИТЬ ЗАКАЗ СЕЙЧАС',
				'Place Order Now'                                                                                    => 'Разместить заказ сейчас',
				'Method'                                                                                             => 'Способ',
				'Hide'                                                                                               => 'Скрыть',
				'Show'                                                                                               => 'Показать',
				'Place Your Order Now'                                                                               => 'Разместите ваш заказ сейчас',
				'Apply'                                                                                              => 'Применить',
				'Review Order Summary'                                                                               => 'Просмотреть сводку заказа',
				'Apartment, suite, unit, etc.'                                                                       => 'Квартира, офис, блок и т.д.',
				'Proceed to Final Step'                                                                              => 'Перейти к последнему шагу',
				'Contact Information'                                                                                => 'Контактная информация',
			],
			'ro_RO' => [
				'Your Payment Information'                                                                           => 'Informațiile dumneavoastră de plată',
				'Your payment information'                                                                           => 'Informațiile dumneavoastră de plată',
				'Payment Information'                                                                                => 'Informații de plată',
				'Shipping Information'                                                                               => 'Informații de livrare',
				'Select Payment Method'                                                                              => 'Selectați metoda de plată',
				'All transactions are secured and encrypted'                                                         => 'Toate tranzacțiile sunt securizate și criptate',
				'We Respect Your Privacy & Information'                                                              => 'Respectăm confidențialitatea și informațiile dumneavoastră',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Toate tranzacțiile sunt sigure și criptate. Informațiile cardului de credit nu sunt niciodată stocate pe serverele noastre.',
				'Customer Information'                                                                               => 'Informații client',
				'Your Products'                                                                                      => 'Produsele dumneavoastră',
				'Billing Details'                                                                                    => 'Detalii de facturare',
				'Shipping'                                                                                           => 'Livrare',
				'Payment'                                                                                            => 'Plată',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'CE ESTE INCLUS ÎN PLANUL DUMNEAVOASTRĂ?',
				'Best Value'                                                                                         => 'Cea mai bună valoare',
				'Your Plans'                                                                                         => 'Planurile dumneavoastră',
				'Select Your Plan'                                                                                   => 'Selectați planul dumneavoastră',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* Plăți 100% sigure și securizate *',
				'Use a different Billing address'                                                                    => 'Folosiți o adresă de facturare diferită',
				'Use a different billing address'                                                                    => 'Folosiți o adresă de facturare diferită',
				'Choose Your Product'                                                                                => 'Alegeți produsul dumneavoastră',
				'Use a different shipping address'                                                                   => 'Folosiți o adresă de livrare diferită',
				'Use a different Shipping address'                                                                   => 'Folosiți o adresă de livrare diferită',
				'Your Billing Address'                                                                               => 'Adresa dumneavoastră de facturare',
				'Enter Customer Information'                                                                         => 'Introduceți informațiile clientului',
				'All transactions are secure and encrypted.'                                                         => 'Toate tranzacțiile sunt sigure și criptate.',
				'Your Cart'                                                                                          => 'Coșul dumneavoastră',
				'Country'                                                                                            => 'Țară',
				'Order Summary'                                                                                      => 'Rezumatul comenzii',
				'Shipping Address'                                                                                   => 'Adresa de livrare',
				'Billing Address'                                                                                    => 'Adresa de facturare',
				'Your Shipping Address'                                                                              => 'Adresa dumneavoastră de livrare',
				'Your Information'                                                                                   => 'Informațiile dumneavoastră',
				'show order summary'                                                                                 => 'afișează rezumatul comenzii',
				'Show Order Summary'                                                                                 => 'Afișează rezumatul comenzii',
				'Hide Order Summary'                                                                                 => 'Ascunde rezumatul comenzii',
				'Shipping Phone'                                                                                     => 'Telefon pentru livrare',
				'Confirm Your Order'                                                                                 => 'Confirmați comanda dumneavoastră',
				'Confirm your order'                                                                                 => 'Confirmați comanda dumneavoastră',
				'Select Shipping Method'                                                                             => 'Selectați metoda de livrare',
				'COMPLETE PURCHASE'                                                                                  => 'FINALIZAȚI ACHIZIȚIA',
				'INFORMATION'                                                                                        => 'INFORMAȚII',
				'Information'                                                                                        => 'Informații',
				'Complete Your Order Now'                                                                            => 'Finalizați comanda dumneavoastră acum',
				'Payment method'                                                                                     => 'Metodă de plată',
				'Payment Methods'                                                                                    => 'Metode de plată',
				'Payment Method'                                                                                     => 'Metodă de plată',
				'PLACE ORDER NOW'                                                                                    => 'PLASAȚI COMANDA ACUM',
				'Place Order Now'                                                                                    => 'Plasați comanda acum',
				'Method'                                                                                             => 'Metodă',
				'Hide'                                                                                               => 'Ascunde',
				'Show'                                                                                               => 'Afișează',
				'Place Your Order Now'                                                                               => 'Plasați comanda dumneavoastră acum',
				'Apply'                                                                                              => 'Aplică',
				'Review Order Summary'                                                                               => 'Verificați rezumatul comenzii',
				'Apartment, suite, unit, etc.'                                                                       => 'Apartament, suite, unitate, etc.',
				'Proceed to Final Step'                                                                              => 'Continuați la pasul final',
				'Contact Information'                                                                                => 'Informații de contact',
			],
			'hu_HU' => [
				'Your Payment Information'                                                                           => 'Az Ön fizetési adatai',
				'Your payment information'                                                                           => 'Az Ön fizetési adatai',
				'Payment Information'                                                                                => 'Fizetési információk',
				'Shipping Information'                                                                               => 'Szállítási információk',
				'Select Payment Method'                                                                              => 'Fizetési mód kiválasztása',
				'All transactions are secured and encrypted'                                                         => 'Minden tranzakció biztonságos és titkosított',
				'We Respect Your Privacy & Information'                                                              => 'Tiszteletben tartjuk az Ön adatait és magánéletét',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'Minden tranzakció biztonságos és titkosított. A bankkártya adatokat soha nem tároljuk a szervereinken.',
				'Customer Information'                                                                               => 'Vásárlói információk',
				'Your Products'                                                                                      => 'Az Ön termékei',
				'Billing Details'                                                                                    => 'Számlázási adatok',
				'Shipping'                                                                                           => 'Szállítás',
				'Payment'                                                                                            => 'Fizetés',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'MIT TARTALMAZ AZ ÖN CSOMAGJA?',
				'Best Value'                                                                                         => 'Legjobb érték',
				'Your Plans'                                                                                         => 'Az Ön csomagjai',
				'Select Your Plan'                                                                                   => 'Válassza ki az Ön csomagját',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* 100% Biztonságos fizetési módok *',
				'Use a different Billing address'                                                                    => 'Használjon másik számlázási címet',
				'Use a different billing address'                                                                    => 'Használjon másik számlázási címet',
				'Choose Your Product'                                                                                => 'Válassza ki az Ön termékét',
				'Use a different shipping address'                                                                   => 'Használjon másik szállítási címet',
				'Use a different Shipping address'                                                                   => 'Használjon másik szállítási címet',
				'Your Billing Address'                                                                               => 'Az Ön számlázási címe',
				'Enter Customer Information'                                                                         => 'Adja meg a vásárlói információkat',
				'All transactions are secure and encrypted.'                                                         => 'Minden tranzakció biztonságos és titkosított.',
				'Your Cart'                                                                                          => 'Az Ön kosara',
				'Country'                                                                                            => 'Ország',
				'Order Summary'                                                                                      => 'Rendelés összegzése',
				'Shipping Address'                                                                                   => 'Szállítási cím',
				'Billing Address'                                                                                    => 'Számlázási cím',
				'Your Shipping Address'                                                                              => 'Az Ön szállítási címe',
				'Your Information'                                                                                   => 'Az Ön adatai',
				'show order summary'                                                                                 => 'rendelés összegzésének megjelenítése',
				'Show Order Summary'                                                                                 => 'Rendelés összegzésének megjelenítése',
				'Hide Order Summary'                                                                                 => 'Rendelés összegzésének elrejtése',
				'Shipping Phone'                                                                                     => 'Szállítási telefonszám',
				'Confirm Your Order'                                                                                 => 'Rendelés megerősítése',
				'Confirm your order'                                                                                 => 'Rendelés megerősítése',
				'Select Shipping Method'                                                                             => 'Szállítási mód kiválasztása',
				'COMPLETE PURCHASE'                                                                                  => 'VÁSÁRLÁS BEFEJEZÉSE',
				'INFORMATION'                                                                                        => 'INFORMÁCIÓK',
				'Information'                                                                                        => 'Információk',
				'Complete Your Order Now'                                                                            => 'Fejezze be rendelését most',
				'Payment method'                                                                                     => 'Fizetési mód',
				'Payment Methods'                                                                                    => 'Fizetési módok',
				'Payment Method'                                                                                     => 'Fizetési mód',
				'PLACE ORDER NOW'                                                                                    => 'RENDELÉS LEADÁSA MOST',
				'Place Order Now'                                                                                    => 'Rendelés leadása most',
				'Method'                                                                                             => 'Mód',
				'Hide'                                                                                               => 'Elrejtés',
				'Show'                                                                                               => 'Megjelenítés',
				'Place Your Order Now'                                                                               => 'Adja le rendelését most',
				'Apply'                                                                                              => 'Alkalmazás',
				'Review Order Summary'                                                                               => 'Rendelés összegzésének áttekintése',
				'Apartment, suite, unit, etc.'                                                                       => 'Lakás, emelet, ajtó, stb.',
				'Proceed to Final Step'                                                                              => 'Tovább az utolsó lépéshez',
				'Contact Information'                                                                                => 'Kapcsolattartási információk',
			],
			'ja' => [
				'Your Payment Information'                                                                           => 'お支払い情報',
				'Your payment information'                                                                           => 'お支払い情報',
				'Payment Information'                                                                                => '支払い情報',
				'Shipping Information'                                                                               => '配送情報',
				'Select Payment Method'                                                                              => 'お支払い方法を選択',
				'All transactions are secured and encrypted'                                                         => 'すべての取引は安全に暗号化されています',
				'We Respect Your Privacy & Information'                                                              => 'お客様のプライバシーと情報を尊重します',
				'All transactions are secure and encrypted. Credit card information is never stored on our servers.' => 'すべての取引は安全かつ暗号化されています。クレジットカード情報は当社のサーバーに保存されることはありません。',
				'Customer Information'                                                                               => 'お客様情報',
				'Your Products'                                                                                      => 'ご注文商品',
				'Billing Details'                                                                                    => '請求先詳細',
				'Shipping'                                                                                           => '配送',
				'Payment'                                                                                            => 'お支払い',
				"WHAT\'S INCLUDED IN YOUR PLAN?"                                                                     => 'プランに含まれるもの',
				'Best Value'                                                                                         => 'ベストバリュー',
				'Your Plans'                                                                                         => 'あなたのプラン',
				'Select Your Plan'                                                                                   => 'プランを選択',
				'* 100% Secure &amp; Safe Payments *'                                                                => '* 100%安全なお支払い *',
				'Use a different Billing address'                                                                    => '別の請求先住所を使用',
				'Use a different billing address'                                                                    => '別の請求先住所を使用',
				'Choose Your Product'                                                                                => '商品を選択',
				'Use a different shipping address'                                                                   => '別の配送先住所を使用',
				'Use a different Shipping address'                                                                   => '別の配送先住所を使用',
				'Your Billing Address'                                                                               => '請求先住所',
				'Enter Customer Information'                                                                         => 'お客様情報を入力',
				'All transactions are secure and encrypted.'                                                         => 'すべての取引は安全かつ暗号化されています。',
				'Your Cart'                                                                                          => 'カート',
				'Country'                                                                                            => '国',
				'Order Summary'                                                                                      => '注文概要',
				'Shipping Address'                                                                                   => '配送先住所',
				'Billing Address'                                                                                    => '請求先住所',
				'Your Shipping Address'                                                                              => '配送先住所',
				'Your Information'                                                                                   => 'お客様情報',
				'show order summary'                                                                                 => '注文概要を表示',
				'Show Order Summary'                                                                                 => '注文概要を表示',
				'Hide Order Summary'                                                                                 => '注文概要を隠す',
				'Shipping Phone'                                                                                     => '配送先電話番号',
				'Confirm Your Order'                                                                                 => '注文を確認',
				'Confirm your order'                                                                                 => '注文を確認',
				'Select Shipping Method'                                                                             => '配送方法を選択',
				'COMPLETE PURCHASE'                                                                                  => '購入を完了',
				'INFORMATION'                                                                                        => '情報',
				'Information'                                                                                        => '情報',
				'Complete Your Order Now'                                                                            => '今すぐ注文を完了',
				'Payment method'                                                                                     => 'お支払い方法',
				'Payment Methods'                                                                                    => 'お支払い方法',
				'Payment Method'                                                                                     => 'お支払い方法',
				'PLACE ORDER NOW'                                                                                    => '今すぐ注文',
				'Place Order Now'                                                                                    => '今すぐ注文',
				'Method'                                                                                             => '方法',
				'Hide'                                                                                               => '隠す',
				'Show'                                                                                               => '表示',
				'Place Your Order Now'                                                                               => '今すぐ注文する',
				'Apply'                                                                                              => '適用',
				'Review Order Summary'                                                                               => '注文概要を確認',
				'Apartment, suite, unit, etc.'                                                                       => 'マンション・アパート名、部屋番号など',
				'Proceed to Final Step'                                                                              => '最終ステップに進む',
				'Contact Information'                                                                                => '連絡先情報',
			],

		];
	}
}

