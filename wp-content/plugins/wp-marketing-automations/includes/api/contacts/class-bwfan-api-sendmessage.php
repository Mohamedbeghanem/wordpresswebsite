<?php

class BWFAN_API_SendMessage extends BWFAN_API_Base {
	public static $ins;
	private $conversation = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = 'v3/contacts/(?P<contact_id>[\\d]+)/sendmessage';
		$this->request_args = array(
			'contact_id' => array(
				'description' => __( 'Contact ID to send message', 'wp-marketing-automations' ),
				'type'        => 'integer',
			)
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
			'title'      => '',
			'message'    => '',
			'type'       => 'email'
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$title      = $this->get_sanitized_arg( 'title', 'text_field' );
		$message    = $this->args['message'];
		$type       = $this->get_sanitized_arg( 'type', 'text_field' );

		if ( BWFAN_Common::is_pro_3_0() ) {
			$this->conversation = BWFAN_Core()->conversation;
		} else {
			$this->conversation = BWFCRM_Core()->conversation;
		}

		$contact = new BWFCRM_Contact( absint( $contact_id ) );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 400;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		/** Check if contacts status is bounced */
		if ( 4 === $contact->get_display_status() ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Contact status is bounced', 'wp-marketing-automations' ) );
		}

		if ( 'email' === $type && empty( $title ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Title is mandatory', 'wp-marketing-automations' ) );
		}

		if ( empty( $message ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Message is mandatory', 'wp-marketing-automations' ) );
		}

		$author_id = get_current_user_id();

		$mode                                = BWFAN_Email_Conversations::$MODE_EMAIL;
		$this->conversation->engagement_type = BWFAN_Email_Conversations::$TYPE_EMAIL;
		switch ( $type ) {
			case 'sms':
				$mode                                = BWFAN_Email_Conversations::$MODE_SMS;
				$this->conversation->engagement_type = BWFAN_Email_Conversations::$TYPE_SMS;
				break;
			case 'whatsapp':
				$mode = BWFAN_Email_Conversations::$MODE_WHATSAPP;
				break;
			case 'push-notification':
				$mode = BWFAN_Email_Conversations::$MODE_NOTIFICATION;
				break;
		}

		BWFAN_Merge_Tag_Loader::set_data( array(
			'contact_id'   => $contact->get_id(),
			'contact_mode' => $mode
		) );
		$message      = BWFAN_Common::decode_merge_tags( ( 1 === $mode ? wpautop( $message ) : $message ) );
		$template     = [
			'subject'  => ( empty( $title ) ? '' : $title ),
			'template' => ( empty( $message ) ? '' : $message )
		];
		$message_data = [];
		if ( BWFAN_Email_Conversations::$MODE_NOTIFICATION === $mode ) {
			$url = $this->get_sanitized_arg( 'url', 'text_field' );
			if ( ! empty( $url ) ) {
				$template['url']                  = $url;
				$message_data['notification_url'] = $url;
			}
			$image_url = $this->get_sanitized_arg( 'image_url', 'text_field' );
			if ( ! empty( $image_url ) ) {
				$template['image_url']              = $image_url;
				$message_data['notification_image'] = $image_url;
			}
		}


		$conversation = $this->conversation->create_campaign_conversation( $contact, 0, 0, $author_id, $mode, true, $template, BWFAN_Email_Conversations::$MODE_NOTIFICATION === $mode ? 8 : 0 );

		if ( empty( $conversation['conversation_id'] ) ) {
			return $this->error_response( $conversation );
		}

		if ( class_exists( 'BWFAN_Message' ) ) {
			$message_obj = new BWFAN_Message();
			$message_obj->set_message( 0, $conversation['conversation_id'], $title, $message );
			$message_obj->set_data( $message_data );
			$messageid = $message_obj->save();
			if ( $messageid && property_exists( BWFAN_Core()->conversation, 'template_id' ) ) {
				/** Save the message id in conversation */
				BWFAN_Core()->conversation->template_id = $messageid;
			}
		}

		$conversation['template'] = $message;
		$conversation['subject']  = $title;

		if ( 'sms' === $type ) {
			$sent = $this->send_single_sms( $conversation, $contact );
		} else if ( 'whatsapp' === $type ) {
			$sent = $this->send_single_whatsapp_message( $conversation, $contact );
		} else if ( 'push-notification' === $type ) {
			$sent = $this->send_single_push_notification( $conversation, $contact, $template );

		} else {
			$sent = $this->send_single_email( $title, $conversation, $contact );
		}

		if ( true === $sent ) {
			return $this->success_response( [], __( 'Message sent', 'wp-marketing-automations' ) );
		}

		return $this->error_response( $sent, null, 500 );
	}

	public function send_single_push_notification( $conversation, $contact, $template ) {
		if ( ! bwfan_is_autonami_pro_active() ) {
			return __( 'FunnelKit automations Pro is not active ', 'wp-marketing-automations' );
		}

		$contact_id           = $contact->contact->get_id();
		$notification_message = $this->conversation->prepare_notification_data( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], $conversation['template'] );

		/** Append tracking code in url */
		$notification_url = $this->conversation->prepare_notification_data( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], $template['url'] );

		$data = [
			'contact_id'           => $contact_id,
			'notification_message' => $notification_message,
			'notification_url'     => $notification_url,
			'notification_title'   => $template['subject'],
			'notification_image'   => isset( $template['image_url'] ) ? $template['image_url'] : '',
		];

		$res = $this->send_notification( $data );

		if ( $res instanceof WP_Error ) {
			$this->conversation->fail_the_conversation( $conversation['conversation_id'], $res->get_error_message() );

			return $res->get_error_message();
		}

		/** Save the date of last sent engagement **/
		$data = array( 'cid' => $contact_id );
		$this->conversation::save_last_sent_engagement( $data );

		$this->conversation->update_conversation_status( $conversation['conversation_id'], BWFAN_Email_Conversations::$STATUS_SEND );

		return true;
	}

