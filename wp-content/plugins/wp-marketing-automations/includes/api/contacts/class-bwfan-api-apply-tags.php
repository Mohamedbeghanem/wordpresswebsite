<?php

class BWFAN_API_Apply_Tags extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/v3/contacts/(?P<contact_id>[\\d]+)/tags';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'tags'       => array(),
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		if ( empty( $contact_id ) ) {

			$this->response_code = 404;

			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found related with contact id : %1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$tags = $this->args['tags'];
		$tags = array_filter( array_values( $tags ) );
		if ( empty( $tags ) ) {
			$response            = __( 'No Tags provided', 'wp-marketing-automations' );
			$this->response_code = 400;

			return $this->error_response( $response );
		}

		$tags       = BWFAN_Common::check_for_comma_seperated( $tags );
		$added_tags = $contact->add_tags( $tags );
		if ( is_wp_error( $added_tags ) ) {
			$this->response_code = 500;

			return $this->error_response( '', $added_tags );
		}

		if ( empty( $added_tags ) ) {
			$this->response_code = 200;

			return $this->success_response( '', __( 'Provided tags are applied already.', 'wp-marketing-automations' ) );
		}
		$tags_added = array_map( function ( $tag ) {
			return $tag->get_array();
		}, $added_tags );
		$result     = [];
		$message    = __( 'Tag(s) added', 'wp-marketing-automations' );
		if ( count( $tags ) !== count( $added_tags ) ) {
			$applied_tags_names  = array_map( function ( $tag ) {
				return $tag->get_name();
			}, $added_tags );
			$applied_tags_names  = implode( ', ', $applied_tags_names );
			$this->response_code = 200;
			/* translators: 1: comma seperated tags  */
			$message = sprintf( __( 'Some tags are applied already. Applied Tags are: %1$s', 'wp-marketing-automations' ), $applied_tags_names );
		}
		$result['tags_added']    = is_array( $tags_added ) ? array_values( $tags_added ) : $tags_added;
		$result['last_modified'] = $contact->contact->get_last_modified();

		return $this->success_response( $result, $message );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Apply_Tags' );
