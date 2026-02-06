<?php

class BWFAN_API_Get_Contact_Note extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/v3/contacts/(?P<contact_id>[\\d]+)/notes';
		$this->pagination->limit  = 25;
		$this->pagination->offset = 0;
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
		);
	}

	public function process_api_call() {
		/** checking if search present in params **/
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$offset     = ! empty( $this->get_sanitized_arg( 'offset', 'key' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : $this->pagination->offset;
		$limit      = ! empty( $this->get_sanitized_arg( 'limit', 'key' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : $this->pagination->limit;

		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$contact_notes = $contact->get_contact_notes_array( $offset, $limit );

		if ( empty( $contact_notes ) ) {
			$this->response_code = 200;

			/* translators: 1: Contact ID */

			return $this->success_response( [], sprintf( __( 'No contact notes found related with contact id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}
		$all_contact_notes   = $contact->get_contact_notes_array( 0, 0 );
		$this->total_count   = count( $all_contact_notes );
		$this->response_code = 200;
		/* translators: 1: Contact ID */
		$success_message = sprintf( __( 'Contact notes related to contact id : #%1$d', 'wp-marketing-automations' ), $contact_id );

		return $this->success_response( $contact_notes, $success_message );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Contact_Note' );
