<?php

class BWFAN_API_Update_Tag extends BWFAN_API_Base {

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
		$this->route  = '/v3/tags/(?P<tag_id>[\\d]+)';
	}

	public function default_args_values() {
		return array(
			'tag_id'   => '',
			'tag_name' => '',
		);
	}

	public function process_api_call() {
		$tag_id = $this->get_sanitized_arg( 'tag_id', 'text_field' );
		if ( empty( $tag_id ) ) {
			$this->response_code = 404;
			$response            = __( "Tag ID is missing", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$tag_name = $this->get_sanitized_arg( 'tag_name', 'text_field' );
		if ( empty( $tag_name ) ) {
			$this->response_code = 404;
			$response            = __( "Tag name is required", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		/** Check if tag is already exists */
		$already_exists = BWFCRM_Tag::get_terms( BWFCRM_Term_Type::$TAG, [], $tag_name, 0, 0, ARRAY_A, 'exact' );
		if ( ! empty( $already_exists ) ) {
			$this->response_code = 404;
			/* translators: 1: Tag name */
			$response = sprintf( __( 'Tag already exists with name: %1$s', 'wp-marketing-automations' ), $tag_name );

			return $this->error_response( $response );
		}

		/** Checking if the provided id is tag id or not $data */
		$check_tag = BWFCRM_Tag::get_terms( BWFCRM_Term_Type::$TAG, array( $tag_id ) );
		if ( empty( $check_tag ) ) {
			$this->response_code = 404;
			/* translators: 1: Tag ID */
			$response = sprintf( __( 'Tag doesn\'t exists with ID: %1$d', 'wp-marketing-automations' ), $tag_id );

			return $this->error_response( $response );
		}

		/** check if the passed name and db name are same then return success message */
		if ( isset( $check_tag[0]['name'] ) && $tag_name === $check_tag[0]['name'] ) {
			$this->response_code = 200;
			$response            = __( 'Tag updated', 'wp-marketing-automations' );

			return $this->success_response( $response );
		}

		$data = array(
			'name' => $tag_name,
		);

		$where = array(
			'ID' => $tag_id,
		);

		$update_tag = BWFAN_Model_Terms::update( $data, $where );
		if ( 0 === $update_tag ) {
			$response            = __( 'Unable to update tag with tag id ', 'wp-marketing-automations' ) . $tag_id;
			$this->response_code = 400;

			return $this->error_response( $response );
		}

		$response = __( 'Tag updated', 'wp-marketing-automations' );

		return $this->success_response( [], $response );
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Update_Tag' );

