<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Class to initiate admin functionalists
 * Class WFFN_Admin
 */
if ( ! class_exists( 'WFFN_Admin' ) ) {
	#[AllowDynamicProperties]
	class WFFN_Admin {

		private static $ins             = null;
		private $funnel                 = null;
		public $fk_memory_limit         = 256 * 1024 * 1024;
		private $step_against_fid       = array();
		private $step_count_against_fid = array();

		/**
		 * @var WFFN_Background_Importer $updater
		 */
		public $wffn_updater;

		/**
		 * WFFN_Admin constructor.
		 */
		public function __construct() {

			/** Admin enqueue scripts*/
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 99 );
			if ( $this->is_wffn_flex_page( 'all' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'js_variables' ), 0 );
			}
			if ( $this->is_wffn_flex_page() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'maybe_register_breadcrumb_nodes' ), 5 );
			}

			/** Add update count badge to FunnelKit menu - runs after core menu modifications */
			add_action( 'admin_head', array( $this, 'add_menu_update_badge' ), 9999 );
			/**
			 * DB updates and table installation
			 */
			$get_db_version = get_option( '_wffn_db_version', '0.0.0' );

			if ( version_compare( WFFN_DB_VERSION, $get_db_version, '>' ) ) {
				add_action( 'admin_init', array( $this, 'check_db_version' ), 990 );
			}
			add_action( 'admin_init', array( $this, 'maybe_update_database_update' ), 995 );

			add_action( 'admin_init', array( $this, 'reset_wizard' ) );
			if ( $this->is_wffn_flex_page() && ( ! isset( $_GET['path'] ) || '/user-setup' !== $_GET['path'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				add_action( 'admin_init', array( $this, 'maybe_force_redirect_to_wizard' ) );
			}
			add_action( 'admin_head', array( $this, 'hide_from_menu' ) );

			add_filter( 'get_pages', array( $this, 'add_landing_in_home_pages' ), 10, 2 );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
			global $wffn_notices;
			if ( is_array( $wffn_notices ) || ! empty( $wffn_notices ) ) {
				add_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );
			}
			if ( $this->is_wffn_flex_page( 'all' ) ) {
				add_action( 'admin_notices', array( $this, 'remove_all' ), - 1 );
			}
			add_filter( 'plugin_action_links_' . WFFN_PLUGIN_BASENAME, array( $this, 'plugin_actions' ) );

			/** Initiate Background updater if action scheduler is not available for template importing */
			add_action( 'init', array( $this, 'wffn_maybe_init_background_updater' ), 110 );
			add_filter(
				'bwf_general_settings_link',
				function () {
					return admin_url( 'admin.php?page=bwf&path=/funnels' );
				},
				100000
			);
			add_filter( 'woofunnels_show_reset_tracking', '__return_true', 999 );
			add_action( 'admin_head', array( $this, 'menu_highlight' ), 99999 );
			add_action( 'pre_get_posts', array( $this, 'load_page_to_home_page' ), 9999 );
			add_filter( 'bwf_settings_config_general', array( $this, 'settings_config' ) );

			add_filter( 'bwf_experiment_ref_link', array( $this, 'maybe_modify_link' ), 10, 2 );

			add_action( 'before_delete_post', array( $this, 'delete_funnel_step_permanently' ), 10, 2 );
			add_filter( 'wffn_rest_get_funnel_steps', array( $this, 'maybe_delete_funnel_step' ), 10, 2 );
			add_action( 'admin_bar_menu', array( $this, 'add_menu_in_admin_bar' ), 99 );

			add_action( 'wffn_rest_plugin_activate_response', array( $this, 'maybe_add_auth_link_stripe' ), 10, 2 );

			add_filter( 'woofunnels_global_settings', array( $this, 'add_global_setting_tabs' ), 5 );

			add_filter( 'woofunnels_global_settings_fields', array( $this, 'add_settings_fields_array' ), 110 );

			add_action( 'wp_ajax_wffn_blocks_incompatible_switch_to_classic', array( $this, 'blocks_incompatible_switch_to_classic_cart_checkout' ) );
			add_action( 'wp_ajax_wffn_dismiss_notice', array( $this, 'ajax_dismiss_admin_notice' ) );
			add_filter(
				'bwf_general_settings_default_config',
				function ( $config ) {
					if ( isset( $config['allow_theme_css'] ) ) {

						/**
						 * Allow default theme script if user use any snippet
						 */
						$allowed_themes = apply_filters( 'wffn_allowed_themes', array( 'flatsome', 'Extra', 'divi', 'Divi', 'astra', 'jupiterx', 'kadence' ) );

						if ( function_exists( 'WFFN_Core' ) && ( ( is_array( $allowed_themes ) && in_array( get_template(), $allowed_themes, true ) ) || WFFN_Core()->page_builders->is_divi_theme_enabled() ) ) {
							$config['allow_theme_css'] = array(
								'wfacp_checkout',
								'wffn_ty',
								'wffn_landing',
								'wffn_optin',
								'wffn_oty',
								'wfocu_offer',
							);

						}
					}

					return $config;
				},
				10,
				2
			);

			if ( isset( $_GET['wfacp_id'] ) && isset( $_GET['new_ui'] ) && 'wffn' === $_GET['new_ui'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_action( 'init', array( $this, 'redirect_checkout_edit_link_on_new_ui' ) );
			}
			if ( defined( 'FKCART_PLUGIN_FILE' ) ) {
				add_filter(
					'fkcart_app_header_menu',
					function ( $menu ) {
						if ( isset( $menu['analytics'] ) ) {
							return $menu;
						}

						$keys   = array_keys( $menu );
						$values = array_values( $menu );

						$indexToInsert = array_search( 'templates', $keys, true );

						array_splice( $keys, $indexToInsert, 0, 'analytics' );
						array_splice(
							$values,
							$indexToInsert,
							0,
							array(
								'analytics' => array(
									'name' => 'Analytics',
									'link' => admin_url( 'admin.php?page=bwf&path=/analytics' ),
								),
							)
						);

						$resultArray = array_combine( $keys, $values );
						if ( isset( $menu['settings'] ) ) {
							unset( $resultArray['settings'] );
						}

						return $resultArray;
					}
				);
			}

			add_action( 'after_plugin_row', array( $this, 'maybe_add_notice' ), 10 );
			add_action( 'plugin_action_links', array( $this, 'plugin_action_link' ), 10, 2 );
			add_action( 'current_screen', array( $this, 'conditional_includes' ), 1 );

			add_action( 'fk_optimize_conversion_table_analytics', array( $this, 'optimize_conversion_table_analytics' ) );
			add_action( 'fk_remove_optimize_conversion_table_schedule', array( $this, 'remove_optimize_conversion_table_schedule' ) );

			add_action( 'fk_fb_every_day', array( $this, 'check_memory_limit_errors' ), 9999 );
			/** Email notification callback */
			add_action( 'fk_fb_every_day', array( $this, 'maybe_setup_notification_schedule' ) );
			add_action( 'wffn_performance_notification', array( $this, 'run_notifications' ) );
			add_action( 'bwfan_run_midnight_cron', array( $this, 'maybe_setup_notification_schedule' ) );
			if ( isset( $_GET['wffn_email_preview'] ) && current_user_can( 'administrator' ) ) { // @codingStandardsIgnoreLine
				add_action( 'admin_init', array( $this, 'test_notification_admin' ) );
			}
			add_action( 'bwf_global_save_settings_funnelkit_notifications', array( $this, 'save_settings_for_email_notification' ), 10, 1 );
			add_filter( 'use_block_editor_for_post', array( $this, 'maybe_enable_gutenberg_for_funnel_steps' ), PHP_INT_MAX, 2 );
		}

		/**
		 * Enable Gutenberg editor for funnel steps that have Gutenberg design selected
		 *
		 * @param bool    $use_block_editor Whether to use block editor
		 * @param WP_Post $post            The post object
		 * @return bool
		 */
		public function maybe_enable_gutenberg_for_funnel_steps( $use_block_editor, $post ) {
			try {
				// Define step types and their corresponding meta keys
				$step_meta_keys = array(
					'wffn_landing'   => '_wflp_selected_design',
					'wffn_optin'     => '_wfop_selected_design',
					'wffn_oty'       => '_wfoty_selected_design',
					'wfacp_checkout' => '_wfacp_selected_design',
					'wffn_ty'        => '_wftp_selected_design',
					'wfocu_offer'    => '_wfocu_setting',
				);

				// Check if the post type is one of our funnel steps
				if ( isset( $step_meta_keys[ $post->post_type ] ) ) {
					// Get the post meta using the corresponding meta key
					$selected_design = get_post_meta( $post->ID, $step_meta_keys[ $post->post_type ], true );

					// Handle different meta structures for different step types
					if ( 'wfocu_offer' === $post->post_type ) {
						// Upsell offers use template_group property for design type (object structure)
						if ( ! empty( $selected_design ) && isset( $selected_design->template_group ) && 'gutenberg' === $selected_design->template_group ) {
							return true; // Enable Gutenberg
						}
					} else {
						// Other step types use selected_type property (array structure)
						if ( ! empty( $selected_design ) && isset( $selected_design['selected_type'] ) && 'gutenberg' === $selected_design['selected_type'] ) {
							return true; // Enable Gutenberg
						}
					}
				}

				return $use_block_editor; // Default editor (Classic Editor) for all other cases
			} catch ( \Throwable $e ) {
				// Log error and return default behavior
				BWF_logger::get_instance()->log( 'Error in use_block_editor_for_post filter: ' . $e->getMessage(), 'wffn_admin' );
				return $use_block_editor;
			}

			// Ensure we always return a value (fallback)
			return $use_block_editor;
		}

		/**
		 * @return WFFN_Admin|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * Checks WooCommerce fatal error logs for memory limit issues from the previous day.
		 * If found, updates the `fk_memory_limit` option if the memory size is below the configured limit.
		 * Reads the log file using WP Filesystem and searches for memory-related error patterns.
		 */
		public function check_memory_limit_errors() {
			global $wp_filesystem;
			try {
				$log_dir = wp_upload_dir()['basedir'] . '/wc-logs/';
				if ( ! is_dir( $log_dir ) ) {
					return;
				}

				$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

				$log_files = glob( $log_dir . "fatal-errors-{$yesterday}-*.log" );

				if ( empty( $log_files ) ) {
					return;
				}

				$log_file     = $log_files[0];
				$log_contents = '';

				if ( empty( $wp_filesystem ) ) {
					require_once ABSPATH . '/wp-admin/includes/file.php';
					WP_Filesystem();
				}

				if ( $wp_filesystem ) {
					$log_contents = $wp_filesystem->get_contents( $log_file );
				}
				$log_lines   = array_reverse( explode( "\n", $log_contents ) );
				$memory_size = null;

				foreach ( $log_lines as $line ) {
					if ( preg_match( '/Allowed memory size of (\d+) bytes exhausted/', $line, $matches ) ) {
						$memory_size = isset( $matches[1] ) ? intval( $matches[1] ) : null;
						break;
					} elseif ( preg_match( '/Out of memory \(allocated (\d+)\)/', $line, $matches ) ) {
						$memory_size = isset( $matches[1] ) ? intval( $matches[1] ) : null;
						break;
					}
				}
				if ( $memory_size && $memory_size < $this->fk_memory_limit ) {
					update_option( 'fk_memory_limit', $memory_size, 'no' );
				}
			} catch ( Exception | Error $e ) {
				return;
			}
		}

		public function add_global_setting_tabs( $menu ) {
			$f_tracking = array(
				'title'    => __( 'First Party Tracking', 'funnel-builder' ),
				'slug'     => 'funnelkit_first_party_tracking',
				'link'     => apply_filters( 'bwf_general_settings_link', 'javascript:void(0)' ),
				'priority' => 6,
				'pro_tab'  => true,
			);

			array_push( $menu, $f_tracking );
			array_push(
				$menu,
				array(
					'title'    => __( 'Pixel Tracking', 'funnel-builder' ),
					'slug'     => 'funnelkit_pixel_tracking',
					'link'     => apply_filters( 'bwf_general_settings_link', 'javascript:void(0)' ),
					'priority' => 7,
				)
			);
			array_push(
				$menu,
				array(
					'title'    => __( 'Advanced', 'funnel-builder' ),
					'slug'     => 'funnelkit_advanced',
					'link'     => apply_filters( 'bwf_general_settings_link', 'javascript:void(0)' ),
					'priority' => 70,
				)
			);
			array_push(
				$menu,
				array(
					'title'    => __( 'Notifications', 'funnel-builder' ),
					'slug'     => 'funnelkit_notifications',
					'link'     => apply_filters( 'bwf_general_settings_link', 'javascript:void(0)' ),
					'priority' => 70,
				)
			);

			return $menu;
		}

		public function add_settings_fields_array( $settings ) {

			$temp_settings         = $settings['woofunnels_general_settings'];
			$pixel_tracking        = array();
			$first_party_tracking  = array();
			$funnelkit_advanced    = array();
			$notification_settings = array(
				'title'   => 'Email Performance Summary',
				'heading' => 'Email Performance Summary',
				'slug'    => 'email_performance_summary',
				'fields'  => array(
					array(
						'key'          => 'bwf_enable_notification',
						'label'        => __( 'Enable Email Performance Summary', 'funnel-builder' ),
						'styleClasses' => 'wffn-tools-toggle bwf-tooglecontrol-advance',
						'type'         => 'toggle',
					),
					array(
						'key'         => 'bwf_notification_frequency',
						'label'       => __( 'Frequency', 'funnel-builder' ),
						'type'        => 'checkbox_grid',
						'class'       => '',
						'placeholder' => '',
						'required'    => false,
						'options'     => array(
							'weekly'  => __( 'Weekly', 'funnel-builder' ),
							'monthly' => __( 'Monthly', 'funnel-builder' ),
						),
						'hint'        => __( 'Emails will be skipped if there are no metrics to show', 'funnel-builder' ),
						'toggler'     => array(
							'key'   => 'bwf_enable_notification',
							'value' => true,
						),
					),
					array(
						'key'                 => 'bwf_notification_user_selector',
						'label'               => __( 'Users', 'funnel-builder' ),
						'type'                => 'search',
						'autocompleter'       => 'users',
						'allowFreeTextSearch' => false,
						'required'            => false,
						'wrap_before'         => '',
						'toggler'             => array(
							'key'   => 'bwf_enable_notification',
							'value' => true,
						),
					),
					array(
						'key'      => 'bwf_external_user',
						'label'    => __( 'Other Recipient', 'funnel-builder' ),
						'type'     => 'addrecipient',
						'class'    => '',
						'required' => false,
						'toggler'  => array(
							'key'   => 'bwf_enable_notification',
							'value' => true,
						),
					),

					array(
						'key'     => 'bwf_notification_time',
						'type'    => 'timeselector',
						'label'   => __( 'Send Time', 'funnel-builder' ),
						'toggler' => array(
							'key'   => 'bwf_enable_notification',
							'value' => true,
						),
					),
					array(
						'key'     => 'send_test_mail',
						'type'    => 'testmail',
						'label'   => '',
						'class'   => 'bwf-position-test-mail-bottom',
						'toggler' => array(
							'key'   => 'bwf_enable_notification',
							'value' => true,
						),
					),
				),
			);

			$values = array();
			foreach ( $notification_settings['fields'] as &$field ) {

				$values[ $field['key'] ] = BWF_Admin_General_Settings::get_instance()->get_option( $field['key'] );
			}
			$notification_settings['values'] = $values;

			$filter_setting = array_filter(
				$temp_settings,
				function ( $v, $k ) use ( &$pixel_tracking, &$first_party_tracking, &$funnelkit_advanced ) {
					if ( in_array( $k, array( 'general', 'permalinks', 'fk_stripe_gateway', 'funnelkit_google_maps' ), true ) ) {
						return true;
					}
					if ( 'funnelkit_advanced' === $k ) {
						$funnelkit_advanced[ $k ] = $v;
					} elseif ( 'utm_parameter' === $k ) {
						$first_party_tracking[ $k ] = $v;
					} else {
						$pixel_tracking[ $k ] = $v;
					}

					return false;
				},
				ARRAY_FILTER_USE_BOTH
			);

			if ( defined( 'WFFN_PRO_VERSION' ) ) {
				$settings['funnelkit_first_party_tracking'] = array(
					array(
						'tabs' => $first_party_tracking,
					),
				);
			}

			$settings['funnelkit_pixel_tracking'] = array(
				array(
					'tabs' => $pixel_tracking,
				),
			);
			$settings['funnelkit_notifications']  = array(
				array(
					'tabs' => array(
						'funnelkit_notifications' => $notification_settings,
					),
				),
			);
			$settings['funnelkit_advanced']       = array(
				array(
					'tabs' => $funnelkit_advanced,
				),
			);

			$permalinks = $filter_setting['permalinks'];

			/**
			 * place upsell permalink after checkout link
			 */
			if ( isset( $permalinks['fields'] ) && is_array( $permalinks['fields'] ) ) {
				$wfocu_key = array_search( 'wfocu_page_base', wp_list_pluck( $permalinks['fields'], 'key' ), true );
				if ( false !== $wfocu_key ) {
					$wfacp_key = array_search( 'checkout_page_base', wp_list_pluck( $permalinks['fields'], 'key' ), true );
					if ( false !== $wfacp_key ) {
						$wfocu_field = $permalinks['fields'][ $wfocu_key ];
						unset( $permalinks['fields'][ $wfocu_key ] );
						array_splice( $permalinks['fields'], $wfacp_key + 1, 0, array( $wfocu_field ) );

					}
				}
			}

			$settings['woofunnels_general_settings'] = array(
				array(
					'heading' => __( 'License', 'funnel-builder' ),
					'tabs'    => array( 'general' => $temp_settings['general'] ),
				),
				array(
					'heading' => __( 'Permalinks', 'funnel-builder' ),
					'tabs'    => array( 'permalinks' => $permalinks ),
				),

				array(
					'heading' => __( 'Google Maps', 'funnel-builder' ),
					'tabs'    => array( 'funnelkit_google_maps' => $temp_settings['funnelkit_google_maps'] ),
				),
			);

			return $settings;
		}

		public function add_automations_menu() {
			$user = WFFN_Role_Capability::get_instance()->user_access( 'menu', 'read' );
			if ( $user ) {
				add_submenu_page(
					'woofunnels',
					__( 'Automations', 'funnel-builder' ),
					__( 'Automations', 'funnel-builder' ) . '<span style="padding-left: 2px;color: #f18200; vertical-align: super; font-size: 9px;"> NEW!</span>',
					$user,
					'bwf&path=/automations',
					array(
						$this,
						'bwf_funnel_pages',
					)
				);
			}
		}

		/**
		 * Add update count badge to FunnelKit main menu
		 * Hooks into admin_head at priority 9999 to run AFTER core woofunnels menu modifications
		 * Core's correct_sub_menu_order() runs at admin_head priority 999
		 * This approach avoids modifying core woofunnels files
		 *
		 * @hooked admin_head - 9999
		 * @return void
		 */
		public function add_menu_update_badge() {
			global $menu;

			// Get the updates count HTML badge
			$updates_count_html = $this->get_updates_count_html();

			// If no badge to show, return early
			if ( empty( $updates_count_html ) ) {
				return;
			}

			// Find and update the FunnelKit menu item
			foreach ( $menu as $key => &$menu_item ) {
				// Check if this is the FunnelKit menu (woofunnels page)
				if ( isset( $menu_item[5] ) && $menu_item[5] === 'toplevel_page_woofunnels' ) {
					// Add the badge to the menu title (index 0)
					$menu_item[0] = $menu_item[0] . $updates_count_html;
					break;
				}
			}
		}

		/**
		 * Get the number of updates available for FunnelKit
		 * Returns 1 when BF or promotional campaigns are active
		 *
		 * @return int Number of updates available
		 */
		public function get_updates_count() {
			// Check if any promotional campaign is active
			if ( $this->is_promotional_campaign_active() ) {
				return 1;
			}

			// No active campaigns
			return 0;
		}

		/**
		 * Check if any promotional campaign is active and NOT dismissed
		 * Follows the complete user flow: Sticky Banner → Transient Cooldown → Admin Notification
		 *
		 * Badge shows if:
		 * - Campaign is active AND
		 * - Transient cooldown is NOT active AND
		 * - Either sticky banner OR admin notification is NOT dismissed
		 *
		 * User Journey:
		 * 1. Sticky shows (not dismissed) → Badge shows
		 * 2. User dismisses sticky → Transient active (5 min) → Badge hidden
		 * 3. Transient expires → Notification shows → Badge shows
		 * 4. User dismisses notification → Badge hidden permanently
		 *
		 * Total 7 campaigns grouped into 3 menu items:
		 * - BF menu: Pre-BF, BF, Small Business Saturday, BF Extended
		 * - CM menu: CM, Extended CM
		 * - Green Monday menu: GM
		 *
		 * @return bool True if campaign active and not fully dismissed, false otherwise
		 */
		public function is_promotional_campaign_active() {
			// Only show for non-PRO users
			if ( defined( 'WFFN_PRO_VERSION' ) ) {
				return false;
			}

			$year            = gmdate( 'Y' );
			$current_user_id = get_current_user_id();
			$notifications   = WFFN_Core()->admin_notifications;

			// Check if transient cooldown is active (5-minute period) - if yes, don't show badge
			$transient = get_transient( 'wffn_sticky_banner_dismissed_' . $current_user_id );
			if ( false !== $transient ) {
				return false;
			}

			// BF group campaigns (4 campaigns)
			if ( $notifications->show_pre_bfcm_header_notification() ) {
				$notification_key = 'promo_bf_pre_' . $year;
				$sticky_key       = 'sticky_pre_bfcm_' . $year;
				// Show badge if sticky is active (not dismissed) OR if notification is active (not dismissed)
				if ( ! $notifications->is_user_dismissed( $current_user_id, $sticky_key )
				|| ! $notifications->is_user_dismissed( $current_user_id, $notification_key ) ) {
					return true;
				}
			}

			if ( $notifications->show_bf_header_notification() ) {
				$notification_key = 'promo_bf_' . $year;
				$sticky_key       = 'sticky_bfcm_' . $year;

				if ( ! $notifications->is_user_dismissed( $current_user_id, $sticky_key )
				|| ! $notifications->is_user_dismissed( $current_user_id, $notification_key ) ) {
					return true;
				}
			}

			if ( $notifications->show_small_business_saturday_header_notification() ) {
				$notification_key = 'promo_bf_sbs_' . $year;
				$sticky_key       = 'sticky_sbs_' . $year;

				if ( ! $notifications->is_user_dismissed( $current_user_id, $sticky_key )
				|| ! $notifications->is_user_dismissed( $current_user_id, $notification_key ) ) {
					return true;
				}
			}

			if ( $notifications->show_bfext_header_notification() ) {
				$notification_key = 'promo_bf_bfext_' . $year;
				$sticky_key       = 'sticky_bfext_' . $year;

				if ( ! $notifications->is_user_dismissed( $current_user_id, $sticky_key )
				|| ! $notifications->is_user_dismissed( $current_user_id, $notification_key ) ) {
					return true;
				}
			}

			// CM group campaigns (2 campaigns)
			if ( $notifications->show_cm_header_notification() ) {
				$notification_key = 'promo_bf_cm_' . $year;
				$sticky_key       = 'sticky_cm_' . $year;

				if ( ! $notifications->is_user_dismissed( $current_user_id, $sticky_key )
				|| ! $notifications->is_user_dismissed( $current_user_id, $notification_key ) ) {
					return true;
				}
			}

			if ( $notifications->show_cmext_header_notification() ) {
				$notification_key = 'promo_bf_cmext_' . $year;
				$sticky_key       = 'sticky_cmext_' . $year;

				if ( ! $notifications->is_user_dismissed( $current_user_id, $sticky_key )
				|| ! $notifications->is_user_dismissed( $current_user_id, $notification_key ) ) {
					return true;
				}
			}

			// Green Monday campaign (1 campaign)
			if ( $notifications->show_green_monday_header_notification() ) {
				$notification_key = 'promo_bf_gm_' . $year;
				$sticky_key       = 'sticky_gm_' . $year;

				if ( ! $notifications->is_user_dismissed( $current_user_id, $sticky_key )
				|| ! $notifications->is_user_dismissed( $current_user_id, $notification_key ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Return the updates count markup for FunnelKit menus
		 *
		 * @return string Updates count markup, empty string if no updates available
		 */
		public function get_updates_count_html() {
			$count = $this->get_updates_count();

			if ( $count <= 0 ) {
				return '';
			}

			$count_html = sprintf(
				' <span class="update-plugins count-%d"><span class="update-count">%d</span></span>',
				$count,
				number_format_i18n( $count )
			);

			return $count_html;
		}

		public function register_admin_menu() {
			$steps = WFFN_Core()->steps->get_supported_steps();
			if ( count( $steps ) < 1 ) {
				return;
			}

			$user = WFFN_Role_Capability::get_instance()->user_access( 'menu', 'read' );
			if ( $user ) {

				add_submenu_page(
					'woofunnels',
					__( 'Dashboard', 'funnel-builder' ),
					__( 'Dashboard', 'funnel-builder' ),
					$user,
					'bwf',
					array(
						$this,
						'bwf_funnel_pages',
					)
				);

				add_submenu_page(
					'woofunnels',
					__( 'Funnels', 'funnel-builder' ),
					__( 'Funnels', 'funnel-builder' ),
					$user,
					'bwf&path=/funnels',
					array(
						$this,
						'bwf_funnel_pages',
					)
				);

				add_submenu_page(
					'woofunnels',
					__( 'Templates', 'funnel-builder' ),
					__( 'Templates', 'funnel-builder' ),
					$user,
					'bwf&path=/templates',
					array(
						$this,
						'bwf_funnel_pages',
					)
				);
				add_submenu_page(
					'woofunnels',
					__( 'Analytics', 'funnel-builder' ),
					__( 'Analytics', 'funnel-builder' ),
					$user,
					'bwf&path=/analytics',
					array(
						$this,
						'bwf_funnel_pages',
					)
				);

				add_submenu_page(
					'woofunnels',
					__( 'Store Checkout', 'funnel-builder' ),
					__( 'Store Checkout', 'funnel-builder' ),
					$user,
					'bwf&path=/store-checkout',
					array(
						$this,
						'bwf_funnel_pages',
					)
				);
			}
		}

		public function is_basic_exists() {
			return defined( 'WFFN_BASIC_FILE' );
		}

		public function bwf_funnel_pages() {

			?>
			<div id="wffn-contacts" class="wffn-page">
			</div>
			<?php

			wp_enqueue_style( 'wffn-flex-admin', $this->get_admin_url() . '/assets/css/admin.css', array(), WFFN_VERSION_DEV );
		}


		public function admin_enqueue_assets( $hook_suffix ) {
			wp_enqueue_style( 'bwf-admin-font', $this->get_admin_url() . '/assets/css/bwf-admin-font.css', array(), WFFN_VERSION_DEV );

			if ( strpos( $hook_suffix, 'woofunnels_page' ) > - 1 || strpos( $hook_suffix, 'page_woofunnels' ) > - 1 ) {
				wp_enqueue_style( 'bwf-admin-header', $this->get_admin_url() . '/assets/css/admin-global-header.css', array(), WFFN_VERSION_DEV );
			}

			if ( $this->is_wffn_flex_page( 'all' ) ) {
				if ( WFFN_Role_Capability::get_instance()->user_access( 'funnel', 'write' ) ) {
					add_filter( 'user_can_richedit', '__return_true', 999 );
				}

				wp_enqueue_style( 'wffn-flex-admin', $this->get_admin_url() . '/assets/css/admin.css', array(), WFFN_VERSION_DEV );

				if ( WFFN_Core()->admin->is_wffn_flex_page() ) {
					$this->load_react_app( 'main-20251209133743' ); //phpcs:ignore WordPressVIPMinimum.Security.Mustache.OutputNotation
					if ( isset( $_GET['page'] ) && $_GET['page'] === 'bwf' && method_exists( 'BWF_Admin_General_Settings', 'get_localized_bwf_data' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						wp_localize_script( 'wffn-contact-admin', 'bwfAdminGen', BWF_Admin_General_Settings::get_instance()->get_localized_bwf_data() );

					} else {
						wp_localize_script( 'wffn-contact-admin', 'bwfAdminGen', BWF_Admin_General_Settings::get_instance()->get_localized_data() );

					}

					add_filter(
						'wffn_noconflict_scripts',
						function ( $scripts = array() ) {
							return array_merge( $scripts, array( 'wffn-contact-admin' ) );
						}
					);
				}

				do_action( 'wffn_admin_assets', $this );
			}
		}

		public function get_local_app_path() {
			return '/admin/views/contact/dist/';
		}

		public function get_editor_priority_theme_name() {
			try {
				$current_theme = strtolower( get_template() );

				// Theme priority mapping
				$theme_map = array(
					'flatsome' => 'Flatsome',
					'avada'    => 'Avada',
					'x'        => 'X',
				);

				// Check for theme priority first
				if ( isset( $theme_map[ $current_theme ] ) ) {
					return $theme_map[ $current_theme ];
				}

				// Plugin priority mapping (page builders)
				$plugin_map = array(
					'breakdance/plugin.php'               => 'Breakdance',
					'thrive-visual-editor/thrive-visual-editor.php' => 'Thrive',
					'bb-plugin/fl-builder.php'            => 'Beaver Builder',
					'js_composer/js_composer.php'         => 'Visual Composer',
					'visualcomposer/visualcomposer.php'   => 'Visual Composer',
					'themify-builder/themify-builder.php' => 'Themify Builder',
					'brizy/brizy.php'                     => 'Brizy',
					'zionbuilder/zionbuilder.php'         => 'Zion',
					'seedprod/seedprod.php'               => 'SeedProd',
				);

				// Check for plugin priority
				foreach ( $plugin_map as $plugin_basename => $plugin_name ) {
					if ( wffn_is_plugin_active( $plugin_basename ) ) {
						return $plugin_name;
					}
				}

				return '';
			} catch ( \Throwable $e ) {
				// Log error and return empty string
				BWF_logger::get_instance()->log( 'Error in get_editor_priority_theme_name: ' . $e->getMessage(), 'wffn_admin' );
				return '';
			}
		}



		public function load_react_app( $app_name = 'main' ) {
			$app_name          = str_replace( '-{{{APP_VERSION}}}', '', $app_name ); //phpcs:ignore WordPressVIPMinimum.Security.Mustache.OutputNotation
			$min               = 60 * get_option( 'gmt_offset' );
			$sign              = $min < 0 ? '-' : '+';
			$absmin            = abs( $min );
			$tz                = sprintf( '%s%02d:%02d', $sign, $absmin / 60, $absmin % 60 );
			$contact_page_data = array(
				'is_wc_active'                => false,
				'date_format'                 => get_option( 'date_format', 'F j, Y' ),
				'time_format'                 => get_option( 'time_format', 'g:i a' ),
				'lev'                         => $this->get_license_config(),
				'app_path'                    => WFFN_Core()->get_plugin_url() . '/admin/views/contact/dist/',
				'timezone'                    => $tz,
				'flag_img'                    => WFFN_Core()->get_plugin_url() . '/admin/assets/img/phone/flags.png',
				'updated_pro_version'         => defined( 'WFFN_PRO_VERSION' ) && version_compare( WFFN_PRO_VERSION, '3.0.0 beta', '>=' ),
				'get_pro_link'                => WFFN_Core()->admin->get_pro_link(),
				'wc_add_product_url'          => admin_url( 'post-new.php?post_type=product' ),
				'admin_url'                   => admin_url(),
				'multilingual'                => $this->is_language_support_enabled(),
				'multilingual_name'           => WFFN_Plugin_Compatibilities::get_language_compatible_plugin(),
				'block_editor_priority_theme' => $this->get_editor_priority_theme_name(),
			);
			if ( class_exists( 'WooCommerce' ) ) {
				$currency                          = get_woocommerce_currency();
				$contact_page_data['currency']     = array(
					'code'              => $currency,
					'precision'         => wc_get_price_decimals(),
					'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
					'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
					'decimalSeparator'  => wc_get_price_decimal_separator(),
					'thousandSeparator' => wc_get_price_thousand_separator(),
					'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
				);
				$contact_page_data['is_wc_active'] = true;
			}

			$frontend_dir = ( 0 === WFFN_REACT_ENVIRONMENT ) ? WFFN_REACT_DEV_URL : WFFN_Core()->get_plugin_url() . $this->get_local_app_path();
			if ( class_exists( 'WooCommerce' ) ) {
				wp_dequeue_style( 'woocommerce_admin_styles' );
				wp_dequeue_style( 'wc-components' );
			}

			$assets_path = 1 === WFFN_REACT_ENVIRONMENT ? WFFN_PLUGIN_DIR . $this->get_local_app_path() . "$app_name.asset.php" : $frontend_dir . "/$app_name.asset.php";
			$assets      = file_exists( $assets_path ) ? include $assets_path : array(
				'dependencies' => array(
					'lodash',
					'moment',
					'react',
					'react-dom',
					'wp-api-fetch',
					'wp-components',
					'wp-compose',
					'wp-date',
					'wp-deprecated',
					'wp-block-editor',
					'wp-block-library',
					'wp-dom',
					'wp-element',
					'wp-hooks',
					'wp-html-entities',
					'wp-i18n',
					'wp-keycodes',
					'wp-polyfill',
					'wp-primitives',
					'wp-url',
					'wp-viewport',
					'wp-color-picker',
					'wp-i18n',
				),
				'version'      => time(),
			);
			$deps        = ( isset( $assets['dependencies'] ) ? array_merge( $assets['dependencies'], array( 'jquery' ) ) : array( 'jquery' ) );
			$version     = $assets['version'];

			$script_deps = array_filter(
				$deps,
				function ( $dep ) {
					return false === strpos( $dep, 'css' );
				}
			);
			if ( 'settings' === $app_name ) {
				$script_deps = array_merge( $script_deps, array( 'wp-color-picker' ) );
			}

			if ( class_exists( 'WFFN_Header' ) ) {
				$header_ins                       = new WFFN_Header();
				$contact_page_data['header_data'] = $header_ins->get_render_data();
			}

			$contact_page_data['localize_texts'] = apply_filters( 'wffn_localized_text_admin', array() );

			wp_enqueue_style( 'wp-components' );
			wp_enqueue_style( 'wffn_material_icons', 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined' );//phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_style( 'wffn-contact-admin', $frontend_dir . "$app_name.css", array(), $version );
			wp_register_script( 'wffn-contact-admin', $frontend_dir . "$app_name.js", $script_deps, $version, true );
			wp_localize_script( 'wffn-contact-admin', 'wffn_contacts_data', $contact_page_data );
			wp_enqueue_script( 'wffn-contact-admin' );
			wp_set_script_translations( 'wffn-contact-admin', 'funnel-builder' );

			$this->setup_js_for_localization( $app_name, $frontend_dir, $script_deps, $version );
			wp_enqueue_editor();
			wp_tinymce_inline_scripts();
			wp_enqueue_media();
		}


		public function get_admin_url() {
			return WFFN_Core()->get_plugin_url() . '/admin';
		}

		public function get_admin_path() {
			return WFFN_PLUGIN_DIR . '/admin';
		}

		/**
		 * @param string $page
		 *
		 * @return bool
		 */
		public function is_wffn_flex_page( $page = 'bwf' ) {

			if ( isset( $_GET['page'] ) && $_GET['page'] === $page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}

			if ( isset( $_GET['page'] ) && 'bwf' === $_GET['page'] && 'all' === $page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}

			return false;
		}


		public function js_variables() {
			if ( $this->is_wffn_flex_page( 'all' ) ) {
				$steps_data               = WFFN_Common::get_steps_data();
				$substeps_data            = WFFN_Common::get_substeps_data();
				$substeps_data['substep'] = true;

				$upsell_exist = function_exists( 'WFOCU_Core' );

				$data = array(
					'steps_data' => $steps_data,
					'substeps'   => $substeps_data,
					'icons'      => array(
						'error_cross'   => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" class="wffn_loader wffn_loader_error">
                        <circle fill="#e6283f" stroke="#e6283f" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1" class="path circle"></circle>
                        <line fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3" class="path line"></line>
                        <line fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2" class="path line"></line>
                    </svg>',
						'success_check' => '<svg class="wffn_loader wffn_loader_ok" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                                <circle class="path circle" fill="#13c37b" stroke="#13c37b" stroke-width="5" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"></circle>
                                <polyline class="path check" fill="none" stroke="#ffffff" stroke-width="9" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "></polyline>
                            </svg>',
						'delete_alert'  => '<div class="swal2-header wf_funnel-icon-without-swal"><div class="swal2-icon swal2-warning swal2-animate-warning-icon" style="display: flex;"><span class="swal2-icon-text">!</span></div></div>',
					),
				);

				$data['settings_texts'] = apply_filters( 'wffn_funnel_settings', array() );

				$data['i18n'] = array(
					'plugin_activate' => __( 'Activating plugin...', 'funnel-builder' ),
					'plugin_install'  => __( 'Installing plugin...', 'funnel-builder' ),
					'preparingsteps'  => __( 'Preparing steps...', 'funnel-builder' ),
					'redirecting'     => __( 'Redirecting...', 'funnel-builder' ),
					'importing'       => __( 'Importing...', 'funnel-builder' ),
					'custom_import'   => __( 'Setting up your funnel...', 'funnel-builder' ),
					'ribbons'         => array(
						'lite' => __( 'Lite', 'funnel-builder' ),
						'pro'  => __( 'PRO', 'funnel-builder' ),
					),
					'test'            => __( 'Test', 'funnel-builder' ),
				);
				if ( wffn_is_wc_active() && false === $upsell_exist ) {
					$data['wc_upsells'] = array(
						'type'      => 'wc_upsells',
						'group'     => WFFN_Steps::STEP_GROUP_WC,
						'title'     => __( 'One Click Upsells', 'funnel-builder' ),
						'desc'      => __( 'Deploy post purchase one click upsells to increase average order value', 'funnel-builder' ),
						'dashicons' => 'dashicons-tag',
						'icon'      => 'tags',
						'pro'       => true,
					);
				}

				$data['welcome_note_dismiss'] = get_user_meta( get_current_user_id(), '_wffn_welcome_note_dismissed', true );//phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_get_user_meta
				$data['is_bump_dismissed']    = get_user_meta( get_current_user_id(), '_wffn_bump_promotion_hide', true );//phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_get_user_meta
				$data['is_upsell_dismissed']  = get_user_meta( get_current_user_id(), '_wffn_upsell_promotion_hide', true );//phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_get_user_meta

				$data['current_logged_user'] = get_current_user_id();
				$data['is_rtl']              = is_rtl();

				$data['is_ab_experiment']         = class_exists( 'BWFABT_Core' ) ? 1 : 0;
				$data['is_ab_experiment_support'] = ( class_exists( 'BWFABT_Core' ) && version_compare( BWFABT_VERSION, '1.3.5', '>' ) ) ? 1 : 0;
				$data['wizard_status']            = $this->is_wizard_available();

				$data['automation_plugin_status']           = WFFN_Common::get_plugin_status( 'wp-marketing-automations/wp-marketing-automations.php' );
				$data['fkcart_img_url']                     = esc_url( plugin_dir_url( WFFN_PLUGIN_FILE ) . 'admin/assets/img/fkcart-img.png' );
				$data['fkcart_plugin_status']               = WFFN_Common::get_plugin_status( 'cart-for-woocommerce/plugin.php' );
				$data['fkcart_show_analytics']              = ( defined( 'FKCART_DB_VERSION' ) && version_compare( FKCART_DB_VERSION, '1.7.2', '>=' ) ) ? 'yes' : 'no';
				$data['ob_arrow_blink_img_url']             = esc_url( plugin_dir_url( WFFN_PLUGIN_FILE ) . 'admin/assets/img/arrow-blink.gif' );
				$data['pro_modal_img_path']                 = esc_url( plugin_dir_url( WFFN_PLUGIN_FILE ) . 'admin/assets/img/pro_modal/' );
				$data['admin_img_path']                     = esc_url( plugin_dir_url( WFFN_PLUGIN_FILE ) . 'admin/assets/img/' );
				$bwf_notifications                          = get_user_meta( get_current_user_id(), '_bwf_notifications_close', true ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_get_user_meta
				$bwf_notifications                          = is_array( $bwf_notifications ) ? array_values( $bwf_notifications ) : $bwf_notifications;
				$data['user_preferences']                   = array( 'notices_close' => $bwf_notifications );
				$data['user_has_notifications']             = WFFN_Core()->admin_notifications->user_has_notifications( get_current_user_id() );
				$data['pro_link']                           = $this->get_pro_link();
				$data['upgrade_button_text']                = __( 'Upgrade to PRO Now', 'funnel-builder' );
				$data['site_options']                       = get_option( 'fb_site_options', array() );
				$data['nonce_contact_export_download']      = wp_create_nonce( 'bwf_contact_export_download' );
				$data['user_display_name']                  = get_user_by( 'id', get_current_user_id() )->display_name;
				$data['user_email']                         = get_user_by( 'id', get_current_user_id() )->user_email;
				$data['bfcm_promo_sticky_banner']           = WFFN_Core()->admin_notifications->get_sticky_banner();
				$data['bfcm_promo_sticky_secondary_banner'] = WFFN_Core()->admin_notifications->get_sticky_secondary_banner();

				$data['pro_version_number']     = defined( 'WFFN_PRO_VERSION' ) ? WFFN_PRO_VERSION : '0.0.0';
				$data['cart_analytics_version'] = '3.11.0';

				$data['review_count']    = WFFN_REVIEW_RATING_COUNT;
				$data['feature_enabled'] = array(
					'funnel_categories' => defined( 'WFFN_PRO_VERSION' ) && version_compare( WFFN_PRO_VERSION, '3.11.0', '>=' ),
				);

				?>
				<script>window.wffn = <?php echo wp_json_encode( apply_filters( 'wffn_localize_admin', $data ) ); ?>;</script>
				<script>
					<?php
					echo '
						(function() {
							setTimeout(() => {
							const scriptElement = document.getElementById("wffn-contact-admin-js");
							if (scriptElement) {

								scriptElement.onerror = function() {
									if (typeof window.wffn_loaded === "undefined") {
										console.warn("Main JS not loaded, retrying with version parameter...");

										const newScript = document.createElement("script");
										const version = `?ver=${new Date().getTime()}`;
										newScript.src = `${scriptElement.src.split("?")[0]}${version}`;
										newScript.id = "wffn-contact-admin-js";
										newScript.async = true;

										document.body.appendChild(newScript);
									}
								};
							}
							}, 3000)
						})();
					'
					?>
				</script>
				<?php
			}
		}

		/**
		 * Get the already setup funnel object
		 *
		 * @return WFFN_Funnel
		 */
		public function get_funnel( $funnel_id = 0 ) {
			if ( $funnel_id > 0 ) {
				if ( $this->funnel instanceof WFFN_Funnel && $funnel_id === $this->funnel->get_id() ) {
					return $this->funnel;
				}
				$this->initiate_funnel( $funnel_id );
			}
			if ( $this->funnel instanceof WFFN_Funnel ) {
				return $this->funnel;
			}
			$this->funnel = new WFFN_Funnel( $funnel_id );

			return $this->funnel;
		}

		/**
		 * @param $funnel_id
		 */
		public function initiate_funnel( $funnel_id ) {
			if ( ! empty( $funnel_id ) ) {
				$this->funnel = new WFFN_Funnel( $funnel_id );

			}
		}

		public static function get_template_filter() {

			$options = array(
				'all'   => __( 'All', 'funnel-builder' ),
				'sales' => __( 'Sales', 'funnel-builder' ),
				'optin' => __( 'Optin', 'funnel-builder' ),
			);

			return $options;
		}


		public function get_license_status() {
			$license_key = WFFN_Core()->remote_importer->get_license_key( true );

			if ( empty( $license_key ) ) {
				return false;
			} elseif ( isset( $license_key['manually_deactivated'] ) && 1 === $license_key['manually_deactivated'] ) {
				return 'deactiavted';
			} elseif ( isset( $license_key['expired'] ) && 1 === $license_key['expired'] ) {
				return 'expired';
			} elseif ( isset( $license_key['activated'] ) && 0 === $license_key['activated'] ) {
				return 'not-active';
			}

			return true;
		}

		public function is_license_active() {
			return true === $this->get_license_status();
		}


		/**
		 * @hooked over `admin_enqueue_scripts`
		 * Check the environment and register appropiate node for the breadcrumb to process
		 * @since 1.0.0
		 */
		public function maybe_register_breadcrumb_nodes() {
			$single_link = '';
			$funnel      = null;
			/**
			 * IF its experiment builder UI
			 */
			if ( $this->is_wffn_flex_page() ) {

				$funnel = $this->get_funnel();

			} else {

				/**
				 * its its a page where experiment page is a referrer
				 */
				$get_ref = filter_input( INPUT_GET, 'funnel_id', FILTER_UNSAFE_RAW ); //phpcs:ignore WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter
				$get_ref = apply_filters( 'maybe_setup_funnel_for_breadcrumb', $get_ref );
				if ( ! empty( $get_ref ) ) {
					$funnel = $this->get_funnel( $get_ref );
					if ( absint( $funnel->get_id() ) === WFFN_Common::get_store_checkout_id() ) {
						$single_link = WFFN_Common::get_store_checkout_edit_link();
					} else {
						$single_link = WFFN_Common::get_funnel_edit_link( $funnel->get_id() );
					}
				}
			}

			/**
			 * Register nodes
			 */
			if ( ! empty( $funnel ) && null === filter_input( INPUT_GET, 'bwf_exp_ref', FILTER_UNSAFE_RAW ) ) { //phpcs:ignore WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter

				BWF_Admin_Breadcrumbs::register_node(
					array(
						'text' => WFFN_Core()->admin->maybe_empty_title( $funnel->get_title() ),
						'link' => $single_link,
					)
				);
				BWF_Admin_Breadcrumbs::register_ref( 'funnel_id', $funnel->get_id() );

			}
		}


		public function get_date_format() {
			return get_option( 'date_format', '' ) . ' ' . get_option( 'time_format', '' );
		}

		/**
		 * @return array
		 */
		public function get_funnels( $args = array() ) {
			global $wpdb;
			$is_total_query_required = true;
			if ( isset( $args['offset'] ) && ! empty( $args['offset'] ) ) {
				$is_total_query_required = false;
			}
			if ( isset( $args['s'] ) ) {
				$search_str = wffn_clean( $args['s'] );
			} else {
				$search_str = isset( $_REQUEST['s'] ) ? wffn_clean( $_REQUEST['s'] ) : '';  // phpcs:ignore WordPress.Security.NonceVerification , WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			}
			$need_draft_count = $args['need_draft_count'] ?? false;
			if ( isset( $args['status'] ) ) {
				$status = wffn_clean( $args['status'] );
			} else {
				$status = isset( $_REQUEST['status'] ) ? wffn_clean( $_REQUEST['status'] ) : '';  // phpcs:ignore WordPress.Security.NonceVerification , WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			}
			$args['meta'] = isset( $args['meta'] ) ? $args['meta'] : array();
			$limit        = isset( $args['limit'] ) ? absint( $args['limit'] ) : $this->posts_per_page();

			$sql_query = ' FROM {table_name}';

			$args = apply_filters( 'wffn_funnels_args_query', $args );

			if ( isset( $args['meta'] ) && is_array( $args['meta'] ) && ! empty( $args['meta'] ) && ! isset( $args['meta']['compare'] ) ) {
				$args['meta']['compare'] = '=';
			}

			/*
			 * Trying to add join in query base on meta
			 */
			if ( ! empty( $args['meta'] ) ) {
				if ( $args['meta']['compare'] === 'NOT_EXISTS' ) {
					$sql_query .= ' LEFT JOIN ';
				} else {
					$sql_query .= ' INNER JOIN ';
				}
				$sql_query .= '{table_name_meta} ON ( {table_name}.id = {table_name_meta}.bwf_funnel_id ';
				if ( $args['meta']['compare'] === 'NOT_EXISTS' ) {
					$sql_query .= 'AND {table_name_meta}.meta_key = \'' . esc_sql( $args['meta']['key'] ) . '\'';
				}
				$sql_query .= ')';

			}

			/*
			 * Sort list with meta key.
			 */
			if ( ! empty( $args['order_by_meta'] ) ) {
				$sql_query .= " LEFT JOIN {table_name_meta} as order_by_meta
                ON ( {table_name}.id = order_by_meta.bwf_funnel_id AND order_by_meta.meta_key = '" . esc_sql( $args['order_by_meta']['key'] ) . "' )";
			}
			if ( ! empty( $args['categories'] ) && is_array( $args['categories'] ) ) {
				$sql_query .= ' INNER JOIN {table_name_meta} AS cat_meta ON ( {table_name}.id = cat_meta.bwf_funnel_id AND cat_meta.meta_key = "wffn_funnel_category" )';
			}
			/*
			 * where clause start here in query
			 */
			$sql_query .= ' WHERE 1=1';

			if ( ! empty( $args['categories'] ) && is_array( $args['categories'] ) ) {
				$cat_conditions = array();

				foreach ( $args['categories'] as $category ) {
					$clean_category   = wffn_clean( $category );
					$cat_conditions[] = $wpdb->prepare( '(cat_meta.meta_value LIKE %s)', "%$clean_category%" );
				}

				$sql_query .= ' AND (' . implode( ' OR ', $cat_conditions ) . ')';
			}

			if ( ! empty( $status ) && 'all' !== $status ) {
				$status     = ( 'live' === $status ) ? 1 : 0;
				$sql_query .= ' AND `status` = ' . "'$status'";
			}

			if ( ! empty( $search_str ) ) {
				$sql_query .= $wpdb->prepare( ' AND ( `title` LIKE %s OR `desc` LIKE %s )', '%' . $search_str . '%', '%' . $search_str . '%' );
			}
			if ( ! empty( $args['meta'] ) ) {
				if ( $args['meta']['compare'] === 'NOT_EXISTS' ) {

					$sql_query .= ' AND ({table_name_meta}.bwf_funnel_id IS NULL) ';
					if ( false === $is_total_query_required ) {
						$sql_query .= ' GROUP BY {table_name}.id';

					}
				} else {
					$sql_query .= ' AND ( {table_name_meta}.meta_key = \'' . esc_sql( $args['meta']['key'] ) . '\' AND {table_name_meta}.meta_value = \'' . esc_sql( $args['meta']['value'] ) . '\' )';
				}
			}

			if ( ! empty( $args['order_by_meta'] ) ) {
				$sql_query .= ' ORDER BY
                    CASE WHEN order_by_meta.meta_value IS NULL THEN 1 ELSE 0 END,
                    order_by_meta.meta_value ' . esc_sql( $args['order_by_meta']['order'] );
			} else {
				$sql_query .= ' ORDER BY {table_name}.id DESC';
			}

			if ( false === $is_total_query_required ) {
				$sql_query .= ' LIMIT ' . esc_sql( $args['offset'] ) . ', ' . $limit;
			} else {
				$found_funnels = WFFN_Core()->get_dB()->get_results( 'SELECT count({table_name}.id) as count ' . $sql_query );
				$sql_query    .= ' LIMIT ' . 0 . ', ' . $limit;
			}
			$funnel_ids = WFFN_Core()->get_dB()->get_results( 'SELECT {table_name}.id as funnel_id ' . $sql_query );
			$items      = array();

			if ( isset( $args['search_filter'] ) ) {
				foreach ( $funnel_ids as $funnel_id ) {
					$funnel  = new WFFN_Funnel( $funnel_id['funnel_id'] );
					$item    = array(
						'id'   => $funnel->get_id(),
						'name' => $funnel->get_title(),
					);
					$items[] = $item;
				}

				return $items;

			} else {
				foreach ( $funnel_ids as $funnel_id ) {
					$funnel = new WFFN_Funnel( $funnel_id['funnel_id'] );
					$steps  = $funnel->get_steps();
					$view   = ( is_array( $steps ) && count( $steps ) > 0 ) ? get_permalink( $steps[0]['id'] ) : '';
					if ( false !== $need_draft_count || isset( $args['need_steps_data'] ) ) {
						$this->parse_funnels_step_ids( $funnel );
					}
					$item = array(
						'id'          => $funnel->get_id(),
						'title'       => $funnel->get_title(),
						'desc'        => $funnel->get_desc(),
						'date_added'  => $funnel->get_date_added(),
						'last_update' => $funnel->get_last_update_date(),
						'steps'       => ( is_array( $this->step_count_against_fid ) && count( $this->step_count_against_fid ) > 0 ) ? count( array_keys( $this->step_count_against_fid, absint( $funnel->get_id() ), true ) ) : 0,
						//phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						'view_link'   => $view,
						'categories'  => ( defined( 'WFFN_PRO_VERSION' ) ) ? $funnel->get_category() : array(),
					);
					if ( isset( $args['need_steps_data'] ) ) {
						$item['steps_data'] = $steps;
					}
					if ( ! isset( $args['context'] ) || 'listing' !== $args['context'] ) {
						$item['__funnel'] = $funnel;
					}
					$items[] = $item;
				}
				if ( true === $is_total_query_required ) {
					$found_posts = array( 'found_posts' => (int) $found_funnels[0]['count'] );

				} else {
					$found_posts = array();

				}
				if ( false !== $need_draft_count ) {
					$draft_counts = $this->get_draft_steps();
					$items        = array_map(
						function ( $item ) use ( $draft_counts ) {
							$item['draft_count'] = $draft_counts[ $item['id'] ] ?? 0;

							return $item;
						},
						$items
					);

				}
				$found_posts['items'] = $items;

				return apply_filters( 'wffn_funnels_lists', $found_posts );
			}
		}

		/**
		 * @param WFFN_Funnel $funnel
		 *
		 * @return void
		 */
		private function parse_funnels_step_ids( $funnel ) {
			$steps     = $funnel->get_steps();
			$funnel_id = $funnel->get_id();
			foreach ( $steps as $step ) {

				$step_id                            = $step['id'];
				$this->step_against_fid[ $step_id ] = $funnel_id;
				$this->step_count_against_fid[ absint( $step_id ) ] = absint( $funnel_id );

				/**
				 * Handle case of upsells separately
				 */
				if ( $step['type'] === 'wc_upsells' && WFFN_Core()->steps->get_integration_object( 'wc_upsells' ) instanceof WFFN_Step ) {
					$funnel_offers = WFOCU_Core()->funnels->get_funnel_steps( $step['id'] );
					if ( ! empty( $funnel_offers ) && count( $funnel_offers ) > 1 ) {
						$offer_ids = wp_list_pluck( $funnel_offers, 'id' );

						$count = 0;
						foreach ( $offer_ids as $offer_id ) {
							$this->step_against_fid[ absint( $offer_id ) ] = $funnel_id;
							/**
							 * skip first offer in step listing
							 */
							if ( 0 !== $count ) {
								$this->step_count_against_fid[ absint( $offer_id ) ] = absint( $funnel_id );
							}
							++$count;
						}
					}
				}
			}
		}

		private function get_draft_steps() {
			if ( empty( $this->step_against_fid ) ) {
				return array();
			}

			$step_ids = array_keys( $this->step_against_fid );

			global $wpdb;
			$results     = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}posts WHERE 1=1 AND post_status != %s and id IN (" . implode( ',', $step_ids ) . ')', 'publish' ), ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$draft_count = array();
			foreach ( $results as $result ) {
				$step_id   = $result['ID'];
				$funnel_id = $this->step_against_fid[ $step_id ];
				if ( ! isset( $draft_count[ $funnel_id ] ) ) {
					$draft_count[ $funnel_id ] = 1;
				} else {
					++$draft_count[ $funnel_id ];
				}
			}

			return $draft_count;
		}

		public function posts_per_page() {
			return 20;
		}


		public function hide_from_menu() {
			global $submenu;
			foreach ( $submenu as $key => $men ) {
				if ( 'woofunnels' !== $key ) {
					continue;
				}
				foreach ( $men as $k => $d ) {
					if ( 'woofunnels-settings' === $d[2] ) {
						unset( $submenu[ $key ][ $k ] );
					}
				}
			}
		}


		/**
		 * Adding landing pages in homepage display settings
		 *
		 * @param $pages
		 * @param $args
		 *
		 * @return array
		 */
		public function add_landing_in_home_pages( $pages, $args ) {
			if ( is_array( $args ) && isset( $args['name'] ) && 'page_on_front' !== $args['name'] && '_customize-dropdown-pages-page_on_front' !== $args['name'] ) {
				return $pages;
			}

			if ( is_array( $args ) && isset( $args['name'] ) && ( 'page_on_front' === $args['name'] || '_customize-dropdown-pages-page_on_front' === $args['name'] ) ) {
				$landing_pages = get_posts(
					array( //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
					'post_type'   => WFFN_Core()->landing_pages->get_post_type_slug(),
					'numberposts' => 100,
					'post_status' => 'publish',
					)
				);

				$optin_pages = get_posts(
					array( //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
					'post_type'   => WFOPP_Core()->optin_pages->get_post_type_slug(),
					'numberposts' => 100,
					'post_status' => 'publish',
					)
				);

				$pages = array_merge( $pages, $landing_pages, $optin_pages );
			}

			return $pages;
		}


		public function admin_footer_text( $footer_text ) {
			if ( false === WFFN_Role_Capability::get_instance()->user_access( 'funnel', 'read' ) ) {
				return $footer_text;
			}

			$current_screen = get_current_screen();
			$wffn_pages     = array( 'woofunnels_page_bwf', 'woofunnels_page_wffn-settings' );

			// Check to make sure we're on a WooFunnels admin page.
			if ( isset( $current_screen->id ) && apply_filters( 'bwf_funnels_funnels_display_admin_footer_text', in_array( $current_screen->id, $wffn_pages, true ), $current_screen->id ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// Change the footer text.
				$footer_text = sprintf( __( 'Over %1$s 5 star reviews show that FunnelKit users trust our top-rated support for their online business. Do you need help? <a href="%2$s" target="_blank"><b>Contact FunnelKit Support</b></a>', 'funnel-builder' ), WFFN_REVIEW_RATING_COUNT . '+', 'https://funnelkit.com/support/?utm_source=WordPress&utm_medium=Support+Footer&utm_campaign=FB+Lite+Plugin' );

			}

			return $footer_text;
		}

		public function maybe_show_notices() {

			global $wffn_notices;
			foreach ( $wffn_notices as $notice ) {
				echo wp_kses_post( $notice );
			}
		}

		public function remove_all() {

				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
		}

		/**
		 * Hooked over 'plugin_action_links_{PLUGIN_BASENAME}' WordPress hook to add deactivate popup support & add PRO link
		 *
		 * @param array $links array of existing links
		 *
		 * @return array modified array
		 */
		public function plugin_actions( $links ) {
			if ( isset( $links['deactivate'] ) ) {
				$links['deactivate'] .= '<i class="woofunnels-slug" data-slug="' . WFFN_PLUGIN_BASENAME . '"></i>';
			}
			if ( ! defined( 'WFFN_PRO_VERSION' ) ) {
				$link  = add_query_arg(
					array(
						'utm_source'   => 'WordPress',
						'utm_medium'   => 'All+Plugins',
						'utm_campaign' => 'FB+Lite+Plugin',
						'utm_content'  => WFFN_VERSION,
					),
					$this->get_pro_link()
				);
				$links = array_merge(
					array(
						'pro_upgrade' => '<a href="' . $link . '" target="_blank" style="color: #1da867 !important;font-weight:600">' . __( 'Upgrade to Pro', 'funnel-builder' ) . '</a>',
					),
					$links
				);
			}

			return $links;
		}

		/**
		 * Initiate WFFN_Background_Importer class if ActionScheduler class doesn't exist
		 *
		 * @see woofunnels_maybe_update_customer_database()
		 */
		public function wffn_maybe_init_background_updater() {
			if ( class_exists( 'WFFN_Background_Importer' ) ) {

				$this->wffn_updater = new WFFN_Background_Importer();
			}
		}

		/**
		 * @hooked over `admin_init`
		 * This method takes care of template importing
		 * Checks whether there is a need to import
		 * Iterates over define callbacks and passes it to background updater class
		 * Updates templates for all steps of the funnels
		 */
		public function wffn_maybe_run_templates_importer() {
			if ( is_null( $this->wffn_updater ) ) {
				return;
			}
			$funnel_id = get_option( '_wffn_scheduled_funnel_id', 0 );

			if ( $funnel_id > 0 ) { // WPCS: input var ok, CSRF ok.

				$task = 'wffn_maybe_import_funnel_in_background';  // Scanning order table and updating customer tables
				$this->wffn_updater->push_to_queue( $task );
				BWF_Logger::get_instance()->log( '**************START Importing************', 'wffn_template_import' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				$this->wffn_updater->save()->dispatch();
				BWF_Logger::get_instance()->log( 'First Dispatch completed', 'wffn_template_import' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}
		}

		/**
		 * Delete wffn-wizard and redirect install
		 */
		public function reset_wizard() {
			if ( current_user_can( 'manage_options' ) && isset( $_GET['wffn_show_wizard_force'] ) && 'yes' === $_GET['wffn_show_wizard_force'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended

				delete_option( '_wffn_onboarding_completed' );
				delete_user_meta( get_current_user_id(), '_bwf_notifications_close' );
				wp_redirect( $this->wizard_url() );
				exit;

			}
		}

		/**
		 * @return array
		 */
		public function get_all_active_page_builders() {
			$page_builders = array( 'gutenberg', 'elementor', 'divi', 'oxy', 'bricks' );

			return $page_builders;
		}

		/**
		 * Detect available page builders and set default settings
		 *
		 * This method checks for the following page builders in order of priority:
		 * - Elementor (elementor/elementor.php)
		 * - Divi (divi-builder/divi-builder.php or Divi theme)
		 * - Oxygen (oxygen/functions.php)
		 * - Bricks (Bricks theme)
		 *
		 * Only works if current default builder is 'elementor' or blank.
		 *
		 * @return string|null The detected page builder slug or null if none found
		 */
		public function detect_and_set_default_page_builder( $force = false ) {
			try {
				$general_settings = BWF_Admin_General_Settings::get_instance();
				$current_default  = $general_settings->get_option( 'default_selected_builder' );

				// Only proceed if current default is 'elementor' or blank
				if ( ! $force && ! empty( $current_default ) && $current_default !== 'elementor' ) {
					return null;
				}

				$detected_builder = null;
				$page_builders    = array(
					'elementor' => array(
						'plugin_file'    => 'elementor/elementor.php',
						'check_function' => function () {
							return class_exists( '\Elementor\Plugin' );
						},
					),
					'divi'      => array(
						'plugin_file'    => 'divi-builder/divi-builder.php',
						'check_function' => function () {
							return WFFN_Core()->page_builders->is_divi_theme_enabled();
						},
					),
					'oxy'       => array(
						'plugin_file'    => 'oxygen/functions.php',
						'check_function' => function () {
							return class_exists( 'OxygenElement' );
						},
					),
					'bricks'    => array(
						'plugin_file'    => '',
						'check_function' => function () {
							return WFFN_Core()->page_builders->is_bricks_theme_enabled();
						},
					),
				);

				// Check each page builder
				foreach ( $page_builders as $builder_slug => $builder_config ) {
					$is_available = false;

					// Check if plugin file exists and is active
					if ( ! empty( $builder_config['plugin_file'] ) ) {
						$plugin_status = WFFN_Common::get_plugin_status( $builder_config['plugin_file'] );
						$is_available  = ( 'activated' === $plugin_status );
					}

					// If no plugin file or plugin not active, check using custom function
					if ( ! $is_available && isset( $builder_config['check_function'] ) ) {
						$is_available = $builder_config['check_function']();
					}

					if ( $is_available ) {
						$detected_builder = $builder_slug;
						break;
					}
				}

				// Set default builder if detected, otherwise set to blank
				if ( ! empty( $detected_builder ) ) {
					// Only update if no default is set or if it's different from detected
					if ( empty( $current_default ) || $current_default !== $detected_builder ) {
						// Update using the proper settings method
						$general_settings->update_global_settings_fields( array( 'default_selected_builder' => $detected_builder ) );

						// Log the detection for debugging
						BWF_Logger::get_instance()->log(
							sprintf( 'Page builder detected and set as default: %s', $detected_builder ),
							'wffn_page_builder_detection'
						);
					}
				} else {
					// No builder detected, set to blank if not already blank
					if ( ! empty( $current_default ) ) {
						// Update using the proper settings method
						$general_settings->update_global_settings_fields( array( 'default_selected_builder' => '' ) );

						// Log the reset for debugging
						BWF_Logger::get_instance()->log(
							'No page builder detected, default set to blank',
							'wffn_page_builder_detection'
						);
					}
				}

				return $detected_builder;
			} catch ( \Throwable $e ) {
				BWF_Logger::get_instance()->log(
					sprintf( 'Error detecting page builder: %s', $e->getMessage() ),
					'wffn_page_builder_detection'
				);
				return null;
			}
		}

		/**
		 * Get the currently detected/default page builder
		 *
		 * @return string The page builder slug or empty string if none set
		 */
		public function get_detected_page_builder() {
			try {
				$general_settings = BWF_Admin_General_Settings::get_instance();
				$default_builder  = $general_settings->get_option( 'default_selected_builder' );

				// If no default is set, try to detect one

				$detected_builder = $this->detect_and_set_default_page_builder( true );
				$default_builder  = ( $detected_builder !== null ) ? $detected_builder : '';

				// Ensure we always return a string
				return ( is_string( $default_builder ) ) ? $default_builder : '';
			} catch ( \Throwable $e ) {
				BWF_Logger::get_instance()->log(
					sprintf( 'Error getting detected page builder: %s', $e->getMessage() ),
					'wffn_page_builder_detection'
				);
				return '';
			}
		}



		/**
		 * Keep the menu open when editing the flows.
		 * Highlights the wanted admin (sub-) menu items for the CPT.
		 *
		 * @since 1.0.0
		 */
		public function menu_highlight() {
			global $submenu_file;
			$get_ref = filter_input( INPUT_GET, 'funnel_id' );
			if ( ! empty( $get_ref ) && absint( $get_ref ) === WFFN_Common::get_store_checkout_id() ) {
				$submenu_file = 'admin.php?page=bwf&path=/store-checkout'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} elseif ( $get_ref ) {
				$submenu_file = 'admin.php?page=bwf&path=/funnels'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		/**
		 * @param $query WP_Query
		 */
		public function load_page_to_home_page( $query ) {
			if ( $query->is_main_query() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

				$post_type = $query->get( 'post_type' );

				$page_id = $query->get( 'page_id' );

				if ( empty( $post_type ) && ! empty( $page_id ) ) {
					$t_post = get_post( $page_id );
					if ( in_array( $t_post->post_type, array( WFFN_Core()->landing_pages->get_post_type_slug(), WFOPP_Core()->optin_pages->get_post_type_slug() ), true ) ) {
						$query->set( 'post_type', get_post_type( $page_id ) );
					}
				}
			}
		}

		public function check_db_version() {

				include_once plugin_dir_path( WFFN_PLUGIN_FILE ) . 'admin/db/class-wffn-db-tables.php';
				$tables = WFFN_DB_Tables::get_instance();
				$tables->define_tables();
				$tables->add_if_needed();
		}

		/**
		 * @hooked over `admin_init`
		 * This method takes care of database updating process.
		 * Checks whether there is a need to update the database
		 * Iterates over define callbacks and passes it to background updater class
		 */
		public function maybe_update_database_update() {

			try {
				$task_list = array(
					'3.3.1' => array( 'wffn_handle_store_checkout_config' ),
					'3.3.3' => array( 'wffn_alter_conversion_table' ),
					'3.3.4' => array( 'wffn_add_utm_columns_in_conversion_table' ),
					'3.3.5' => array( 'wffn_update_migrate_data_for_currency_switcher' ),
					'3.3.6' => array( 'wffn_alter_conversion_table_add_source' ),
					'3.3.7' => array( 'wffn_update_email_default_settings' ),
					'3.3.8' => array( 'wffn_set_default_value_in_autoload_option' ),
					'3.3.9' => array( 'wffn_cleanup_data_for_conversion' ),
					'3.4.0' => array( 'wffn_detect_and_set_default_page_builder' ),
					'4.0.0' => array( 'wffn_add_order_number_column_in_conversion_table' ),

				);

				$task_list_for_new_install = array(
					'wffn_update_email_default_settings',
					'wffn_set_default_value_in_autoload_option',
					'wffn_detect_and_set_default_page_builder',
				);
				$current_db_version        = get_option( '_wffn_db_version', '0.0.0' );

				/**
				 * 1. Fresh customer with no DB data. -
				 * - no task should run
				 * - direct update
				 * 2. Existing customer with db version less than current with task.
				 * - remaining tasks should run
				 * - update
				 * 3. Existing customer with db version less than current but no task.
				 * - no tasks should run
				 * - update
				 * 4. db version is update with current version.
				 * - return
				 * 5. db version is more than the current version.
				 * - return
				 */

				/**
				 * if the current db version is greater than or equal to the current version then no need to update the database
				 * case 4 and 5
				 */
				if ( version_compare( $current_db_version, WFFN_DB_VERSION, '>=' ) ) {
					return;
				}

				/**
				 * if the current db version is 0.0.0
				 * case 1
				 */
				if ( $current_db_version === '0.0.0' ) {
					foreach ( $task_list_for_new_install as $task ) {
						call_user_func( $task );
					}
					update_option( '_wffn_db_version', WFFN_DB_VERSION, true );

					return;
				}

				if ( ! empty( $task_list ) ) {
					foreach ( $task_list as $version => $tasks ) {
						if ( version_compare( $current_db_version, $version, '<' ) ) {
							foreach ( $tasks as $update_callback ) {

								call_user_func( $update_callback );
								update_option( '_wffn_db_version', $version, true );
								$current_db_version = $version;
							}
						}
					}

					/**
					 * If we do not have any task for the specific DB version then directly update option
					 */
					if ( version_compare( $current_db_version, WFFN_DB_VERSION, '<' ) ) {
						update_option( '_wffn_db_version', WFFN_DB_VERSION, true );
					}

					$this->clear_endpoints_from_cache();

				}
			} catch ( Exception | Error $e ) {
				BWF_Logger::get_instance()->log( $e->getMessage(), 'wffn-db-update-exception', 'buildwoofunnels', true );
			}
		}

		public function settings_config( $config ) {
			$License    = WooFunnels_licenses::get_instance();
			$fields     = array();
			$has_fb_pro = false;
			if ( is_object( $License ) && is_array( $License->plugins_list ) && count( $License->plugins_list ) ) {
				foreach ( $License->plugins_list as $license ) {
					/**
					 * Excluding data for automation and connector addon
					 */
					if ( in_array( $license['product_file_path'], array( '7b31c172ac2ca8d6f19d16c4bcd56d31026b1bd8', '913d39864d876b7c6a17126d895d15322e4fd2e8' ), true ) ) {
						continue;
					}

					$license_data = array();
					if ( isset( $license['_data'] ) && isset( $license['_data']['data_extra'] ) ) {
						$license_data = $license['_data']['data_extra'];
						if ( isset( $license_data['api_key'] ) ) {
							$license_data['api_key'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxx' . substr( $license_data['api_key'], - 6 );
							$license_data['licence'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxx' . substr( $license_data['api_key'], - 6 );
						}
					}

					$data = array(
						'id'                      => $license['product_file_path'],
						'label'                   => $license['plugin'],
						'type'                    => 'license',
						'key'                     => $license['product_file_path'],
						'license'                 => ! empty( $license_data ) ? $license_data : false,
						'is_manually_deactivated' => ( isset( $license['_data']['manually_deactivated'] ) && true === wffn_string_to_bool( $license['_data']['manually_deactivated'] ) ) ? 1 : 0,
						'activated'               => ( isset( $license['_data']['activated'] ) && true === wffn_string_to_bool( $license['_data']['activated'] ) ) ? 1 : 0,
						'expired'                 => ( isset( $license['_data']['expired'] ) && true === wffn_string_to_bool( $license['_data']['expired'] ) ) ? 1 : 0,
					);
					if ( $license['plugin'] === 'FunnelKit Funnel Builder Pro' || $license['plugin'] === 'FunnelKit Funnel Builder Basic' ) {
						$has_fb_pro = true;
						array_unshift( $fields, $data );
					} else {
						$fields[] = $data;
					}
				}
			}

			if ( empty( $has_fb_pro ) ) {
				$field_no_license = array(
					'type'         => 'label',
					'key'          => 'label_no_license',
					'label'        => __( 'FunnelKit Funnel Builder Pro', 'funnel-builder' ),
					'styleClasses' => array( 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ),
				);
				array_unshift( $fields, $field_no_license );
				$field_no_license = array(
					'key'          => 'no_license',
					'type'         => 'upgrade_pro',
					'label'        => __( '<strong>You are currently using FunnelKit Lite, which does not require a license.</strong><br/> To access more features, consider upgrading to FunnelKit PRO now.', 'funnel-builder' ),
					'styleClasses' => array( 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end' ),
					'hint'         => '',
				);
				array_unshift( $fields, $field_no_license );

			} elseif ( is_multisite() ) {

					/**
					 * Check if sitewide installed, if yes then get the plugin info from primary site
					 */
					$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

				if ( is_array( $active_plugins ) && ( in_array( WFFN_PLUGIN_BASENAME, apply_filters( 'active_plugins', $active_plugins ), true ) || array_key_exists( WFFN_PLUGIN_BASENAME, apply_filters( 'active_plugins', $active_plugins ) ) ) && ! is_main_site() ) {
					$fields           = array();
					$field_no_license = array(
						'type'         => 'label',
						'key'          => 'label_no_license',
						'label'        => __( 'FunnelKit Funnel Builder Pro', 'funnel-builder' ),
						'styleClasses' => array( 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ),
					);
					array_unshift( $fields, $field_no_license );
					$main_site_id        = 1; // Main site ID in Multisite
					$main_site_admin_url = get_site_url( $main_site_id, 'wp-admin/admin.php?page=bwf&path=/settings' );
					$field_no_license    = array(
						'key'          => 'no_license',
						'type'         => 'multisite_notice',
						'linkButton'   => esc_url( $main_site_admin_url ),
						'label'        => __( 'You have activated FunnelKit on a multisite network, So the licenses will be managed on the main site and not on the sub sites. ', 'funnel-builder' ),
						'styleClasses' => array( 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end' ),
						'hint'         => '',
					);
					array_unshift( $fields, $field_no_license );
				}
			}

			return array_merge( $fields, $config );
		}


		/**
		 * @param $link
		 * @param BWFABT_Experiment $experiment
		 *
		 * @return string
		 */
		function maybe_modify_link( $link, $experiment ) {

			$get_control_id = $experiment->get_control();

			$get_funnel_id = get_post_meta( $get_control_id, '_bwf_in_funnel', true );

			if ( ! empty( $get_funnel_id ) ) {

				return WFFN_Common::get_experiment_edit_link( $get_funnel_id, $get_control_id );
			}

			return $link;
		}

		/*
		 * @param $post_id
		 * @param $all_meta
		 *
		 * Return selected builder based on post meta when import page
		 * @return string[]
		 */
		public function get_selected_template( $post_id, $all_meta ) {
			$meta = '';
			if ( ! empty( $all_meta ) ) {
				$meta = wp_list_pluck( $all_meta, 'meta_key' );
			}

			$template = array(
				'selected'        => 'wp_editor_1',
				'selected_type'   => 'wp_editor',
				'template_active' => 'yes',
			);

			$selected_template = apply_filters( 'wffn_set_selected_template_on_duplicate', array(), $post_id, $meta );

			if ( is_array( $selected_template ) && count( $selected_template ) > 0 ) {
				return $selected_template;
			}

			if ( is_array( $meta ) ) {
				if ( in_array( '_elementor_data', $meta, true ) ) {
					$template['selected']      = 'elementor_1';
					$template['selected_type'] = 'elementor';

					return $template;
				}
				if ( in_array( '_et_builder_version', $meta, true ) ) {
					$template['selected']      = 'divi_1';
					$template['selected_type'] = 'divi';

					return $template;
				}
				if ( in_array( 'ct_builder_shortcodes', $meta, true ) ) {
					$template['selected']      = 'oxy_1';
					$template['selected_type'] = 'oxy';

					return $template;
				}
			}

			if ( false !== strpos( get_post_field( 'post_content', $post_id ), '<!-- wp:' ) ) {
				$template['selected']      = 'gutenberg_1';
				$template['selected_type'] = 'gutenberg';

				return $template;
			}

			return $template;
		}

		public function get_pro_link() {
			return esc_url( 'https://funnelkit.com/funnel-builder-lite-upgrade/' );
		}

		public function setup_js_for_localization( $app_name, $frontend_dir, $script_deps, $version ) {
			/** enqueue other js file from the dist folder */
			$path = WFFN_PLUGIN_DIR . $this->get_local_app_path();
			foreach ( glob( $path . '*.js' ) as $dist_file ) {
				$file_info = pathinfo( $dist_file );

				if ( $app_name === $file_info['filename'] ) {
					continue;
				}
				wp_register_script( 'wffn_admin_' . $file_info['filename'], $frontend_dir . '' . $file_info['basename'], $script_deps, $version, true );
				wp_set_script_translations( 'wffn_admin_' . $file_info['filename'], 'funnel-builder' );
			}
			add_action(
				'admin_print_footer_scripts',
				function () {

					if ( 0 === WFFN_REACT_ENVIRONMENT ) {
						return;
					}
					$path = WFFN_PLUGIN_DIR . $this->get_local_app_path();
					global $wp_scripts;
					foreach ( glob( $path . '*.js' ) as $dist_file ) {

						$file_info = pathinfo( $dist_file );

						$translations = $wp_scripts->print_translations( 'wffn_admin_' . $file_info['filename'], false );
						if ( $translations ) {
							$translations = sprintf( "<script%s id='%s-js-translations'>\n%s\n</script>\n", '', esc_attr( 'wffn_admin_' . $file_info['filename'] ), $translations );
						}
						echo $translations; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				},
				99999
			);
		}

		/**
		 * @param $post_id
		 * @param $post
		 *
		 * hooked over `before_delete_post`
		 * Checks if funnel step delete, then update associated funnel step meta
		 *
		 * @return void
		 */
		public function delete_funnel_step_permanently( $post_id, $post ) {

			if ( is_null( $post ) ) {
				return;
			}

			if ( ! in_array(
				$post->post_type,
				array(
					'wfacp_checkout',
					'wffn_landing',
					'wffn_ty',
					'wffn_optin',
					'wffn_oty',
				),
				true
			) ) {
				return;
			}

			$get_funnel_id = get_post_meta( $post_id, '_bwf_in_funnel', true );

			if ( empty( $get_funnel_id ) ) {
				return;
			}

			$funnel = new WFFN_Funnel( $get_funnel_id );

			if ( $funnel instanceof WFFN_Funnel ) {
				$funnel->delete_step( $get_funnel_id, $post_id );
			}
		}

		/**
		 * @param $steps
		 * @param $funnel
		 *
		 * Removed step if not exists on funnel steps listing
		 *
		 * @return mixed
		 */
		public function maybe_delete_funnel_step( $steps, $funnel ) {

			if ( ! $funnel instanceof WFFN_Funnel ) {
				return $steps;
			}
			if ( is_array( $steps ) && count( $steps ) > 0 ) {
				foreach ( $steps as $key => &$step ) {

					/**
					 * Skip if store funnel have native checkout
					 */
					if ( absint( $funnel->get_id() ) === WFFN_Common::get_store_checkout_id() && WFFN_Common::store_native_checkout_slug() === $step['type'] ) {
						continue;
					}

					/**
					 * IF current step post not exist, then remove this step from funnel meta
					 */
					if ( 0 <= $step['id'] && ! get_post( $step['id'] ) instanceof WP_Post ) {
						unset( $steps[ $key ] );
						$funnel->delete_step( $funnel->get_id(), $step['id'] );
					}
				}
			}

			return $steps;
		}

		/**
		 * Add a toolbar menu funnelkit and all submenu
		 *
		 * @param WP_Admin_Bar $wp_admin_bar
		 *
		 * @return void
		 * @throws Exception
		 */
		public function add_menu_in_admin_bar( WP_Admin_Bar $wp_admin_bar ) {
			$user = WFFN_Role_Capability::get_instance()->user_access( 'menu', 'read' );
			if ( ! $user ) {
				return;
			}
			global $post;
			$wp_admin_bar->add_node(
				array(
					'id'    => 'wffn_funnel',
					'title' => 'FunnelKit',
					'href'  => admin_url( 'admin.php?page=bwf&path=/funnels' ),
				)
			);
			$admin_sub_nodes = array(
				array(
					'id'     => 'fk_funnel',
					'parent' => 'wffn_funnel',
					'title'  => __( 'Funnels', 'funnel-builder' ),
					'href'   => admin_url( 'admin.php?page=bwf&path=/funnels' ),
				),
				array(
					'id'     => 'fk_funnel_store_checkout',
					'parent' => 'wffn_funnel',
					'title'  => __( 'Store Checkout', 'funnel-builder' ),
					'href'   => admin_url( 'admin.php?page=bwf&path=/store-checkout' ),
				),
				array(
					'id'     => 'fk_funnel_analytics',
					'parent' => 'wffn_funnel',
					'title'  => __( 'Analytics', 'funnel-builder' ),
					'href'   => admin_url( 'admin.php?page=bwf&path=/analytics' ),
				),
				array(
					'id'     => 'fk_funnel_templates',
					'parent' => 'wffn_funnel',
					'title'  => __( 'Templates', 'funnel-builder' ),
					'href'   => admin_url( 'admin.php?page=bwf&path=/templates' ),
				),
				array(
					'id'     => 'fk_funnel_settings',
					'parent' => 'wffn_funnel',
					'title'  => __( 'Settings', 'funnel-builder' ),
					'href'   => admin_url( 'admin.php?page=bwf&path=/settings' ),
				),

			);

			$wffn_steps = array(
				'wffn_landing'   => array(
					'slug'  => 'funnel-landing',
					'title' => __( 'Edit Sales', 'funnel-builder' ),
				),
				'wfacp_checkout' => array(
					'slug'  => 'funnel-checkout',
					'title' => __( 'Edit Checkout', 'funnel-builder' ),
				),
				'wfocu_offer'    => array(
					'slug'  => 'funnel-offer',
					'title' => __( 'Edit Offer', 'funnel-builder' ),
				),
				'wffn_ty'        => array(
					'slug'  => 'funnel-thankyou',
					'title' => __( 'Edit Thank You', 'funnel-builder' ),
				),
				'wffn_optin'     => array(
					'slug'  => 'funnel-optin',
					'title' => __( 'Edit Optin', 'funnel-builder' ),
				),
				'wffn_oty'       => array(
					'slug'  => 'funnel-optin-confirmation',
					'title' => __( 'Edit Optin Confirmation', 'funnel-builder' ),
				),
			);
			if ( ! is_null( $post ) && ! empty( $post->post_type ) && isset( $wffn_steps[ $post->post_type ] ) ) {
				$step = $wffn_steps[ $post->post_type ];

				if ( 'wfocu_offer' === $post->post_type ) {
					$upsell_id = get_post_meta( $post->ID, '_funnel_id', true );
					$funnel_id = get_post_meta( $upsell_id, '_bwf_in_funnel', true );
				} else {
					$funnel_id = get_post_meta( $post->ID, '_bwf_in_funnel', true );
				}
				if ( ! empty( $funnel_id ) && abs( $funnel_id ) !== 0 ) {
					$funnel_link = BWF_Admin_Breadcrumbs::maybe_add_refs(
						add_query_arg(
							array(
								'page' => 'bwf',
								'path' => '/funnels/' . $funnel_id . '/steps',
							),
							admin_url( 'admin.php' )
						)
					);

					$step_link = BWF_Admin_Breadcrumbs::maybe_add_refs(
						add_query_arg(
							array(
								'page'      => 'bwf',
								'path'      => '/' . $step['slug'] . '/' . $post->ID . '/design',
								'funnel_id' => $funnel_id,
							),
							admin_url( 'admin.php' )
						)
					);

					/**
					 * Add submenu for the frontend if its our step
					 */
					$wp_admin_bar->add_menu(
						array(
							'id'     => 'wffn_edit_funnel',
							'parent' => 'wffn_funnel',
							'title'  => __( 'Edit Funnel', 'funnel-builder' ),
							'href'   => $funnel_link,
						)
					);

					$wp_admin_bar->add_menu(
						array(
							'id'     => 'wffn_edit_step',
							'parent' => 'wffn_funnel',
							'title'  => $step['title'],
							'href'   => $step_link,
						)
					);
					?>
					<style type="text/css">
						ul#wp-admin-bar-wffn_funnel-default li#wp-admin-bar-wffn_edit_step {
							margin-bottom: 5px;
							padding-bottom: 5px;
							border-bottom: 1px dashed #65686b;
						}
					</style>
					<?php
				}
			}

			/**
			 * Add all sub nodes which are static
			 */
			foreach ( $admin_sub_nodes as $node ) {
				$wp_admin_bar->add_menu( $node );
			}

			/**
			 * Possibly add sub nodes for different pro states
			 */
			if ( ! defined( 'WFFN_PRO_VERSION' ) ) {
				$link = add_query_arg(
					array(
						'utm_source'   => 'WordPress',
						'utm_medium'   => 'Toolbar+Menu',
						'utm_campaign' => 'FB+Lite+Plugin',
					),
					WFFN_Core()->admin->get_pro_link()
				);
				$wp_admin_bar->add_menu(
					array(
						'id'     => 'wffn_funnel-additional_lite',
						'parent' => 'wffn_funnel',
						'title'  => __( 'Upgrade to Pro', 'funnel-builder' ),
						'href'   => $link,
					)
				);
				?>
				<style type="text/css">
					ul#wp-admin-bar-wffn_funnel-default li#wp-admin-bar-wffn_funnel-additional_lite a.ab-item {
						color: white;
						background-color: #1DA867;
					}
				</style>
				<?php
			} else {
				$License = WooFunnels_licenses::get_instance();
				$License->get_plugins_list();
				$current = new DateTime( current_time( 'mysql', true ) );

				$a = WFFn_Core()->admin->get_license_config( true );

				if ( ! empty( $a['f']['ed'] ) ) {

					$expiry = new DateTime( $a['f']['ed'] );

					/**
					 * the expiry should always be less than on current utc
					 */
					if ( $expiry->getTimestamp() < $current->getTimestamp() ) {
						$link = add_query_arg(
							array(
								'utm_source'   => 'WordPress',
								'utm_medium'   => 'Toolbar+Menu',
								'utm_campaign' => 'FB+Lite+Plugin',
							),
							'https://funnelkit.com/my-account/'
						);

						$wp_admin_bar->add_menu(
							array(
								'id'     => 'wffn_funnel-license',
								'parent' => 'wffn_funnel',
								'title'  => __( 'License Expired', 'funnel-builder' ),
								'href'   => $link,
							)
						);
					}
				}
				?>
				<style type="text/css">
					ul#wp-admin-bar-wffn_funnel-default li#wp-admin-bar-wffn_funnel-license a.ab-item {
						color: white;
						background-color: #e15334;
					}
				</style>
				<?php
			}
		}

		/**
		 * @param $title
		 * set default post title if post tile empty
		 *
		 * @return mixed|string|null
		 */
		public function maybe_empty_title( $title ) {
			if ( empty( $title ) ) {
				return __( '(no title)', 'funnel-builder' );
			}

			return $title;
		}


		public function wizard_url() {
			return admin_url( 'admin.php?page=bwf&path=/user-setup' );
		}

		/**
		 * Filter CB to alter response data to pass connect link to the REST cll
		 *
		 * @param array  $response
		 * @param string $basename
		 *
		 * @return mixed
		 */
		public function maybe_add_auth_link_stripe( $response, $basename ) {
			if ( 'funnelkit-stripe-woo-payment-gateway/funnelkit-stripe-woo-payment-gateway.php' === $basename ) {
				$response['next_action'] = 'funnelkit-app/stripe-connect-link';
			}

			return $response;
		}


		public function license_fb_pro_data() {

			$License = WooFunnels_licenses::get_instance();

			$data = array();
			if ( is_object( $License ) && is_array( $License->plugins_list ) && count( $License->plugins_list ) ) {
				foreach ( $License->plugins_list as $license ) {
					/**
					 * Excluding data for automation and connector addon
					 */
					if ( in_array( $license['product_file_path'], array( '7b31c172ac2ca8d6f19d16c4bcd56d31026b1bd8', '913d39864d876b7c6a17126d895d15322e4fd2e8' ), true ) ) {
						continue;
					}

					$license_data = array();
					if ( isset( $license['_data'] ) && isset( $license['_data']['data_extra'] ) ) {
						$license_data = $license['_data']['data_extra'];
						if ( isset( $license_data['api_key'] ) ) {
							$license_data['api_key'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxx' . substr( $license_data['api_key'], - 6 );
							$license_data['licence'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxx' . substr( $license_data['api_key'], - 6 );
						}
					}
					if ( $license['plugin'] === 'FunnelKit Funnel Builder Pro' || $license['plugin'] === 'FunnelKit Funnel Builder Basic' ) {
						$data = array(
							'id'                      => $license['product_file_path'],
							'label'                   => $license['plugin'],
							'type'                    => 'license',
							'key'                     => $license['product_file_path'],
							'license'                 => ! empty( $license_data ) ? $license_data : false,
							'is_manually_deactivated' => ( isset( $license['_data']['manually_deactivated'] ) && true === bwf_string_to_bool( $license['_data']['manually_deactivated'] ) ) ? 1 : 0,
							'activated'               => ( isset( $license['_data']['activated'] ) && true === bwf_string_to_bool( $license['_data']['activated'] ) ) ? 1 : 0,
							'expired'                 => ( isset( $license['_data']['expired'] ) && true === bwf_string_to_bool( $license['_data']['expired'] ) ) ? 1 : 0,
						);

					}
				}
			}

			return $data;
		}

		public function get_license_expiry() {

			$licenses = $this->license_fb_pro_data();

			if ( empty( $licenses ) || empty( $licenses['license'] ) ) {
				return '';
			}

			$expiry = $licenses['license']['expires'];
			if ( '' === $expiry ) {
				return gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
			}

			return $expiry;
		}


		public function license_data( $hash ) {

			$License = WooFunnels_licenses::get_instance();
			if ( is_object( $License ) && is_array( $License->plugins_list ) && count( $License->plugins_list ) ) {
				foreach ( $License->plugins_list as $license ) {
					if ( $license['product_file_path'] !== $hash ) {
						continue;
					}
					if ( isset( $license['_data'] ) && isset( $license['_data']['data_extra'] ) ) {
						$license_data = $license['_data']['data_extra'];

						return array(
							'id'                      => $license['product_file_path'],
							'label'                   => $license['plugin'],
							'type'                    => 'license',
							'key'                     => $license['product_file_path'],
							'license'                 => ! empty( $license_data ) ? $license_data : false,
							'is_manually_deactivated' => ( isset( $license['_data']['manually_deactivated'] ) && true === bwf_string_to_bool( $license['_data']['manually_deactivated'] ) ) ? 1 : 0,
							'activated'               => ( isset( $license['_data']['activated'] ) && true === bwf_string_to_bool( $license['_data']['activated'] ) ) ? 1 : 0,
							'expired'                 => ( isset( $license['_data']['expired'] ) && true === bwf_string_to_bool( $license['_data']['expired'] ) ) ? 1 : 0,
						);
					}
				}
			}

			return array();
		}

		public function is_license_active_for_checkout() {
			$hashes = $this->get_license_hashes();

			if ( $this->is_basic_exists() ) {
				$license_basic = $this->license_data( $hashes['basic'] );

				if ( empty( $license_basic ) ) {
					return false;
				} elseif ( isset( $license_basic['is_manually_deactivated'] ) && 1 === $license_basic['is_manually_deactivated'] ) {
					return 'deactiavted';
				} elseif ( isset( $license_basic['expired'] ) && 1 === $license_basic['expired'] ) {
					return 'expired';
				} elseif ( isset( $license_basic['activated'] ) && 0 === $license_basic['activated'] ) {
					return 'not-active';
				}

				return true;
			}

			if ( defined( 'WFFN_PRO_VERSION' ) & ! $this->is_basic_exists() ) {
				$license_pro = $this->license_data( $hashes['pro'] );
				if ( empty( $license_pro ) ) {
					return false;
				} elseif ( isset( $license_pro['is_manually_deactivated'] ) && 1 === $license_pro['is_manually_deactivated'] ) {
					return 'deactiavted';
				} elseif ( isset( $license_pro['expired'] ) && 1 === $license_pro['expired'] ) {
					return 'expired';
				} elseif ( isset( $license_pro['activated'] ) && 0 === $license_pro['activated'] ) {
					return 'not-active';
				}

				return true;
			}

			if ( class_exists( 'WFACP_Core' ) ) {
				$license_checkout = $this->license_data( $hashes['checkout'] );

				if ( empty( $license_checkout ) ) {
					return false;
				} elseif ( isset( $license_checkout['is_manually_deactivated'] ) && 1 === $license_checkout['is_manually_deactivated'] ) {
					return 'deactiavted';
				} elseif ( isset( $license_checkout['expired'] ) && 1 === $license_checkout['expired'] ) {
					return 'expired';
				} elseif ( isset( $license_checkout['activated'] ) && 0 === $license_checkout['activated'] ) {
					return 'not-active';
				}

				return true;
			}

			return false;
		}

		public function get_license_expiry_for_checkout() {
			$hashes = $this->get_license_hashes();

			if ( $this->is_basic_exists() ) {
				$licenses = $this->license_data( $hashes['basic'] );
				if ( empty( $licenses ) || empty( $licenses['license'] ) ) {
					return '';
				}

				if ( '' === $licenses['license']['expires'] ) {
					return gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
				}

				return $licenses['license']['expires'];
			}

			if ( defined( 'WFFN_PRO_VERSION' ) & ! $this->is_basic_exists() ) {
				$licenses = $this->license_data( $hashes['pro'] );
				if ( empty( $licenses ) || empty( $licenses['license'] ) ) {
					return '';
				}

				if ( '' === $licenses['license']['expires'] ) {
					return gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
				}

				return $licenses['license']['expires'];
			}

			if ( class_exists( 'WFACP_Core' ) ) {
				$licenses = $this->license_data( $hashes['checkout'] );
				if ( empty( $licenses ) || empty( $licenses['license'] ) ) {
					return '';
				}

				if ( '' === $licenses['license']['expires'] ) {
					return gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
				}

				return $licenses['license']['expires'];
			}

			return false;
		}

		public function is_license_active_for_upsell() {
			$hashes = $this->get_license_hashes();

			if ( defined( 'WFFN_PRO_VERSION' ) & ! $this->is_basic_exists() ) {
				$license_pro = $this->license_data( $hashes['pro'] );

				if ( empty( $license_pro ) ) {
					return false;
				} elseif ( isset( $license_pro['is_manually_deactivated'] ) && 1 === $license_pro['is_manually_deactivated'] ) {
					return 'deactiavted';
				} elseif ( isset( $license_pro['expired'] ) && 1 === $license_pro['expired'] ) {
					return 'expired';
				} elseif ( isset( $license_pro['activated'] ) && 0 === $license_pro['activated'] ) {
					return 'not-active';
				}

				return true;
			}

			if ( class_exists( 'WFOCU_Core' ) ) {
				$license_upsells = $this->license_data( $hashes['upsell'] );

				if ( empty( $license_upsells ) ) {
					return false;
				} elseif ( isset( $license_upsells['is_manually_deactivated'] ) && 1 === $license_upsells['is_manually_deactivated'] ) {
					return 'deactiavted';
				} elseif ( isset( $license_upsells['expired'] ) && 1 === $license_upsells['expired'] ) {
					return 'expired';
				} elseif ( isset( $license_upsells['activated'] ) && 0 === $license_upsells['activated'] ) {
					return 'not-active';
				}

				return true;
			}

			return false;
		}

		public function get_license_expiry_for_upsell() {
			$hashes = $this->get_license_hashes();
			if ( defined( 'WFFN_PRO_VERSION' ) & ! $this->is_basic_exists() ) {
				$licenses = $this->license_data( $hashes['pro'] );
				if ( empty( $licenses ) || empty( $licenses['license'] ) ) {
					return '';
				}

				if ( '' === $licenses['license']['expires'] ) {
					return gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
				}

				return $licenses['license']['expires'];
			}

			if ( class_exists( 'WFOCU_Core' ) ) {
				$licenses = $this->license_data( $hashes['upsell'] );
				if ( empty( $licenses ) || empty( $licenses['license'] ) ) {
					return '';
				}

				if ( '' === $licenses['license']['expires'] ) {
					return gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
				}

				return $licenses['license']['expires'];
			}

			return '';
		}

		public function get_license_hashes() {
			return array(
				'checkout' => '742fc61c1b455e2b1efa4154a92da8fb7f9866d3',
				'upsell'   => 'e837ebc716ca979006da34eecdce9f650ced6bef',
				'pro'      => 'ffec4bb68f0841db41213ce12305aaef7e0237f3',
				'basic'    => 'e234ca9ec3e4856bb05ea9f8ec90e7f3831b05c5',

			);
		}


		public function blocks_incompatible_switch_to_classic_cart_checkout( $is_rest = false ) {

			if ( ! class_exists( '\Automattic\WooCommerce\Blocks\BlockTypes\ClassicShortcode' ) || // Make sure WC version is at least 8.3. This class is added at version 8.3.
				! current_user_can( 'manage_options' ) || ( empty( $is_rest ) && false === check_ajax_referer( 'wffn_blocks_incompatible_switch_to_classic', 'nonce', false ) ) ) {
				return;
			}

			$wc_cart_page     = get_post( wc_get_page_id( 'cart' ) );
			$wc_checkout_page = get_post( wc_get_page_id( 'checkout' ) );

			if ( has_block( 'woocommerce/checkout', $wc_checkout_page ) ) {
				wp_update_post(
					array(
						'ID'           => $wc_checkout_page->ID,
						'post_content' => '<!-- wp:woocommerce/classic-shortcode {"shortcode":"checkout"} /-->',
					)
				);
			}

			if ( has_block( 'woocommerce/cart', $wc_cart_page ) ) {
				wp_update_post(
					array(
						'ID'           => $wc_cart_page->ID,
						'post_content' => '<!-- wp:woocommerce/classic-shortcode {"shortcode":"cart"} /-->',
					)
				);
			}

			$userdata   = get_user_meta( get_current_user_id(), '_bwf_notifications_close', true );
			$userdata   = empty( $userdata ) && ! is_array( $userdata ) ? array() : $userdata;
			$userdata[] = 'wc_block_incompat';
			update_user_meta( get_current_user_id(), '_bwf_notifications_close', array_values( array_unique( $userdata ) ) ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_update_user_meta

			if ( ! empty( $is_rest ) ) {
				return rest_ensure_response( array( 'success' => true ) );

			}
			$redirect     = isset( $_REQUEST['redirect'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect'] ) ) : null;
			$redirect_url = $redirect && strpos( $redirect, '.php' ) ? admin_url( $redirect ) : null;

			wp_safe_redirect( $redirect_url ?? admin_url( 'admin.php?page=bwf&path=/funnels' ) );
			exit;
		}

		/**
		 * AJAX dismiss admin notice.
		 *
		 * @since 1.1
		 * @since 4.5.1 Add nonce verification when dismissing notices.
		 * @access public
		 */
		public function ajax_dismiss_admin_notice() {
			$notice_key = isset( $_REQUEST['nkey'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nkey'] ) ) : '';

			if (
				defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( 'manage_options' ) && $notice_key && isset( $_REQUEST['nonce'] ) && false !== check_ajax_referer( 'wp_wffn_dismiss_notice', 'nonce', false )

			) {

				$userdata   = get_user_meta( get_current_user_id(), '_bwf_notifications_close', true );
				$userdata   = empty( $userdata ) && ! is_array( $userdata ) ? array() : $userdata;
				$userdata[] = $notice_key;

				update_user_meta( get_current_user_id(), '_bwf_notifications_close', array_values( array_unique( $userdata ) ) ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_update_user_meta
				// If primary sticky banner is dismissed, set 5-minute transient to hide admin notifications
				// Secondary sticky banner does NOT trigger this transient - it will always show until dismissed
				if ( strpos( $notice_key, 'sticky_' ) === 0 && strpos( $notice_key, 'secondary_sticky_' ) !== 0 ) {
					set_transient( 'wffn_sticky_banner_dismissed_' . get_current_user_id(), $notice_key, 5 * MINUTE_IN_SECONDS );
				}
			}
			$redirect = isset( $_REQUEST['redirect'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect'] ) ) : null;

			$redirect_url = $redirect && strpos( $redirect, '.php' ) ? admin_url( $redirect ) : null;

			wp_safe_redirect( $redirect_url ?? admin_url( 'admin.php?page=bwf&path=/funnels' ) );
			exit;
		}

		public function get_pro_activation_date() {
			if ( ! defined( 'WFFN_PRO_VERSION' ) ) {
				return '';
			}
			$pro_activation_date = get_option( 'fk_fb_active_date', array() );
			if ( empty( $pro_activation_date ) || ! isset( $pro_activation_date['pro'] ) ) {
				$date = new DateTime( 'now' );
				$date->modify( '-10 days' );
				$pro_activation_date['pro'] = $date->getTimestamp();
				update_option( 'fk_fb_active_date', $pro_activation_date, false );

				return $date->format( 'Y-m-d H:i:s' );
			}

			return gmdate( 'Y-m-d H:i:s', $pro_activation_date['pro'] );
		}

		public function get_lite_activation_date() {

			$pro_activation_date = get_option( 'fk_fb_active_date', array() );
			if ( empty( $pro_activation_date ) || ! isset( $pro_activation_date['lite'] ) ) {
				$date                        = new DateTime( 'now' );
				$pro_activation_date['lite'] = $date->getTimestamp();
				update_option( 'fk_fb_active_date', $pro_activation_date, false );

				return $date->format( 'Y-m-d H:i:s' );
			}

			return gmdate( 'Y-m-d H:i:s', $pro_activation_date['lite'] );
		}


		/**
		 * Get license related configuration to spread over all product to handle all features
		 *
		 * @param boolean $expiry_only true if only expiry related info need, else all info
		 *
		 * @return array a complete array for every product to handle license related info
		 */
		public function get_license_config( $expiry_only = false, $get_ad = true ) {

			if ( $expiry_only ) {
				return array(
					'f'  => array(
						'ed' => $this->get_license_expiry(),
					),
					'gp' => array( 2, 2 ),
				);
			} else {
				$license_config = array(
					'f'  => array(
						'e'  => defined( 'WFFN_PRO_VERSION' ),
						'la' => $this->is_license_active(),  // false on not exist
						// true when activated
						// false when manually deactivated
						// on expiry it could be both true and false, not recommended checking this value
						'ed' => $this->get_license_expiry(),
						'ib' => $this->is_basic_exists(),
					),
					'ck' => array(
						'e'  => wfacp_pro_dependency(), // should cover aero, basic and pro addon
						'la' => $this->is_license_active_for_checkout(),
						'ed' => $this->get_license_expiry_for_checkout(),

					),
					'ul' => array(
						'e'  => function_exists( 'WFOCU_Core' ), // should cover upstroke & pro addon
						'la' => $this->is_license_active_for_upsell(),
						'ed' => $this->get_license_expiry_for_upsell(),
					),
					'gp' => array( 2, 2 ),
				);
				if ( $get_ad === true ) {
					$license_config['f']['adl'] = $this->get_lite_activation_date();
					$license_config['f']['ad']  = $this->get_pro_activation_date();
					$license_config['ck']['ad'] = $this->get_pro_activation_date();
					$license_config['ul']['ad'] = $this->get_pro_activation_date();

				}

				return $license_config;
			}
		}

		/**
		 * redirect checkout edit link on react screen when click on edit link from wc order screen
		 *
		 * @return void
		 */
		public function redirect_checkout_edit_link_on_new_ui() {
			$funnel_id = get_post_meta( $_GET['wfacp_id'], '_bwf_in_funnel', true ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized , WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			if ( ! empty( $funnel_id ) && abs( $funnel_id ) > 0 ) {
				// @codingStandardsIgnoreStart
				$edit_link = add_query_arg( [
					'page' => 'bwf',
					'path' => "/funnel-checkout/" . $_GET['wfacp_id'] . "/design"
					//phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				], admin_url( 'admin.php' ) );
				// @codingStandardsIgnoreEnd
				wp_redirect( $edit_link );
				exit;
			}
		}

		public function maybe_force_redirect_to_wizard() {

			if ( false === $this->is_wizard_available( true ) ) {
				return;
			}

			wp_redirect( WFFN_Core()->admin->wizard_url() );
			exit;
		}


		/**
		 * Helper method to detect if the wizard is available to show
		 * Check all prior conditions before showing force wizard or notice
		 *
		 * @param bool $force check if it's a check for force wizard open
		 *
		 * @return bool true if all checks are passed, false otherwise
		 */
		public function is_wizard_available( $force = false ) {
			try {
				$first_version = get_option( 'wffn_first_v', '0.0.0' );
				if ( $force !== false && false === version_compare( $first_version, WFFN_VERSION, '=' ) ) {

					/**
					 * bail out if old users versions
					 */
					return false;
				}

				if ( WFFN_Core()->admin_notifications->is_user_dismissed( get_current_user_id(), 'onboarding_wizard' ) ) {
					/**
					 * This flag tells us that the wizard notice has already been dismissed
					 */
					return false;
				}

				$status = get_option( '_wffn_onboarding_completed', false );
				if ( false !== $status ) {
					/**
					 * bail out if wizard started/skipped/completed
					 */
					return false;
				}

				if ( $force !== false && WFFN_Core()->admin_notifications->is_user_dismissed( get_current_user_id(), 'wizard_open' ) ) {
					/**
					 * This flag tells us that the wizard first step is already opened
					 * We are using this to make sure we never redirect multiple times.
					 */
					return false;
				}

				if ( false !== get_option( '_bwfan_onboarding_completed', false ) && 'activated' === WFFN_Common::get_plugin_status( 'funnelkit-stripe-woo-payment-gateway/funnelkit-stripe-woo-payment-gateway.php' ) ) {
					/**
					 * This checks if user has completed wizard from automations plugin & we have stripe plugin activated
					 */
					return false;
				}

				return true;
			} catch ( Exception | Error $e ) {
				return false;
			}
		}


		/**
		 * Check if update is available for the plugin lite Or PRO versions
		 *
		 * @return false|mixed version number if update available, false if not available
		 */
		public function is_update_available() {

			$plugins = get_site_transient( 'update_plugins' );
			if ( isset( $plugins->response ) && is_array( $plugins->response ) && isset( $plugins->response[ WFFN_PLUGIN_BASENAME ] ) ) {

				return $this->compare_version( WFFN_VERSION, $plugins->response[ WFFN_PLUGIN_BASENAME ]->new_version );
			} elseif ( defined( 'WFFN_PRO_PLUGIN_BASENAME' ) && isset( $plugins->response ) && is_array( $plugins->response ) && isset( $plugins->response[ WFFN_PRO_PLUGIN_BASENAME ] ) ) {
				return $this->compare_version( WFFN_PRO_VERSION, $plugins->response[ WFFN_PRO_PLUGIN_BASENAME ]->new_version );
			}

			return false;
		}

		/**
		 * Compare version
		 *
		 * @param $old_version
		 * @param $new_version
		 *
		 * @return false|mixed
		 */
		public function compare_version( $old_version, $new_version ) {

			if ( version_compare( $new_version, $old_version, '>' ) ) {
				return $new_version;
			} else {
				return false;
			}
		}


		public function maybe_add_notice( $plugin_file ) {

			if ( defined( 'WFFN_PRO_PLUGIN_BASENAME' ) && $plugin_file === WFFN_PRO_PLUGIN_BASENAME ) {

				$render_css = false;
				$License    = WooFunnels_licenses::get_instance();
				$License->get_plugins_list();
				$current = new DateTime( current_time( 'mysql', true ) );

				$a = WFFn_Core()->admin->get_license_config( true );

				if ( ! empty( $a['f']['ed'] ) ) {

					$expiry = new DateTime( $a['f']['ed'] );

					$diff_in_days = $expiry->diff( $current )->format( '%a' );

					if ( ( $expiry->getTimestamp() < $current->getTimestamp() && absint( $diff_in_days ) <= 7 ) ) {
						$render_css = true;

						$time = $current->modify( '+7 days' )->format( 'F j, Y' );
						?>
						<tr class="plugin-update-tr fb_license_notice active fbk_renew" id="cart-for-woocommerce-update"
							data-slug="cart-for-woocommerce" data-plugin="cart-for-woocommerce/plugin.php">
							<td colspan="4" class="plugin-update colspanchange">
								<div class="update-message notice inline notice-error notice-alt">

									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path
											d="M21.8012 18.6522L13.336 3.78261C13.0546 3.28702 12.5687 3 12.0061 3C11.4435 3 10.9575 3.28702 10.6763 3.78261L2.21104 18.6522C1.92965 19.1478 1.92965 19.7218 2.21104 20.2174C2.49242 20.713 2.97829 21 3.54089 21H20.4459C21.0085 21 21.4946 20.713 21.7758 20.2174C22.0572 19.7218 22.0827 19.1478 21.8013 18.6522H21.8012ZM20.9317 19.6956C20.8805 19.7739 20.7527 19.9564 20.4969 19.9564L3.56641 19.9566C3.31071 19.9566 3.15726 19.774 3.13157 19.6958C3.08036 19.6175 3.00363 19.4088 3.13157 19.174L11.5968 4.3044C11.7247 4.06962 11.9549 4.04359 12.0316 4.04359C12.1084 4.04359 12.3385 4.06962 12.4665 4.3044L20.9317 19.174C21.0596 19.4088 20.9829 19.6173 20.9317 19.6956V19.6956Z"
											fill="#d63638" stroke="#d63638" stroke-width="0.3"/>
										<path
											d="M12.0316 10.5216C11.7502 10.5216 11.52 10.7564 11.52 11.0434V17.0435C11.52 17.3306 11.7502 17.5653 12.0316 17.5653C12.313 17.5653 12.5431 17.3306 12.5431 17.0435V11.0434C12.5431 10.7564 12.313 10.5216 12.0316 10.5216Z"
											fill="#d63638" stroke="#d63638" stroke-width="0.3"/>
										<path
											d="M12.5433 8.95637C12.5433 9.24461 12.3141 9.47817 12.0317 9.47817C11.7493 9.47817 11.5201 9.24461 11.5201 8.95637C11.5201 8.66831 11.7493 8.43475 12.0317 8.43475C12.3141 8.43475 12.5433 8.66832 12.5433 8.95637Z"
											fill="#d63638" stroke="#d63638" stroke-width="0.5"/>
									</svg>

									<p>
										<?php
										printf( wp_kses_post( __( '<strong>Your FunnelKit Pro license has expired!</strong> We\'ve extended its features until %1$s, after which they\'ll be limited. <a href="https://funnelkit.com/exclusive-offer/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Plugin+Inline+Notice">Renew Now</a> or <a href="%2$s">I have My License Key</a>', 'funnel-builder' ) ), esc_html( $time ), esc_url( admin_url( 'admin.php?page=bwf&path=/settings/woofunnels_general_settings' ) ) );
										?>
									</p>


								</div>
							</td>
						</tr>

						<?php
					} /**
					 * the expiry should always be less than on current utc
					 */ elseif ( $expiry->getTimestamp() < $current->getTimestamp() ) {
						$render_css = true;
						?>
						<tr class="plugin-update-tr fb_license_notice active">
							<td colspan="4" class="plugin-update colspanchange">
								<div class="update-message notice inline notice-error notice-alt">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path
											d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47716 6.47715 2 12 2C17.5228 2 22 6.47716 22 12ZM16.119 9.45234C16.5529 9.01843 16.5529 8.31491 16.119 7.88099C15.6851 7.44708 14.9816 7.44708 14.5477 7.88099L12 10.4287L9.45234 7.88099C9.01843 7.44708 8.31491 7.44708 7.88099 7.88099C7.44708 8.31491 7.44708 9.01843 7.88099 9.45234L10.4287 12L7.88099 14.5477C7.44708 14.9816 7.44708 15.6851 7.88099 16.119C8.31491 16.5529 9.01842 16.5529 9.45234 16.119L12 13.5714L14.5477 16.119C14.9816 16.5529 15.6851 16.5529 16.119 16.119C16.5529 15.6851 16.5529 14.9816 16.119 14.5477L13.5713 12L16.119 9.45234Z"
											fill="#d63638"/>
									</svg>

									<p>
										<?php
										printf( wp_kses_post( __( '<strong>Your FunnelKit Pro license has expired!</strong> Please renew your license to continue using premium features without interruption. <a href="https://funnelkit.com/my-account/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Plugin+Inline+Notice">Renew Now</a> or <a href="%s">I have My License Key</a>', 'funnel-builder' ) ), esc_url( admin_url( 'admin.php?page=bwf&path=/settings/woofunnels_general_settings' ) ) );
										?>
									</p>
								</div>
							</td>
						</tr>
						<?php
}
				}

				if ( $render_css ) {
					?>
					<style>
						tr[data-slug="funnelkit-funnel-builder-pro"] th,
						tr[data-slug="funnelkit-funnel-builder-pro"] td {
							box-shadow: none !important;
						}

						.fb_license_notice .update-message {
							position: relative;
						}

						.fb_license_notice .update-message svg {
							position: absolute;
							left: 12px;
							top: 5px;
							width: 20px;
						}

						.fb_license_notice .update-message p {
							padding-left: 14px !important;
						}

						.fb_license_notice.fbk_renew .update-message svg {
							top: 4px;
							width: 16px;
						}

						.fb_license_notice .update-message.notice-error p::before {
							content: "";
						}
					</style>
					<?php
				}
			}
		}

		public function plugin_action_link( $actions, $plugin_file ) {
			$new_action = array();

			if ( ! is_array( $actions ) ) {
				$actions = array();
			}
			if ( defined( 'WFFN_PRO_PLUGIN_BASENAME' ) && $plugin_file === WFFN_PRO_PLUGIN_BASENAME ) {

				$License = WooFunnels_licenses::get_instance();
				$License->get_plugins_list();
				$current = new DateTime( current_time( 'mysql', true ) );

				$a = WFFn_Core()->admin->get_license_config( true );

				if ( ! empty( $a['f']['ed'] ) ) {

					$expiry = new DateTime( $a['f']['ed'] );

					if ( $expiry->getTimestamp() < $current->getTimestamp() ) {
						$link                          = esc_url( 'https://funnelkit.com/my-account/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Plugin+Inline+Notice' );
						$new_action['renewal_license'] = '<style>tr[data-slug="funnelkit-funnel-builder-pro"] .renewal_license{position: relative}tr[data-slug="funnelkit-funnel-builder-pro"] .renewal_license svg{position:absolute;top:1px;left:0}</style><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_835_18634)">
<path d="M10.2957 1.75368C10.1928 1.76698 10.0983 1.81626 10.0298 1.89236C9.9613 1.96846 9.92347 2.06621 9.92333 2.16745C9.92336 2.18598 9.92462 2.2045 9.92711 2.22287L10.0257 2.94891C9.06453 2.28807 7.90358 1.96102 6.729 2.02021C5.55442 2.0794 4.43425 2.52139 3.54808 3.27532C2.66191 4.02926 2.06109 5.05145 1.84194 6.17802C1.6228 7.30459 1.79802 8.47027 2.33952 9.48816C2.88102 10.5061 3.75743 11.3172 4.82823 11.7915C5.89903 12.2659 7.10219 12.3759 8.2448 12.104C9.38741 11.8322 10.4033 11.1941 11.1295 10.2922C11.8558 9.39026 12.2504 8.2767 12.25 7.13005C12.25 7.0192 12.2048 6.9129 12.1244 6.83452C12.044 6.75614 11.935 6.7121 11.8213 6.7121C11.7076 6.7121 11.5986 6.75614 11.5182 6.83452C11.4378 6.9129 11.3926 7.0192 11.3926 7.13005C11.3936 8.09095 11.0634 9.02432 10.4552 9.78043C9.847 10.5366 8.9959 11.0716 8.03846 11.2997C7.08103 11.5279 6.07273 11.4359 5.17532 11.0386C4.27792 10.6412 3.5434 9.96156 3.0896 9.10856C2.6358 8.25557 2.48902 7.2787 2.6728 6.33466C2.85658 5.39061 3.36028 4.5341 4.10308 3.90251C4.84589 3.27093 5.78476 2.90087 6.76909 2.85171C7.75342 2.80255 8.72617 3.07712 9.53129 3.63139L8.58699 3.6711C8.47664 3.67573 8.37239 3.7217 8.29596 3.79943C8.21953 3.87715 8.17681 3.98064 8.17672 4.08832C8.17672 4.09444 8.17692 4.10046 8.17713 4.10658C8.18202 4.21732 8.23183 4.32162 8.3156 4.39656C8.39938 4.47149 8.51025 4.51092 8.62384 4.50616L10.6202 4.4223C10.6265 4.42202 10.6322 4.42021 10.6384 4.41973C10.6427 4.41935 10.647 4.41973 10.6515 4.41922C10.6536 4.41897 10.6557 4.41933 10.6581 4.41904H10.6583C10.6669 4.41791 10.6754 4.41498 10.684 4.41333C10.6969 4.41089 10.7094 4.40825 10.7218 4.40472C10.7283 4.40286 10.7351 4.402 10.7416 4.39983C10.7497 4.39711 10.7568 4.39281 10.7646 4.38965C10.7761 4.38499 10.7874 4.38027 10.7984 4.37469C10.8048 4.37139 10.8118 4.36918 10.8181 4.36552C10.8261 4.36098 10.8328 4.35507 10.8404 4.34995C10.8495 4.34387 10.8585 4.33775 10.8671 4.33102C10.8698 4.32893 10.8728 4.32725 10.8754 4.3251C10.8791 4.32209 10.8833 4.32011 10.8869 4.31695C10.8942 4.31068 10.8995 4.30314 10.9062 4.29649C10.913 4.28985 10.9188 4.2837 10.9248 4.27696C10.9307 4.27021 10.9373 4.26461 10.9427 4.25769C10.9494 4.24916 10.9543 4.23988 10.9603 4.23096C10.9643 4.22486 10.968 4.21865 10.9718 4.21234C10.9765 4.20436 10.9822 4.197 10.9864 4.18871C10.9911 4.17924 10.9942 4.16929 10.9982 4.15957C11.0012 4.15253 11.0037 4.14543 11.0062 4.1382C11.0092 4.12952 11.0132 4.12129 11.0156 4.11239C11.0182 4.10309 11.0191 4.09358 11.021 4.08416C11.0229 4.07473 11.0244 4.06535 11.0256 4.0559C11.0267 4.04784 11.0288 4.0401 11.0293 4.03191C11.0299 4.0233 11.0289 4.01474 11.0289 4.00608C11.0289 3.99961 11.0304 3.99342 11.0302 3.98686C11.0299 3.9803 11.028 3.97482 11.0275 3.96864C11.0272 3.9655 11.0275 3.96237 11.0271 3.95925C11.0267 3.95614 11.0272 3.95298 11.0268 3.94991V3.94916V3.94849L10.7773 2.11314C10.7699 2.05869 10.7516 2.00619 10.7234 1.95864C10.6952 1.9111 10.6577 1.86944 10.613 1.83605C10.5682 1.80266 10.5172 1.7782 10.4628 1.76407C10.4083 1.74994 10.3516 1.74641 10.2957 1.75368Z" fill="#C5443F"/>
</g>
<defs>
<clipPath id="clip0_835_18634">
<rect width="14" height="14" fill="white"/>
</clipPath>
</defs>
</svg>
<a href="' . $link . '" class="wffn_renew_license" style="color: #d63638;padding-left: 20px;">' . __( 'Renew Expired License', 'funnel-builder' ) . '</a>';
					}
				}
				?>

				<?php
			}

			return array_merge( $new_action, $actions );
		}

		/**
		 * Clear wffn endpoints form cache plugins
		 *
		 * @return void
		 */
		public function clear_endpoints_from_cache() {
			/** Cache handling */
			if ( class_exists( 'BWF_JSON_Cache' ) && method_exists( 'BWF_JSON_Cache', 'run_json_endpoints_cache_handling' ) ) {
				BWF_JSON_Cache::run_json_endpoints_cache_handling();
			}
		}

		public function conditional_includes() {

			$screen = get_current_screen();

			if ( ! $screen ) {
				return;
			}

			switch ( $screen->id ) {
				case 'dashboard':
				case 'dashboard-network':
					include_once __DIR__ . '/class-wffn-admin-dashboard-widget.php';

					break;

			}
		}

		/**
		 * Function run on schedule for clean up data
		 * duplicate entry and remove subscription form conversion
		 *
		 * @return void
		 */
		public function optimize_conversion_table_analytics() {

			global $wpdb;
			try {
				if ( ! class_exists( 'BWF_WC_Compatibility' ) ) {
					return;
				}
				$conv_table              = $wpdb->prefix . 'bwf_conversion_tracking';
				$order_table             = $wpdb->prefix . 'wc_orders';
				$aero_table              = $wpdb->prefix . 'wfacp_stats';
				$limit                   = 100;
				$is_clear_schedule       = true;
				WFFN_Common::$start_time = time();

				while ( true ) {
					// Stop if time or memory exceeded
					if ( ( WFFN_Common::time_exceeded() || WFFN_Common::memory_exceeded() ) ) {
						$is_clear_schedule = false;
						break;
					}

					// Query Setup
					if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
						$order_meta_table = $wpdb->prefix . 'wc_orders_meta';
						$conv_query       = "SELECT conv.source as ID FROM {$conv_table} AS conv
                        INNER JOIN {$order_meta_table} AS om ON (conv.source = om.order_id AND om.meta_key = '_subscription_renewal')
                        WHERE conv.type = %s
                        ORDER BY conv.timestamp DESC LIMIT 0, %d";//phpcs:ignore

					} else {
						$conv_query = "SELECT conv.source as ID FROM {$conv_table} AS conv
                        INNER JOIN {$wpdb->postmeta} AS pm ON (conv.source = pm.post_id AND pm.meta_key = '_subscription_renewal')
                        WHERE conv.type = %s
                        ORDER BY conv.timestamp DESC LIMIT 0, %d";
					}

					$get_ids = $wpdb->get_col( $wpdb->prepare( $conv_query, 2, $limit ) );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					// Stop loop if no more data
					if ( empty( $get_ids ) || ! is_array( $get_ids ) ) {
						/*
						 * Attempt duplicate query
						 */
						$duplicate_row = $wpdb->get_col( $wpdb->prepare( "SELECT source FROM {$conv_table} WHERE type = 2 AND source != 0 GROUP BY source HAVING COUNT(*) > %d ", 1 ) );//phpcs:ignore

						if ( ! empty( $duplicate_row ) && is_array( $duplicate_row ) ) {
							$is_clear_schedule = false;
							$delete_query      = "DELETE FROM {$conv_table} WHERE id IN ( SELECT id FROM (
                                        SELECT id FROM {$conv_table} WHERE source IN (
                                        SELECT source FROM {$conv_table} WHERE type = 2 AND source != 0
                                        GROUP BY source HAVING COUNT(*) > 1 ) AND id NOT IN (
                                        SELECT MAX(id) FROM {$conv_table} GROUP BY source ) LIMIT 100 ) AS subquery )";
							$wpdb->query( $delete_query );  //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							WFFN_Core()->logger->log( 'Clear duplicate rows.', 'wffn_ay', true );
							continue;

						} else {
							WFFN_Core()->logger->log( 'No more data to clear from conversion tracking.', 'wffn_ay', true );
							break;
						}
					}

					$is_clear_schedule = false;
					$delete_query      = "DELETE FROM {$conv_table} WHERE type = %d AND source IN (" . implode( ',', array_map( 'intval', $get_ids ) ) . ')';
					$wpdb->query( $wpdb->prepare( $delete_query, 2 ) );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( ! empty( $wpdb->last_error ) ) {
						$is_clear_schedule = true;
						WFFN_Core()->logger->log( 'Conversion tracking cleanup last error: ' . $wpdb->last_error, 'wffn_ay', true );
					}

					$update_query = "UPDATE {$conv_table} AS conv LEFT JOIN {$aero_table} AS stats ON conv.source = stats.order_id SET conv.checkout_total = stats.total_revenue
                                              WHERE 1=1 AND conv.funnel_id != 0
                                                AND conv.checkout_total = 0
                                                AND stats.total_revenue != 0
                                                AND conv.type = %d
                                                AND conv.timestamp > '2025-01-01 00:00:00'";

					$wpdb->query( $wpdb->prepare( $update_query, 2 ) );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( ! empty( $wpdb->last_error ) ) {
						$is_clear_schedule = true;
						WFFN_Core()->logger->log( 'Conversion tracking update last error: ' . $wpdb->last_error, 'wffn_ay', true );
					}
				}

				if ( ( WFFN_Common::time_exceeded() || WFFN_Common::memory_exceeded() ) ) {
					return;
				}

				/**
				 * Remove all subscription order from aero
				 */
				$aero_table = $wpdb->prefix . 'wfacp_stats';

				if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
					$aero_query = "SELECT ch.order_id as ID FROM {$aero_table} AS ch
                        INNER JOIN {$order_table} AS ot ON (ch.order_id = ot.id )
                        WHERE ot.type = %s
                        ORDER BY ch.date DESC LIMIT 0, %d";

				} else {
					$aero_query = "SELECT ch.order_id as ID FROM {$aero_table} AS ch
                        INNER JOIN {$wpdb->posts} AS p ON (ch.order_id = p.ID)
                        WHERE p.post_type = %s
                        ORDER BY ch.date DESC LIMIT 0, %d";
				}

				$get_aero_ids = $wpdb->get_col( $wpdb->prepare( $aero_query, 'shop_subscription', $limit ) );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( is_array( $get_aero_ids ) && count( $get_aero_ids ) > 0 ) {
					$is_clear_schedule = false;
					$wpdb->query( "DELETE FROM {$aero_table} WHERE order_id IN (" . implode( ',', $get_aero_ids ) . " )" );//phpcs:ignore
					if ( ! empty( $wpdb->last_error ) ) {
						$is_clear_schedule = true;
						WFFN_Core()->logger->log( 'Conversion aero tracking cleanup last error: ' . $wpdb->last_error, 'wffn_ay', true );
					}
				}

				/**
				 * Remove duplicate entry
				 */
				$duplicate_query = "SELECT order_id as ID FROM {$aero_table}
                	WHERE order_id IN ( SELECT order_id  FROM {$aero_table}
    					GROUP BY order_id
    					HAVING COUNT(order_id) > %d
					)
					ORDER BY order_id, ID LIMIT 0, %d";

				$duplicate_aero_ids = $wpdb->get_col( $wpdb->prepare( $duplicate_query, 1, $limit ) );//phpcs:ignore

				if ( is_array( $duplicate_aero_ids ) && count( $duplicate_aero_ids ) > 0 ) {
					$is_clear_schedule = false;
					$delete_query      = "DELETE FROM {$aero_table} WHERE ID IN (
                        SELECT ID FROM (
                            SELECT ID
                            FROM {$aero_table} t1
                            WHERE ID NOT IN (
                                SELECT MAX(ID) FROM {$aero_table} t2 GROUP BY order_id
                            )
                            AND order_id IN (
                                SELECT order_id FROM {$aero_table} GROUP BY order_id HAVING COUNT(order_id) > %d
                            )
                            ORDER BY ID DESC
                            LIMIT %d
                        ) AS subquery
                    )";
					$wpdb->query( $wpdb->prepare( $delete_query, 1, $limit ) );//phpcs:ignore
					if ( ! empty( $wpdb->last_error ) ) {
						$is_clear_schedule = true;
						WFFN_Core()->logger->log( 'Conversion duplicate tracking cleanup last error: ' . $wpdb->last_error, 'wffn_ay', true );
					}
				}

				/**
				 * Remove all subscription order from bump
				 */
				if ( class_exists( 'WFOB_Core' ) ) {
					$bump_table = $wpdb->prefix . 'wfob_stats';
					if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
						$bump_query = "SELECT ch.oid as ID FROM {$bump_table} AS ch
                        INNER JOIN {$order_table} AS ot ON (ch.oid = ot.id )
                        WHERE ot.type = %s
                        ORDER BY ch.date DESC LIMIT 0, %d";

					} else {
						$bump_query = "SELECT ch.oid as ID FROM {$bump_table} AS ch
                        INNER JOIN {$wpdb->posts} AS p ON (ch.oid = p.ID)
                        WHERE p.post_type = %s
                        ORDER BY ch.date DESC LIMIT 0, %d";
					}

					$get_bump_ids = $wpdb->get_col( $wpdb->prepare( $bump_query, 'shop_subscription', $limit ) );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( is_array( $get_bump_ids ) && count( $get_bump_ids ) > 0 ) {
						$is_clear_schedule = false;
						$wpdb->query( "DELETE FROM {$bump_table} WHERE oid IN (" . implode( ',', $get_bump_ids ) . " )" );//phpcs:ignore
						if ( ! empty( $wpdb->last_error ) ) {
							$is_clear_schedule = true;
							WFFN_Core()->logger->log( 'Conversion bump tracking cleanup last error: ' . $wpdb->last_error, 'wffn_ay', true );
						}
					}
				}

				// If no remaining rows, schedule a single action to remove the recurring action
				if ( $is_clear_schedule ) {
					wp_schedule_single_event( time(), 'fk_remove_optimize_conversion_table_schedule' );
				}
			} catch ( Exception | Error $e ) {
				// Schedule a single action to delete the recurring action
				wp_schedule_single_event( time(), 'fk_remove_optimize_conversion_table_schedule' );
				WFFN_Core()->logger->log( 'Exception occurred in : ' . __FUNCTION__ . $e->getMessage(), 'wffn_ay', true );

			}
		}

		public function remove_optimize_conversion_table_schedule() {
			// Clear the scheduled action
			wp_clear_scheduled_hook( 'fk_optimize_conversion_table_analytics' );
			WFFN_Core()->logger->log( 'Recurring action "fk_optimize_conversion_table_analytics" cleared : ' . __FUNCTION__, 'wffn_ay', true );
		}

		/**
		 * Runs the email notifications.
		 *
		 * @return void
		 */
		public function run_notifications() {
			WFFN_Email_Notification::run_notifications();
		}

		/**
		 * Runs the email notifications.
		 *
		 * @return void
		 */
		public function maybe_setup_notification_schedule() {
			WFFN_Email_Notification::maybe_setup_notification_schedule();
		}

		/**
		 * Tests the email notification in the admin area.
		 *
		 * @return void
		 */
		public function test_notification_admin() {

			WFFN_Email_Notification::test_notification_admin();
		}

		/**
		 * save settings for email notification
		 **/
		public function save_settings_for_email_notification( $settings ) {
			WFFN_Email_Notification::save_settings( $settings );
		}


		/**
		 * Check if language support is enabled
		 *
		 * @return bool
		 */
		public function is_language_support_enabled() {
			return '' !== WFFN_Plugin_Compatibilities::get_language_compatible_plugin();
		}
	}

	if ( class_exists( 'WFFN_Core' ) ) {
		WFFN_Core::register( 'admin', 'WFFN_Admin' );
	}
}
