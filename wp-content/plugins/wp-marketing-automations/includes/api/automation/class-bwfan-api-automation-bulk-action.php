<?php
if ( ! class_exists( 'BWFAN_API_Automation_Bulk_Action' ) && BWFAN_Common::is_pro_3_0() ) {
	class BWFAN_API_Automation_Bulk_Action extends BWFAN_API_Base {
		public static $ins;

		public $total_count = 0;

		public function __construct() {
			parent::__construct();
			$this->method       = WP_REST_Server::EDITABLE;
			$this->route        = 'v3/automation/(?P<automation_id>[\\d]+)/bulk-action';
			$this->request_args = array(
				'automation_id' => array(
					'description' => __( 'Automation ID', 'wp-marketing-automations' ),
					'type'        => 'integer',
				),
			);
		}

		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		public function process_api_call() {
			$automation_id = $this->get_sanitized_arg( 'automation_id', 'text_field' );
			$action        = $this->get_sanitized_arg( 'action', 'text_field' );
			$status        = $this->get_sanitized_arg( 'status', 'text_field' );
			$a_cids        = isset( $this->args['a_cids'] ) ? $this->args['a_cids'] : [];
			$a_cids        = array_map( 'absint', $a_cids );
			if ( empty( $a_cids ) ) {
				return $this->error_response( [], __( 'Required parameter is missing', 'wp-marketing-automations' ) );
			}

			/** Initiate automation object */
			$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

			/** Check for automation exists */
			if ( ! empty( $automation_obj->error ) ) {
				return $this->error_response( [], $automation_obj->error );
			}

			$dynamic_string = BWFAN_Common::get_dynamic_string();
			$args           = [ 'key' => $dynamic_string, 'aid' => $automation_id, 'action' => $action, 'status' => $status ];
			sort( $a_cids );
			update_option( "bwfan_bulk_automation_contact_{$action}_{$dynamic_string}", $a_cids );
			bwf_schedule_recurring_action( time(), 60, "bwfan_automation_contact_bulk_action", $args );
			BWFCRM_Common::ping_woofunnels_worker();
			$this->response_code = 200;

			return $this->success_response( [], __( 'Process is scheduled. Will soon be executed.', 'wp-marketing-automations' ) );
		}

	}

	BWFAN_API_Loader::register( 'BWFAN_API_Automation_Bulk_Action' );
}