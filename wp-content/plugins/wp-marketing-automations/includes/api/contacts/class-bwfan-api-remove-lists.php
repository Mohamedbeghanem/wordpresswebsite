<?php

class BWFAN_API_Remove_Lists extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::DELETABLE;
		$this->route  = '/v3/contacts/(?P<contact_id>[\\d]+)/lists';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'lists'      => '',
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		$lists      = $this->get_sanitized_arg( '', 'key', $this->args['lists'] );
		/** No Lists Provided */
		if ( empty( $lists ) ) {
			$response            = __( 'No Lists provided', 'wp-marketing-automations' );
			$this->response_code = 404;

			return $this->error_response( $response );
		}

		/** No Contact found */
		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		/** Check if provided lists ids are valid numbers */
		$lists = array_map( 'absint', $lists );
		$lists = array_filter( $lists );
		if ( empty( absint( $lists ) ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'No list found', 'wp-marketing-automations' ) );
		}

		$removed_lists = $contact->remove_lists( $lists );
		$contact->save();
		$lists_not_removed = array_diff( $lists, $removed_lists );

		if ( count( $lists_not_removed ) === count( $lists ) ) {
			$response            = __( 'Unable to remove the list', 'wp-marketing-automations' );
			$this->response_code = 500;

			return $this->error_response( $response );
		}
		$result   = [];
		$response = __( 'Lists Unassigned', 'wp-marketing-automations' );
		if ( ! empty( $lists_not_removed ) ) {
			$removed_lists_text = implode( ', ', $removed_lists );
			/* translators: 1: comma seperated list  */
			$response = sprintf( __( 'Some lists removed: %1$s', 'wp-marketing-automations' ), $removed_lists_text );
		}
		$result['last_modified'] = $contact->contact->get_last_modified();

		return $this->success_response( $result, $response );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Remove_Lists' );
