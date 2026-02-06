<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

if ( ! class_exists( 'WFFN_Data' ) ) {
	class WFFN_Data extends WFFN_Session_Handler {

		private static $ins = null;
		protected $cache = array();
		private $page_id = false;
		private $page_link = false;
		private $order_id = false;
		private $order = false;
		private $page_layout = false;
		private $page_layout_info = false;
		private $options = null;

		public function __construct() {
			add_action( 'wffn_global_settings', array( $this, 'sanitize_scripts' ), 10 );

			/**
			 * As we have extended the class 'WFOCU_Session_Handler', We have to create a construct over there and not using native register method.
			 */
			parent::__construct();

		}

		/**
		 * @return WFFN_Session_Handler|self|null
		 */
		public static function get_instance() {
			if ( self::$ins === null ) {
				self::$ins = new self;
			}

			return self::$ins;
		}

		/**
		 * @return array|mixed|object|string
		 */
		public function get_current_step() {
			return $this->get( 'current_step', false );
		}


		public function sanitize_scripts( $options ) {

			if ( $options && ( isset( $options['scripts'] ) && '' !== $options['scripts'] ) ) {
				$options['scripts'] = stripslashes_deep( $options['scripts'] );
			}

			if ( $options && ( isset( $options['scripts_head'] ) && '' !== $options['scripts_head'] ) ) {
				$options['scripts_head'] = stripslashes_deep( $options['scripts_head'] );
			}

			return $options;
		}

		/**
		 * Find the next url to open in the funnel
		 *
		 * @param $current_step_id array Id to take into account to search for the next link
		 *
		 * @return false|string
		 */
		public function get_next_url( $current_step_id ) {

			$get_funnel   = $this->get_session_funnel();
			$current_step = $this->get_current_step();
			if ( ! is_array( $current_step ) || 0 === count( $current_step ) ) {
				return false;
			}

			$current_step['type'] = isset( $current_step['type'] ) ? $current_step['type'] : '';
			$step_object          = WFFN_Core()->steps->get_integration_object( $current_step['type'] );

			/**
			 * return if current step not support next link
			 */
			if ( empty( $step_object ) || ! $step_object->supports( 'next_link' ) ) {
				return false;
			}

			$get_next_step = $this->get_next_step( $get_funnel, $current_step_id );
			if ( false === $get_next_step ) {
				return false;
			}

			$get_next_id    = isset( $get_next_step['id'] ) ? $get_next_step['id'] : 0;
			$filtered_value = apply_filters( 'wffn_funnel_next_link', $current_step_id );

			if ( $filtered_value !== $current_step_id ) {
				return $filtered_value;
			}

			return ( $get_next_id === 0 ) ? false : get_permalink( $get_next_id );

		}


		/**
		 * @return WFFN_Funnel|null
		 */
		public function get_session_funnel() {

			return $this->get( 'funnel', false );

		}

		/**
		 * Loop over the current funnel running and compare the steps against the current one
		 * Find out if the next step available & return
		 *
		 * @param $funnel WFFN_Funnel
		 * @param $current_step
		 *
		 * @return array|false
		 */
		public function get_next_step( $funnel, $current_step ) {
			$current_step = apply_filters( 'wffn_maybe_get_ab_control', $current_step );
			$next_step    = $funnel->get_next_step_id( absint( $current_step ) );
			if ( is_array( $next_step ) ) {
				return $next_step;
			}

			return false;
		}

	}

	if ( class_exists( 'WFFN_Core' ) ) {
		WFFN_Core::register( 'data', 'WFFN_Data' );
	}
}
