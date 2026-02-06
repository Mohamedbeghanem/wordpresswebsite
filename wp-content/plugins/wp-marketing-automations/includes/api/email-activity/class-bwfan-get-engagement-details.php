<?php

class BWFAN_API_Get_Engagement_Details extends BWFAN_API_Base {

	public static $ins;
	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/engagement/(?P<e_id>[\\d]+)';
	}

	public function process_api_call() {
		$e_id       = $this->get_sanitized_arg( 'e_id' );
		$engagement = BWFAN_Core()->conversations->get_conversation_email( $e_id );
		if ( empty( $engagement ) || ! is_array( $engagement ) ) {
			$message = $engagement['error'] ?? 'Unknown error occurred';

			return $this->error_response( $message, null, 500 );
		}

		return $this->success_response( $engagement );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Engagement_Details' );