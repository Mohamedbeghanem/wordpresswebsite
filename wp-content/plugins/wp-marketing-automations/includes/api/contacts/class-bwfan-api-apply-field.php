<?php

class BWFAN_API_Apply_Field extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/v3/contacts/(?P<contact_id>[\\d]+)/fields';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'email'      => '',
			'fields'     => '',
		);
	}

	public function process_api_call() {
		$contact = $this->get_contact_by_id_or_email( 'contact_id', 'email' );
		$email   = $this->get_sanitized_arg( 'email', 'text_field' );
		if ( is_wp_error( $contact ) ) {
			return $contact;
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			$email = $contact->contact->get_email();
		}

		$sanitize_cb = 'text_field';
		if ( is_array( $this->args['fields'] ) ) {
			$ids = array_keys( $this->args['fields'] );
			$ids = array_filter( $ids, [ $this, 'check_field_type_textarea' ] );
			if ( count( $ids ) > 0 ) {
				$sanitize_cb = 'textarea_field';
			}
		}

		$fields = $this->get_sanitized_arg( '', $sanitize_cb, $this->args['fields'] );

		if ( empty( $fields ) ) {
			$response            = __( 'Required Fields missing', 'wp-marketing-automations' );
			$this->response_code = 400;

			return $this->error_response( $response );
		}

		$field_email = $this->get_sanitized_arg( 'email', 'text_field', $this->args['fields'] );
		if ( ! empty( $field_email ) && $email !== $field_email ) {
			if ( ! is_email( $field_email ) ) {
				$this->response_code = 400;

				return $this->error_response( __( 'Email is not valid.', 'wp-marketing-automations' ) );
			}

			$check_contact = new BWFCRM_Contact( $field_email );
			/** If email is already exists with other contacts*/
			if ( $check_contact->is_contact_exists() ) {

				/** If Only email field to be updated then return error response */
				if ( 1 === count( $this->args['fields'] ) ) {
					$this->response_code = 400;

					return $this->error_response( __( 'Email is already associated with other contact.', 'wp-marketing-automations' ) );
				}

				/**If other fields also available for update then unset the email */
				unset( $fields['email'] );
			}
		}

		$response = $contact->update_custom_fields( $fields );
		if ( ! ( $response ) || empty( $response ) ) {
			$this->response_code = 200;

			return $this->success_response( '', __( 'Unable to Update fields', 'wp-marketing-automations' ) );
		}

		/** If email changed and email is set for change then hook added */
		if ( ! empty( $field_email ) && $email !== $field_email ) {
			do_action( 'bwfan_contact_email_changed', $field_email, $email, $contact );
		}

		return $this->success_response( $contact->get_array( false, true, true, true ), __( 'Contact updated', 'wp-marketing-automations' ) );
	}

	public function check_field_type_textarea( $id ) {
		$fields = BWFCRM_Fields::get_custom_fields( null, null, null );
		$fields = array_filter( $fields, function ( $val ) {
			if ( isset( $val['type'] ) && 3 == $val['type'] ) {
				/** 3 is textarea */
				return true;
			}

			return false;
		} );
		if ( is_array( $fields ) && array_key_exists( $id, $fields ) ) {
			return true;
		}

		return false;
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Apply_Field' );
