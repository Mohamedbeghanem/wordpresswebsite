<?php

class BWFAN_API_Delete_Contact extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}


	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::DELETABLE;
		$this->route  = '/v3/contacts/(?P<contact_id>[\\d]+)';
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
		);
	}

	public function process_api_call() {
		/** checking if search present in params **/

		$contact_id = intval( $this->get_sanitized_arg( 'contact_id', 'text_field' ) );
		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Contact id is missing.', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact instanceof BWFCRM_Contact || 0 === $contact->get_id() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$delete_contact_result = BWFCRM_Model_Contact::delete_contact( $contact_id );

		if ( ! $delete_contact_result ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Some error occurred during deletion of contact and its data.', 'wp-marketing-automations' ) );
		}

		$this->response_code = 200;
		$success_message     = __( 'Contact deleted', 'wp-marketing-automations' );

		return $this->success_response( array( 'contact_id' => $contact_id ), $success_message );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Delete_Contact' );
