<?php

class BWFAN_API_Create_List extends BWFAN_API_Base {

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
		$this->route         = '/v3/lists';
		$this->response_code = 200;
	}

	public function process_api_call() {
		$lists = $this->get_sanitized_arg( '', 'text_field', $this->args['lists'] );
		/** IN CASE Lists PARAMS ARE MISSING **/
		if ( empty( $lists ) ) {
			$this->response_code = 404;
			$response            = __( 'No list names provided to create', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$list_data = array();

		foreach ( $lists as $key => $list ) {
			if ( ! isset( $list_data[ $key ] ) ) {
				$list_data[ $key ] = [];
			}
			$list_data[ $key ]['id']    = 0;
			$list_data[ $key ]['value'] = $list;
		}

		$lists = BWFCRM_Term::get_or_create_terms( $list_data, BWFCRM_Term_Type::$LIST, true, true );
		if ( is_wp_error( $lists ) ) {
			$this->response_code = 500;

			return $this->error_response( '', $lists );
		}

		if ( ! isset( $lists['existing'] ) || ! isset( $lists['created'] ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Some error occurred', 'wp-marketing-automations' ) );
		}

		$existing_lists = BWFCRM_Term::get_collection_array( $lists['existing'] );
		$created_lists  = BWFCRM_Term::get_collection_array( $lists['created'] );
		$all_lists      = array_merge( $created_lists, $existing_lists );

		if ( empty( $created_lists ) ) {
			return $this->success_response( $all_lists, __( 'Given list already exists.', 'wp-marketing-automations' ) );
		}

		return $this->success_response( $all_lists, __( 'New list added', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Create_List' );
