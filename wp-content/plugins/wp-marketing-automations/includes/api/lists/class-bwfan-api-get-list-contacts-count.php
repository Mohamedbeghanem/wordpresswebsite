<?php

class BWFAN_API_Get_List_Contacts_Count extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/v3/lists/contacts';
	}

	public function default_args_values() {
		$args = [
			'list_ids' => []
		];

		return $args;
	}

	public function process_api_call() {

		$list_ids = $this->get_sanitized_arg( '', 'text_field', $this->args['list_ids'] );
		$data     = [];
		if ( empty( $list_ids ) ) {
			return $this->success_response( $data );
		}

		foreach ( $list_ids as $list_id ) {
			if ( ! isset( $data[ $list_id ] ) ) {
				$data[ $list_id ] = [];
			}

			$data[ $list_id ]['contact_count']     = $this->get_contact_count( $list_id );
			$data[ $list_id ]['subscribers_count'] = $this->get_contact_count( $list_id, true );
		}

		$this->response_code = 200;

		return $this->success_response( $data );
	}

	/**
	 * @param $list_id
	 * @param $exclude_unsubs
	 *
	 * @return int
	 */
	public static function get_contact_count( $list_id, $exclude_unsubs = false ) {
		global $wpdb;
		$list_id = '%"' . $list_id . '"%';
		$query   = "SELECT COUNT(DISTINCT c.id) FROM {$wpdb->prefix}bwf_contact as c   WHERE 1=1 AND ( c.email != '' AND c.email IS NOT NULL ) AND ( c.lists LIKE '$list_id'  )";

		if ( true === $exclude_unsubs ) {
			$query .= " AND ( c.status = 1 )    AND (   NOT EXISTS   (SELECT 1 FROM {$wpdb->prefix}bwfan_message_unsubscribe AS unsub WHERE c.email = unsub.recipient ) AND   NOT EXISTS  (SELECT 1 FROM {$wpdb->prefix}bwfan_message_unsubscribe AS unsub1 WHERE c.contact_no = unsub1.recipient ))";
		}

		return intval( $wpdb->get_var( $query ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_List_Contacts_Count' );
