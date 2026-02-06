<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

/**
 * This class will Create send email after optin form submit
 * Class WFFN_Optin_Action_Admin_Email
 */
if ( ! class_exists( 'WFFN_Optin_Action_Admin_Email' ) ) {
	#[AllowDynamicProperties]
	class WFFN_Optin_Action_Admin_Email extends WFFN_Optin_Action {

		private static $slug = 'admin_email_notification';
		private static $ins = null;
		public $priority = 50;

		/**
		 * WFFN_Optin_Action_Admin_Email constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * @return WFFN_Optin_Action_Admin_Email|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self;
			}

			return self::$ins;
		}

		public static function get_slug() {
			return self::$slug;
		}

		/**
		 * @param $posted_data
		 * @param $fields_settings
		 * @param $optin_action_settings
		 *
		 * @return array|bool|mixed
		 */
		public function handle_action( $posted_data, $fields_settings, $optin_action_settings ) {
			$db_options = WFOPP_Core()->optin_pages->get_option();

			if ( ! is_array( $posted_data ) || count( $posted_data ) === 0 ) {
				return $posted_data;
			}

			$subject     = __( 'You\'ve got a new optin', 'funnel-builder' );
			$email_data  = '';

			// REPLACE POSTED data with a new variable

			/** Optin id found */
			if ( isset( $posted_data['optin_page_id'] ) && absint( $posted_data['optin_page_id'] ) > 0 ) {
				/** @var WP_Post $optin_data */
				$optin_data = get_post( absint( $posted_data['optin_page_id'] ) );
				if ( $optin_data instanceof WP_Post ) {
					$posted_data['page']      = $optin_data->post_title;
					$posted_data['page_link'] = get_permalink( $optin_data->ID );
					$subject                  .= ' - ' . $optin_data->post_title;
				}
				$fields_data = $this->before_submit_form( $posted_data['optin_page_id'], $posted_data );

				foreach ( $fields_data as $label => $value ) {
					if ( in_array( $label, [ 'opid', 'cid' ], true ) ) {
						continue;
					}
					if ( in_array( $label, [ 'page', 'page_link', 'optin_page_id', 'optin_entry_id', 'wfop_optin_country' ], true ) ) {
						if ( strpos( $label, 'optin_' ) === 0 ) {
							$label = substr( $label, 6 );
						}
						$label = ucwords( str_replace( "_", " ", str_replace( "-", " ", $label ) ) );
					}
					$email_data .= "<tr><td style='width:30%;'><strong>" . $label . "</strong> </td><td style='width:70%;'>" . $value . "</td></tr>";
				}

				unset( $posted_data['optin_page_id'] );
			}

			$is_notify = isset( $optin_action_settings['admin_email_notify'] ) && ( $optin_action_settings['admin_email_notify'] === 'true' );
			$admin_emails = ( isset( $optin_action_settings['op_admin_email'] ) && ( $optin_action_settings['op_admin_email'] !== '' ) ) ? explode( ',', trim( $optin_action_settings['op_admin_email'] ) ) : [];

			$admin_emails = array_unique( array_map( 'trim', $admin_emails ) );

			$email_data = WFFN_Common::wffn_correct_protocol_url( $email_data );

			if ( ( true === $is_notify ) && count( $admin_emails ) > 0 ) {

				foreach ( $admin_emails as $email ) {

					$to      = trim( $email );
					$headers = "From: " . $db_options['op_user_name'] . '<' . $db_options['op_user_email'] . '>' . "\r\n";
					$headers .= $email . "\r\n";
					$headers .= "Reply-To: " . $db_options['op_user_email_reply'] . "\r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

					$message          = '<table>';
					$message          .= $email_data;
					$message          .= "</table>";
					$modified_content = WFFN_Common::modify_content_emogrifier( $message );
					if ( ! empty( $modified_content ) ) {
						$message = $modified_content;
					}
					$result = wp_mail( $to, $subject, $message, $headers ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail

					if ( $result ) {
						WFFN_Core()->logger->log( "Admin optin form notification email send" );
					} else {
						WFFN_Core()->logger->log( "Admin optin form notification email failed" );
					}
				}

			}

			return $posted_data;
		}

		/**
		 * @param $optin_id
		 * @param $posted_data
		 *
		 * save custom field with label instead with advanced name
		 *
		 * @return mixed
		 */
		public function before_submit_form( $optin_id, $posted_data ) {
			$get_fields   = WFOPP_Core()->optin_pages->form_builder->get_form_fields( $optin_id );
			$basic_fields = array( 'optin_first_name', 'optin_last_name', 'optin_phone', 'optin_email' );

			foreach ( $get_fields as $field ) {
				$field_key = in_array( $field['type'], $basic_fields, true ) ? $field['type'] : $field['InputName'];

				if ( isset( $posted_data[ $field_key ] ) ) {
					$posted_data[ $field['label'] ] = $posted_data[ $field_key ];
					unset( $posted_data[ $field_key ] );
				}
			}

			return $posted_data;
		}

	}

	if ( class_exists( 'WFOPP_Core' ) ) {
		WFOPP_Core()->optin_actions->register( WFFN_Optin_Action_Admin_Email::get_instance() );
	}
}
