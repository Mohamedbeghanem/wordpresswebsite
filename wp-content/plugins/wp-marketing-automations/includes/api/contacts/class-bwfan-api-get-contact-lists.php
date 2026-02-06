<?php

class BWFAN_API_Get_Contact_Lists extends BWFAN_API_Base {
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
		$this->route              = '/v3/contacts/(?P<contact_id>[\\d]+)/lists';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 30;
		$this->request_args       = array(
			'search' => array(
				'description' => __( 'Search from list name', 'wp-marketing-automations' ),
				'type'        => 'string',
			),
			'offset' => array(
				'description' => __( 'list Offset', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
			'limit'  => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
		);
	}

	public function process_api_call() {
		/** checking if search present in params **/

		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$search     = $this->get_sanitized_arg( 'search', 'text_field' );
		$offset     = $this->get_sanitized_arg( 'offset', 'text_field' );
		$limit      = $this->get_sanitized_arg( 'limit', 'text_field' );

		$offset = empty( $offset ) ? $this->pagination->offset : $offset;
		$limit  = empty( $offset ) ? $this->pagination->limit : $limit;

		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Contact id is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

//		$contact_terms_count = BWFCRM_Model_Contact_Terms::get_contact_terms_count( $contact->get_id(), $search, 1 );
//		$contact_terms       = BWFCRM_Model_Contact_Terms::get_contact_terms( $contact->get_id(), $offset, $limit, $search, 1 );
		$contact_terms = $contact->get_all_lists();
		if ( empty( $contact_terms ) ) {
			$this->response_code = 404;
			/* translators: 1: Search term */
			$error_message = ! empty( $search ) ? sprintf( __( 'No Searched lists found for contact with name %1$s', 'wp-marketing-automations' ), " '$search'" ) : __( 'No contacts lists found', 'wp-marketing-automations' );

			return $this->error_response( $error_message );
		}

		$this->total_count   = count( $contact_terms );
		$this->response_code = 200;
		/* translators: 1: Search term */
		$success_message = ! empty( $search ) ? sprintf( __( 'Searched lists for contact ', 'wp-marketing-automations' ), " '$search'" ) : __( 'Got all contacts lists', 'wp-marketing-automations' );

		return $this->success_response( $contact_terms, $success_message );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Contact_Lists' );
