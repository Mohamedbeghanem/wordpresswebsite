<?php

class BWFAN_API_Get_Fields_With_Groups extends BWFAN_API_Base {
	public static $ins;

	public $fields;
	public $count_data = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::READABLE;
		$this->route         = '/v3/groupfields';
		$this->response_code = 200;
		$this->fields        = array();
	}

	public function process_api_call() {
		$response = '';
		try {
			$fields = BWFCRM_Fields::get_groups_with_fields( false, true, true );
		} catch ( Error $e ) {
			$response = $e->getMessage();
		}
		if ( empty( $fields ) ) {
			$this->response_code = 500;
			$response            = empty( $response ) ? __( "No fields found", "wp-marketing-automations" ) : $response;

			return $this->error_response( $response );
		}

		$this->fields     = $fields;
		$address_fields   = BWFCRM_Fields::get_address_fields_from_db();
		$final_result     = array(
			'fields'       => $this->fields,
			'extra_fields' => $address_fields
		);
		$this->count_data = BWFAN_Common::get_contact_data_counts();

		return $this->success_response( $final_result, __( 'Got ALL Groups with Fields', 'wp-marketing-automations' ) );
	}


	public function get_result_total_count() {
		return count( $this->fields );
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}


BWFAN_API_Loader::register( 'BWFAN_API_Get_Fields_With_Groups' );
