<?php

class BWFAN_Api_Update_Group extends BWFAN_API_Base {

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
		$this->route         = '/v3/groupfields/(?P<group_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'group_id'   => '',
			'group_name' => '',
		);

		return $args;
	}

	public function process_api_call() {

		$group_id = $this->get_sanitized_arg( 'group_id', 'text_field' );

		if ( empty( $group_id ) ) {
			$this->response_code = 400;
			$response            = __( "Group Id is missing", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$group_name = $this->get_sanitized_arg( 'group_name', 'text_field' );

		if ( empty( $group_name ) ) {
			$this->response_code = 400;
			$response            = __( "Group name is missing", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$data = array(
			'name' => $group_name,
		);

		$where = array(
			'ID' => $group_id,
		);

		$update_group = BWFAN_Model_Field_Groups::update( $data, $where );

		if ( 0 === $update_group ) {

			$this->response_code = 400;

			/* translators: 1: Group ID */

			return $this->error_response( sprintf( __( 'Unable to update group with group id %1$d', 'wp-marketing-automations' ), $group_id ) );
		}
		$group  = BWFCRM_Group::get_groupby_id( $group_id );
		$fields = BWFCRM_Fields::get_group_fields( $group_id );
		if ( ! empty( $fields ) ) {
			$group[0]['fields'] = $fields['fields'];
		}

		return $this->success_response( $group, __( 'Field group updated', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Update_Group' );
