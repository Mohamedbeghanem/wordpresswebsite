<?php

class BWFAN_API_Create_Contact_Note extends BWFAN_API_Base {
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
		$this->route  = '/v3/contacts/(?P<contact_id>[\\d]+)/notes';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'notes'      => array(),
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		if ( empty( $contact_id ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations' ) );
		}

		$notes = $this->args['notes'];
		if ( empty( $notes ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Note is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$note_added = $contact->add_note_to_contact( $notes );
		if ( false === $note_added || is_wp_error( $note_added ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Unable to add note to contact', 'wp-marketing-automations' ) );
		}

		$this->response_code = 200;

		return $this->success_response( [], __( 'Contact note added', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Create_Contact_Note' );
