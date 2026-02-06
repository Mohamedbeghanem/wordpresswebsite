<?php

class BWFAN_API_Change_Automation_Status extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::EDITABLE;
		$this->route        = '/automation/(?P<contact_automation_id>[\\d]+)/contact/status';
		$this->request_args = array(
			'contact_automation_id' => array(
				'description' => __( 'Automation contact ID to retrieve', 'wp-marketing-automations' ),
				'type'        => 'integer',
			)
		);
	}

	public function process_api_call() {
		$a_cid = $this->get_sanitized_arg( 'contact_automation_id', 'text_field' );

		if ( empty( $a_cid ) ) {
			return $this->error_response( [], __( 'Automation contact ID is missing', 'wp-marketing-automations' ) );
		}
		/** To be changed status*/
		$to = $this->get_sanitized_arg( 'to', 'text_field' );
		if ( empty( $to ) ) {
			return $this->error_response( [], __( 'Status is missing', 'wp-marketing-automations' ) );
		}

		$data = ( 're_run' === $to ) ? BWFAN_Model_Automation_Complete_Contact::get( $a_cid ) : BWFAN_Model_Automation_Contact::get_data( $a_cid );

		$aid = isset( $data['aid'] ) ? $data['aid'] : 0;

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $aid );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->errors ) ) {
			return $this->error_response( __( 'Automation not found', 'wp-marketing-automations' ), $automation_obj->errors );
		}

		switch ( $to ) {
			case 'end' === $to:
				$trail = isset( $data['trail'] ) ? $data['trail'] : '';

				/** Update the step trail status as complete if active */
				BWFAN_Model_Automation_Contact_Trail::update_all_step_trail_status_complete( $trail );

				$result = BWFAN_Common::end_v2_automation( $a_cid, $data, 'manually' );
				break;
			case 're_run' === $to:
				global $wpdb;
				$query        = " SELECT `ID`,`cid`,`aid`,`trail`,`event`,`data` FROM {$wpdb->prefix}bwfan_automation_complete_contact WHERE `ID` = '$a_cid' ";
				$query_result = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				BWFAN_Common::insert_automations( $query_result );
				$result = true;
				break;
			case 'startbegin' === $to:
				global $wpdb;
				$query        = " SELECT `ID`,`cid`,`aid`,`trail` FROM {$wpdb->prefix}bwfan_automation_contact WHERE `ID` = '$a_cid'";
				$query_result = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$trails       = array_column( $query_result, 'trail' );
				BWFAN_Common::update_automation_status( array( $a_cid ), 1, $trails, current_time( 'timestamp', 1 ), true );
				$result = true;
				break;
			default:
				$result = $automation_obj->change_automation_status( $to, $a_cid );
				break;
		}
		if ( $result ) {
			$this->response_code = 200;

			return $this->success_response( [], __( 'Automation contact updated', 'wp-marketing-automations' ) );
		}
		$this->response_code = 404;

		return $this->error_response( [], __( 'Unable to update automation contact', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Change_Automation_Status' );