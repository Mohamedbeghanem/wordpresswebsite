<?php

class BWFAN_API_Get_Automation_Step_Contacts extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/automation/(?P<automation_id>[\\d]+)/step/(?P<step_id>[\\d]+)/contacts';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation ID to retrieve', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
			'step_id'       => array(
				'description' => __( 'Step ID data to retrieve', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id' );
		$step_id       = $this->get_sanitized_arg( 'step_id' );
		$type          = $this->get_sanitized_arg( 'type' );
		$path          = $this->get_sanitized_arg( 'path' );
		$offset        = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? absint( $this->get_sanitized_arg( 'offset', 'text_field' ) ) : 0;
		$limit         = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 25;

		/** If step id is 0 , event data to be returned */

		if ( empty( $automation_id ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations' ), null, 400 );
		}

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}
		$step = BWFAN_Model_Automation_Step::get( $step_id );
		$path = isset( $step['type'] ) && 7 === intval( $step['type'] ) ? $path : '';

		$data = BWFAN_Common::get_completed_contacts( $automation_id, $step_id, $type, $offset, $limit, $path );

		if ( ! isset( $data['contacts'] ) || ! is_array( $data['contacts'] ) ) {
			return $this->error_response( [], __( 'No data found', 'wp-marketing-automations' ) );
		}

		$contacts = array_map( function ( $contact ) {
			$trail = isset( $contact['trail'] ) ? $contact['trail'] : '';

			$time                   = isset( $contact['c_date'] ) ? strtotime( $contact['c_date'] ) : '';
			$prepared_data          = [];
			$prepared_data['id']    = $contact['cid'];
			$prepared_data['name']  = ! empty( $contact['f_name'] ) || ! empty( $contact['l_name'] ) ? $contact['f_name'] . ' ' . $contact['l_name'] : '';
			$prepared_data['phone'] = isset( $contact['contact_no'] ) ? $contact['contact_no'] : '';
			$prepared_data['email'] = isset( $contact['email'] ) ? $contact['email'] : '';
			$prepared_data['trail'] = isset( $contact['tid'] ) ? $contact['tid'] : $trail;
			$prepared_data['time']  = isset( $contact['c_time'] ) ? $contact['c_time'] : $time;
			/** Set path if path is available */
			if ( isset( $contact['data'] ) && ! empty( $contact['data'] ) ) {
				$trail_data = json_decode( $contact['data'], true );
				if ( isset( $trail_data['path'] ) ) {
					$prepared_data['path'] = $trail_data['path'];
				}
			}

			return $prepared_data;
		}, $data['contacts'] );

		$total_contacts = isset( $data['total'] ) ? intval( $data['total'] ) : 0;
		$path_total     = 0;
//		/** Get split step's contact count */
//		if ( intval( $step_id ) > 0 ) {
//			$path_total     = $total_contacts;
//			$total_contacts = BWFAN_Model_Automation_Contact_Trail::get_step_count( $step_id );
//		}


		$automation = [
			'total'      => $total_contacts,
			'path_total' => $path_total,
			'data'       => $contacts
		];

		$this->response_code = 200;

		return $this->success_response( $automation, ! empty( $automation_data['message'] ) ? $automation_data['message'] : __( 'Automation contact found', 'wp-marketing-automations' ) );
	}

}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Automation_Step_Contacts' );