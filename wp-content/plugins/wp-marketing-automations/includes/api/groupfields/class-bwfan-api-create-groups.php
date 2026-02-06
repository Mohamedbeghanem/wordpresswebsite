<?php

class BWFAN_API_Create_Groups extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function default_args_values() {
		$args = array(
			'group_name' => '',
		);

		return $args;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/v3/groupfields';
		$this->response_code = 200;
	}

	public function process_api_call() {
		/**
		 *  getting post data
		 */

		$group_name = $this->get_sanitized_arg( 'group_name', 'text_field' );

		if ( empty( $group_name ) ) {
			$this->response_code = 400;

			return $this->error_response( __( "Required group missing", "wp-marketing-automations" ) );
		}


		$group = BWFCRM_Group::add_group( $group_name );

		$response = __( 'Field group created', 'wp-marketing-automations' );

		return $this->success_response( $group, $response );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Create_Groups' );
