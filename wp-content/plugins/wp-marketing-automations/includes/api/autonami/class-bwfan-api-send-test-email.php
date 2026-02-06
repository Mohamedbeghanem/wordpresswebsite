<?php
/**
 * Test Mail API file
 *
 * @package BWFAN_API_Base
 */

/**
 * Test Mail API class
 */
class BWFAN_API_Send_Test_Mail extends BWFAN_API_Base {

	/**
	 * BWFAN_API_Base obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

	/**
	 * Return class instance
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/autonami/send-test-email';
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'email'   => '',
			'content' => 0
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$content = isset( $this->args['content'] ) ? $this->args['content'] : [];
		if ( empty( $content ) ) {
			return $this->error_response( __( 'No mail data found', 'wp-marketing-automations' ) );
		}

		$content = BWFAN_Common::is_json( $content ) ? json_decode( $content, true ) : $content;
		if ( isset( $content['mail_data'] ) && is_array( $content['mail_data'] ) ) {
			$mail_data = $content['mail_data'];
			unset( $content['mail_data'] );
			$content = array_replace( $content, $mail_data );
		}
		$content['email'] = $this->get_sanitized_arg( 'email', 'text_field' );
		if ( BWFAN_Common::send_test_email( $content ) ) {
			return $this->success_response( '', __( 'Test email sent', 'wp-marketing-automations' ) );
		}

		return $this->error_response( __( 'Unable to send test email', 'wp-marketing-automations' ), null, 500 );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Send_Test_Mail' );
