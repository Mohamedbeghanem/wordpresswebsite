<?php

class BWFAN_API_Get_Tag_By_ID extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/v3/tags/(?P<tag_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'tag_id' => '' );
	}

	public function process_api_call() {
		$tag_id = $this->get_sanitized_arg( 'tag_id', 'key' );
		if ( empty( $tag_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Tag ID is missing ', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$tag_data = BWFAN_Model_Terms::get( absint( $tag_id ) );
		if ( empty( $tag_data ) ) {
			$this->response_code = 404;
			/* translators: 1: Tag ID */
			$response = sprintf( __( 'No tag data found related with tag id: %1$d', 'wp-marketing-automations' ), $tag_id );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		/* translators: 1: Tag ID */
		$success_message = sprintf( __( 'Tag data found with tag id: %1$d', 'wp-marketing-automations' ), $tag_id );

		return $this->success_response( $tag_data, $success_message );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Tag_By_ID' );
