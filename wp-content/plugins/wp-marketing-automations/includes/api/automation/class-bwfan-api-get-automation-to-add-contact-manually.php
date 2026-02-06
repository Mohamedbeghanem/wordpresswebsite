<?php

class BWFAN_API_Get_Automation_To_Add_Contact_Manually extends BWFAN_API_Base {
	public static $ins;
	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/automation/allowed-automations';
	}

	public function process_api_call() {
		$limit   = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 25;
		$offset  = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$search  = ! empty( $this->get_sanitized_arg( 'search', 'text_field' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$status  = ! empty( $this->get_sanitized_arg( 'status', 'text_field' ) ) ? $this->get_sanitized_arg( 'status', 'text_field' ) : 1;
		$version = ! empty( $this->get_sanitized_arg( 'version', 'text_field' ) ) ? $this->get_sanitized_arg( 'version', 'text_field' ) : 2;

		$events = BWFAN_Core()->sources->get_events_to_add_contact_manually();

		if ( empty( $events ) ) {
			return $this->success_response( [], __( 'No allowed events found', 'wp-marketing-automations' ) );
		}

		$placeholder = array_fill( 0, count( $events ), '%s' );
		$placeholder = implode( ", ", $placeholder );

		$args = $events;
		$args = array_merge( $args, [ $status, $version, '%' . $search . '%', $offset, $limit ] );

		global $wpdb;
		$automations = $wpdb->get_results( $wpdb->prepare( "SELECT `ID` as `key`,`title` as `value` FROM `{$wpdb->prefix}bwfan_automations` WHERE `event` IN($placeholder) AND `status`=%d AND `v`=%d AND `title` LIKE %s LIMIT %d, %d", $args ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $automations ) ) {
			return $this->success_response( [], __( 'No automations found in which contact can be added manually', 'wp-marketing-automations' ) );
		}

		$this->response_code = 200;

		return $this->success_response( $automations, __( 'Automations found', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Automation_To_Add_Contact_Manually' );
