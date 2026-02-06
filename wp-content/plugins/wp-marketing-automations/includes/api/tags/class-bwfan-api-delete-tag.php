<?php

class BWFAN_API_Delete_Tag extends BWFAN_API_Base {

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
		$this->route  = '/v3/tags/(?P<tag_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'tag_id' => '' );
	}

	public function process_api_call() {
		$tag_id = $this->get_sanitized_arg( 'tag_id', 'key' );
		if ( empty( $tag_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Tag ID is missing', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		/** Checking if the provided id is tag id or not $data */
		$check_tag = BWFCRM_Tag::get_terms( BWFCRM_Term_Type::$TAG, array( $tag_id ) );
		if ( empty( $check_tag ) ) {
			$this->response_code = 404;
			/* translators: 1: Tag ID */
			$response = sprintf( __( 'Tag not exist with given ID #%1$d', 'wp-marketing-automations' ), $tag_id );

			return $this->error_response( $response );
		}

		$delete_tag = BWFCRM_Tag::delete_tag( absint( $tag_id ) );
		if ( false === $delete_tag ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the tag', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Tag deleted', 'wp-marketing-automations' );

		return $this->success_response( [], $success_message );
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Delete_Tag' );

