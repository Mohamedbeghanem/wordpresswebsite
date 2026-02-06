<?php

class BWFAN_API_Get_Contact_Tags extends BWFAN_API_Base {
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
		$this->route              = '/v3/contacts/(?P<contact_id>[\\d]+)/tags';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 30;
		$this->request_args       = array(
			'search' => array(
				'description' => __( 'Search from tag name', 'wp-marketing-automations' ),
				'type'        => 'string',
			),
			'offset' => array(
				'description' => __( 'Tags list Offset', 'wp-marketing-automations' ),
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
			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( _sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

//		$contact_terms       = BWFCRM_Model_Contact_Terms::get_contact_terms( $contact->get_id(), $offset, $limit, $search, 0 );
		$contact_terms       = $contact->get_all_tags();
		$this->response_code = 200;

		return $this->success_response( $contact_terms );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Contact_Tags' );
