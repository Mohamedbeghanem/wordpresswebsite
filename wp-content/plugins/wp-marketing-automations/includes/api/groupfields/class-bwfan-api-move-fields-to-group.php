<?php

class BWFAN_API_Moved_Fields_To_Group extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::EDITABLE;
		$this->route         = '/v3/groupfields/move';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'group_id'      => '',
			'move_group_id' => ''
		);

		return $args;
	}

	public function process_api_call() {

		$group_id      = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$move_group_id = $this->get_sanitized_arg( 'move_group_id', 'text_field' );

		if ( ! isset( $group_id ) ) {
			$this->response_code = 400;
			$response            = __( "Group Id is missing", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		if ( ! isset( $move_group_id ) ) {
			$this->response_code = 400;
			$response            = __( "Move Group Id is missing", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$moved = BWFCRM_Fields::field_move_to_group( $group_id, $move_group_id );

		if ( 0 === $moved ) {

			$this->response_code = 400;

			return $this->error_response( __( 'Unable to move the field to new group', 'wp-marketing-automations' ) );
		}

		return $this->success_response( __( 'Field updated', 'wp-marketing-automations' ) );
	}
}


BWFAN_API_Loader::register( 'BWFAN_API_Moved_Fields_To_Group' );
