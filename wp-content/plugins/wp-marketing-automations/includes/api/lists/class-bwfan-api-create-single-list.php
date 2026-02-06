<?php

class BWFAN_API_Create_Single_List extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function default_args_values() {
		return array(
			'lists' => array(),
		);
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/v3/list';
		$this->response_code = 200;
	}

	public function process_api_call() {
		$name        = $this->get_sanitized_arg( 'name', 'text_field' );
		$description = $this->get_sanitized_arg( 'description', 'text_field' );
		if ( empty( $name ) ) {
			$this->response_code = 400;
			$response            = __( 'Name is required', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		/** Check if list is already exists */
		$already_exists = BWFCRM_Tag::get_terms( BWFCRM_Term_Type::$LIST, [], $name, 0, 0, ARRAY_A, 'exact' );
		if ( ! empty( $already_exists ) ) {
			$this->response_code = 404;
			/* translators: 1: List name */
			$response = sprintf( __( 'List already exists with name: %1$s', 'wp-marketing-automations' ), $name );

			return $this->error_response( $response );
		}

		$list = new BWFCRM_Lists();
		$list->set_name( $name );
		if ( ! empty( $description ) ) {
			$list->set_description( $description );
		}

		if ( empty( $list->save() ) ) {
			return $this->error_response( __( 'Unable to create new list', 'wp-marketing-automations' ), null, 500 );
		}

		return $this->success_response( $list->get_array(), __( 'List Created', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Create_Single_List' );

