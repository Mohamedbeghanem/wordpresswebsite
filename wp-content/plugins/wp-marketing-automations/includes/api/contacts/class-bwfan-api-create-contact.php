<?php

class BWFAN_API_Create_Contact extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/v3/contacts';
	}


	public function process_api_call() {
		$email = $this->get_sanitized_arg( 'email', 'email' );
		if ( false === $email || ! is_email( $email ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Email is not valid', 'wp-marketing-automations' ) );
		}

		$params = array(
			'f_name'         => isset( $this->args['f_name'] ) ? $this->get_sanitized_arg( 'f_name', 'text_field', $this->args['f_name'] ) : '',
			'l_name'         => isset( $this->args['l_name'] ) ? $this->get_sanitized_arg( 'l_name', 'text_field', $this->args['l_name'] ) : '',
			'create_wp_user' => isset( $this->args['create_wp_user'] ) ? rest_sanitize_boolean( $this->args['create_wp_user'] ) : '',
			'wp_password'    => isset( $this->args['wp_password'] ) ? $this->args['wp_password'] : '',
			'contact_no'     => isset( $this->args['contact_no'] ) ? $this->get_sanitized_arg( 'contact_no', 'text_field', $this->args['contact_no'] ) : '',
			'source'         => isset( $this->args['source'] ) ? $this->get_sanitized_arg( 'source', 'text_field', $this->args['source'] ) : '',
			'status'         => isset( $this->args['status'] ) ? $this->get_sanitized_arg( 'status', 'text_field', $this->args['status'] ) : '',
		);

		foreach ( $this->args as $key => $value ) {
			if ( ! is_numeric( $key ) ) {
				continue;
			}
			$params[ $key ] = $value;
		}

		$contact = new BWFCRM_Contact( $email, true, $params );
		if ( isset( $this->args['tags'] ) ) {
			$contact->set_tags( $this->args['tags'], true, false );
		}

		if ( isset( $this->args['lists'] ) ) {
			$contact->set_lists( $this->args['lists'], true, false );
		}

		$contact->save();

		if ( $contact->already_exists ) {
			$this->response_code = 422;

			return $this->error_response( __( 'Contact already exists', 'wp-marketing-automations' ) );
		}

		if ( $contact->is_contact_exists() ) {
			$this->response_code = 200;

			return $this->success_response( $contact->get_array( false, class_exists( 'WooCommerce' ) ), __( 'Contact created', 'wp-marketing-automations' ) );
		}

		$this->response_code = 500;

		return $this->error_response( __( 'Unable to create contact', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Create_Contact' );
