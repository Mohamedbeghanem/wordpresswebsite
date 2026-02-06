<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

/**
 * Funnel Public facing functionality
 * Class WFFN_Public
 */
if ( ! class_exists( 'WFFN_Public' ) ) {
	class WFFN_Public {

		private static $ins = null;
		public $environment = null;
		public $funnel_setup_result = null;

		/**
		 * WFFN_Public constructor..
		 * @since  1.0.0
		 */
		public function __construct() {
			/**
			 * Maybe try and setup the funnel
			 */
			add_action( 'template_redirect', array( $this, 'maybe_initialize_setup' ), 999 );

			/**
			 * Request actors for teh setup funnel ajax request
			 */
			add_action( 'wp_ajax_wffn_maybe_setup_funnel', array( $this, 'setup_funnel_ajax' ) );
			add_action( 'wp_ajax_nopriv_wffn_maybe_setup_funnel', array( $this, 'setup_funnel_ajax' ) );

			/**
			 * handle analytics requests
			 */
			add_action( 'wp_ajax_wffn_frontend_analytics', array( $this, 'frontend_analytics' ) );
			add_action( 'wp_ajax_nopriv_wffn_frontend_analytics', array( $this, 'frontend_analytics' ) );


			add_action( 'wp_ajax_wffn_tracking_events', array( $this, 'tracking_events' ) );
			add_action( 'wp_ajax_nopriv_wffn_tracking_events', array( $this, 'tracking_events' ) );

			add_action( 'wp', [ $this, 'maybe_register_assets_on_load' ], 10 );
			add_action( 'wffn_mark_pending_conversions', [ $this, 'wffn_record_unique_funnel_session' ], 5, 3 );
			add_action( 'wffn_mark_pending_conversions', [ $this, 'mark_pending_conversions' ], 10, 2 );
			add_action( 'wffn_mark_step_viewed', [ $this, 'mark_funnel_step_viewed' ], 10, 2 );
			add_action( 'woocommerce_thankyou', array( $this, 'maybe_log_thankyou_visited' ), 999, 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_setup_tracking_script' ), 11 );
			add_action( 'woocommerce_add_to_cart', [ $this, 'maybe_track_add_to_cart' ], 10, 4 );
			add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'send_pending_events' ], 99 );
			add_filter( 'fkcart_fragments', [ $this, 'send_pending_events' ], 10 );
			add_filter( 'wc_add_to_cart_message_html', [ $this, 'send_pending_events_on_cart' ], 100, 1 );
			add_action( 'woocommerce_ajax_added_to_cart', [ $this, 'clear_pending_events_data_from_session' ], 10 );
			add_action( 'woocommerce_thankyou', array( $this, 'maybe_destroyed_funnel_session' ), 999, 1 );
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
			add_action( 'wp_footer', [ $this, 'send_pending_events_on_footer' ], 9999 );

		}

		/**
		 * @return WFFN_Public|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self;
			}

			return self::$ins;
		}


		public function print_custom_js_in_footer() {
			$environment = $this->environment;
			$step_id     = isset( $environment['id'] ) ? $environment['id'] : 0;

			if ( $step_id > 0 ) {
				$custom_script = get_post_meta( $step_id, 'wffn_step_custom_settings', true );
				$custom_js     = isset( $custom_script['custom_js'] ) ? $custom_script['custom_js'] : '';

				if ( ! empty( $custom_js ) ) {
					echo html_entity_decode( $custom_js ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}

		/**
		 * @hooked over 'template_redirect'
		 * Try to initialize the funnel based on the current environment
		 */
		public function maybe_initialize_setup() {
			$is_preview_mode = WFFN_Common::is_page_builder_preview();
			if ( $is_preview_mode ) {
				return;
			}

			global $post;

			if ( is_null( $post ) ) {
				return;
			}

			$id                = $post->ID;
			$post_type         = $post->post_type;
			$this->environment = apply_filters( 'wffn_funnel_environment', array(
				'id'         => $id,
				'post_type'  => $post_type,
				'setup_time' => strtotime( gmdate( 'c' ) ),
			) );


			/**
			 * Pass environment to controller function to get the funnel setup result
			 */
			$this->funnel_setup_result = $this->maybe_setup_funnel( $this->environment );
			/**
			 * Do nothing if funnel setup fails,which means its not our step which is request right now and we do not have to move further
			 */
			if ( empty( $this->funnel_setup_result ) || false === $this->funnel_setup_result['success'] ) {
				return;
			}

			/**
			 * Go ahead and enqueue the scripts
			 */
			$this->funnel_setup_result['setup_time'] = strtotime( gmdate( 'c' ) );
			$this->funnel_setup_result['is_preview'] = $is_preview_mode;
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_add_script' ) );
		}

		/**
		 * Maybe setup funnel based upon the environment
		 * It checks for the correct environment and finds the running funnel based on that
		 *
		 * @param bool|array $environment environment to set funnel against
		 *
		 * @return array
		 */
		public function maybe_setup_funnel( $environment = false ) {
			/**
			 * Loop over all the supported steps to check if step supports open links and claiming the environment, only then we can initiate the funnel
			 */
			try {

				$get_all_steps = WFFN_Core()->steps->get_supported_steps();
				foreach ( $get_all_steps as $step ) {

					/**
					 * Skip all the other steps which cannot initiate funnel, like upsell and thank you
					 */
					if ( ! $step->supports( 'open_link' ) || false === $step->claim_environment( $environment ) ) {
						continue;
					}

					/**
					 * Ask step to find the funnel based on environment
					 */
					$funnel = $step->get_funnel_to_run( $environment );
					/**
					 * bail if no funnel found
					 */
					if ( ! wffn_is_valid_funnel( $funnel ) ) {
						return ( array( 'success' => false ) );
					}


					do_action( 'wffn_before_setup_funnel', $funnel );
					/**
					 * Setup funnel information for future use
					 */

					if ( isset( $environment['id'] ) && $environment['id'] !== '' ) {
						$environment['id'] = absint( $environment['id'] );
					}

					WFFN_Core()->data->set( 'funnel', $funnel );
					WFFN_Core()->data->set( 'current_step', [
						'id'   => $environment['id'],
						'type' => $step->slug,
					] );


					WFFN_Core()->data->save();

					do_action( 'wffn_after_setup_funnel', $funnel );

					/**
					 * Return the block of info
					 */
					return ( array(
						'success'       => true,
						'current_step'  => [
							'id'   => $environment['id'],
							'type' => $step->slug,
						],
						'hash'          => WFFN_Core()->data->get_transient_key(),
						'next_link'     => WFFN_Core()->data->get_next_url( $environment['id'] ),
						'support_track' => $step->supports( 'track_views' ),
						'fid'           => $funnel->get_id(),
					) );
				}
			}catch ( Exception|Error $e ) {
				WFFN_Core()->logger->log( __FUNCTION__ . ' error during setup funnel : ' . $e->getMessage(), 'wffn', true );
			}

			return ( array( 'success' => false ) );

		}

		public function maybe_add_script() {
			$live_or_dev = 'live';
			$suffix      = '.min';

			if ( defined( 'WFFN_IS_DEV' ) && true === WFFN_IS_DEV ) {
				$live_or_dev = 'dev';
				$suffix      = '';
			}

			/**
			 * register cookie script for funnel steps for handle blocking script plugins issues
			 */ global $post;

			// Use new WooCommerce handle for WC >= 10.3.0, fallback to legacy handle for older versions
			$cookie_handle = ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '10.3.0', '>=' ) ) ? 'wc-js-cookie' : 'js-cookie';

			if ( ! is_null( $post ) && $post instanceof WP_Post ) {
				if ( in_array( $post->post_type, array( 'wffn_landing', 'wffn_optin', 'wffn_oty', 'wffn_ty' ), true ) ) {

					wp_deregister_script( $cookie_handle );
					wp_register_script( $cookie_handle, plugin_dir_url( WFFN_PLUGIN_FILE ) . 'assets/' . $live_or_dev . '/js/js.cookie.min.js', array( 'jquery' ), WFFN_VERSION, array(
						'is_footer' => false,
						'strategy'  => 'defer'
					) );
				}
			}

			wp_enqueue_script( $cookie_handle );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wffn-public', plugin_dir_url( WFFN_PLUGIN_FILE ) . 'assets/' . $live_or_dev . '/js/public' . $suffix . '.js', [
				$cookie_handle,
				'jquery'
			], WFFN_VERSION, array( 'is_footer' => true, 'strategy' => 'defer' ) );

			wp_localize_script( 'wffn-public', 'wffnfunnelData', $this->funnel_setup_result );
			wp_localize_script( 'wffn-public', 'wffnfunnelEnvironment', $this->environment );

			wp_localize_script( 'wffn-public', 'wffnfunnelVars', apply_filters( 'wffn_localized_data', array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'restUrl'      => rest_url() . 'wffn/front',
				'is_ajax_mode' => true,
			) ) );

		}


		public function setup_funnel_ajax() {
			$get_data = isset( $_POST['data'] ) ? wffn_clean( wp_unslash( $_POST['data'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,FunnelBuilder.CodeAnalysis.FunnelBuilderSpecific.MissingCapabilityCheck -- Public AJAX endpoint for frontend funnel setup
			$result   = ( array( 'success' => false ) );
			if ( ! empty( $get_data ) ) {
				try {

					$get_data = json_decode( stripslashes( $get_data ), true );
					if ( is_array( $get_data ) ) {
						$result = $this->maybe_record_data_with_funnel_setup( array( 'data' => $get_data ) );
					}
				} catch ( Exception|Error $e ) {
					WFFN_Core()->logger->log( "Error in send data : " . __FUNCTION__ . $e->getMessage(), 'wffn', true );

				}
			}
			wp_send_json( $result );
		}

		/**
		 * Handle Views to be marked during ajax call
		 * This function allows individual step classes to take care of their specific step viewed
		 */
		public function frontend_analytics() {
			$get_data = isset( $_POST['data'] ) ? wffn_clean( wp_unslash( $_POST['data'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,FunnelBuilder.CodeAnalysis.FunnelBuilderSpecific.MissingCapabilityCheck -- Public AJAX endpoint for frontend analytics
			$result   = $this->send_frontend_analytics( $get_data );
			if ( is_array( $result ) ) {
				$result['funnel_setup'] = false;
			}
			wp_send_json( $result );
		}

		public function send_frontend_analytics( $args = array() ) {
			$current_step = WFFN_Core()->data->get_current_step();
			$response     = [
				'track_views' => false,
				'ecom_event'  => false
			];

			/**
			 * Check if we have valid session to proceed
			 */

			if ( WFFN_Core()->data->has_valid_session() && ! empty( $current_step ) ) {


				/**
				 * Start Marking Impressions
				 */
				$get_data = isset( $args ) ? wffn_clean( $args ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! empty( $get_data ) ) {
					$get_data = json_decode( wp_kses_stripslashes( $get_data ), true );
					$get_data = $this->maybe_setup_step_in_cache( $get_data, $current_step );
					/**
					 * maybe change current step after cache environment check
					 */
					$current_step = WFFN_Core()->data->get_current_step();
				}

				$get_step_object = WFFN_Core()->steps->get_integration_object( $current_step['type'] );

				if ( ! empty( $get_data ) ) {
					if ( is_array( $get_data ) && ! empty( $get_data['events'] ) ) {
						$get_step_object->maybe_ecomm_events( $get_data );
						$response['ecom_event'] = true;
					}
				}
				$funnel = WFFN_Core()->data->get_session_funnel();
				do_action( 'wffn_mark_pending_conversions', $current_step, $get_step_object, $funnel );

				/**
				 *  only track views for the steps that supports
				 */
				if ( $get_step_object->supports( 'track_views' ) ) {
					do_action( 'wffn_mark_step_viewed', $current_step, $get_step_object );
					/**
					 * Now that we have recorded the analytics, we can check if we can mark the funnel ended and clean up the session data
					 */
					$this->maybe_end_funnel_and_clear_data();
					$response['track_views'] = true;
				}
			}

			return $response;
		}

		/**
		 * Handle Views to be marked during ajax call
		 * This function allows individual step classes to take care of their specific step viewed
		 */
		public function tracking_events() {
			$get_data = isset( $_POST ) ? wffn_clean( wp_unslash( $_POST ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,FunnelBuilder.CodeAnalysis.FunnelBuilderSpecific.MissingCapabilityCheck -- Public AJAX endpoint for tracking events
			$this->send_tracking_events( $get_data );
		}

		public function send_tracking_events( $args = array() ) {
			/**
			 * handel case for sitewide events come from api
			 */
			if ( isset( $args['data'] ) && isset( $args['data']['is_sitewide'] ) ) {
				$args = $args['data'];
			}
			$is_sitewide = isset( $args['is_sitewide'] ) ? true : false;
			if ( true === $is_sitewide ) {
				$get_data = isset( $args['events'] ) ? wffn_clean( $args['events'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! empty( $get_data ) ) {
					WFFN_Tracking_SiteWide::get_instance()->maybe_ecomm_events( $get_data );
				}
				return;
			}

			$post_data = isset( $args['data'] ) ? wffn_clean( $args['data'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$post_data = ! is_array($post_data) ? json_decode( wp_kses_stripslashes( $post_data ), true ) : $post_data;

			$current_step = WFFN_Core()->data->get_current_step();
			if ( empty( $current_step ) && isset( $post_data['step_data'] ) && isset( $post_data['step_data']['post_type'] ) ) {
				$current_step = array(
					'type' => WFFN_Common::get_step_type( $post_data['step_data']['post_type'] )
				);
			}

			/**
			 * Check if we have valid session to proceed
			 */

			if ( ! empty( $current_step ) ) {

				/**
				 * Start Running ecomm events
				 */
				$get_step_object = WFFN_Core()->steps->get_integration_object( $current_step['type'] );
				$get_data        = isset( $post_data['events_data'] ) ? $post_data['events_data'] : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! empty( $get_data ) && ! empty( $get_step_object ) ) {
					if ( is_array( $get_data ) && count( $get_data ) > 0 ) {
						$get_step_object->maybe_ecomm_events( $get_data );
					}
				}

			}
		}

		/**
		 * Maybe terminate funnel and clear the session data
		 * Checks if the step is the last step we have for the funnel, if yes then terminate
		 */
		public function maybe_end_funnel_and_clear_data() {
			$current_step = WFFN_Core()->data->get_current_step();

			$funnel     = WFFN_Core()->data->get_session_funnel();
			$found_step = 0;
			foreach ( $funnel->steps as $step ) {
				$get_object = WFFN_Core()->steps->get_integration_object( $step['type'] );

				/**
				 * continue till we found the current step
				 */
				if ( absint( $current_step['id'] ) === absint( $step['id'] ) && true === $get_object->supports( 'close_funnel' ) ) {
					$found_step = $current_step['id'];
					continue;
				}

				/**
				 * Continue if we have not found the current step yet
				 */
				if ( 0 === $found_step ) {
					continue;
				}

				if ( empty( $get_object ) ) {
					continue;
				}
				$properties = $get_object->populate_data_properties( $step, $funnel->get_id() );

				if ( false === $get_object->is_disabled( $get_object->get_enitity_data( $properties['_data'], 'status' ) ) ) {
					$found_step = $step['id'];
					break;
				}
			}

			if ( absint( $found_step ) === absint( $current_step['id'] ) ) {
				WFFN_Core()->logger->log( "Funnel id: #{$funnel->get_id()} Closing Funnel" );
				do_action( 'wffn_funnel_ended_event', $current_step, $funnel );
				WFFN_Core()->data->destroy_session();
			}
		}

		public function maybe_register_assets_on_load() {
			$should_register = apply_filters( 'wffn_should_register_assets', true );

			if ( true === $should_register ) {
				$this->maybe_register_assets( [], '', true );
			}
		}

		public function maybe_register_assets( $handles = [], $environment = '', $force_environment = false ) {
			$this->maybe_register_styles( $handles, $environment, $force_environment );
			$this->maybe_register_scripts( $handles, $environment, $force_environment );
		}

		public function maybe_register_styles( $handles = [], $environment = '', $force_environment = false ) {

			$styles = $this->get_styles();

			foreach ( $styles as $handle => $style ) {

				if ( ! empty( $handles ) && false === in_array( $handle, $handles, true ) ) {
					continue;
				}

				if ( false === $force_environment && ! empty( $environment ) && false === in_array( $environment, $style['supports'], true ) ) {
					continue;
				}

				wp_register_style( $handle, $style['path'], [], $style['version'] );
			}
		}

		public function maybe_register_scripts( $handles = [], $environment = '', $force_environment = false ) {
			$scripts = $this->get_scripts();

			foreach ( $scripts as $handle => $script ) {
				if ( ! empty( $handles ) && false === in_array( $handle, $handles, true ) ) {
					continue;
				}

				if ( false === $force_environment && ! empty( $environment ) && false === in_array( $environment, $script['supports'], true ) ) {
					continue;
				}
				wp_register_script( $handle, $script['path'], [], $script['version'], $script['in_footer'] );
			}
		}

		public function get_styles() {
			$live_or_dev = 'live';
			$suffix      = '.min';

			if ( defined( 'WFFN_IS_DEV' ) && true === WFFN_IS_DEV ) {
				$live_or_dev = 'dev';
				$suffix      = '';
			}

			return apply_filters( 'wffn_assets_styles', array(
				'wffn-frontend-style' => array(
					'path'      => WFFN_Core()->get_plugin_url() . '/assets/' . $live_or_dev . '/css/wffn-frontend' . $suffix . '.css',
					'version'   => WFFN_VERSION_DEV,
					'in_footer' => false,
					'supports'  => array(),
				),
				'wffn-template-style' => array(
					'path'      => WFFN_Core()->get_plugin_url() . '/assets/' . $live_or_dev . '/css/wffn-template' . $suffix . '.css',
					'version'   => WFFN_VERSION_DEV,
					'in_footer' => false,
					'supports'  => array(),
				)
			) );
		}

		public function get_scripts() {
			return apply_filters( 'wffn_assets_scripts', array(
				'jquery' => array(
					'path'      => includes_url() . 'js/jquery/jquery.js',
					'version'   => null,
					'in_footer' => false,
					'supports'  => array(
						'customizer',
						'customizer-preview',
						'offer',
						'offer-page',
						'offer-single',
					),
				),
			) );
		}


		public function wffn_record_unique_funnel_session( $current_step, $get_step_object, $funnel ) {

			$funnel_id          = $funnel->get_id();
			$recorded_funnel_id = WFFN_Core()->data->get( 'recorded_funnel_id_' . $funnel_id );

			if ( ( absint( $funnel_id ) ) !== absint( $recorded_funnel_id ) ) {

				$this->increase_funnel_visit_session_view( $funnel_id );
				WFFN_Core()->data->set( 'recorded_funnel_id_' . $funnel_id, $funnel_id )->save();
				WFFN_Core()->logger->log( __FUNCTION__ . ':: ' . $funnel_id );
			}
		}


		public function increase_funnel_visit_session_view( $funnel_id ) {
			WFCO_Model_Report_views::update_data( gmdate( 'Y-m-d', current_time( 'timestamp' ) ), $funnel_id, 7 );
		}

		public function mark_pending_conversions( $current_step, $get_step_object ) {
			/**
			 * Mark Pending Conversions
			 */
			$get_step_to_convert = WFFN_Core()->data->get( 'to_convert' );

			if ( empty( $get_step_to_convert ) ) {
				return;
			}

			if ( absint( $get_step_to_convert['id'] ) === absint( $current_step['id'] ) ) {
				return;
			}

			$get_step_object = WFFN_Core()->steps->get_integration_object( $get_step_to_convert['type'] );

			/**
			 *  only track conversion for the steps that supports
			 */
			if ( $get_step_object instanceof WFFN_Step && $get_step_object->supports( 'track_conversions' ) ) {
				$get_step_object->mark_step_converted( $get_step_to_convert );
				WFFN_Core()->data->set( 'to_convert', '0' )->save();
			}
		}

		public function mark_funnel_step_viewed( $current_step, $get_step_object ) {
			/**
			 * return if we found that this very step is already visited
			 */
			if ( empty( $current_step ) ) {
				return;
			}

			/**
			 * Check if we already tracked view of this step in the current session
			 */
			$get_all_visit_data = WFFN_Core()->data->get( 'step_analytics' );
			if ( false === $get_all_visit_data ) {
				$get_all_visit_data = [];
			}

			/**
			 * return if we found that this step is already visited
			 */
			if ( isset( $get_all_visit_data[ $current_step['id'] ] ) && isset( $get_all_visit_data[ $current_step['id'] ]['visit'] ) && '1' === $get_all_visit_data[ $current_step['id'] ]['visit'] ) {
				return;
			}

			if ( ! is_array( $get_all_visit_data ) ) {
				$get_all_visit_data = [];
			}
			/**
			 * GO ahead & track view
			 */
			if ( ! isset( $get_all_visit_data[ $current_step['id'] ] ) ) {
				$get_all_visit_data[ $current_step['id'] ] = [];
			}

			$get_step_object = WFFN_Core()->steps->get_integration_object( $current_step['type'] );
			/**
			 * Tell step to mark step viewed
			 */
			$get_step_object->mark_step_viewed();

			/**
			 * sets up flag that this step is visited
			 */
			$get_all_visit_data[ $current_step['id'] ]['visit'] = '1';
			WFFN_Core()->data->set( 'step_analytics', $get_all_visit_data )->save();

			/**
			 * if the current step supports tracking conversions then set the flag that this step needs to be converted later in the funnel
			 */
			if ( $get_step_object->supports( 'track_conversions' ) ) {
				WFFN_Core()->data->set( 'to_convert', $current_step )->save();
			}
		}

		public function maybe_log_thankyou_visited( $order_id ) {

			global $post;

			if ( ! is_null( $post ) ) {
				WFFN_Core()->logger->log( 'Order #' . $order_id . ': Thankyou page #' . $post->ID . ' viewed successfully', 'wffn', true );
				if ( isset( $_GET['wfty_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					WFFN_Core()->logger->log( 'Order #' . $order_id . ': wfty thankyou page id #' . $_GET['wfty_source'], 'wffn', true ); // phpcs:ignore

					if ( isset( $_COOKIE[ 'wfty_native_' . $order_id ] ) && 'yes' === $_COOKIE[ 'wfty_native_' . $order_id ] ) {
						return;
					}

					/**
					 * increase store checkout funnel thankyou page views when native checkout set
					 */
					if ( 0 === WFFN_Common::get_store_checkout_id() ) {
						return;
					}

					$funnel = new WFFN_Funnel( WFFN_Common::get_store_checkout_id() );

					/**
					 * Check if this is a valid funnel and has native checkout
					 */
					if ( ! wffn_is_valid_funnel( $funnel ) || false === $funnel->is_funnel_has_native_checkout() ) {
						return;
					}
					/**
					 * Record thankyou page views for native store checkout
					 */
					$order = wc_get_order( $order_id );
					if ( $order instanceof WC_Order ) {
						if ( empty( $order->get_meta( '_wfacp_post_id' ) ) ) {
							WFCO_Model_Report_views::update_data( gmdate( 'Y-m-d', current_time( 'timestamp' ) ), $post->ID, 5 );
							WFFN_Core()->data->set_cookie( 'wfty_native_' . $order_id, 'yes', time() + ( DAY_IN_SECONDS * 1 ) );
							WFFN_Core()->logger->log( 'Order #' . $order_id . ': record view thankyou page #' . $_GET['wfty_source'], 'wffn', true ); // phpcs:ignore

						}
					}

				}
			}
		}


		public function maybe_setup_tracking_script() {
			$is_preview_mode = WFFN_Common::is_page_builder_preview();
			if ( $is_preview_mode ) {
				return;
			}
			WFFN_Tracking_SiteWide::get_instance()->tracking_script();
		}

		public function maybe_track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id ) {
			WFFN_Tracking_SiteWide::get_instance()->add_to_cart_process( $cart_item_key, $product_id, $quantity, $variation_id );

			$events = WFFN_Tracking_SiteWide::get_instance()->get_pending_events();
			if ( ! is_null( $events ) && is_array( $events ) && count( $events ) > 0 ) {
				if ( function_exists( 'WC' ) && ! is_null( WC()->session ) && WC()->session->has_session() ) {
					$final_events = [];
					if ( ! empty( WC()->session->get( 'wffn_pending_data' ) ) ) {
						$final_events = array_merge( WC()->session->get( 'wffn_pending_data' ), array( $events ) );
					} else {
						$final_events[] = $events;
					}
					WC()->session->set( 'wffn_pending_data', $final_events );
				}
			}
		}

		public function send_pending_events( $fragments ) {

			$events                    = WFFN_Tracking_SiteWide::get_instance()->get_pending_events();
			$fragments['wffnTracking'] = [ 'pending_events' => $events ];

			/**
			 * Session events not clear on fkcart fragment and refreshed fragments
			 * Some theme not run wc ajax but run refreshed
			 */
			if ( function_exists( 'did_filter' ) && ( ( 0 === did_filter( 'fkcart_fragments' ) ) && ( 0 === did_action( 'wc_ajax_get_refreshed_fragments' ) ) ) ) {
				$this->clear_pending_events_data_from_session();
			}
			return $fragments;
		}

		/* fire events on cart page if product 'Redirect to the cart page after successful addition' setting enabled
		 * @param $message
		 *
		 * @return mixed|string
		 */
		public static function send_pending_events_on_cart( $message ) {
			if ( 'yes' !== get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				return $message;
			}

			$events = WFFN_Tracking_SiteWide::get_instance()->get_pending_events();

			if ( ! is_null( $events ) && is_array( $events ) && count( $events ) > 0 ) {
				$message .= "<div id='wffn_late_event' dir='" . json_encode( $events ) . "'></div>"; //phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				WFFN_Core()->public->clear_pending_events_data_from_session();
			}

			return $message;
		}

		/**
		 * handle pending events data and fire data which theme not run wc ajax
		 * pending event data fire on next reload or next page
		 * @return void
		 */
		public static function send_pending_events_on_footer() {
			if ( function_exists( 'WC' ) && ! is_null( WC()->session ) && WC()->session->has_session() ) {
				$events = WC()->session->get( 'wffn_pending_data' );
				if ( ! is_null( $events ) && is_array( $events ) && count( $events ) > 0 ) {
					echo "<div id='wffn_late_event' style='display:none' dir='" . json_encode( $events ) . "'></div>"; //phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
					WFFN_Core()->public->clear_pending_events_data_from_session();
				}
			}

		}

		public function clear_pending_events_data_from_session() {
			if ( function_exists( 'WC' ) && ! is_null( WC()->session ) && WC()->session->has_session() ) {

				if ( function_exists( 'is_checkout' ) && is_checkout() ) {
					return;
				}
				$events = WC()->session->get( 'wffn_pending_data' );
				if ( ! is_null( $events ) && is_array( $events ) && count( $events ) > 0 ) {
					WC()->session->set( 'wffn_pending_data', '' );
				}
			}
		}


		/**
		 * @param $args
		 * @param $current_step
		 *
		 * handle case when optin_ty and wc_thankyou page open in cache environment
		 * in this sometimes both step not set in funnel session current step due to cache
		 * so we manually set here
		 *
		 * @return mixed
		 */
		public function maybe_setup_step_in_cache( $args, $current_step ) {

			if ( ! is_array( $args ) || count( $args ) === 0 ) {
				return $args;
			}


			foreach ( $args as $key => &$data ) {
				if ( isset( $data['current_step'] ) && is_array( $data['current_step'] ) ) {

					/**
					 * If data is incomplete to process, then unset and break loop
					 */
					if ( ! isset( $data['current_step']['post_type'] ) || empty( $data['current_step']['post_type'] ) ) {
						unset( $args[ $key ] );
						break;
					}

					/*
					 * Check if we have correct post types to process
					 */
					if ( is_array( $current_step ) && ! in_array( $current_step['type'], [ 'optin_ty', 'wc_thankyou' ], true ) && in_array( $data['current_step']['post_type'], [
							'wffn_oty',
							'wffn_ty'
						], true ) ) {
						WFFN_Core()->data->set( 'current_step', [
							'id'   => $data['current_step']['id'],
							'type' => ( $data['current_step']['post_type'] === 'wffn_oty' ) ? 'optin_ty' : 'wc_thankyou',
						] );
						WFFN_Core()->data->save();
					}

					/**
					 * Making sure its unset from the array of tracking events
					 */
					unset( $args[ $key ] );
				}

			}

			return $args;
		}

		/*
	* Destroyed funnel session in case order created by funnel checkout and
	* funnel not have thankyou step and user land on native thankyou page
	* @param $order_id
	*
	* @return void
	*/
		public function maybe_destroyed_funnel_session( $order_id ) {
			if ( isset( $_GET['wfty_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}


			$order = wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				if ( ! empty( $order->get_meta( '_wfacp_post_id' ) ) ) {

					$funnel_id = get_post_meta( absint( $order->get_meta( '_wfacp_post_id' ) ), '_bwf_in_funnel', true );
					if ( empty( $funnel_id ) ) {
						return;
					}
					$funnel = new WFFN_Funnel( $funnel_id );
					if ( ! $funnel instanceof WFFN_Funnel ) {
						return;
					}
					WFFN_Core()->logger->log( "Funnel id: #{ $funnel->id} Closing Funnel on native thankyou page Order #{$order_id}", 'wffn', true );
					do_action( 'wffn_ty_funnel_ended_event', $funnel, $order_id );
					WFFN_Core()->data->destroy_session();
				}
			}
		}

		public function register_routes() {
			register_rest_route( 'wffn', '/' . 'front/', array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'handle_api_request' ),
				'permission_callback' => '__return_true',
			) );
		}

		public function handle_api_request( WP_REST_Request $request ) {
			$params = $request->get_params();

			$resp   = [
				'status'       => true,
				'funnel_setup' => false,
				'track_views'  => false,
				'ecom_event'   => false,
			];

			try {
				if ( empty( $params['action'] ) ) {
					return rest_ensure_response( $resp );
				}
				$action = sanitize_text_field( $params['action'] );
				switch ( $action ) {
					case 'wffn_maybe_setup_funnel':
						$resp = $this->maybe_record_data_with_funnel_setup( $params );
						break;

					case 'wffn_frontend_analytics':
						$analytics_result    = WFFN_Core()->public->send_frontend_analytics( wp_json_encode( $params['data'] ) );
						$resp['track_views'] = $analytics_result['track_views'] ?? false;
						$resp['ecom_event']  = $analytics_result['ecom_event'] ?? false;
						break;

					case 'wffn_tracking_events':
						WFFN_Core()->public->send_tracking_events( $params );
						$resp['ecom_event'] = true;
						break;
				}
			} catch ( Exception|Error $e ) {
				WFFN_Core()->logger->log( "Error in send data : " . __FUNCTION__ . $e->getMessage(), 'wffn', true );

			}

			return rest_ensure_response( $resp );
		}

		/**
		 * @param $args
		 * @param $track_data
		 *
		 * @return false[]
		 */
		public function maybe_record_frontend_analytics( $args, $track_data ) {
			$response = [
				'track_views' => false,
				'ecom_event'  => false
			];

			if ( empty( $args ) || ! is_array( $args ) || empty ( $track_data ) ) {
				return $response;
			}

			if ( isset( $args['is_preview'] ) && true === wffn_string_to_bool( $args['is_preview'] ) ) {
				return $response;
			}

			if ( ! isset( $args['hash'] ) || empty( $args['current_step']['id'] ) ) {
				return $response;
			}

			return WFFN_Core()->public->send_frontend_analytics( wp_json_encode( $track_data, true ) );

		}

		/**
		 * Try to setup funnel and record analytics and fire ecom events in single call
		 *
		 * @param $data
		 *
		 * @return void
		 */
		public function maybe_record_data_with_funnel_setup( $args ) {
			$resp   = [
				'status'       => true,
				'funnel_setup' => false,
				'track_views'  => false,
				'ecom_event'   => false,
			];
			$result = WFFN_Core()->public->maybe_setup_funnel( $args['data'] );
			if ( is_array( $result ) && ! empty( $result['success'] ) ) {
				if ( ! empty( $args['data']['hash'] ) && ! empty( $result['hash'] ) && $args['data']['hash'] === $result['hash'] ) {
					$resp['funnel_setup'] = false;
				} else {
					$resp['funnel_setup'] = true;
				}
				$resp = array_merge( $result, $resp );
			}


			if ( is_array( $resp ) && ! empty( $args['data']['track_data'] ) ) {
				$tracking_result = $this->maybe_record_frontend_analytics( $resp, $args['data']['track_data'] );
				if ( ! empty( $tracking_result ) ) {
					$resp['track_views'] = $tracking_result['track_views'] ?? false;
					$resp['ecom_event']  = $tracking_result['ecom_event'] ?? false;
				}
			}

			return $resp;

		}

	}

	if ( class_exists( 'WFFN_Core' ) ) {
		WFFN_Core::register( 'public', 'WFFN_Public' );
	}
}
