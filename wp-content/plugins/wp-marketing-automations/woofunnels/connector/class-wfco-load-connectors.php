<?php
if ( ! class_exists( 'WFCO_Load_Connectors' ) ) {
	#[AllowDynamicProperties]
	class WFCO_Load_Connectors {
		/** @var class instance */
		private static $ins = null;

		/** @var array All connectors with object */
		private static $connectors = array();

		/** @var array All calls with object */
		private static $registered_calls = array();

		/** @var array All calls objects group by connectors */
		private static $registered_connectors_calls = array();

		public function __construct() {
			add_action( 'rest_api_init', [ $this, 'load_connectors_rest_call' ], 8 );
			add_action( 'current_screen', [ $this, 'load_connectors_admin' ], 8 );
			add_action( 'admin_init', [ $this, 'load_connectors_admin_ajax' ], 8 );
		}

		/**
		 * Return class instance
		 *
		 * @return class|WFCO_Load_Connectors
		 */
		public static function get_instance() {
			if ( null == self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * Include all connectors files directly
		 *
		 * @return void
		 */
		public static function load_connectors_direct() {
			do_action( 'wfco_load_connectors' );
		}

		/**
		 * Include all connectors files on rest endpoints
		 *
		 * @return void
		 */
		public static function load_connectors_rest_call() {
			$rest_route = isset( $_GET['rest_route'] ) ? $_GET['rest_route'] : '';
			if ( empty( $rest_route ) ) {
				$rest_route = $_SERVER['REQUEST_URI'];
			}
			if ( empty( $rest_route ) ) {
				return;
			}
			if ( strpos( $rest_route, 'autonami/' ) === false && strpos( $rest_route, 'woofunnel_customer/' ) === false && strpos( $rest_route, 'funnelkit-app' ) === false && strpos( $rest_route, 'autonami-app' ) === false && strpos( $rest_route, 'funnelkit-automations' ) === false && strpos( $rest_route, 'autonami-webhook' ) === false && strpos( $rest_route, 'woofunnels/' ) === false && strpos( $rest_route, '/omapp/' ) === false ) {
				return;
			}

			do_action( 'wfco_load_connectors' );
		}

		/**
		 * Include all connectors files on admin screen
		 *
		 * @return void
		 */
		public static function load_connectors_admin() {
			$screen = get_current_screen();
			if ( ! is_object( $screen ) ) {
				return;
			}
			if ( empty( $screen->id ) || ( 'toplevel_page_autonami' !== $screen->id && 'funnelkit-automations_page_autonami-automations' !== $screen->id ) ) {
				return;
			}
			do_action( 'wfco_load_connectors' );
		}

		/**
		 * Include all connectors files on admin ajax call
		 *
		 * @return void
		 */
		public static function load_connectors_admin_ajax() {
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				return;
			}
			$ajax_action = $_POST['action'] ?? '';
			if ( empty( $ajax_action ) ) {
				return;
			}
			$array = [ 'wfco', 'bwfan', 'bwf_' ];
			foreach ( $array as $value ) {
				if ( strpos( $ajax_action, $value ) !== false ) {
					do_action( 'wfco_load_connectors' );
					break;
				}
			}
		}

		/**
		 * Register a connector with their object
		 * Assign to static property $connectors
		 * Load connector respective calls
		 *
		 * @param $class
		 */
		public static function register( $class ) {
			if ( ! class_exists( $class ) && ! method_exists( $class, 'get_instance' ) ) {
				return;
			}

			$temp_ins = $class::get_instance();
			if ( ! $temp_ins instanceof BWF_CO ) {
				return;
			}

			$slug = $temp_ins->get_slug();

			self::$connectors[ $slug ] = $temp_ins;
			$temp_ins->load_calls();
		}

		/**
		 * Register a call with their object
		 * Assign to static property $registered_calls
		 * Assign to static property $registered_connectors_calls
		 *
		 * @param WFCO_Call $call_obj
		 */
		public static function register_calls( WFCO_Call $call_obj ) {
			if ( method_exists( $call_obj, 'get_instance' ) ) {
				$slug           = $call_obj->get_slug();
				$connector_slug = $call_obj->get_connector_slug();

				self::$registered_connectors_calls[ $connector_slug ][ $slug ] = self::$registered_calls[ $slug ] = $call_obj;
			}
		}

		/**
		 * Return all the connectors with their calls objects
		 *
		 * @return array
		 */
		public static function get_all_connectors() {
			return self::$registered_connectors_calls;
		}


		/**
		 * Returns Instance of single connector
		 *
		 * @param $connector
		 *
		 * @return BWF_CO
		 */
		public static function get_connector( $connector ) {
			return isset( self::$connectors[ $connector ] ) ? self::$connectors[ $connector ] : null;
		}


		/**
		 * Returns all the active connectors i.e. plugin active
		 *
		 * @return array
		 */
		public static function get_active_connectors() {
			return self::$connectors;
		}

		/**
		 * Return a call object if call slug is passed.
		 * Return all calls object if no single call slug passed.
		 *
		 * @param string $slug
		 *
		 * @return array|mixed
		 */
		public function get_calls( $slug = '' ) {
			if ( empty( $slug ) ) {
				return self::$registered_calls;
			}
			if ( isset( self::$registered_calls[ $slug ] ) ) {
				return self::$registered_calls[ $slug ];
			}
		}

		/**
		 * Return a call object based on the given slug.
		 *
		 * @param string $slug call slug
		 *
		 * @return WFCO_Call | null
		 */
		public function get_call( $slug ) {
			return ( ! empty( $slug ) && isset( self::$registered_calls[ $slug ] ) ) ? self::$registered_calls[ $slug ] : null;
		}
	}

	/**
	 * Initiate the class as soon as it is included
	 */
	WFCO_Load_Connectors::get_instance();
}