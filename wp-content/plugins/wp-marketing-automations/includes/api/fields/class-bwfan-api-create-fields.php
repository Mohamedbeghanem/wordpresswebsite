<?php

class BWFAN_API_Create_Fields extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function default_args_values() {
		$args = array(
			'group_id' => '',
			'field'    => [],
		);

		return $args;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/v3/fields';
		$this->response_code = 200;
	}

	public function process_api_call() {

		/**
		 *  getting post data
		 */
		$field    = $this->get_sanitized_arg( '', 'text_field', $this->args['field'] );
		$group_id = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$group_id = ! empty( $group_id ) && is_numeric( $group_id ) ? $group_id : 0;
		if ( empty( $field['name'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Field name is mandatory', 'wp-marketing-automations' ) );
		}
		/** Checking field slug is reserved key or not */
		if ( in_array( sanitize_title( $field['name'] ), BWFCRM_Fields::$reserved_keys, true ) ) {
			$this->response_code = 400;

			/* translators: 1: Field name */

			return $this->error_response( sprintf( __( '%1$s is a reserved key', 'wp-marketing-automations' ), sanitize_title( $field['name'] ) ) );
		}

		/**
		 *  Check group exist.
		 */
		$group = BWFCRM_Group::get_groupby_id( $group_id );
		if ( $group_id > 0 && empty( $group ) ) {
			$this->response_code = 400;

			/* translators: 1: Group ID */

			return $this->error_response( sprintf( __( 'Group with id %1$d not found', 'wp-marketing-automations' ), $group_id ) );  // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
		}

		if ( empty( $field['type'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Field type is mandatory', 'wp-marketing-automations' ) );
		}
		$field_name = $field['name'];
		$type       = $field['type'];
		$mode       = isset( $field['mode'] ) ? absint( $field['mode'] ) : 1;
		$vmode      = isset( $field['vmode'] ) ? absint( $field['vmode'] ) : 1;
		$search     = isset( $field['search'] ) ? absint( $field['search'] ) : 1;

		if ( ! empty( $this->args['field']['options'] ) ) {
			$options = $this->get_sanitized_arg( '', 'text_field', $this->args['field']['options'] );
		}
		$options = ! empty( $options ) && is_array( $options ) ? $options : [];

		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$field       = BWFCRM_Fields::add_field( $field_name, $type, $options, $placeholder, $mode, $vmode, $search, $group_id );
		if ( is_wp_error( $field ) ) {
			return $this->error_response( '', $field, $field->get_error_code() );
		}

		if ( isset( $field['err_msg'] ) ) {
			$this->response_code = 400;

			/* translators: 1: Error message */

			return $this->error_response( sprintf( __( 'Cannot create a field. Error: %1$s. Contact Funnelkit support.', 'wp-marketing-automations' ), $field['err_msg'] ) );  // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
		}

		$field['merge_tag'] = BWFAN_Core()->merge_tags->get_field_tag( $field['slug'] );

		return $this->success_response( $field, __( 'Field created', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Create_Fields' );
