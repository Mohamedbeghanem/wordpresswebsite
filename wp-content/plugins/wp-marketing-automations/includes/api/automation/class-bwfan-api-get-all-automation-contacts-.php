<?php

class BWFAN_API_Get_All_Automation_Contacts extends BWFAN_API_Base {

	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/automations/contacts/';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/** Customer journey Api call */
	public function process_api_call() {
		$offset = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? intval( $this->get_sanitized_arg( 'offset', 'text_field' ) ) : 0;
		$limit  = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 25;
		$type   = ! empty( $this->get_sanitized_arg( 'type', 'text_field' ) ) ? $this->get_sanitized_arg( 'type', 'text_field' ) : 'active';
		$search = ! empty( $this->get_sanitized_arg( 'search', 'text_field' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$cid    = ! empty( $this->get_sanitized_arg( 'contact', 'text_field' ) ) ? intval( $this->get_sanitized_arg( 'contact', 'text_field' ) ) : '';
		$aid    = ! empty( $this->get_sanitized_arg( 'automation', 'text_field' ) ) ? intval( $this->get_sanitized_arg( 'automation', 'text_field' ) ) : '';

		$contacts = BWFAN_Common::get_automation_contacts( $aid, $cid, $search, $limit, $offset, $type );
		$message  = __( 'Successfully fetched Automations', 'wp-marketing-automations' );
		if ( ! isset( $contacts['contacts'] ) || empty( $contacts['contacts'] ) || ! is_array( $contacts['contacts'] ) ) {
			$message = __( 'No data found', 'wp-marketing-automations' );
		}

		$completed_count = BWFAN_Model_Automation_Complete_Contact::get_complete_count( $aid, $cid );

		/** active contacts = active + wait + retry */
		$active_count = BWFAN_Model_Automation_Contact::get_active_count( $aid, 'active', '', $cid );
		$countdata    = [
			'active'    => $active_count,
			'paused'    => BWFAN_Model_Automation_Contact::get_active_count( $aid, 3, '', $cid ),
			'failed'    => BWFAN_Model_Automation_Contact::get_active_count( $aid, 2, '', $cid ),
			'completed' => $completed_count,
			'delayed'   => BWFAN_Model_Automation_Contact::get_active_count( $aid, 'delayed', '', $cid, 'AND cc.e_time < ' . current_time( 'timestamp', 1 ) ),
			'inactive'  => BWFAN_Model_Automation_Contact::get_active_count( $aid, 'inactive', '', $cid ),
		];

		$all = 0;
		foreach ( $countdata as $data ) {
			$all += intval( $data );
		}

		// adding automation title and url
		$final_contacts = [];
		$total          = isset( $contacts['total'] ) ? $contacts['total'] : 0;
		foreach ( $contacts['contacts'] as $contact ) {
			$automation_obj = BWFAN_Automation_V2::get_instance( $contact['aid'] );
			/** Check for automation exists */
			if ( ! empty( $automation_obj->error ) ) {
				continue;
			}
			if ( ! empty( $contact['error_msg'] ) && is_array( $contact['error_msg'] ) ) {
				$contact['error_msg'] = $this->get_error_message( $contact['error_msg'] );
			}

			$title            = isset( $automation_obj->automation_data['title'] ) && ! empty( $automation_obj->automation_data['title'] ) ? $automation_obj->automation_data['title'] : '';
			$event_slug       = isset( $automation_obj->automation_data['event'] ) && ! empty( $automation_obj->automation_data['event'] ) ? $automation_obj->automation_data['event'] : '';
			$event_obj        = BWFAN_Core()->sources->get_event( $event_slug );
			$event            = ! empty( $event_obj ) ? $event_obj->get_name() : '';
			$final_contacts[] = array_merge( $contact, array(
				'title' => $title,
				'event' => $event
			) );

			BWFAN_Automation_V2::unset_instance(); //unsetting the current instance
		}

		$countdata['all'] = $all;
		$contacts_data    = [
			'total'      => $total,
			'data'       => $final_contacts,
			'count_data' => $countdata
		];

		return $this->success_response( $contacts_data, $message );
	}

	public function get_error_message( $res = '' ) {
		if ( is_array( $res ) ) {
			$res = $this->get_error_message( array_values( $res )[0] );
		}

		return $res;
	}

}

BWFAN_API_Loader::register( 'BWFAN_API_Get_All_Automation_Contacts' );