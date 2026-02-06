<?php

class BWFAN_API_Get_Contact_Automations extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;
	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/v3/contact/(?P<contact_id>[\\d]+)/automations';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
		);
	}

	public function process_api_call() {
		/** checking if id or email present in params **/
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		$offset     = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? absint( $this->get_sanitized_arg( 'offset', 'text_field' ) ) : 0;
		$limit      = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 25;

		/** contact id missing than return  */
		if ( empty( $contact_id ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Contact id is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$contact_automations = [];
		if ( class_exists( 'BWFAN_Common' ) ) {
			$contact_automations = BWFAN_Common::get_automations_for_contact( $contact_id, $limit, $offset );
		}

		if ( empty( $contact_automations ) ) {
			$this->response_code = 200;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact automation found related with contact id : %1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$contact_automations['contacts'] = array_map( function ( $contact ) {

			/** Get event name */
			$event_slug = BWFAN_Model_Automations::get_event_name( $contact['aid'] );
			$event_obj  = BWFAN_Core()->sources->get_event( $event_slug );
			$event_name = ! empty( $event_obj ) ? $event_obj->get_name() : '';

			$automation_meta = BWFAN_Core()->automations->get_automation_data_meta( $contact['aid'] );

			$contact['event']            = $event_name;
			$contact['automation_title'] = $automation_meta['title'];

			return $contact;
		}, $contact_automations['contacts'] );


		$this->total_count   = isset( $contact_automations['total'] ) ? $contact_automations['total'] : 0;
		$this->response_code = 200;
		$success_message     = __( 'Got all contact automations', 'wp-marketing-automations' );

		return $this->success_response( $contact_automations, $success_message );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

}

if ( function_exists( 'BWFAN_Core' ) ) {
	BWFAN_API_Loader::register( 'BWFAN_API_Get_Contact_Automations' );
}
