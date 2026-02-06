<?php

class BWFAN_Api_Delete_Field extends BWFAN_API_Base {

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
		$this->route         = '/v3/fields/(?P<field_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'field_id' => ''
		);

		return $args;
	}

	public function process_api_call() {

		$field_id = $this->get_sanitized_arg( 'field_id', 'text_field' );

		$delete_field = BWFCRM_Fields::delete_field( $field_id );

		if ( 0 === $delete_field ) {

			$this->response_code = 400;

			/* translators: 1: Field ID */

			return $this->error_response( sprintf( __( 'Unable to delete field with id #%1$d', 'wp-marketing-automations' ), $field_id ) );
		}

		return $this->success_response( __( 'Field deleted', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Delete_Field' );
