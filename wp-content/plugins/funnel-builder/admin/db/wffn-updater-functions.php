<?php
if ( ! function_exists( 'wffn_handle_store_checkout_config' ) ) {
	function wffn_handle_store_checkout_config() {
		if ( ! class_exists( 'WFACP_Common' ) ) {
			return;
		}
		/** check if store checkout already exists */
		if ( WFFN_Common::get_store_checkout_id() > 0 ) {
			return;
		}

		/** Remove _is_global meta if any funnel exists */
		global $wpdb;
		$sql_query     = $wpdb->prepare( "SELECT bwf_funnel_id as id FROM {table_name_meta} WHERE meta_key = %s",'_is_global' );
		$found_funnels = WFFN_Core()->get_dB()->get_results( $sql_query );
		if ( is_array( $found_funnels ) && count( $found_funnels ) > 0 && isset( $found_funnels[0]['id'] ) && absint( $found_funnels[0]['id'] ) > 0 ) {
			foreach ( $found_funnels as $funnel ) {
				if ( isset( $funnel['id'] ) ) {
					$del_query = $wpdb->prepare( "DELETE FROM {table_name_meta} WHERE bwf_funnel_id = %d AND meta_key = '_is_global'", $funnel['id'] );
					WFFN_Core()->get_dB()->delete_multiple( $del_query );
				}
			}
		}

		$global_settings = WFACP_Common::global_settings( true );

		if ( ! is_array( $global_settings ) || count( $global_settings ) === 0 ) {
			return;
		}

		if ( ! isset( $global_settings['override_checkout_page_id'] ) || absint( $global_settings['override_checkout_page_id'] ) === 0 ) {
			return;
		}

		$wfacp_id = absint( $global_settings['override_checkout_page_id'] );

		$get_funnel_id = get_post_meta( $wfacp_id, '_bwf_in_funnel', true );

		if ( empty( $get_funnel_id ) ) {
			return;
		}


		WFFN_Common::update_store_checkout_meta( $get_funnel_id, 1 );

		/** we need to remove the old settings here since we are using filter for frontend execution
		 * If the settings exists then the current setup will always show
		 */

		unset( $global_settings['override_checkout_page_id'] );
		WFACP_AJAX_Controller::update_global_settings_fields( $global_settings );

	}
}

