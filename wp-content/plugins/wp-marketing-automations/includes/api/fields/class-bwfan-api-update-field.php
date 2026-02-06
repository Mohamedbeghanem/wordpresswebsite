<?php

class BWFAN_API_Update_Field extends BWFAN_API_Base {

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
		$this->route         = '/v3/fields/(?P<field_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'group_id'    => '',
			'name'        => '',
			'type'        => '',
			'slug'        => '',
			'options'     => [],
			'placeholder' => '',
		);

		return $args;
	}

	public function process_api_call() {
		$field_id = $this->get_sanitized_arg( 'field_id', 'text_field' );
		$group_id = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$group_id = ! empty( $group_id ) && is_numeric( $group_id ) ? $group_id : 0;
		if ( empty( $field_id ) ) {
			$this->response_code = 400;
			$response            = __( "Field Id is missing", 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$slug = $this->get_sanitized_arg( 'slug', 'text_field' );
		/** Checking field slug is reserved key or not */
		if ( in_array( sanitize_title( $slug ), BWFCRM_Fields::$reserved_keys, true ) ) {
			$this->response_code = 400;

			/* translators: 1: Field slug */

			return $this->error_response( sprintf( __( '%1$s is a reserved key', 'wp-marketing-automations' ), sanitize_title( $slug ) ) );
		}

		$group = BWFCRM_Group::get_groupby_id( $group_id );
		if ( $group_id > 0 && empty( $group ) ) {
			$this->response_code = 400;

			/* translators: 1: Group ID */

			return $this->error_response( sprintf( __( 'Field Group ID %1$d is mandatory', 'wp-marketing-automations' ), $group_id ) );  // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
		}

		if ( ! isset( $this->args['group_id'] ) ) {
			$group_id = false;
		}

		$field_name = $this->get_sanitized_arg( 'name', 'text_field' );

		$type        = $this->get_sanitized_arg( 'type', 'text_field' );
		$options     = $this->get_sanitized_arg( '', 'text_field', $this->args['options'] );
		$placeholder = $this->get_sanitized_arg( 'placeholder', 'text_field' );
		$mode        = $this->get_sanitized_arg( 'mode', 'text_field' );
		$vmode       = $this->get_sanitized_arg( 'vmode', 'text_field' );
		$search      = $this->get_sanitized_arg( 'search', 'text_field' );

		if ( empty( $slug ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Field slug is mandatory', 'wp-marketing-automations' ) );
		}


		$update_field = BWFCRM_Fields::update_field( $field_id, $group_id, $field_name, $type, $options, $placeholder, $slug, $mode, $vmode, $search );

		$this->response_code = $update_field['status'];
		if ( $update_field['status'] == 404 ) {
			return $this->error_response( $update_field['message'] );
		}
		$field         = BWFAN_Model_Fields::get( $field_id );
		$meta          = json_decode( $field['meta'] );
		$field['meta'] = $meta;

		$field['merge_tag'] = BWFAN_Core()->merge_tags->get_field_tag( $slug );

		return $this->success_response( $field, __( 'Field updated', 'wp-marketing-automations' ) );
	}
}


BWFAN_API_Loader::register( 'BWFAN_API_Update_Field' );
