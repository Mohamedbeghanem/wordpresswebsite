<?php

class BWFAN_API_Get_Fields extends BWFAN_API_Base {

	public static $ins;
	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::READABLE;
		$this->route         = '/v3/fields';
		$this->response_code = 200;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$type              = isset( $this->args['type'] ) ? $this->get_sanitized_arg( 'type', 'text_field', $this->args['type'] ) : null;
		$all_fields        = isset( $this->args['all'] ) ? $this->get_sanitized_arg( 'all', 'text_field', $this->args['all'] ) : false;
		$fields            = BWFCRM_Fields::get_custom_fields( null, $all_fields ? 1 : null, null, $all_fields ? false : true, $all_fields ? null : 1, $type );
		$fields            = method_exists( 'BWFCRM_Fields', 'get_sorted_fields' ) ? BWFCRM_Fields::get_sorted_fields( $fields ) : $fields;
		$this->total_count = count( $fields );

		return $this->success_response( $fields, empty( $fields ) ? __( 'No Fields found.', 'wp-marketing-automations' ) : __( 'Got all fields', 'wp-marketing-automations' ) );
	}


	public function get_result_total_count() {
		return $this->total_count;
	}
}


BWFAN_API_Loader::register( 'BWFAN_API_Get_Fields' );
