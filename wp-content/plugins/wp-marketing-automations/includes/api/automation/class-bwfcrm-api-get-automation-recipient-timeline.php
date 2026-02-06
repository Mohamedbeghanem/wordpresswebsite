<?php

class BWFAN_API_Get_Automation_Recipient_Timeline extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $recipients_timeline = [];

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = 'v3/automation/(?P<automation_id>[\\d]+)/recipients/(?P<contact_id>[\\d]+)/timeline';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;
		$this->request_args       = array(
			'automation_id' => array(
				'description' => __( 'Automation ID to retrieve engagements', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
			'contact_id'    => array(
				'description' => __( 'Contact ID to retrieve recipient timeline', 'wp-marketing-automations' ),
				'type'        => 'integer',
			)
		);

	}

	public function process_api_call() {
		$automation_id       = $this->get_sanitized_arg( 'automation_id', 'text_field' );
		$contact_id          = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$mode                = ! empty( absint( $this->get_sanitized_arg( 'mode', 'text_field' ) ) ) ? $this->get_sanitized_arg( 'mode', 'text_field' ) : 1;
		$recipients_timeline = BWFAN_Model_Engagement_Tracking::get_automation_recipient_timeline( $automation_id, $contact_id, $mode );
		if ( empty( $recipients_timeline ) ) {
			return $this->success_response( [], __( 'No engagement found', 'wp-marketing-automations' ) );
		}
		$this->recipients_timeline = $recipients_timeline;

		return $this->success_response( $recipients_timeline, __( 'Got All timeline', 'wp-marketing-automations' ) );
	}

	public function get_result_total_count() {
		return count( $this->recipients_timeline );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Automation_Recipient_Timeline' );
