<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class WFFN_REST_Tools
 *
 * * @extends WP_REST_Controller
 */
if ( ! class_exists( 'WFFN_REST_Tools' ) ) {
	#[AllowDynamicProperties]
	class WFFN_REST_Tools extends WP_REST_Controller {

		public static $_instance = null;

		/**
		 * Route base.
		 *
		 * @var string
		 */

		protected $namespace = 'funnelkit-app';
		protected $rest_base = 'funnels/tools';

		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Register the routes for taxes.
		 */
		public function register_routes() {
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_tools' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),

				),
			) );
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'tools_action' ),
					'permission_callback' => array( $this, 'get_write_api_permission_check' ),
					'args'                => array(
						'woofunnels_transient' => array(
							'description'       => __( 'Clear woofunnels transient', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'woofunnels_tracking'  => array(
							'description'       => __( 'Clear woofunnels tracking', 'funnel-builder' ),
							'type'              => 'boolean',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'index_past_order'     => array(
							'description'       => __( 'Clear woofunnels tracking', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			) );

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/get-all-log-files', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_log_files' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),

				),
			) );

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/view-log-file', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'view_log_files' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
					'args'                => array(
						'log_selected' => array(
							'description'       => __( 'Selected log file for view', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			) );

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/delete-log-file', array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_log_files' ),
					'permission_callback' => array( $this, 'get_write_api_permission_check' ),
					'args'                => array(
						'log_selected' => array(
							'description'       => __( 'Selected log file for view', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			) );

		}

		public function get_read_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'funnel', 'read' );
		}

		public function get_write_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'funnel', 'write' );
		}

		public function get_index_orders() {

			$threshold_order     = defined( 'BWF_THRESHOLD_ORDERS' ) ? BWF_THRESHOLD_ORDERS : 0;
			$get_threshold_order = get_option( '_bwf_order_threshold', $threshold_order );
			$bwf_db_upgrade      = WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state();
			$index_orders        = [];
			global $wpdb;
			if ( ! class_exists( 'WooCommerce' ) ) {
				return $index_orders;
			}

			if ( '3' !== $bwf_db_upgrade || $get_threshold_order < 1 ) {

				$paid_statuses = implode( ',', array_map( function ( $status ) {
					return "'wc-$status'";
				}, wc_get_is_paid_statuses() ) );

				if ( ! BWF_WC_Compatibility::is_hpos_enabled() ) {

					$query = $wpdb->prepare( "SELECT COUNT(p.ID) FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} AS pm ON ( p.ID = pm.post_id AND pm.meta_key = '_woofunnel_cid') LEFT JOIN {$wpdb->postmeta} AS pm2 ON (p.ID = pm2.post_id) WHERE 1=1 AND pm.post_id IS NULL AND ( pm2.meta_key = '_billing_email' AND pm2.meta_value != '' ) AND p.post_type = %s AND p.post_status IN ({$paid_statuses}) ORDER BY p.post_date DESC", 'shop_order' ); //phpcs:ignore

				} else {
					$order_table      = $wpdb->prefix . 'wc_orders';
					$order_meta_table = $wpdb->prefix . 'wc_orders_meta';
					$query            = $wpdb->prepare( "SELECT COUNT(p.id) FROM {$order_table} AS p LEFT JOIN {$order_meta_table} AS pm ON ( p.id = pm.order_id AND pm.meta_key = '_woofunnel_cid') WHERE 1=1 AND pm.order_id IS NULL AND p.billing_email != '' AND  p.type = %s  AND p.status IN ({$paid_statuses}) ORDER BY p.date_created_gmt DESC", 'shop_order' ); //phpcs:ignore


				}


				$query_results = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				$get_threshold_order = $query_results;


			}


			if ( 0 === absint( $get_threshold_order ) && 0 === absint( $bwf_db_upgrade ) ) {
				WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->set_upgrade_state( '5' );
				$bwf_db_upgrade = '5';
			}

			$description = __( 'This tool will scan all the previous orders and create an optimized index to run efficient queries. <a href="https://funnelkit.com/docs/upstroke/miscellaneous/index-past-order/?utm_source=WordPress&utm_medium=Index+Past+Orders&utm_campaign=FB+Lite+Plugin">Learn more</a>', 'funnel-builder' );

			if ( '1' === $bwf_db_upgrade || '6' === $bwf_db_upgrade ) {
				$description .= esc_html__( ' Unable to complete indexing of orders.', 'funnel-builder' );

				$description .= ' <a target="_blank" href="https://funnelkit.com/support/?utm_source=WordPress&utm_medium=Indexing+Failed+Support&utm_campaign=FB+Lite+Plugin">Contact support to get the issue resolved.</a>';

			}
			if ( true === apply_filters( 'bwf_needs_order_indexing', false ) ) {
				$index_orders = array(
					'title' => __( 'Index Past Orders', 'funnel-builder' ),
					'desc'  => $description,

				);


				if ( '3' === $bwf_db_upgrade ) {

					$index_orders['cta'] = array(
						'type' => 'button',
						'text' => __( 'Running', 'funnel-builder' ),
						'slug' => 'index_past_order',
						'prop' => 'disabled',
					);
				} elseif ( '4' === $bwf_db_upgrade || '5' === $bwf_db_upgrade ) {
					$index_orders['cta']  = array(
						'type' => 'button',
						'text' => __( 'Completed', 'funnel-builder' ),
						'slug' => 'index_past_order',
						'prop' => 'disabled',
					);
					$index_orders['desc'] .= sprintf( '</br>%s <a href="%s">%s</a> </li>', __( 'Indexing has been completed. If you need to restart the indexing process, ', 'funnel-builder' ), admin_url( 'admin.php?page=bwf&path=/settings/tools&bwf_index_clean=yes' ), __( 'Click here', 'funnel-builder' ) );

				} else {
					$index_orders['cta'] = array(
						'type' => 'button',
						'text' => __( 'Start', 'funnel-builder' ),
						'slug' => 'index_past_order',
						'prop' => ( $get_threshold_order > 0 ) ? '' : 'disabled',
					);
				}

				if ( '3' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					$index_orders['notice'] = array(
						'type' => 'success',
						'text' => __( 'Indexing of orders has started. It may take sometime to finish the process. We will update this notice once the process completes.', 'funnel-builder' )
					);
				}


			}

			return $index_orders;
		}

		public function get_all_tools() {
			return rest_ensure_response( $this->get_all_tools_array() );
		}

		public function get_all_tools_array() {
			$tools_array = array(

				array(
					'title' => __( 'FunnelKit transients', 'funnel-builder' ),
					'desc'  => __( 'This tool will clear all the FunnelKit plugins transients cache.', 'funnel-builder' ),
					'cta'   => array(
						'type' => 'button',
						'text' => __( 'Clear Transients', 'funnel-builder' ),
						'slug' => 'woofunnels_transient',
					),
				),
				array(
					'title' => __( 'Verify Base Tables', 'funnel-builder' ),
					'desc'  => __( 'This will verify FunnelKit all the base tables which are required for smooth functioning.', 'funnel-builder' ),
					'cta'   => array(
						'type' => 'button',
						'text' => __( 'Verify', 'funnel-builder' ),
						'slug' => 'woofunnels_verify',
					),
				),
				array(
					'title' => __( 'Usage Tracking', 'funnel-builder' ),
					'desc'  => __( 'This action controls Usage Tracking', 'funnel-builder' ),
					'cta'   => array(
						'type'         => 'toggle',
						'value'        => wffn_string_to_bool( WooFunnels_OptIn_Manager::get_optIn_state() ),
						'text_enable'  => __( 'User Tracking Enabled', 'funnel-builder' ),
						'text_disable' => __( 'User Tracking Disabled', 'funnel-builder' ),
						'slug'         => 'woofunnels_tracking',
					),
				),

			);
			$index       = $this->get_index_orders();
			if ( count( $index ) > 0 ) {
				return array_merge( [ $index ], $tools_array );
			} else {
				return $tools_array;
			}

		}

		public function get_all_log_files() {

			$file_list   = array();

			if ( ! class_exists( 'BWF_Logger' ) ) {
				return rest_ensure_response( $file_list );
			}

			$logger_obj        = BWF_Logger::get_instance();
			$final_logs_result = $logger_obj->get_log_options();

			foreach ( $final_logs_result as $plugin_folder => $plugin_log_files ) {
				foreach ( $plugin_log_files as $file_slug => $file_name ) {
					$option_value = $plugin_folder . '/' . $file_slug;
					$file_list[]  = array(
						'label' => $this->extractFilename( $file_name ),
						'value' => $option_value,
						'key'   => $option_value
					);

				}
			}

			return rest_ensure_response( $file_list );
		}

		/**
		 * Extract the filename correct
		 *
		 * @param $filename
		 *
		 * @return string
		 */
		public function extractFilename( $filename ) {
			$pattern = '/^(.*?-\d{4}-\d{2}-\d{2})(?:-[a-f0-9]{32})?$/';

			if ( preg_match( $pattern, $filename, $matches ) ) {
				return $matches[1];
			}

			return $filename; // Return original if no match
		}

		public function view_log_files( $request ) {

			$resp = array(
				'status' => false,
				'msg'    => __( 'No log file found', 'funnel-builder' )
			);

			$selected_log_file = isset( $request['log_selected'] ) ? $request['log_selected'] : '';

			if ( empty( $selected_log_file ) ) {
				return rest_ensure_response( $resp );
			}

			$folder_prefix    = explode( '/', $selected_log_file );
			$folder_file_name = $folder_prefix[1];
			$folder_prefix    = $folder_prefix[0];
			$file_api         = new WooFunnels_File_Api( $folder_prefix );

			// View log submit is clicked, get the content from the selected file
			$content = $file_api->get_contents( $folder_file_name );

			if ( $content !== false ) {
				return rest_ensure_response( $content );
			}

			return rest_ensure_response( $resp );
		}

		public function delete_log_files( $request ) {

			$resp = array(
				'status' => false,
				'msg'    => __( 'No log file found', 'funnel-builder' )
			);

			$selected_log_file = isset( $request['log_selected'] ) ? $request['log_selected'] : '';

			if ( empty( $selected_log_file ) ) {
				return rest_ensure_response( $resp );
			}

			$folder_prefix    = explode( '/', $selected_log_file );
			$folder_file_name = $folder_prefix[1];
			$folder_prefix    = $folder_prefix[0];
			$file_api         = new WooFunnels_File_Api( $folder_prefix );

			// View log submit is clicked, get the content from the selected file
			$delete = $file_api->delete_file( $folder_file_name );

			if ( $delete ) {
				$resp = array(
					'status' => true,
					'msg'    => __( 'Successfully delete log file', 'funnel-builder' )
				);
			}

			return rest_ensure_response( $resp );
		}

		public function tools_action( $request ) {
			$resp = array(
				'status' => false,
			);

			$transient         = ( isset( $request['woofunnels_transient'] ) && $request['woofunnels_transient'] === 'yes' ) ? $request['woofunnels_transient'] : '';
			$woofunnels_verify = ( isset( $request['woofunnels_verify'] ) && $request['woofunnels_verify'] === 'yes' ) ? $request['woofunnels_verify'] : '';
			$tracking          = ( isset( $request['woofunnels_tracking'] ) ) ? $request['woofunnels_tracking'] : '';
			$index_orders      = ( isset( $request['index_past_order'] ) && $request['index_past_order'] === 'yes' ) ? $request['index_past_order'] : '';

			if ( $transient !== '' ) {
				$woofunnels_transient_obj = WooFunnels_Transient::get_instance();
				$woofunnels_transient_obj->delete_force_transients();
				$resp['status'] = true;
				$resp['tool']   = $this->get_index_orders();
				$resp['msg']    = __( 'All Plugins transients cleared.', 'funnel-builder' );

				return rest_ensure_response( $resp );
			}

			if ( $woofunnels_verify !== '' ) {
				global $wpdb;
				$wpdb->hide_errors();
				$wpdb->suppress_errors();
				$tables          = $this->get_tables_list();
				$filtered_tables = $this->get_filtered_tables( $tables );

				$this->force_create_tables( $filtered_tables );

				return rest_ensure_response( [
					'status' => true,
					'msg'    => 'Tables have been successfully verified.'
				] );
			}

			if ( $tracking !== '' ) {

				if ( true === $tracking ) {
					WooFunnels_OptIn_Manager::Allow_optin();
				} else {
					delete_option( 'bwf_is_opted' );
				}


				$resp['status'] = true;
				$resp['msg']    = sprintf( __( 'Usage tracking successfully %s.', 'funnel-builder' ), true === $tracking ? 'enabled' : 'disabled' );

				return rest_ensure_response( $resp );
			}

			if ( $index_orders !== '' ) {

				if ( 'yes' === $index_orders && '0' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->set_upgrade_state( '2' );
				}
				if ( 'yes' === $index_orders && '1' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->reset_indexing_data( true );
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->set_upgrade_state( '2' );
				}
				if ( '2' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->bwf_start_indexing();
				}

				if ( function_exists( 'wffn_conversion_tracking_migrator' ) && in_array( absint( wffn_conversion_tracking_migrator()->get_upgrade_state() ), [ 0, 3, 4 ], true ) ) {
					/**
					 * Remove table from db table list for reattempt
					 * to create table with all column
					 */
					$current_tables = get_option( '_bwf_db_table_list' );
					if ( ! empty( $current_tables['tables'] ) && is_array( $current_tables['tables'] ) ) {
						$key = array_search( 'bwf_conversion_tracking', $current_tables['tables'], true );

						if ( $key !== false ) {
							unset( $current_tables['tables'][ $key ] );
							update_option( '_bwf_db_table_list', $current_tables, true );
						}
					}
					/**
					 * reset conversion migration
					 */
					delete_option( '_bwf_conversion_threshold' );
					delete_option( '_bwf_conversion_offset' );
					WFFN_Core()->admin_notifications->conversion_migration_content( 2 );
				}

				$get_index_array = $this->get_all_tools_array();
				$resp['tool']    = $get_index_array[0];
				$resp['status']  = true;
				$resp['msg']     = __( 'Indexing started successfully', 'funnel-builder' );

				return rest_ensure_response( $resp );
			}

			return rest_ensure_response( $resp );

		}

		/**
		 * Force creates all database tables without checking if they exist.
		 *
		 * This method deletes existing version options and executes table creation
		 * methods for all filtered tables to ensure they are properly created/recreated.
		 *
		 * @param array $tables Associative array where keys are version keys and values are table lists
		 * @return array List of all table names that were processed for creation
		 * @since 1.0.0
		 */
		public function force_create_tables( $tables ) {
			$all_created_tables = [];

			foreach ( $tables as $version_key => $table_list ) {
				delete_option( $version_key );
				$this->execute_table_creation( $version_key );

				$all_created_tables = array_merge( $all_created_tables, $table_list );
			}

			return $all_created_tables;
		}

		/**
		 * Executes table creation based on the version key.
		 *
		 * Maps version keys to their corresponding class instances and methods,
		 * then calls the appropriate methods to create database tables.
		 *
		 * @param string $version_key The version key identifier for table creation
		 * @return void
		 * @since 1.0.0
		 */
		private function execute_table_creation( $version_key ) {
			$option_key_map = [
				'_wfocu_db_version'  => [ 'class' => 'WFOCU_Admin', 'method' => 'check_db_version', 'update_method' => 'maybe_update_database_update' ],
				'_wffn_db_version'   => [ 'class' => 'WFFN_ADMIN', 'method' => 'check_db_version', 'update_method' => 'maybe_update_database_update' ],
				'_wfopp_db_version'  => [ 'class' => 'WFOPP_DB_Tables', 'method' => 'add_if_needed' ],
				'wfob_db_ver_3_0'    => [ 'class' => 'WFOB_Reporting', 'method' => 'create_table' ],
				'wfacp_db_ver_2_1'   => [ 'class' => 'WFACP_Reporting', 'method' => 'create_table' ],
				'_bwf_db_table_list' => [ 'class' => 'WooFunnels_Create_DB_Tables', 'method' => 'create' ],
				'fkcart_db_version'  => [ 'class' => 'FKCart\\Includes\\DB', 'method' => 'create_db', 'static' => true ]
			];

			if ( isset( $option_key_map[ $version_key ] ) ) {
				$table_handler = $option_key_map[ $version_key ];
				if ( ! class_exists( $table_handler['class'] ) ) {
					return;
				}
				$tables = $table_handler['class']::get_instance();

				if ( isset( $table_handler['method'] ) ) {
					$tables->{$table_handler['method']}();
				}

				if ( isset( $table_handler['update_method'] ) ) {
					$tables->{$table_handler['update_method']}();
				}
			}
		}

		/**
		 * Filters the tables list based on whether funnel pro is active.
		 *
		 * If funnel pro is active, all tables are included. Otherwise, certain
		 * pro-specific tables are excluded from the list.
		 *
		 * @param array $tables Complete list of tables grouped by version keys
		 * @return array Filtered tables array based on pro activation status
		 * @since 1.0.0
		 */
		public function get_filtered_tables( array $tables ): array {
			$is_funnel_pro_active = WFFN_Common::wffn_is_funnel_pro_active();
			$is_fkcart_active = $this->is_fkcart_active();

			return array_filter( $tables, function ( $table_list, $version_key ) use ( $is_funnel_pro_active, $is_fkcart_active ) {
				if ( $is_funnel_pro_active ) {
					return true;
				}
				
				// Include cart-for-woocommerce tables if the plugin is active
				if ( 'fkcart_db_version' === $version_key ) {
					return $is_fkcart_active;
				}
				
				return ! in_array( $version_key, [ '_wfocu_db_version', 'wfob_db_ver_3_0', 'wfacp_db_ver_2_1' ] , true );
			}, ARRAY_FILTER_USE_BOTH );
		}

		/**
		 * Checks if FunnelKit Cart for WooCommerce plugin is active.
		 *
		 * @return bool True if the plugin is active, false otherwise
		 * @since 1.0.0
		 */
		private function is_fkcart_active() {
			// Check if the plugin class exists
			if ( class_exists( 'FKCart\\Plugin' ) ) {
				return true;
			}
			
			// Check if the plugin file is active
			return in_array( 'cart-for-woocommerce/plugin.php', (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
		}

		/**
		 * Returns the complete list of database tables organized by version keys.
		 *
		 * Each version key corresponds to a specific plugin/module and contains
		 * an array of table names that belong to that version.
		 *
		 * @return array Associative array mapping version keys to their table lists
		 * @since 1.0.0
		 */
		private function get_tables_list(): array {
			return [
				'_wfocu_db_version'  => [ 'wfocu_session', 'wfocu_event', 'wfocu_event_meta' ],
				'_wffn_db_version'   => [ 'bwf_funnels', 'bwf_funnelmeta' ],
				'_wfopp_db_version'  => [ 'bwf_optin_entries' ],
				'wfob_db_ver_3_0'    => [ 'wfob_stats' ],
				'wfacp_db_ver_2_1'   => [ 'wfacp_stats' ],
				'_bwf_db_table_list' => [ 'bwf_contact', 'bwf_contact_meta', 'bwf_wc_customers', 'wfco_report_views', 'bwf_conversion_tracking' ],
				'fkcart_db_version'  => [ 'fk_cart', 'fk_cart_products' ],
			];
		}
	}


	return WFFN_REST_Tools::get_instance();


}