	/**
	 * @param $title
	 * @param $conversation
	 * @param $contact BWFCRM_Contact
	 *
	 * @return string|true|null
	 */
	public function send_single_email( $title, $conversation, $contact ) {
		$conversation_id = $conversation['conversation_id'];
		$contact_id      = $contact->contact->get_id();

		$email_subject = $this->prepare_email_subject( $title, $contact_id );
		try {
			$email_body = $this->conversation->prepare_email_body( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], 'rich', $conversation['template'] );
		} catch ( Error $e ) {
			$this->conversation->fail_the_conversation( $conversation_id, $e->getMessage() );

			return $e->getMessage();
		}
		if ( is_wp_error( $email_body ) ) {
			$this->conversation->fail_the_conversation( $conversation_id, $email_body->get_error_message() );

			return $email_body->get_error_message();
		}

		$to = $contact->contact->get_email();
		if ( ! is_email( $to ) ) {
			$message = __( 'No email found for this contact: #', 'wp-marketing-automations' ) . $contact_id;
			$this->conversation->fail_the_conversation( $conversation_id, $message );

			return $message;
		}

		$global_email_settings = BWFAN_Common::get_global_settings();

		$headers = array(
			'MIME-Version: 1.0',
			'Content-type: text/html;charset=UTF-8'
		);

		$from = '';
		if ( isset( $global_email_settings['bwfan_email_from_name'] ) && ! empty( $global_email_settings['bwfan_email_from_name'] ) ) {
			$from = 'From: ' . $global_email_settings['bwfan_email_from_name'];
		}

		if ( isset( $global_email_settings['bwfan_email_from'] ) && ! empty( $global_email_settings['bwfan_email_from'] ) ) {

			$headers[] = $from . ' <' . $global_email_settings['bwfan_email_from'] . '>';
		}

		if ( isset( $global_email_settings['bwfan_email_reply_to'] ) && ! empty( $global_email_settings['bwfan_email_reply_to'] ) ) {
			$headers[] = 'Reply-To:  ' . $global_email_settings['bwfan_email_reply_to'];
		}

		/** Set unsubscribe link in header */
		$unsubscribe_link = BWFAN_Common::get_unsubscribe_link( [ 'uid' => $contact->contact->get_uid() ] );
		if ( ! empty( $unsubscribe_link ) ) {
			$headers[] = "List-Unsubscribe: <$unsubscribe_link>";
			$headers[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
		}

		$headers = apply_filters( 'bwfan_email_headers', $headers );

		BWFAN_Common::bwf_remove_filter_before_wp_mail();
		$result = wp_mail( $to, $email_subject, $email_body, $headers );
		if ( true === $result ) {
			/** Save the time of last sent engagement **/
			$data = array( 'cid' => $contact_id );
			$this->conversation::save_last_sent_engagement( $data );

			$this->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );
		} else {
			$this->conversation->fail_the_conversation( $conversation_id, __( 'Email not sent', 'wp-marketing-automations' ) );

			return __( 'Email not sent', 'wp-marketing-automations' );
		}

