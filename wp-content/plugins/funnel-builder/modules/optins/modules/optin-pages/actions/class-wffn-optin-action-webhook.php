<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

/**
 * This class will Create send email after optin form submit
 * Class WFFN_Optin_Action_Webhook
 */
if ( ! class_exists( 'WFFN_Optin_Action_Webhook' ) ) {
	#[AllowDynamicProperties]
	class WFFN_Optin_Action_Webhook extends WFFN_Optin_Action {

		private static $slug = 'op_webhook_url';
		private static $ins = null;
		public $priority = 45;

		/**
		 * WFFN_Optin_Action_Webhook constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * @return WFFN_Optin_Action_Webhook|null
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

			if ( ! is_array( $posted_data ) || count( $posted_data ) === 0 ) {
				return $posted_data;
			}

			$post_fields = $posted_data;


			if ( isset( $optin_action_settings['op_webhook_enable'] ) && wffn_string_to_bool( $optin_action_settings['op_webhook_enable'] ) === true && ! empty( $optin_action_settings['op_webhook_url'] ) ) {

				$op_webhook_url = urldecode( $optin_action_settings['op_webhook_url'] );
				$post_fields    = $this->send_utm_tracking_data( $post_fields );

				if ( isset( $posted_data['optin_page_id'] ) && absint( $posted_data['optin_page_id'] ) > 0 ) {
					$optin_data = get_post( absint( $posted_data['optin_page_id'] ) );
					if ( $optin_data instanceof WP_Post ) {
						$post_fields['page']      = $optin_data->post_title;
						$post_fields['page_link'] = get_permalink( $optin_data->ID );
					}
				}

				$post_fields    = apply_filters( 'wffn_optin_posted_data_before_send_webhook', $post_fields, $fields_settings, $optin_action_settings );

				$optin_webhook_request = wp_remote_post( $op_webhook_url, array( 'body' => apply_filters( 'wffn_optin_filter_webhook_fields', $post_fields ) ) );

				if ( is_wp_error( $optin_webhook_request ) ) {
					WFFN_Core()->logger->log( "Webhook Failure: " . $optin_webhook_request->get_error_message() );

					return $posted_data; // Return false on error
				}
			}

			return $posted_data;

		}

		/**
		 * Attach utm tracking data
		 *
		 * @param $post_fields
		 *
		 * @return array
		 */
		public function send_utm_tracking_data( $post_fields ) {
			try {
				if ( ! class_exists( 'BWF_Ecomm_Tracking_Common' ) ) {
					return $post_fields;
				}

				$utm_data = BWF_Ecomm_Tracking_Common::get_instance()->get_common_tracking_data();

				if ( empty( $utm_data ) || ! is_array( $utm_data ) ) {
					return $post_fields;
				}

				// Remove unnecessary keys directly
				unset( $utm_data['journey'], $utm_data['source_id'] );
				$utm_data['timestamp'] = current_time( 'mysql' );

				return array_merge( $post_fields, $utm_data );
			} catch ( Exception $e ) {

			}

			return $post_fields;
		}

	}

	if ( class_exists( 'WFOPP_Core' ) ) {
		WFOPP_Core()->optin_actions->register( WFFN_Optin_Action_Webhook::get_instance() );
	}
}
