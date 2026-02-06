<?php

class BWFAN_Api_Get_Group_Fields extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::READABLE;
		$this->route         = '/v3/groupfields/(?P<group_id>[\\d]+)';
		$this->request_args  = array(
			'group_id' => array(
				'description' => __( 'Group ID to retrieve', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
		);
		$this->response_code = 200;
		$this->fields        = array();
	}

	public function default_args_values() {
		$args = array(
			'group_id' => ''
		);

		return $args;
	}

	public function process_api_call() {
		$group_id = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$fields   = BWFCRM_Fields::get_group_fields( $group_id );

		if ( empty( $fields ) ) {
			$this->response_code = 404;
			$response            = __( "No fields found", "wp-marketing-automations" );

			return $this->error_response( $response );
		}
		$this->fields = $fields;

		return $this->success_response( $fields, __( 'Got All Group\'s Fields', 'wp-marketing-automations' ) );
	}


	public function get_result_total_count() {
		return count( $this->fields );
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Get_Group_Fields' );