		return $result;
	}

	public function send_single_sms( $conversation, $contact ) {
		if ( ! bwfan_is_autonami_pro_active() ) {
			return __( 'FunnelKit automations Pro is not active ', 'wp-marketing-automations' );
		}

		$conversation_id = $conversation['conversation_id'];
		$contact_id      = $contact->contact->get_id();
		$sms_body        = $this->conversation->prepare_sms_body( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], $conversation['template'] );

		if ( is_wp_error( $sms_body ) ) {
			$this->conversation->fail_the_conversation( $conversation_id, $sms_body->get_error_message() );

			return $sms_body->get_error_message();
		}

		$to = BWFAN_Common::get_contact_full_number( $contact->contact );

		if ( empty( $to ) ) {
			$message = __( 'No phone number found for this contact: #', 'wp-marketing-automations' ) . $contact_id;
			$this->conversation->fail_the_conversation( $conversation_id, $message );

			return $message;
		}

		$send_sms_result = BWFCRM_Common::send_sms( array(
			'to'   => $to,
			'body' => $sms_body,
		) );

		if ( $send_sms_result instanceof WP_Error ) {
			$this->conversation->fail_the_conversation( $conversation_id, $send_sms_result->get_error_message() );

			return $send_sms_result->get_error_message();
		}

		/** Save the date of last sent engagement **/
		$data = array( 'cid' => $contact_id );
		$this->conversation::save_last_sent_engagement( $data );

		$this->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );

		return true;
	}

	public function send_single_whatsapp_message( $conversation, $contact ) {
		if ( ! bwfan_is_autonami_pro_active() ) {
			return __( 'FunnelKit automations Pro is not active ', 'wp-marketing-automations' );
		}
		$conversation_id = $conversation['conversation_id'];
		$contact_id      = $contact->contact->get_id();
		$message_body    = $this->conversation->prepare_sms_body( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], $conversation['template'] );

		if ( is_wp_error( $message_body ) ) {
			$this->conversation->fail_the_conversation( $conversation_id, $message_body->get_error_message() );

			return $message_body->get_error_message();
		}

		$to = BWFAN_Common::get_contact_full_number( $contact->contact );

		if ( empty( $to ) ) {
			$message = __( 'No phone number found for this contact: #', 'wp-marketing-automations' ) . $contact_id;
			$this->conversation->fail_the_conversation( $conversation_id, $message );

			return $message;
		}

		$response = $this->conversation->send_whatsapp_message( $to, array(
			array(
				'type' => 'text',
				'data' => $message_body,
			)
		) );

		if ( ! empty( $response['status'] ) && $response['status'] == true ) {
			/** Save the time of last sent engagement **/
			$data = array( 'cid' => $contact_id );
			$this->conversation::save_last_sent_engagement( $data );
			$this->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );

			return true;
		}
		$error_message = isset( $response['msg'] ) && ! empty( $response['msg'] ) ? $response['msg'] : __( 'Unable to send message', 'wp-marketing-automations' );
		$this->conversation->fail_the_conversation( $conversation_id, $error_message );

		return $error_message;
	}

	public function prepare_email_subject( $subject, $contact_id ) {
		BWFAN_Merge_Tag_Loader::set_data( array(
			'contact_id'   => $contact_id,
			'contact_mode' => 1
		) );

		return BWFAN_Common::decode_merge_tags( $subject );
	}

	public function send_notification( $args ) {
		// fetching provider for test sms
		$provider = isset( $args['push_provider'] ) ? $args['push_provider'] : '';

		if ( empty( $provider ) ) {
			$global_settings = BWFAN_Common::get_global_settings();
			$provider        = isset( $global_settings['bwfan_push_service'] ) && ! empty( $global_settings['bwfan_push_service'] ) ? $global_settings['bwfan_push_service'] : BWFAN_Common::get_default_push_provider();
		}

		/** If no provider selected OR selected provider is not within active providers, then select default provider */
		if ( empty( $provider ) || ! isset( $active_services[ $provider ] ) ) {
			$provider = BWFAN_Common::get_default_push_provider();
		}

		$provider = explode( 'bwfco_', $provider );
		$provider = isset( $provider[1] ) ? $provider[1] : '';
		$provider = ! empty( $provider ) ? BWFAN_Core()->integration->get_integration( $provider ) : '';
		if ( ! $provider instanceof BWFAN_Integration || ! method_exists( $provider, 'send_message' ) ) {
			return new WP_Error( 'connector_not_found', __( 'Connector Integration not found', 'wp-marketing-automations' ) );
		}

		return $provider->send_message( $args );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_SendMessage' );
