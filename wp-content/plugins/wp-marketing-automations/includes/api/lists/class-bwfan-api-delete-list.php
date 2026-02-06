<?php

class BWFAN_Api_Delete_List extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::DELETABLE;
		$this->route  = '/v3/lists/(?P<list_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'list_id' => '' );
	}

	public function process_api_call() {

		$list_id = $this->get_sanitized_arg( 'list_id', 'key' );

		if ( empty( $list_id ) ) {
			$this->response_code = 404;
			$response            = __( 'List ID is mandatory ', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		/** checking if the provided id is list id or not $data */
		$check_list = BWFCRM_Lists::get_terms( BWFCRM_Term_Type::$LIST, array( $list_id ) );

		if ( empty( $check_list ) ) {
			$this->response_code = 404;
			/* translators: 1: List ID */
			$response = sprintf( __( 'List not exist with ID #%1$d', 'wp-marketing-automations' ), $list_id );

			return $this->error_response( $response );
		}

		$delete_list = BWFCRM_Lists::delete_list( absint( $list_id ) );

		if ( false === $delete_list ) {
			$this->response_code = 404;
			/* translators: 1: List ID */
			$response = sprintf( __( 'Unable to delete the list with ID #%1$d', 'wp-marketing-automations' ), $list_id );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'List deleted', 'wp-marketing-automations' );

		return $this->success_response( [], $success_message );

	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Delete_List' );
