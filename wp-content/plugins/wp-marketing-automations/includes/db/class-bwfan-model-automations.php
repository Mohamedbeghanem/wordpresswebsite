<?php

class BWFAN_Model_Automations extends BWFAN_Model {
	static $primary_key = 'ID';

	public static function count_rows( $dependency = null ) {
		global $wpdb;
		$table_name = self::_table();
		$sql        = 'SELECT COUNT(*) FROM ' . $table_name;

		if ( isset( $_GET['status'] ) && 'all' !== sanitize_text_field( $_GET['status'] ) ) { // WordPress.CSRF.NonceVerification.NoNonceVerification
			$status = sanitize_text_field( $_GET['status'] ); // WordPress.CSRF.NonceVerification.NoNonceVerification
			$status = ( 'active' === $status ) ? 1 : 2;
			$sql    = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE status = %d", $status ); // WPCS: unprepared SQL OK
		}

		return $wpdb->get_var( $sql ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Return Automation detail with its meta details
	 *
	 * @param $automation_id
	 *
	 * @return array|object|void|null
	 */
	public static function get_automation_with_data( $automation_id ) {
		$data = self::get( $automation_id );
		if ( ! is_array( $data ) || empty( $data ) ) {
			return [];
		}

		$data['meta'] = BWFAN_Model_Automationmeta::get_automation_meta( $automation_id );

		return $data;
	}

	/**
	 * Get first automation id
	 */
	public static function get_first_automation_id() {
		global $wpdb;
		$query = "SELECT MIN(id) FROM " . self::_table();

		return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get automation name
	 */
	public static function get_event_name( $automation_id ) {
		global $wpdb;
		$table_name = self::_table();
		$query      = $wpdb->prepare( "SELECT event FROM {$table_name} WHERE ID = %d", $automation_id );
		$result     = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return isset( $result['event'] ) ? $result['event'] : '';
	}

	/**
	 * Get automation run count for a contact
	 *
	 * @param $automation_id
	 * @param $contact_id
	 *
	 * @return int
	 */
	public static function get_contact_automation_run_count( $automation_id = '', $contact_id = '' ) {
		if ( empty( $automation_id ) || empty( $contact_id ) ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'bwfan_contact_automations';

		$query         = $wpdb->prepare( "SELECT count(*) as count FROM {$table} WHERE `contact_id` = %d AND `automation_id` = %d", $contact_id, $automation_id );
		$running_count = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return absint( $running_count );
	}

	/**
	 * Get top 5 automations
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_top_automations() {
		global $wpdb;

		$automation_table = $wpdb->prefix . 'bwfan_automations';
		if ( ! bwfan_is_woocommerce_active() ) {
			$base_query = "SELECT COALESCE(a.`ID`,'') AS `aid`, COALESCE(a.`title`,'') AS `name`, a.`v`, a.event , SUM(IF(open>0, 1, 0)) AS `open_count` FROM {$automation_table} AS a LEFT JOIN {$wpdb->prefix}bwfan_engagement_tracking AS et ON et.oid = a.ID WHERE et.type = 1 GROUP BY et.`oid` ORDER BY `open_count` DESC LIMIT 0,5";
		} else {
			$conversion_table = $wpdb->prefix . 'bwfan_conversions';
			$base_query       = "SELECT COALESCE(a.`ID`,'') AS `aid`, COALESCE(a.`title`,'') AS `name`, a.`v`, a.event, SUM(c.`wctotal`) AS `total_revenue` FROM $automation_table AS a LEFT JOIN $conversion_table AS c ON c.`oid` = a.`ID` WHERE c.`otype` = 1 GROUP BY a.`ID` ORDER BY `total_revenue` DESC LIMIT 0,5";
		}

		return $wpdb->get_results( $base_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @param string $where
	 *
	 * @return array|object|null
	 */
	public static function get_last_abandoned_cart( $where = '' ) {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT `last_modified`, `items`, `total` FROM {$wpdb->prefix}bwfan_abandonedcarts {$where} ORDER BY `last_modified` DESC LIMIT 0,1", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results;
	}

	public static function get_tasks_for_contact( $contact_id ) {
		global $wpdb;
		$scheduled_tasks           = array();
		$get_contact_automation_id = $wpdb->get_col( "SELECT DISTINCT automation_id from {$wpdb->prefix}bwfan_contact_automations where contact_id='" . $contact_id . "'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $get_contact_automation_id ) ) {
			return $scheduled_tasks;
		}

		$stringPlaceholders     = array_fill( 0, count( $get_contact_automation_id ), '%s' );
		$placeholdersautomation = implode( ', ', $stringPlaceholders );

		$scheduled_tasks_query = $wpdb->prepare( "
								SELECT t.ID as id, t.integration_slug as slug, t.integration_action as action, t.automation_id as a_id, t.status as status, t.e_date as date
								FROM {$wpdb->prefix}bwfan_tasks as t
								LEFT JOIN {$wpdb->prefix}bwfan_taskmeta as m
								ON t.ID = m.bwfan_task_id
								WHERE t.automation_id IN ($placeholdersautomation)
								ORDER BY t.e_date DESC
								", $get_contact_automation_id );
		$scheduled_tasks       = $wpdb->get_results( $scheduled_tasks_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $scheduled_tasks;
	}


	public static function get_logs_for_contact( $contact_id ) {
		$contact_logs = array();

		global $wpdb;

		$get_contact_automation_id = $wpdb->get_col( "SELECT DISTINCT automation_id from {$wpdb->prefix}bwfan_contact_automations where contact_id='" . $contact_id . "'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $get_contact_automation_id ) ) {
			return $contact_logs;
		}

		$stringPlaceholders     = array_fill( 0, count( $get_contact_automation_id ), '%s' );
		$placeholdersautomation = implode( ', ', $stringPlaceholders );

		$contact_logs_query = $wpdb->prepare( "
								SELECT l.ID as id, l.integration_slug as slug, l.integration_action as action, l.automation_id as a_id, l.status as status, l.e_date as date
								FROM {$wpdb->prefix}bwfan_logs as l
								LEFT JOIN {$wpdb->prefix}bwfan_logmeta as m
								ON l.ID = m.bwfan_log_id
								WHERE l.automation_id IN ($placeholdersautomation)
								ORDER BY l.e_date DESC
								", $get_contact_automation_id );
		$contact_logs       = $wpdb->get_results( $contact_logs_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $contact_logs;
	}
}
