<?php

class BWFAN_API_Add_Contact_To_Automation extends BWFAN_API_Base {
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
		$this->method       = WP_REST_Server::EDITABLE;
		$this->route        = '/automation/add-contact';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation ID to add contact to', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
			'contact_id'    => array(
				'description' => __( 'Contact ID to add into automation', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id' );
		$contact_id    = $this->get_sanitized_arg( 'contact_id' );

		if ( empty( $automation_id ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations' ), null, 400 );
		}

		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Invalid / Empty contact ID provided', 'wp-marketing-automations' ), null, 400 );
		}

		$ins      = new BWFAN_Add_Contact_To_Automation_Controller( $automation_id, $contact_id );
		$response = $ins->add_contact_to_automation();

		$this->response_code = $response['code'] ?? 200;
		$message             = $response['message'] ?? __( 'Unknown error occurred', 'wp-marketing-automations' );

		return $this->success_response( [], $message );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Add_Contact_To_Automation' );
