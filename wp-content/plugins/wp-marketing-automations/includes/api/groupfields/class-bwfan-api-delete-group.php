<?php

class BWFAN_Api_Delete_Group extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::DELETABLE;
		$this->route         = '/v3/groupfields/(?P<group_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'group_id'      => '',
			'move_to_group' => ''
		);

		return $args;
	}

	public function process_api_call() {

		$group_id      = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$move_group_id = $this->get_sanitized_arg( 'move_to_group', 'text_field' );
		$move_group_id = ! empty( $move_group_id ) ? $move_group_id : 0;

		if ( empty( $group_id ) ) {
			$this->response_code = 400;
			$response            = __( "Group Id is missing", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		BWFCRM_Fields::field_move_to_group( $group_id, $move_group_id );

		$delete_group = BWFAN_Model_Field_Groups::delete( $group_id );


		if ( 0 === $delete_group ) {

			$this->response_code = 400;

			/* translators: 1: Group ID */

			return $this->error_response( sprintf( __( 'Unable to delete group with group id %1$d', 'wp-marketing-automations' ), $group_id ) );
		}

		return $this->success_response( __( 'Field group deleted', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Delete_Group' );
