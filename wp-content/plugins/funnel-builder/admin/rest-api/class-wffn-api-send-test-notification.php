<?php
/**
 * Test Notification API file
 *
 * @package BWFCRM_API_Base
 */
if ( ! class_exists( 'WFFN_API_Send_Test_Notification' ) ) {
	/**
	 * Test Notification API class
	 */
	class WFFN_API_Send_Test_Notification extends WFFN_REST_Controller {

		public static $ins;

		/**
		 * Global settings.
		 *
		 * @var array
		 */
		protected $global_settings = array();
		/**
		 * Route base.
		 *
		 * @var string
		 */
		protected $args = array();
		protected $namespace = 'funnelkit-app';

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
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		public function register_routes() {
			register_rest_route( $this->namespace, '/' . 'test-email-notification/', array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_api_call' ),
				'permission_callback' => array( $this, 'get_write_api_permission_check' ),
			) );

			register_rest_route( $this->namespace, '/' . 'wp-users', array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_wp_users' ),
				'permission_callback' => array( $this, 'get_write_api_permission_check' ),
			) );
		}

		public function get_write_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'funnel', 'write' );
		}

		public function get_wp_users() {
			$search = isset( $this->args['search'] ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
			$limit  = isset( $this->args['limit'] ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 10;
			$data   = $this->get_users( $search, $limit );


			return rest_ensure_response( [
				'status' => true,
				'data'   => $data
			] );
		}

		public function get_users( $search, $limit = 10 ) {
			$user_data = get_users( array(
				'number'  => $limit,
				'orderby' => 'name',
				'order'   => 'asc',
				'search'  => '*' . esc_attr( $search ) . '*',
				'fields'  => [
					'display_name',
					'user_nicename',
					'user_email',
					'ID'
				]
			) );

			if ( empty( $user_data ) ) {
				return [];
			}
			$data = [];
			foreach ( $user_data as $user ) {
				$data[] = [
					'display_name' => $user->user_nicename,
					'name'         => $user->display_name,
					'email'        => $user->user_email,
					'id'           => $user->id
				];
			}

			return $data;
		}

		/**
		 * API callback
		 */
		public function process_api_call( WP_REST_Request $request, $is_rest = true ) {
			$this->args = $request->get_params();
			$email      = $this->get_sanitized_arg( 'email', 'text_field' );
			if ( empty( $email ) ) {
				return $this->response( false, 'Invalid or missing email address', $is_rest );
			}

			$this->global_settings = WFFN_Email_Notification::load_settings();
			$frequencies           = $this->get_frequencies();
			if ( empty( $frequencies ) ) {
				/** If frequency is set will try that else default */
				$frequencies = [ 'weekly' ];
			}
			// If monthly is not set then add it.
			if ( ! in_array( 'monthly', $frequencies, true ) ) {
				$frequencies[] = 'monthly';
			}

			$frequencies = WFFN_Email_Notification::prepare_frequencies( $frequencies );
			$sent        = array();
			$errors      = new WP_Error();

			foreach ( $frequencies as $frequency => $dates ) {
				/** Prepare metrics */
				$metrics_controller = new WFFN_Notification_Metrics_Controller( $dates, $frequency );
				$metrics_controller->prepare_data();

				$data             = $metrics_controller->get_data();
				$email_controller = new WFFN_Notification_Email_Controller( $frequency, $data, $dates );

				$to      = $email;
				$subject = WFFN_Email_Notification::get_email_subject( $frequency, $dates );
				$body    = $email_controller->get_content_html();
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				if ( wp_mail( $to, $subject, $body, $headers ) ) { // @codingStandardsIgnoreLine
					$sent[ $frequency ] = true;
					break;
				} else {
					$errors->add( $frequency, sprintf( __( 'Unable to send test notification for frequency: %s', 'Funnelkit' ), $frequency ) );
				}
			}

			if ( empty( $sent ) && $errors->has_errors() ) {
				return $this->response( false, implode( ", ", $errors->get_error_messages() ), $is_rest );
			}

			return $this->response( true, 'Notifications Sent', $is_rest );
		}

		/**
		 * Get the frequencies for email notifications.
		 *
		 * @return array
		 */
		protected function get_frequencies() {
			if ( isset( $this->global_settings['bwf_notification_frequency'] ) && is_array( $this->global_settings['bwf_notification_frequency'] ) ) {
				return $this->global_settings['bwf_notification_frequency'];
			}

			return array();
		}

		public function get_sanitized_arg( $key = '', $is_a = 'key', $collection = '' ) {
			$sanitize_method = ( 'bool' === $is_a ) ? 'rest_sanitize_boolean' : 'sanitize_' . $is_a;

			$collection = is_array( $collection ) ? $collection : $this->args;

			if ( ! empty( $key ) && isset( $collection[ $key ] ) && ! empty( $collection[ $key ] ) ) {
				return call_user_func( $sanitize_method, $collection[ $key ] );
			}

			if ( ! empty( $key ) ) {
				return false;
			}

			return array_map( $sanitize_method, $collection );
		}

		protected function response( $success, $message, $is_rest ) {
			$response = array( 'success' => $success, 'message' => $message );

			return $is_rest ? rest_ensure_response( $response ) : $response;
		}
	}

	WFFN_API_Send_Test_Notification::get_instance();
}
