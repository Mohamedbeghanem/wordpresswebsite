<?php

class BWFAN_API_Get_Contact extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/v3/contacts/(?P<contact_id>[\\d]+)';
		$this->request_args = array(
			'contact_id' => array(
				'description' => __( 'Contact ID to retrieve', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
		);
	}

	public function process_api_call() {
		/** checking if id or email present in params **/
		$id      = $this->get_sanitized_arg( 'contact_id', 'key' );
		$contact = new BWFCRM_Contact( $id );
		if ( $contact->is_contact_exists() ) {
			try {
				$data = $contact->get_array( false, true, true, true, true );
			} catch ( Error $e ) {
				$message             = $e->getMessage();
				$this->response_code = 404;

				return $this->error_response( $message );
			}

			return $this->success_response( $data );

		}
		$this->response_code = 404;

		return $this->error_response( __( "No contact found with given id #", 'wp-marketing-automations' ) . $id );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Contact' );
