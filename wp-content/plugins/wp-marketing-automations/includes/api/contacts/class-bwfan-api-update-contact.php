<?php

class BWFAN_API_Update_Contact extends BWFAN_API_Base {
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
		$this->route  = '/v3/contacts/(?P<contact_id>[\\d]+)';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'first_name' => '',
			'last_name'  => '',
			'meta'       => [],
			'lists'      => [],
			'tags'       => [],
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );

		if ( empty( $contact_id ) ) {

			$this->response_code = 404;

			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$contact_basic_info = array();

		$contact_first_name = $this->get_sanitized_arg( 'first_name', 'text_field' );
		$contact_last_name  = $this->get_sanitized_arg( 'last_name', 'text_field' );
		$contact_email      = $this->get_sanitized_arg( 'email', 'text_field' );

		if ( ! empty( $contact_first_name ) ) {
			$contact_basic_info['first_name'] = $contact_first_name;
		}

		if ( ! empty( $contact_last_name ) ) {
			$contact_basic_info['last_name'] = $contact_last_name;
		}

		if ( ! empty( $contact_email ) ) {
			$contact_basic_info['email'] = $contact_email;
		}

		if ( ! empty( $this->args['meta'] ) && is_array( $this->args['meta'] ) ) {
			$contact_basic_info['meta'] = $this->args['meta'];
		}

		if ( empty( $contact_basic_info ) && empty( $this->args['lists'] ) && empty( $this->args['tags'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Unable to update contact as update data missing', 'wp-marketing-automations' ) );
		}

		if ( ! empty( $contact_basic_info ) ) {
			$contact_basic_info['id'] = $contact_id;

			/** @var  $update_contact_details */
			$contact_field_updated = $contact->update( $contact_basic_info );
		}

		if ( ! empty( $this->args['lists'] ) && is_array( $this->args['lists'] ) ) {
			$lists       = $this->args['lists'];
			$added_lists = $contact->set_lists( $lists );
			$lists_added = array_map( function ( $list ) {
				return $list->get_array();
			}, $added_lists );
		}

		if ( ! empty( $this->args['tags'] ) && is_array( $this->args['tags'] ) ) {
			$tags       = $this->args['tags'];
			$added_tags = $contact->set_tags( $tags );
			$tags_added = array_map( function ( $tags ) {
				return $tags->get_array();
			}, $added_tags );
		}

		$contact->save();


		if ( false === $contact_field_updated && empty( $added_lists ) && empty( $added_tags ) ) {
			$this->response_code = 200;

			return $this->error_response( __( 'Unable to update contact fields', 'wp-marketing-automations' ) );
		}
		$result = [];
		if ( ! empty( $contact_field_updated ) ) {
			$result['contact'] = $contact_field_updated;
		}
		if ( ! empty( $lists_added ) ) {
			$result['lists'] = $lists_added;
		}

		if ( ! empty( $tags_added ) ) {
			$result['tags'] = $tags_added;
		}

		return $this->success_response( $result, __( 'Contact updated', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Update_Contact' );
