<?php

class BWFAN_API_Remove_Tags extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::DELETABLE;
		$this->route        = '/v3/contacts/(?P<contact_id>[\\d]+)/tags';
		$this->request_args = array(
			'tags' => array(
				'description' => __( 'Tags to remove', 'wp-marketing-automations' ),
				'type'        => 'array',
			),
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'tags'       => '',
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		$tags       = $this->get_sanitized_arg( '', 'key', $this->args['tags'] );

		/** No Tags Provided */
		if ( empty( $tags ) ) {
			$response            = __( 'No Tags provided', 'wp-marketing-automations' );
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

		/** If provided tags not array */
		if ( ! is_array( $tags ) ) {
			if ( is_string( $tags ) && false !== strpos( $tags, ',' ) ) {
				$tags = explode( ',', $tags );
			} else if ( empty( absint( $tags ) ) ) {
				$this->response_code = 400;

				return $this->error_response( __( 'No tag found', 'wp-marketing-automations' ) );
			} else {
				$tags = array( absint( $tags ) );
			}
		}

		/** Check if provided tag ids are valid numbers */
		$tags = array_map( 'absint', $tags );
		$tags = array_filter( $tags );
		if ( empty( absint( $tags ) ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Invalid Tags Provided', 'wp-marketing-automations' ) );
		}

		$removed_tags = $contact->remove_tags( $tags );
		$contact->save();
		$tags_not_removed = array_diff( $tags, $removed_tags );
		if ( count( $tags_not_removed ) === count( $tags ) ) {
			$response            = __( 'Unable to remove any tag', 'wp-marketing-automations' );
			$this->response_code = 500;

			return $this->error_response( $response );
		}
		$result   = [];
		$response = __( 'Tag removed', 'wp-marketing-automations' );
		if ( ! empty( $tags_not_removed ) ) {
			$removed_tags_text = implode( ', ', $removed_tags );
			/* translators: 1: comma seperated tags  */
			$response = sprintf( __( 'Some tags removed: %1$s', 'wp-marketing-automations' ), $removed_tags_text );
		}
		$result['last_modified'] = $contact->contact->get_last_modified();

		return $this->success_response( $result, $response );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Remove_Tags' );
