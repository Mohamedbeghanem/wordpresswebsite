<?php

class BWFAN_API_Get_Lists extends BWFAN_API_Base {

	public static $ins;
	public $total_count = 0;
	public $count_data = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method            = WP_REST_Server::READABLE;
		$this->route             = '/v3/lists';
		$this->pagination->limit = 50;
		$this->lists             = array();
	}

	public function default_args_values() {
		return array( 's' => '' );
	}

	public function process_api_call() {

		$search   = $this->get_sanitized_arg( 'search', 'text_field' );
		$list_ids = empty( $this->args['ids'] ) ? array() : explode( ',', $this->args['ids'] );
		$limit    = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 0;
		$offset   = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;

		$list_data = BWFCRM_Lists::get_lists( $list_ids, $search, $offset, $limit, ARRAY_A );

		if ( ! is_array( $list_data ) ) {
			$list_data = array();
		}
		$list_count = BWFAN_Model_Terms::get_terms_count( 2, $search, $list_ids );

		$this->total_count   = $list_count;
		$this->lists         = $list_data;
		$this->response_code = 200;
		$this->count_data    = BWFAN_Common::get_contact_data_counts();

		return $this->success_response( $list_data );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Get_Lists' );