if ( ! function_exists( 'wffn_alter_conversion_table' ) ) {

	function wffn_alter_conversion_table() {

		if ( ! class_exists( 'WooFunnels_Create_DB_Tables' ) || ! method_exists( 'WooFunnels_Create_DB_Tables', 'maybe_table_created_current_version' ) ) {
			return;
		}
		/**
		 * no need for alter table if table create in current version
		 */
		$created_tables = WooFunnels_Create_DB_Tables::get_instance()->maybe_table_created_current_version();
		$conv_table     = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
		if ( is_array( $created_tables ) && in_array( $conv_table, $created_tables, true ) ) {
			return;
		}
		global $wpdb;

		$conv_table = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
		$table_name = $wpdb->prefix . $conv_table;
		$is_col     = $wpdb->get_col( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = 'checkout_total'", $table_name ) );

		if ( ! empty( $is_col ) ) {
			return;
		}

		$sql_query = "
				ALTER TABLE {$table_name}
				ADD `checkout_total` double DEFAULT 0 NOT NULL AFTER value,
			    ADD `bump_total` double DEFAULT 0 NOT NULL AFTER value,
			    ADD `offer_total` double DEFAULT 0 NOT NULL AFTER value,
			    ADD `bump_accepted` varchar(255) DEFAULT '' NOT NULL AFTER value,
			    ADD `bump_rejected` varchar(255) DEFAULT '' NOT NULL AFTER value,
			    ADD `offer_accepted` varchar(255) DEFAULT '' NOT NULL AFTER value,
			    ADD `offer_rejected` varchar(255) DEFAULT '' NOT NULL AFTER value,
				ADD `first_landing_url` varchar(255) DEFAULT '' NOT NULL AFTER first_click,
				ADD `journey` longtext DEFAULT '' NOT NULL AFTER referrer,				    
				ADD INDEX (bump_accepted),
			    ADD INDEX (bump_rejected),
			    ADD INDEX (offer_accepted),
			    ADD INDEX (offer_rejected),
			    ADD INDEX (first_landing_url)";

		$wpdb->query( $sql_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

if ( ! function_exists( 'wffn_add_utm_columns_in_conversion_table' ) ) {

	function wffn_add_utm_columns_in_conversion_table() {

		if ( ! class_exists( 'WooFunnels_Create_DB_Tables' ) || ! method_exists( 'WooFunnels_Create_DB_Tables', 'maybe_table_created_current_version' ) ) {
			return;
		}

		/**
		 * no need for alter table if table create in current version
		 */
		$created_tables = WooFunnels_Create_DB_Tables::get_instance()->maybe_table_created_current_version();
		$conv_table     = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
		if ( is_array( $created_tables ) && in_array( $conv_table, $created_tables, true ) ) {
			return;
		}

		global $wpdb;

		$conv_table = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
		$table_name = $wpdb->prefix . $conv_table;
		$is_col     = $wpdb->get_col( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = 'referrer_last'", $table_name ) );

		if ( ! empty( $is_col ) ) {
			return;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max_index_length = 191;
		$sql_query        = "
			ALTER TABLE {$table_name}				    
				ADD `referrer_last` varchar(255) DEFAULT '' NOT NULL AFTER referrer,
				ADD `utm_content_last` varchar(255) DEFAULT '' NOT NULL AFTER utm_content,
				ADD `utm_term_last` varchar(255) DEFAULT '' NOT NULL AFTER utm_content,
				ADD `utm_campaign_last` varchar(255) DEFAULT '' NOT NULL AFTER utm_content,
				ADD `utm_medium_last` varchar(255) DEFAULT '' NOT NULL AFTER utm_content,
				ADD `utm_source_last` varchar(255) DEFAULT '' NOT NULL AFTER utm_content,
				ADD INDEX (referrer_last),
				ADD INDEX (utm_source_last($max_index_length)),
				ADD INDEX (utm_medium_last($max_index_length)),
				ADD INDEX (utm_campaign_last($max_index_length)),
				ADD INDEX (utm_term_last($max_index_length))";
		$wpdb->query( $sql_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

if ( ! function_exists( 'wffn_alter_conversion_table_add_source' ) ) {

	function wffn_alter_conversion_table_add_source() {
		try {
			if ( ! class_exists( 'WooFunnels_Create_DB_Tables' ) || ! method_exists( 'WooFunnels_Create_DB_Tables', 'maybe_table_created_current_version' ) ) {
				return;
			}
			/**
			 * no need for alter table if table create in current version
			 */
			$created_tables = WooFunnels_Create_DB_Tables::get_instance()->maybe_table_created_current_version();
			$conv_table     = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
			if ( is_array( $created_tables ) && in_array( $conv_table, $created_tables, true ) ) {
				return;
			}


			global $wpdb;

			$conv_table = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
			$table_name = $wpdb->prefix . $conv_table;
			$is_col     = $wpdb->get_col( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND table_name = %s AND column_name = 'source_id'", $wpdb->dbname, $table_name ) );
			/**
			 * Check if column already exists
			 */
			if ( ! empty( $is_col ) ) {
				WFFN_Core()->logger->log( __FUNCTION__ . ' source_id already created ', 'wffn', true );

				return;
			}

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql_query = "
				ALTER TABLE {$table_name}
				ADD `source_id` bigint(20) unsigned NOT NULL default 0 COMMENT 'save checkout revenue source',
				ADD INDEX `source_id` (`source_id`)";

			$wpdb->query( $sql_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Log database errors or success
			if ( $wpdb->last_error ) {
				WFFN_Core()->logger->log( __FUNCTION__ . ' Database error during add source id in conversion: ' . $wpdb->last_error, 'wffn', true );
			} else {
				WFFN_Core()->logger->log( __FUNCTION__ . ' Successfully add source id in conversion table.', 'wffn', true );
			}
		} catch ( Exception|Error $e ) {
			WFFN_Core()->logger->log( __FUNCTION__ . ' error during during add source id in conversion table: ' . $e->getMessage(), 'wffn', true );

		}

	}
}
if ( ! function_exists( 'wffn_add_order_number_column_in_conversion_table' ) ) {

	function wffn_add_order_number_column_in_conversion_table() {
		try {
			if ( ! class_exists( 'WooFunnels_Create_DB_Tables' ) || ! method_exists( 'WooFunnels_Create_DB_Tables', 'maybe_table_created_current_version' ) ) {
				return;
			}
			/**
			 * no need for alter table if table create in current version
			 */
			$created_tables = WooFunnels_Create_DB_Tables::get_instance()->maybe_table_created_current_version();
			$conv_table     = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
			if ( is_array( $created_tables ) && in_array( $conv_table, $created_tables, true ) ) {
				return;
			}

			global $wpdb;

			$conv_table = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();
			$table_name = $wpdb->prefix . $conv_table;
			$is_col     = $wpdb->get_col( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND table_name = %s AND column_name = 'order_number'", $wpdb->dbname, $table_name ) );

			/**
			 * Check if column already exists
			 */
			if ( ! empty( $is_col ) ) {
				WFFN_Core()->logger->log( __FUNCTION__ . ' order_number column already exists ', 'wffn', true );
				return;
			}

			$sql_query = "
				ALTER TABLE {$table_name}
				ADD `order_number` varchar(100) DEFAULT '' NOT NULL COMMENT 'stores sequential/custom order number',
				ADD INDEX `order_number` (`order_number`)";

			$wpdb->query( $sql_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( $wpdb->last_error ) {
				WFFN_Core()->logger->log( __FUNCTION__ . ' Database error during adding order_number column: ' . $wpdb->last_error, 'wffn', true );
			} else {
				WFFN_Core()->logger->log( __FUNCTION__ . ' Successfully added order_number column to conversion table.', 'wffn', true );
			}
		} catch ( Exception|Error $e ) {
			WFFN_Core()->logger->log( __FUNCTION__ . ' error during adding order_number column in conversion table: ' . $e->getMessage(), 'wffn', true );
		}
	}
}

if ( ! function_exists( 'wffn_update_migrate_data_for_currency_switcher' ) ) {
	/**
	 * Update previous orders value for currency switcher compatibility
	 * @return void
	 */
	function wffn_update_migrate_data_for_currency_switcher() {
		try {
			// Ensure that necessary classes and methods exist
			if ( ! class_exists( 'WooFunnels_Create_DB_Tables' ) || ! method_exists( WooFunnels_Create_DB_Tables::class, 'maybe_table_created_current_version' ) ) {
				return;
			}

			// Get created tables and check if the conversion table exists
			$created_tables = WooFunnels_Create_DB_Tables::get_instance()->maybe_table_created_current_version();
			$conv_table     = BWF_Ecomm_Tracking_Common::get_instance()->conversion_table_name();

			if ( is_array( $created_tables ) && in_array( $conv_table, $created_tables, true ) ) {
				WFFN_Core()->logger->log( 'Currency switcher table created in current version', 'wffn', true );

				return;
			}

			// Check if the conversion tracking migration has been run
			if ( ! function_exists( 'wffn_conversion_tracking_migrator' ) || ! in_array( absint( WFFN_Conversion_Tracking_Migrator::get_instance()->get_upgrade_state() ), [ 0, 3, 4 ], true ) ) {
				WFFN_Core()->logger->log( 'Conversion migration not yet run on site', 'wffn', true );

				return;
			}

			// Update currency switcher data if the function exists
			if ( function_exists( 'wffn_update_currency_switcher_data' ) ) {
				wffn_update_currency_switcher_data();
			}
		} catch ( Exception|Error $e ) {
			WFFN_Core()->logger->log( 'error during currency switcher migration: ' . $e->getMessage(), 'wffn', true );

		}
	}
}

if ( ! function_exists( 'wffn_update_currency_switcher_data' ) ) {
	function wffn_update_currency_switcher_data() {
		// Ensure that the compatibility class exists
		if ( ! class_exists( 'BWF_Plugin_Compatibilities' ) ) {
			return;
		}

		// Load compatibilities if not already loaded
		if ( empty( BWF_Plugin_Compatibilities::$plugin_compatibilities ) ) {
			BWF_Plugin_Compatibilities::load_all_compatibilities();
		}

		// Check if no currency plugin is found
		if ( empty( BWF_Plugin_Compatibilities::$plugin_compatibilities ) ) {
			WFFN_Core()->logger->log( 'No currency plugin found', 'wffn', true );

			return;
		}

		// Update database values
		global $wpdb;
		$table_name = $wpdb->prefix . 'bwf_conversion_tracking';
		WFFN_Core()->logger->log( 'Time before query:: ' . current_time( 'timestamp' ), 'wffn', true );

		/**
		 * This query below will only update the value column for all the rows where checkout_total or bump_total or offer_total is not 0
		 */
		$wpdb->query( $wpdb->prepare( "UPDATE {$table_name}
			 SET value = IFNULL(checkout_total, %d) + IFNULL(bump_total, %d) + IFNULL(offer_total, %d) WHERE IFNULL(checkout_total, 0) <> 0 OR IFNULL(bump_total, 0) <> 0;", 0, 0, 0 ) );
		WFFN_Core()->logger->log( 'Time after query:: ' . current_time( 'timestamp' ), 'wffn', true );

		// Log database errors or success
		if ( $wpdb->last_error ) {
			WFFN_Core()->logger->log( 'Database error during currency switcher migration: ' . $wpdb->last_error, 'wffn', true );
		} else {
			WFFN_Core()->logger->log( 'Successfully migrated data for currency switcher.', 'wffn', true );
		}
	}
}

if ( ! function_exists( 'wffn_update_email_default_settings' ) ) {
	/**
	 * Function to set the default settings for notification settings
	 *
	 * @return void
	 */

	function wffn_update_email_default_settings() {


		$new_settings = array(
			'bwf_enable_notification'    => true,
			'bwf_notification_frequency' => array( 'weekly', 'monthly' ),
			'bwf_notification_time'      => array(
				'hours'   => '10',
				'minutes' => '00',
				'ampm'    => 'am',
			),
			'bwf_external_user'          => array(),
		);


		$users         = get_users( array( 'role' => 'administrator', 'number' => 10 ) );
		$user_selector = array();

		foreach ( $users as $user ) {
			$user_selector[] = array(
				'id'   => $user->ID,
				'name' => $user->display_name . ' ( ' . $user->user_email . ' )',
			);
		}

		$new_settings['bwf_notification_user_selector'] = $user_selector;

		WFFN_Email_Notification::save_settings( $new_settings );
	}
}

if ( ! function_exists( 'wffn_set_default_value_in_autoload_option' ) ) {
	/**
	 * Function to set the default settings for notification settings
	 *
	 * @return void
	 */

	function wffn_set_default_value_in_autoload_option() {
		try {

			/**
			 * Update site options on onload
			 */
			$gen_config = get_option( 'bwf_gen_config' );
			if ( empty( $gen_config ) && class_exists( 'BWF_Admin_General_Settings' ) ) {
				BWF_Admin_General_Settings::get_instance()->update_global_settings_fields( array( 'fb_pixel_key' => '' ) );
			}

			

			/**
			 * Update notice option on onload
			 */
			$notice = get_option( '_bwf_db_upgrade' );
			if ( empty( $notice ) ) {
				update_option( '_bwf_db_upgrade', '0', true );
			}

			/**
			 * Update notice option on onload
			 */
			$notice = get_option( 'woofunnel_hide_update_notice' );
			if ( empty( $notice ) ) {
				update_option( 'woofunnel_hide_update_notice', 'no', true );
			}

			/**
			 * Update site options on onload
			 */
			$fb_site_options = get_option( 'fb_site_options' );
			if ( empty( $fb_site_options ) ) {
				update_option( 'fb_site_options', [], true );
			}

			/**
			 * Update default option for aero
			 */
			if ( class_exists( 'WFACP_Common' ) ) {
				$g_setting = get_option( '_wfacp_global_settings' );
				if ( empty( $g_setting ) ) {
					$options = WFACP_Common::global_settings( true );
					update_option( '_wfacp_global_settings', $options, true );
				}

				/**
				 * Update google-map key which is saved in funnelkit checkout settings to new settings
				 */
				$options = BWF_Admin_General_Settings::get_instance()->setup_options();
				if ( empty( $options['funnelkit_google_map_key'] ) ) {
					$global_settings                     = get_option( '_wfacp_global_settings', [] );
					$options['funnelkit_google_map_key'] = isset( $global_settings['wfacp_google_address_key'] ) ? $global_settings['wfacp_google_address_key'] : '';
					BWF_Admin_General_Settings::get_instance()->update_global_settings_fields( $options );
				}

			}


		} catch ( Exception|Error $e ) {

		}
	}

}

if ( ! function_exists( 'wffn_cleanup_data_for_conversion' ) ) {
	/**
	 * Cleanup db table entries
	 *
	 * @return void
	 */
	function wffn_cleanup_data_for_conversion() {
		if ( ! class_exists( 'WFFN_Core' ) ) {
			return;
		}

		// Schedule the action if not already scheduled
		if ( ! wp_next_scheduled( 'fk_optimize_conversion_table_analytics' ) ) {
			wp_schedule_event( time(), 'hourly', 'fk_optimize_conversion_table_analytics' );
			WFFN_Core()->logger->log( 'Recurring schedule for fk_optimize_conversion_table_analytics.', 'wffn_ay', true );
		}

	}
}

if ( ! function_exists( 'wffn_detect_and_set_default_page_builder' ) ) {
	/**
	 * Detect and set default page builder during database upgrade
	 *
	 * @return void
	 */
	function wffn_detect_and_set_default_page_builder() {
		try {
			if ( class_exists( 'WFFN_Core' ) && method_exists( WFFN_Core()->admin, 'detect_and_set_default_page_builder' ) ) {
				WFFN_Core()->admin->detect_and_set_default_page_builder();
				BWF_Logger::get_instance()->log( 'Page builder detection completed during database upgrade', 'wffn_page_builder_detection' );
			}
		} catch ( \Throwable $e ) {
			BWF_Logger::get_instance()->log( 
				sprintf( 'Error during page builder detection in database upgrade: %s', $e->getMessage() ), 
				'wffn_page_builder_detection' 
			);
		}
	}
}
