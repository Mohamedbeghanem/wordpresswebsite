<?php


class BWFAN_API_Update_List extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/v3/lists/(?P<list_id>[\\d]+)';
	}

	public function default_args_values() {
		$args = array(
			'list_id'   => '',
			'list_name' => '',
		);

		return $args;
	}

	public function process_api_call() {
		$list_id = $this->get_sanitized_arg( 'list_id', 'text_field' );
		if ( empty( $list_id ) ) {
			$this->response_code = 400;
			$response            = __( 'List ID is mandatory', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$list = new BWFCRM_Lists( absint( $list_id ) );
		if ( ! $list->is_exists() ) {
			$this->response_code = 404;
			$response            = __( 'List doesn\'t exists', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$list_name = $this->get_sanitized_arg( 'list_name', 'text_field' );
		if ( empty( $list_name ) ) {
			$this->response_code = 404;
			$response            = __( 'List name is mandatory', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$current_name = $list->get_name();
		if ( $list_name !== $current_name ) {
			/** Check if list is already exists */
			$already_exists = BWFCRM_Tag::get_terms( BWFCRM_Term_Type::$LIST, [], $list_name, 0, 0, ARRAY_A, 'exact' );
			if ( ! empty( $already_exists ) ) {
				$this->response_code = 404;
				/* translators: 1: List name */
				$response = sprintf( __( 'List already exists with name: %1$s', 'wp-marketing-automations' ), $list_name );

				return $this->error_response( $response );
			}

			$list->set_name( $list_name );
		}

		$description = $this->get_sanitized_arg( 'description', 'text_field' );
		$list->set_description( $description );

		if ( empty( $list->save() ) ) {
			return $this->error_response( __( 'Unable to update the list', 'wp-marketing-automations' ), null, 500 );
		}

		return $this->success_response( __( 'List updated', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Update_List' );

