<?php
/**
 * Class WFFN_Ecom_Tracking_Common
 */
if ( ! class_exists( 'WFFN_Ecomm_Tracking_Common' ) ) {
	class WFFN_Ecomm_Tracking_Common {
		public $api_events = [];
		public $gtag_rendered = false;
		public $admin_general_settings = null;

		public function __construct() {
			add_action( 'wp_head', array( $this, 'render' ), 90 );
			add_action( 'wp_head', array( $this, 'fire_tracking' ), 91 );
			$this->admin_general_settings = BWF_Admin_General_Settings::get_instance();
		}

		public function should_render() {
			return apply_filters( 'wffn_allow_tracking', true, $this );
		}

		public function render() {

			if ( $this->should_render() ) {
				$this->render_pint();
				$this->render_fb();
				$this->render_ga();
				$this->render_gad();
				$this->render_snapchat();
				$this->render_tiktok();
				$this->maybe_render_conv_api();
			}
		}

		public function get_advanced_pixel_data( $type ) {
			if ( 'fb' === $type ) {
				return WFFN_Common::pixel_advanced_matching_data();
			}
			if ( 'tiktok' === $type ) {
				return WFFN_Common::tiktok_advanced_matching_data();
			}

			return array();

		}

		public function render_pint() {
			if ( false !== $this->is_pint_pixel() ) {
				$get_each_pixel_id = explode( ',', $this->is_pint_pixel() );
				?>
                <!-- Pinterest Pixel Base Code -->
                <script type="text/javascript">
                    function wffnPintTrackingIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>

                            if (1 === wffn_shouldRender) {
                                // Enhanced Pinterest tracking with error handling
                                (function (e) {
                                    try {
                                        if (!window.pintrk) {
                                            window.pintrk = function () {
                                                try {
                                                    if (window.pintrk.queue) {
                                                        window.pintrk.queue.push(Array.prototype.slice.call(arguments));
                                                    }
                                                } catch (error) {
                                                    console.log('Pinterest tracking error:', error);
                                                }
                                            };
                                            
                                            var n = window.pintrk;
                                            n.queue = [];
                                            n.version = "3.0";
                                            
                                            var t = document.createElement("script");
                                            t.async = true;
                                            t.src = e;
                                            t.onerror = function() {
                                                console.log('Failed to load Pinterest tracking script');
                                            };
                                            
                                            var r = document.getElementsByTagName("script")[0];
                                            if (r && r.parentNode) {
                                                r.parentNode.insertBefore(t, r);
                                            }
                                        }
                                    } catch (error) {
                                        console.log('Pinterest initialization error:', error);
                                    }
                                })("https://s.pinimg.com/ct/core.js");

								<?php foreach ( $get_each_pixel_id as $id ) { ?>
                                try {
                                    if (typeof pintrk === 'function') {
                                        pintrk('load', '<?php echo esc_attr( $id ) ?>');
										<?php if ( $this->should_render_view( 'pint' ) ) { ?>
                                        pintrk('page');
										<?php } ?>
                                    }
                                } catch (error) {
                                    console.log('Pinterest pixel tracking error for ID <?php echo esc_attr( $id ) ?>:', error);
                                }
								<?php } ?>
                            }
                        } catch (error) {
                            console.log('Pinterest tracking function error:', error);
                        }
                    }
                </script>
				<?php foreach ( $get_each_pixel_id as $id ) { ?>
                    <noscript>
                        <img height="1" width="1" style="display:none;" alt="" src="https://ct.pinterest.com/v3/?tid=<?php echo esc_attr( $id ); ?>&noscript=1"/>
                    </noscript>
				<?php } ?>


                <!-- End Pinterest Pixel Base Code -->
                <script type="text/javascript">
                    function wffnPintTrackingBaseIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                            if (1 === wffn_shouldRender) {
                                try {
									<?php $this->maybe_track_custom_steps_event_pint(); ?>
									<?php $this->maybe_print_pint_ecomm(); ?>
                                } catch (error) {
                                    console.log('Pinterest base tracking error:', error);
                                }
                            }
                        } catch (error) {
                            console.log('Pinterest base tracking function error:', error);
                        }
                    }
                </script>
				<?php
			}
		}

		public function render_fb() {
			if ( false !== $this->is_fb_pixel() ) {
				$fb_advanced_pixel_data = $this->get_advanced_pixel_data( 'fb' );
				?>
                <!-- Facebook Analytics Script Added By WooFunnels -->
                <script type="text/javascript">
					<?php $this->prepare_wffnevents(); ?>
                    function wffnFbTrackingIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                            if (1 === wffn_shouldRender) {
                                // Enhanced Facebook tracking with error handling
                                (function (f, b, e, v, n, t, s) {
                                    try {
                                        if (f.fbq) return;
                                        
                                        n = f.fbq = function () {
                                            try {
                                                if (n.callMethod) {
                                                    n.callMethod.apply(n, arguments);
                                                } else {
                                                    if (n.queue) {
                                                        n.queue.push(arguments);
                                                    }
                                                }
                                            } catch (error) {
                                                console.log('Facebook pixel tracking error:', error);
                                            }
                                        };
                                        
                                        if (!f._fbq) f._fbq = n;
                                        n.push = n;
                                        n.loaded = true;
                                        n.version = '2.0';
                                        n.queue = [];
                                        
                                        t = b.createElement(e);
                                        t.async = true;
                                        t.src = v;
                                        t.onerror = function() {
                                            console.log('Failed to load Facebook tracking script');
                                        };
                                        
                                        s = b.getElementsByTagName(e)[0];
                                        if (s && s.parentNode) {
                                            s.parentNode.insertBefore(t, s);
                                        }
                                    } catch (error) {
                                        console.log('Facebook initialization error:', error);
                                    }
                                })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
								
								<?php
								$get_all_fb_pixel = $this->is_fb_pixel();
								$get_each_pixel_id = explode( ',', $get_all_fb_pixel );
								if ( is_array( $get_each_pixel_id ) && count( $get_each_pixel_id ) > 0 ) {
								foreach ( $get_each_pixel_id as $pixel_id ) {
								?>
								try {
									<?php if ( true === $this->is_fb_advanced_tracking_on() && count( $fb_advanced_pixel_data ) > 0 ) { ?>
									if (typeof fbq === 'function') {
										fbq('init', '<?php echo esc_attr( trim( $pixel_id ) ); ?>', <?php echo wp_json_encode( $fb_advanced_pixel_data ); ?>);
									}
									<?php } else { ?>
									if (typeof fbq === 'function') {
										fbq('init', '<?php echo esc_attr( trim( $pixel_id ) ); ?>');
									}
									<?php } ?>
								} catch (error) {
									console.error('Facebook pixel init error for ID <?php echo esc_attr( trim( $pixel_id ) ); ?>:', error);
								}
								<?php
								}
								?>
								try {
									<?php $this->render_fb_view(); ?>
									<?php $this->maybe_track_custom_steps_event( 'fb' ); ?>
									<?php $this->maybe_print_fb_script(); ?>
								} catch (error) {
									console.error('Facebook tracking events error:', error);
								}
								<?php
								}
								?>
                            }
                        } catch (error) {
                            console.log('Facebook tracking function error:', error);
                        }
                    }

                </script>
				<?php
			}
		}

		public function do_track_gad_purchase() {
			return false;
		}

		public function maybe_print_fb_script() {
			echo '';
		}

		public function maybe_print_gtag_script( $k, $code, $label, $track = false, $is_gads = false ) { //phpcs:ignore
			echo '';
		}

		public function is_fb_enable_content_on() {
			$is_fb_enable_content_on = $this->admin_general_settings->get_option( 'is_fb_enable_content' );
			if ( is_array( $is_fb_enable_content_on ) && count( $is_fb_enable_content_on ) > 0 && 'yes' === $is_fb_enable_content_on[0] ) {
				return true;
			}
		}

		public function is_fb_advanced_tracking_on() {
			$is_fb_advanced_tracking_on = $this->admin_general_settings->get_option( 'is_fb_advanced_event' );
			if ( is_array( $is_fb_advanced_tracking_on ) && count( $is_fb_advanced_tracking_on ) > 0 && 'yes' === $is_fb_advanced_tracking_on[0] ) {
				return true;
			}
		}

		public function get_woo_product_content_id( $product_id, $service = 'pixel' ) {
			$prefix            = '';
			$suffix            = '';
			$content_id_format = '';

			if ( ( 'pixel' === $service ) && ( true === $this->is_fb_enable_content_on() ) ) {
				$prefix            = $this->admin_general_settings->get_option( $service . '_content_id_prefix' );
				$suffix            = $this->admin_general_settings->get_option( $service . '_content_id_suffix' );
				$content_id_format = $this->admin_general_settings->get_option( $service . '_content_id_type' );
			}

			if ( 'pixel' !== $service ) {
				$prefix            = $this->admin_general_settings->get_option( $service . '_content_id_prefix' );
				$suffix            = $this->admin_general_settings->get_option( $service . '_content_id_suffix' );
				$content_id_format = $this->admin_general_settings->get_option( $service . '_content_id_type' );
			}

			if ( $content_id_format === 'product_sku' ) {
				$content_id = get_post_meta( $product_id, '_sku', true );
			} else {
				$content_id = $product_id;
			}
			$content_id = apply_filters( 'wffn_get_product_content_id', $content_id, $product_id );

			$value = $prefix . $content_id . $suffix;

			return ( $value );

		}

		public function should_render_view( $type ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			return false;
		}

		public function should_render_lead( $type ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			return false;
		}

		public function do_treat_variable_as_simple( $mode = 'pixel' ) {

			$do_treat_variable_as_simple = $this->admin_general_settings->get_option( $mode . '_variable_as_simple' );

			if ( ( 'pixel' === $mode ) && ( true !== $this->is_fb_enable_content_on() ) ) {
				return false;
			}

			if ( 1 === absint( $do_treat_variable_as_simple ) ) {
				return true;
			}

			return false;
		}

	/**
	 * maybe render script to fire fb pixel view event
	 */
	public function render_fb_view() {
		if ( $this->should_render_view( 'fb' ) ) {
			?>
                try {
                    if (typeof fbq === 'function') {
						console.trace();
                        var trafficParams = (typeof wffnAddTrafficParamsToEvent !== "undefined") ? wffnAddTrafficParamsToEvent({}) : {};
                        fbq('track', 'PageView', trafficParams, {'eventID': 'PageView_'+wffn_ev_view_fb_event_id});
                    }
                } catch (error) {
                    console.log('Facebook PageView tracking error:', error);
                }
			<?php

		}
	}

	public function prepare_wffnevents() {
		$has_events = false;
		
		// Initialize global variables only once
		?>
        try {
            if(typeof wffn_ev_custom_fb_event_id === 'undefined'){
                var wffn_ev_custom_fb_event_id = Math.random().toString(36).substring(2, 15);
            }
            if(typeof wffn_ev_view_fb_event_id === 'undefined'){
                var wffn_ev_view_fb_event_id = Math.random().toString(36).substring(2, 15);
            }
            if(typeof wffnEvents === 'undefined'){
                var wffnEvents = [];
            }
        } catch (error) {
            console.log('WFFN global variables initialization error:', error);
        }
		<?php
		
		if ( $this->should_render_view( 'fb' ) ) {
			$has_events = true;
			?>
            try {
                wffnEvents.push({event: 'PageView', 'event_id': 'PageView_'+wffn_ev_view_fb_event_id});
            } catch (error) {
                console.log('WFFN PageView event preparation error:', error);
            }
			<?php
		}

		if ( $this->should_render() && $this->is_enable_custom_event() ) {
			$has_events = true;
			?>
            try {
                wffnEvents.push({event: '<?php echo esc_attr( $this->get_custom_event_name() ); ?>', 'event_id': '<?php echo esc_attr( $this->get_custom_event_name() ); ?>_'+wffn_ev_custom_fb_event_id});
            } catch (error) {
                console.log('WFFN custom event preparation error:', error);
            }
			<?php
		}
		
		// Dispatch custom event only once at the end if we have events
		if ( $has_events ) {
			?>
            // Dispatch custom event to notify that wffnEvents is ready
            try {
                if (typeof document !== 'undefined' && !window.wffnEventsReadyDispatched) {
                    window.wffnEventsReadyDispatched = true;
                    document.dispatchEvent(new CustomEvent('wffnEventsReady', {
                        detail: { events: wffnEvents, source: 'wffn_events' }
                    }));
                }
            } catch (error) {
                console.log('WFFN events dispatch error:', error);
            }
			<?php
		}
	}

		public function is_fb_pixel() {

			$steps = WFFN_Core()->data->get_current_step();
			$key   = $this->admin_general_settings->get_option( 'fb_pixel_key' );

			if ( is_array( $steps ) && isset( $steps['id'] ) && get_post( $steps['id'] ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $steps['id'] );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['fb_pixel_key'] ) && ! empty( $setting['fb_pixel_key'] ) ) ? $setting['fb_pixel_key'] : $key;
				}
			}

			$get_pixel_key = apply_filters( 'bwf_fb_pixel_ids', $key );

			return empty( $get_pixel_key ) ? false : $get_pixel_key;
		}

		public function is_pint_pixel() {

			$steps = WFFN_Core()->data->get_current_step();
			$key   = $this->admin_general_settings->get_option( 'pint_key' );

			if ( is_array( $steps ) && isset( $steps['id'] ) && get_post( $steps['id'] ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $steps['id'] );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['pint_key'] ) && ! empty( $setting['pint_key'] ) ) ? $setting['pint_key'] : $key;
				}
			}

			$get_pixel_key = apply_filters( 'bwf_fb_pint_ids', $key );

			return empty( $get_pixel_key ) ? false : $get_pixel_key;
		}


		/**
		 * render google analytics core script to load framework
		 */
		public function render_ga() {
			$get_tracking_code = $this->ga_code();

			if ( false === $get_tracking_code ) {
				return;
			}

			$get_tracking_code = explode( ",", $get_tracking_code );

			if ( ( $this->do_track_ga_purchase() || $this->should_render_lead( 'ga' ) || $this->should_render_view( 'ga' ) ) && ( is_array( $get_tracking_code ) && ! empty( $get_tracking_code ) ) && $this->should_render() ) {
                ?>
                <!-- Google Analytics Script Added By WooFunnels-->
                <script type="text/javascript">
                    function wffnGaTrackingIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                            if (1 === wffn_shouldRender) {
                                try {
                                    <?php if ( false === $this->gtag_rendered ) {
                                        $this->load_gtag( $get_tracking_code[0] );

                                    }
                                    foreach ( $get_tracking_code as $k => $code ) {
                                        echo "if (typeof gtag === 'function') { gtag('config', '" . esc_attr( trim( $code ) ) . "'); }";
                                        $label = false;
                                        esc_js( $this->render_gtag_custom_event( $k, $code, $label, 'ga' ) );
                                        $this->maybe_print_gtag_script( $k, $code, $label, $this->do_track_ga_purchase() ); //phpcs:ignore
                                    }
                                    ?>
                                } catch (error) {
                                    console.log('Google Analytics tracking error:', error);
                                }
                            }
                        } catch (error) {
                            console.log('Google Analytics tracking function error:', error);
                        }
                    }
                </script>
				<?php
			}
		}

		public function do_track_ga_purchase() {
			return false;
		}

		public function is_enable_custom_event_ga() {
			$is_ga_custom_events = $this->admin_general_settings->get_option( 'is_ga_custom_events' );

			if ( '1' === $is_ga_custom_events ) {
				return true;
			}

			return false;
		}

		public function is_enable_custom_event_gad() {
			$is_ga_custom_events = $this->admin_general_settings->get_option( 'is_gad_custom_events' );

			if ( '1' === $is_ga_custom_events ) {
				return true;
			}

			return false;
		}

		/**
		 * maybe render script to fire fb pixel view event
		 */
		public function render_gtag_custom_event( $code, $label, $mode ) {
			if ( ( ( $mode === 'ga' && $this->is_enable_custom_event_ga() ) || ( $mode === 'gad' && $this->is_enable_custom_event_gad() ) ) ) {
				?>
                try {
                    if (typeof gtag === 'function') {
                        gtag('event','<?php echo esc_attr( $this->get_custom_event_name() ); ?>',{send_to: '<?php echo esc_attr( $code ); ?>'});
                    }
                } catch (error) {
                    console.log('Google Analytics custom event error:', error);
                }
				<?php
			}
		}


	public function load_gtag( $id ) {
		?>
        (function (window, document, src) {
            try {
                var a = document.createElement('script');
                var m = document.getElementsByTagName('script')[0];
                
                if (!m || !m.parentNode) {
                    console.log('Google Analytics: Unable to find script insertion point');
                    return;
                }
                
                a.defer = 1;
                a.src = src;
                a.onerror = function() {
                    console.log('Failed to load Google Analytics script');
                };
                
                m.parentNode.insertBefore(a, m);
            } catch (error) {
                console.log('Google Analytics script loading error:', error);
            }
        })(window, document, '//www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( trim( $id ) ); ?>');

        try {
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function gtag() {
                try {
                    if (window.dataLayer) {
                        window.dataLayer.push(arguments);
                    }
                } catch (error) {
                    console.log('Google Analytics gtag error:', error);
                }
            };

            gtag('js', new Date());
        } catch (error) {
            console.log('Google Analytics initialization error:', error);
        }
		<?php
		$this->gtag_rendered = true;
	}

		/**
		 * render google ads analytics core script to load framework
		 */
		public function render_gad() {
			$get_tracking_code = $this->gad_code();

			if ( false === $get_tracking_code ) {
				return;
			}

			$get_tracking_code = explode( ",", $get_tracking_code );

			if ( ( $this->do_track_gad_purchase() || $this->should_render_lead( 'gad' ) || $this->should_render_view( 'gad' ) ) && ( is_array( $get_tracking_code ) && ! empty( $get_tracking_code ) ) && $this->should_render() ) {
				?>
                <!-- Google Ads Script Added By WooFunnels -->
                <script type="text/javascript">
                    function wffnGadTrackingIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                            if (1 === wffn_shouldRender) {
                                try {
									<?php
									if ( false === $this->gtag_rendered ) {
										$this->load_gtag( $get_tracking_code[0] );

									}

									foreach ( $get_tracking_code as $k => $code ) {
										echo "if (typeof gtag === 'function') { gtag('config', '" . esc_attr( trim( $code ) ) . "'); }";
										if ( $this->should_render_view( 'gad' ) ) {
											echo "if (typeof gtag === 'function') { gtag('event', 'page_view', {send_to: '" . esc_attr( trim( $code ) ) . "'}); }";
										}
										$label = false;
										if ( $this->do_track_gad_purchase() && false !== $this->gad_purchase_label() ) {
											$gad_labels = explode( ",", $this->gad_purchase_label() );
											$label      = isset( $gad_labels[ $k ] ) ? $gad_labels[ $k ] : $gad_labels[0];
										}
										esc_js( $this->render_gtag_custom_event( $k, $code, $label, 'gad' ) );
										$this->maybe_print_gtag_script( $k . 'gad', $code, $label, $this->do_track_gad_purchase(), true );
									}

									?>
                                } catch (error) {
                                    console.log('Google Ads tracking error:', error);
                                }
                            }
                        } catch (error) {
                            console.log('Google Ads tracking function error:', error);
                        }
                    }
                </script>
				<?php
			}
		}

		/**
		 * render snapchat analytics core script to load framework
		 */
		public function render_snapchat() {
			$get_tracking_code = $this->snapchat_code();
			if ( false === $get_tracking_code ) {
				return;
			}

			$get_tracking_code = explode( ",", $get_tracking_code );

			if ( ( $this->should_render_view( 'snapchat' ) ) && is_array( $get_tracking_code ) && count( $get_tracking_code ) > 0 ) {


				$get_each_pixel_id = explode( ',', $this->snapchat_code() );
				?>
                <!-- snapchat Pixel Base Code -->
                <script type="text/javascript">
                    function wffnSnapchatTrackingIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                            if (1 === wffn_shouldRender) {
                                // Enhanced Snapchat tracking with error handling
                                (function (win, doc, sdk_url) {
                                    try {
                                        if (win.snaptr) {
                                            return;
                                        }

                                        var tr = win.snaptr = function () {
                                            try {
                                                if (tr.handleRequest) {
                                                    tr.handleRequest.apply(tr, arguments);
                                                } else {
                                                    if (tr.queue) {
                                                        tr.queue.push(arguments);
                                                    }
                                                }
                                            } catch (error) {
                                                console.log('Snapchat tracking error:', error);
                                            }
                                        };
                                        
                                        tr.queue = [];
                                        var s = 'script';
                                        var new_script_section = doc.createElement(s);
                                        new_script_section.async = true;
                                        new_script_section.src = sdk_url;
                                        new_script_section.onerror = function() {
                                            console.log('Failed to load Snapchat tracking script');
                                        };
                                        
                                        var insert_pos = doc.getElementsByTagName(s)[0];
                                        if (insert_pos && insert_pos.parentNode) {
                                            insert_pos.parentNode.insertBefore(new_script_section, insert_pos);
                                        }
                                    } catch (error) {
                                        console.log('Snapchat initialization error:', error);
                                    }
                                })(window, document, 'https://sc-static.net/scevent.min.js');

								<?php foreach ( $get_each_pixel_id as $id ) {

								$email = $this->get_user_email();
								if ( ! empty( $email ) ) {
								?>
                                try {
                                    if (typeof snaptr === 'function') {
                                        snaptr('init', '<?php echo esc_attr( $id ); ?>', {
                                            integration: 'woocommerce',
                                            user_email: '<?php echo esc_attr( $email ); ?>'
                                        });
                                    }
                                } catch (error) {
                                    console.log('Snapchat pixel init error for ID <?php echo esc_attr( $id ); ?>:', error);
                                }
								<?php
								} else {
								?>
                                try {
                                    if (typeof snaptr === 'function') {
                                        snaptr('init', '<?php echo esc_attr( $id ); ?>', {
                                            integration: 'woocommerce'
                                        });
                                    }
                                } catch (error) {
                                    console.log('Snapchat pixel init error for ID <?php echo esc_attr( $id ); ?>:', error);
                                }
								<?php
								}
								} ?>
                            }
                        } catch (error) {
                            console.log('Snapchat tracking function error:', error);
                        }
                    }

                </script>
                <script type="text/javascript">
                    function wffnSnapchatTrackingBaseIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                            if (1 === wffn_shouldRender) {
                                try {
									<?php if ( $this->should_render_view( 'snapchat' ) ) { ?>
                                    if (typeof snaptr === 'function') {
                                        snaptr('track', 'PAGE_VIEW');
                                    }
									<?php } ?>
									<?php esc_js( $this->maybe_print_snapchat_ecomm() ); ?> //phpcs:ignore
                                } catch (error) {
                                    console.log('Snapchat base tracking error:', error);
                                }
                            }
                        } catch (error) {
                            console.log('Snapchat base tracking function error:', error);
                        }
                    }
                </script>

				<?php
			}
		}

		public function tiktok_code() {

			global $post;
			$step_id = 0;
			if ( $post instanceof WP_Post ) {
				$step_id = $post->ID;
			}
			$key = $this->admin_general_settings->get_option( 'tiktok_pixel' );

			if ( $step_id > 0 && get_post( $step_id ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $step_id );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['tiktok_pixel'] ) && ! empty( $setting['tiktok_pixel'] ) ) ? $setting['tiktok_pixel'] : $key;
				}
			}

			$get_ga_key = apply_filters( 'bwf_tiktok_pixel', $key );

			return empty( $get_ga_key ) ? false : $get_ga_key;
		}

		/**
		 * render script to load facebook pixel core js
		 */
		public function render_tiktok() {

			if ( $this->tiktok_code() ) {
				$get_each_pixel_id   = explode( ',', $this->tiktok_code() );
				$advanced_pixel_data = $this->get_advanced_pixel_data( 'tiktok' );

				?>
                <!-- Tiktok Pixel Base Code -->
                <script type="text/javascript">
                    function wffnTiktokTrackingIn() {
                        try {
                            var wffn_shouldRender = 1;
							<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                            if (1 === wffn_shouldRender) {
                                // Enhanced TikTok tracking with error handling
                                (function (w, d, t) {
                                    try {
                                        w.TiktokAnalyticsObject = t;
                                        var ttq = w[t] = w[t] || [];
                                        ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias", "group", "enableCookie", "disableCookie"];
                                        
                                        ttq.setAndDefer = function (t, e) {
                                            t[e] = function () {
                                                try {
                                                    t.push([e].concat(Array.prototype.slice.call(arguments, 0)));
                                                } catch (error) {
                                                    console.log('TikTok tracking method error:', error);
                                                }
                                            }
                                        };
                                        
                                        for (var i = 0; i < ttq.methods.length; i++) {
                                            ttq.setAndDefer(ttq, ttq.methods[i]);
                                        }
                                        
                                        ttq.instance = function (t) {
                                            try {
                                                for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) {
                                                    ttq.setAndDefer(e, ttq.methods[n]);
                                                }
                                                return e;
                                            } catch (error) {
                                                console.log('TikTok instance error:', error);
                                                return [];
                                            }
                                        };
                                        
                                        ttq.load = function (e, n) {
                                            try {
                                                var i = "https://analytics.tiktok.com/i18n/pixel/events.js";
                                                ttq._i = ttq._i || {};
                                                ttq._i[e] = [];
                                                ttq._i[e]._u = i;
                                                ttq._t = ttq._t || {};
                                                ttq._t[e] = +new Date;
                                                ttq._o = ttq._o || {};
                                                ttq._o[e] = n || {};
                                                
                                                var o = document.createElement("script");
                                                o.type = "text/javascript";
                                                o.async = true;
                                                o.src = i + "?sdkid=" + e + "&lib=" + t;
                                                o.onerror = function() {
                                                    console.log('Failed to load TikTok tracking script');
                                                };
                                                
                                                var a = document.getElementsByTagName("script")[0];
                                                if (a && a.parentNode) {
                                                    a.parentNode.insertBefore(o, a);
                                                }
                                            } catch (error) {
                                                console.log('TikTok load error:', error);
                                            }
                                        };
                                    } catch (error) {
                                        console.log('TikTok initialization error:', error);
                                    }
                                })(window, document, 'ttq');

								<?php foreach ( $get_each_pixel_id as $id ) { ?>
                                try {
                                    if (typeof ttq === 'object' && ttq.load) {
                                        ttq.load('<?php echo esc_attr( $id ) ?>');
										<?php if ( count( $advanced_pixel_data ) > 0 ) { ?>
                                        if (ttq.instance) {
                                            ttq.instance('<?php echo esc_attr( $id ); ?>').identify(<?php echo wp_json_encode( $advanced_pixel_data ); ?>);
                                        }
										<?php } ?>
										<?php if ( $this->should_render_view( 'tiktok' ) ) { ?>
                                        if (ttq.page) {
                                            ttq.page();
                                        }
										<?php } ?>
                                    }
                                } catch (error) {
                                    console.log('TikTok pixel tracking error for ID <?php echo esc_attr( $id ) ?>:', error);
                                }
								<?php } ?>
                            }
                        } catch (error) {
                            console.log('TikTok tracking function error:', error);
                        }
                    }

                </script>

				<?php if ( $this->do_track_tiktok()  ) { ?>
                    <!-- END Tiktok Pixel Base Code -->
                    <script type="text/javascript">
                        function wffnTiktokTrackingBaseIn() {
                            try {
                                var wffn_shouldRender = 1;
								<?php do_action( 'wffn_allow_tracking_inline_js' ); ?>
                                if (1 === wffn_shouldRender) {
                                    setTimeout(function () {
                                        try {
											<?php foreach ( $get_each_pixel_id as $id ) {
											 $this->maybe_print_tiktok_ecomm( $id, $this->do_track_tiktok() );
										} ?>
                                        } catch (error) {
                                            console.log('TikTok base tracking timeout error:', error);
                                        }
                                    }, 1200);
                                }
                            } catch (error) {
                                console.log('TikTok base tracking function error:', error);
                            }
                        }
                    </script>
					<?php
				}
			}
		}

		public function do_track_tiktok() {
			return false;
		}

		public function do_track_cp_tiktok() {
			return false;
		}

		public function maybe_print_tiktok_ecomm( $id, $purchase = false ) {  //phpcs:ignore
			echo '';
		}

		/**
		 * Maybe print google analytics javascript
		 * @see WFFN_Ecomm_Tracking_Common::render_ga();
		 */
		public function maybe_print_ga_script() {
			echo '';
		}

		public function ga_code() {

			global $post;
			$step_id = 0;
			if ( $post instanceof WP_Post ) {
				$step_id = $post->ID;
			}

			$key = $this->admin_general_settings->get_option( 'ga_key' );

			if ( $step_id > 0 && get_post( $step_id ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $step_id );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['ga_key'] ) && ! empty( $setting['ga_key'] ) ) ? $setting['ga_key'] : $key;
				}
			}

			$get_ga_key = apply_filters( 'bwf_ga_key', $key );

			return empty( $get_ga_key ) ? false : $get_ga_key;
		}

		public function gad_purchase_label() {

			global $post;
			$step_id = 0;
			if ( $post instanceof WP_Post ) {
				$step_id = $post->ID;
			}
			$key = $this->admin_general_settings->get_option( 'gad_conversion_label' );

			if ( $step_id > 0 && get_post( $step_id ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $step_id );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['gad_conversion_label'] ) && ! empty( $setting['gad_conversion_label'] ) ) ? $setting['gad_conversion_label'] : $key;
				}
			}

			$get_gad_conversion_label = apply_filters( 'bwf_get_conversion_label', $key );

			return empty( $get_gad_conversion_label ) ? false : $get_gad_conversion_label;
		}

		public function gad_code() {
			global $post;
			$step_id = 0;
			if ( $post instanceof WP_Post ) {
				$step_id = $post->ID;
			}
			$key = $this->admin_general_settings->get_option( 'gad_key' );

			if ( $step_id > 0 && get_post( $step_id ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $step_id );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['gad_key'] ) && ! empty( $setting['gad_key'] ) ) ? $setting['gad_key'] : $key;
				}
			}

			$get_ga_key = apply_filters( 'bwf_gad_key', $key );

			return empty( $get_ga_key ) ? false : $get_ga_key;
		}


		public function snapchat_code() {
			global $post;
			$step_id = 0;
			if ( $post instanceof WP_Post ) {
				$step_id = $post->ID;
			}
			$key = $this->admin_general_settings->get_option( 'snapchat_pixel' );

			if ( $step_id > 0 && get_post( $step_id ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $step_id );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['snapchat_pixel'] ) && ! empty( $setting['snapchat_pixel'] ) ) ? $setting['snapchat_pixel'] : $key;
				}
			}

			$get_ga_key = apply_filters( 'bwf_snapchat_pixel', $key );

			return empty( $get_ga_key ) ? false : $get_ga_key;
		}

		public function get_event_id( $event ) {
			return $event . "_" . time();
		}

		public function getRequestUri( $is_ajax = false ) {
			$request_uri = null;
			if ( true === $is_ajax ) {

				if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {//phpcs:ignore
					return $_SERVER['HTTP_REFERER'];//phpcs:ignore
				}

				$current_step = WFFN_Core()->data->get_current_step();
				if ( is_array( $current_step ) && count( $current_step ) > 0 ) {
					return get_permalink( $current_step['id'] );
				}
			}
			if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
				$start       = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://";
				$request_uri = $start . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; //phpcs:ignore
			}

			return $request_uri;
		}

		public function getEventRequestUri() {
			$request_uri = "";
			if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
				$request_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; //phpcs:ignore
			}

			return $request_uri;
		}


		/**
		 * Is conversion API enabled from global settings
		 * @return bool
		 */
		public function is_conversion_api() {
			$admin_general           = BWF_Admin_General_Settings::get_instance();
			if ( !empty( $admin_general->get_option( 'conversion_api_access_token' ) ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Render a JS to fire async ajax calls to fire further events
		 */
		public function maybe_render_conv_api( $is_ajax = false ) {
			/**
			 * Special handling for the order received page
			 */
			if ( $this->is_conversion_api() ) {
				$get_all_fb_pixel = $this->is_fb_pixel();
				$access_token     = $this->get_conversion_api_access_token();

				if ( empty( $get_all_fb_pixel ) || empty( $access_token ) ) {
					return;
				}

				$get_each_pixel_id = explode( ',', $get_all_fb_pixel );
				$access_token      = explode( ',', $access_token );
				if ( ! is_array( $access_token ) || 0 === count( $access_token ) ) {
					return;
				}

				if ( is_array( $get_each_pixel_id ) && count( $get_each_pixel_id ) > 0 ) {
					foreach ( $get_each_pixel_id as $key => $pixel_id ) {
						/**
						 * continue if access token empty
						 */
						if ( empty( $access_token[ $key ] ) ) {
							continue;
						}
						foreach ( $this->api_events as $event ) {
							$this->fire_conv_api_event( $event, $pixel_id, $access_token[ $key ], $key, $is_ajax );
						}
					}
				}

			}
		}

		public function get_conversion_api_access_token() {

			$steps = WFFN_Core()->data->get_current_step();
			$key   = $this->admin_general_settings->get_option( 'conversion_api_access_token' );

			if ( is_array( $steps ) && isset( $steps['id'] ) && get_post( $steps['id'] ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $steps['id'] );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['conversion_api_access_token'] ) && ! empty( $setting['conversion_api_access_token'] ) ) ? $setting['conversion_api_access_token'] : $key;
				}
			}

			$get_pixel_key = apply_filters( 'bwf_conversion_api_access_token', $key );

			return empty( $get_pixel_key ) ? false : $get_pixel_key;
		}

		public function get_conversion_api_test_event_code() {

			$steps = WFFN_Core()->data->get_current_step();
			$key   = $this->admin_general_settings->get_option( 'conversion_api_test_event_code' );

			if ( is_array( $steps ) && isset( $steps['id'] ) && get_post( $steps['id'] ) instanceof WP_Post ) {
				$setting = WFFN_Common::maybe_override_tracking( $steps['id'] );
				if ( is_array( $setting ) ) {
					$key = ( isset( $setting['conversion_api_test_event_code'] ) && ! empty( $setting['conversion_api_test_event_code'] ) ) ? $setting['conversion_api_test_event_code'] : $key;
				}
			}

			$get_pixel_key = apply_filters( 'bwf_conversion_api_test_event_code', $key );

			return empty( $get_pixel_key ) ? false : $get_pixel_key;
		}

		/**
         * Ajax callback modal method to handle firing of multiple events in conv api
		 * @param $event
		 * @param $pixel_id
		 * @param $access_token
		 * @param $key
		 * @param $is_ajax
		 *
		 * @return void|null
		 */
		public function fire_conv_api_event( $event, $pixel_id, $access_token, $key, $is_ajax = false ) {
			$type     = isset( $event['event'] ) ? $event['event'] : '';
			$event_id = isset( $event['event_id'] ) ? $event['event_id'] : '';
			if ( empty( $type ) || empty( $event_id ) ) {
				return;
			}
			BWF_Facebook_Sdk_Factory::setup( trim( $pixel_id ), trim( $access_token ) );

			$get_test      = $this->get_conversion_api_test_event_code();
			$get_test      = explode( ',', $get_test );
			$is_test_event = $this->admin_general_settings->get_option( 'is_fb_conv_enable_test' );
			if ( is_array( $is_test_event ) && count( $is_test_event ) > 0 && $is_test_event[0] === 'yes' && is_array( $get_test ) && count( $get_test ) > 0 ) {
				if ( isset( $get_test[ $key ] ) && ! empty( $get_test[ $key ] ) ) {
					BWF_Facebook_Sdk_Factory::set_test( trim( $get_test[ $key ] ) );
				}
			}

			BWF_Facebook_Sdk_Factory::set_partner( 'woofunnels' );
			$instance = BWF_Facebook_Sdk_Factory::create();
			if ( is_null( $instance ) ) {
				return null;
			}

			$getEventparams = $this->get_generic_event_params_for_conv_api();
			switch ( $type ) {
				case 'PageView':
					$instance->set_event_id( $event_id );
					$instance->set_user_data( $this->get_user_data( $type ) );
					$instance->set_event_source_url( $this->getRequestUri( $is_ajax ) );
					$instance->set_event_data( 'PageView', [ $event_id ] );
					if ( isset( $event['data'] ) && isset( $event['data'] ) ) {
						$instance->set_event_data( 'PageView', $event['data'] );

					} else {
						$instance->set_event_data( 'PageView', $getEventparams );

					}
					break;
				case 'Purchase':
					$instance->set_event_source_url( $this->getRequestUri( $is_ajax ) );
					$instance->set_event_id( $event_id );
					$instance->set_user_data( $this->get_user_data( $type ) );
					$instance->set_event_data( 'Purchase', $this->get_generic_event_params_for_conv_api() );
					break;
				case 'trackCustom':
					$instance->set_event_id( $event_id );
					$instance->set_user_data( $this->get_user_data( $type ) );
					$instance->set_event_source_url( $this->getRequestUri( $is_ajax ) );
					$getEventName = $this->admin_general_settings->get_option( 'general_event_name' );
					$instance->set_event_data( $getEventName, $getEventparams );
					break;
				default:
					$instance->set_event_id( $event_id );
					$instance->set_user_data( $this->get_user_data( $type ) );
					$instance->set_event_source_url( $this->getRequestUri( $is_ajax ) );

					if ( isset( $event['data'] ) && isset( $event['data'] ) ) {
						$instance->set_event_data( $type, $event['data'] );

					} else {
						$instance->set_event_data( $type, $getEventparams );

					}
			}


			$response = $instance->execute();

			if ( $type === 'Purchase' || $type === 'AddToCart' ) {
				$this->maybe_insert_log( '----Facebook conversion API-----------' . print_r( $response, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			}

		}
		/************************************ Conversion API related methods starts here ***************************/
		/**
		 * Maybe insert logs for the conversion API
		 *
		 * @param string $content
		 */
		public function maybe_insert_log( $content ) {

			if ( $this->is_enabled_log() ) {
				wc_get_logger()->log( 'info', $content, array( 'source' => 'bwf_facebook_conversion_api' ) );
			}
		}

		/**
		 * Check if logs are enabled or not for the conversion API
		 * @return bool
		 */
		public function is_enabled_log() {
			$is_conversion_api_log = $this->admin_general_settings->get_option( 'is_fb_conversion_api_log' );
			if ( is_array( $is_conversion_api_log ) && count( $is_conversion_api_log ) > 0 && 'yes' === $is_conversion_api_log[0] ) {
				return true;
			}

			return false;
		}

		/**
		 * Get current hour in the format supported by Facebook
		 * @return string string
		 */
		public function getHour() {
			$array = [
				'00-01',
				'01-02',
				'02-03',
				'03-04',
				'04-05',
				'05-06',
				'06-07',
				'07-08',
				'08-09',
				'09-10',
				'10-11',
				'11-12',
				'12-13',
				'13-14',
				'14-15',
				'15-16',
				'16-17',
				'17-18',
				'18-19',
				'19-20',
				'20-21',
				'21-22',
				'22-23',
				'23-24'
			];

			return $array[ current_time( "G" ) ];

		}

		public function get_user_data( $type ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$user_data                      = WFFN_Common::pixel_advanced_matching_data();
			$user_data['client_ip_address'] = wffn_get_ip_address();
			$user_data['client_user_agent'] = wffn_get_user_agent();
		if ( isset( $_COOKIE['_fbp'] ) && ! empty( $_COOKIE['_fbp'] ) ) {
			$user_data['_fbp'] = wffn_clean( wp_unslash( $_COOKIE['_fbp'] ) ); //phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		}
		if ( isset( $_COOKIE['_fbc'] ) && ! empty( $_COOKIE['_fbc'] ) ) {
			$user_data['_fbc'] = wffn_clean( wp_unslash( $_COOKIE['_fbc'] ) ); //phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		} elseif ( isset( $_COOKIE['wffn_fbclid'] ) && isset( $_COOKIE['wffn_flt'] ) && ! empty( $_COOKIE['wffn_fbclid'] ) ) {
			$user_data['_fbc'] = 'fb.1.' . strtotime( wffn_clean( wp_unslash( $_COOKIE['wffn_flt'] ) ) ) . '.' . wffn_clean( wp_unslash( $_COOKIE['wffn_fbclid'] ) );
		}

			return $user_data;
		}

		public function maybe_ecomm_events( $events ) {
			$this->api_events = $events;
			$this->maybe_render_conv_api( true );
		}


	public function maybe_track_custom_steps_event() {//phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter

		if ( $this->should_render() && $this->is_enable_custom_event() ) {
			?>
                try {
                    if (typeof fbq === 'function') {
                        var trafficParams = (typeof wffnAddTrafficParamsToEvent !== "undefined") ? wffnAddTrafficParamsToEvent(<?php echo $this->get_custom_event_params(); ?>) : <?php echo $this->get_custom_event_params(); ?>; <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        fbq('trackCustom', '<?php echo esc_attr( $this->get_custom_event_name() ); ?>', trafficParams, {'eventID': '<?php echo esc_attr( $this->get_custom_event_name() ); ?>_'+wffn_ev_custom_fb_event_id});
                    }
                } catch (error) {
                    console.log('Facebook custom event tracking error:', error);
                }
			<?php

		}
	}

		public function maybe_track_custom_steps_event_pint() {//phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter

			if ( $this->should_render() && $this->is_enable_custom_event_pint() ) {

				?>
                try {
                    if (typeof pintrk === 'function') {
                        var trafficParams = (typeof wffnAddTrafficParamsToEvent !== "undefined") ? wffnAddTrafficParamsToEvent(<?php echo $this->get_custom_event_params(); ?>) : <?php echo $this->get_custom_event_params(); ?>; <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        pintrk('track', '<?php echo esc_attr( $this->get_custom_event_name() ); ?>', trafficParams);
                    }
                } catch (error) {
                    console.log('Pinterest custom event tracking error:', error);
                }
				<?php

			}
		}

		public function is_enable_custom_event() {
			$is_fb_custom_events = $this->admin_general_settings->get_option( 'is_fb_custom_events' );

			if ( '1' === $is_fb_custom_events ) {
				return true;
			}

			return false;
		}

		public function is_enable_custom_event_pint() {
			$is_pint_custom_events = $this->admin_general_settings->get_option( 'is_pint_custom_events' );

			if ( '1' === $is_pint_custom_events ) {
				return true;
			}

			return false;
		}

		public function get_custom_event_name() {
			return 'WooFunnels_Sales';
		}

		public function get_custom_event_params() {
			return wp_json_encode( [] );
		}

		public function get_user_email() {
			$current_user = wp_get_current_user();

			// not logged in
			if ( empty( $current_user ) || $current_user->ID === 0 ) {
				return '';
			}

			return $current_user->user_email;
		}

		/**
		 * Maybe print google analytics javascript
		 * @see WFFN_Ecomm_Tracking::render_ga();
		 * @see WFFN_Ecomm_Tracking::render_gad();
		 */
		public function maybe_print_pint_ecomm() { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter

		}

		public function get_generic_event_params_for_conv_api() {
			return [];
		}

		/**
		 * Maybe print google analytics javascript
		 * @see WFFN_Ecomm_Tracking::render_ga();
		 * @see WFFN_Ecomm_Tracking::render_gad();
		 */
		public function maybe_print_snapchat_ecomm() { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter

		}

	public function fire_tracking() {
		if ( $this->should_render() ) {
			?>
            <script type="text/javascript">
                (function() {
                    'use strict';
                    
                    // WFFN Error logging utility
                    function wffnLogError(error, context) {
                        try {
                            console.log('WFFN Tracking Error [' + context + ']:', error);
                        } catch (e) {
                            // Fallback if console is not available
                        }
                    }
                    
                    // WFFN Safe function execution wrapper
                    function wffnSafeExecute(fn, context) {
                        try {
                            if (typeof fn === 'function') {
                                fn();
                            }
                        } catch (error) {
                            wffnLogError(error, context);
                        }
                    }
                    
                    // WFFN Optimized tracking functions array for better performance
                    var wffnTrackingFunctions = [
                        { fn: 'wffnFbTrackingIn', name: 'Facebook' },
                        { fn: 'wffnGaTrackingIn', name: 'Google Analytics' },
                        { fn: 'wffnGadTrackingIn', name: 'Google Ads' },
                        { fn: 'wffnPintTrackingIn', name: 'Pinterest' },
                        { fn: 'wffnPintTrackingBaseIn', name: 'Pinterest Base' },
                        { fn: 'wffnTiktokTrackingIn', name: 'TikTok' },
                        { fn: 'wffnTiktokTrackingBaseIn', name: 'TikTok Base' },
                        { fn: 'wffnSnapchatTrackingIn', name: 'Snapchat' },
                        { fn: 'wffnSnapchatTrackingBaseIn', name: 'Snapchat Base' }
                    ];
                    
                    // WFFN Execute all tracking functions safely
                    function wffnExecuteTrackingFunctions() {
                        wffnTrackingFunctions.forEach(function(tracker) {
                            wffnSafeExecute(window[tracker.fn], tracker.name);
                        });
                    }
                    
                    // WFFN DOM ready handler with error protection
                    function wffnInitTracking() {
                        try {
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', function(){
														if(typeof window.cmplz_enable_category !== 'function') {
															wffnExecuteTrackingFunctions();
														}
								});
                            } else {
                                wffnExecuteTrackingFunctions();
                            }
                        } catch (error) {
                            wffnLogError(error, 'DOM Ready Handler');
                        }
                    }
                    
                    // WFFN Initialize tracking - Default execution (open, not wrapped in jQuery)

						wffnInitTracking();
					
                    
                    // WFFN Consent management with enhanced error handling (jQuery only for Complianz compatibility)
                    try {
                        if (typeof $ !== 'undefined' && $.fn && $.fn.on) {
                            $(document).on("cmplz_enable_category", function(event) {
                                try {
                                    // Defensive checks for consent data
                                    if (!event || !event.originalEvent || !event.originalEvent.detail) {
                                        wffnLogError('Invalid consent event data', 'Consent Handler');
                                        return;
                                    }
                                    
                                    var consentData = event.originalEvent;
                                    var category = consentData.detail.category;
                                    
                                    // Validate required data
                                    if (!category) {
                                        wffnLogError('Missing category in consent data', 'Consent Handler');
                                        return;
                                    }
                                    
                                    if (category === 'marketing') {
                                        wffnExecuteTrackingFunctions();
                                    }
                                } catch (error) {
                                    wffnLogError(error, 'Consent Category Handler');
                                }
                            });
                        } 
                    } catch (error) {
                        wffnLogError(error, 'Consent Management Setup');
                    }
                    
                })();
            </script>
			<?php
		}

	}

	}
}