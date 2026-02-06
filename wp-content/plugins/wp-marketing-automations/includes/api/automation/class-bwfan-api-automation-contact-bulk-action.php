<?php

class BWFAN_API_Automation_Contact_Bulk_Action extends BWFAN_API_Base {
	public static $ins;

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/automation/bulk-action';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$action = $this->get_sanitized_arg( 'action', 'text_field' );
		$status = $this->get_sanitized_arg( 'status', 'text_field' );
		$a_cids = isset( $this->args['a_cids'] ) ? $this->args['a_cids'] : [];

		if ( empty( $a_cids ) ) {
			return $this->error_response( [], __( 'Required parameter is missing', 'wp-marketing-automations' ) );
		}

		$dynamic_string = BWFAN_Common::get_dynamic_string();
		$args           = [ 'key' => $dynamic_string, 'action' => $action, 'status' => $status ];
		sort( $a_cids );
		update_option( "bwfan_bulk_automation_all_contact_{$action}_{$dynamic_string}", $a_cids );
		bwf_schedule_recurring_action( time(), 60, "bwfan_automation_all_contact_bulk_action", $args );
		BWFAN_Common::ping_woofunnels_worker();
		$this->response_code = 200;

		return $this->success_response( [], __( 'Process is scheduled. Will soon be executed.', 'wp-marketing-automations' ) );
	}

}

BWFAN_API_Loader::register( 'BWFAN_API_Automation_Contact_Bulk_Action' );
