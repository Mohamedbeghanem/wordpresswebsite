<?php

class BWFAN_API_Contact_Unsubscribe extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/v3/contacts/(?P<contact_id>[\\d]+)/unsubscribe';
		$this->request_args = array(
			'contact_id' => array(
				'description' => __( 'Contact ID to unsubscribe contact', 'wp-marketing-automations' ),
				'type'        => 'integer',
			)
		);
	}

	public function default_args_values() {
		return array(
			'contact_id'    => '',
			'mode'          => '',
			'automation_id' => '',
			'c_type'        => ''
		);
	}

	public function process_api_call() {
		$contact_id    = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$automation_id = $this->get_sanitized_arg( 'automation_id', 'text_field' );
		$c_type        = $this->get_sanitized_arg( 'c_type', 'text_field' );
		$contact       = new BWFCRM_Contact( absint( $contact_id ) );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact doesn\'t exist', 'wp-marketing-automations' ) );
		}

		$unsubscribed = $contact->check_contact_unsubscribed();

		if ( ! empty( $unsubscribed['ID'] ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact already unsubscribed', 'wp-marketing-automations' ) );
		}
		$recipients   = [];
		$email        = $contact->contact->get_email();
		$phone        = $contact->contact->get_contact_no();
		$recipients[] = $email;
		if ( ! empty( $phone ) ) {
			$recipients[] = $phone;
		}

		foreach ( $recipients as $recipient ) {
			$insert_data = array(
				'recipient'     => $recipient,
				'mode'          => is_email( $recipient ) ? 1 : 2,
				'c_date'        => current_time( 'mysql', 1 ),
				'automation_id' => ! empty( $automation_id ) ? absint( $automation_id ) : get_current_user_id(),
				'c_type'        => ! empty( $c_type ) ? absint( $c_type ) : 3
			);

			BWFAN_Model_Message_Unsubscribe::insert( $insert_data );
			/** hook when any contact unsubscribed  */
			do_action( 'bwfcrm_after_contact_unsubscribed', $insert_data );
		}
		$contact->save_last_modified();

		return $this->success_response( [ 'contact_id' => $contact_id ], __( 'Contact unsubscribed', 'wp-marketing-automations' ) );

	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Contact_Unsubscribe' );
