<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Class WFFN_Optin_Contacts_Analytics
 */
if ( ! class_exists( 'WFFN_Optin_Contacts_Analytics' ) ) {

	class WFFN_Optin_Contacts_Analytics {

		/**
		 * instance of class
		 *
		 * @var null
		 */
		private static $ins = null;

		/**
		 * WFFN_Optin_Contacts_Analytics constructor.
		 */
		public function __construct() {
		}

		/**
		 * @return WFFN_Optin_Contacts_Analytics|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * @param $funnel_id
		 * @param string $search
		 *
		 * @return array|object|null
		 */
		public function get_contacts( $funnel_id, $search = '' ) {
			global $wpdb;

			if ( ! empty( $search ) ) {
				$query  = $wpdb->prepare( 'SELECT contact.id as cid, contact.f_name, contact.l_name, contact.email, optin.date, optin.opid FROM ' . $wpdb->prefix . 'bwf_contact' . ' AS contact JOIN ' . $wpdb->prefix . 'bwf_optin_entries' . ' AS optin ON contact.id=optin.cid WHERE optin.funnel_id=%d', $funnel_id );
				$query .= $wpdb->prepare( ' AND (contact.f_name LIKE %s OR contact.email LIKE %s) group by contact.id', '%' . $search . '%', '%' . $search . '%' );

			} else {
				$query = $wpdb->prepare( 'SELECT optin.cid FROM ' . $wpdb->prefix . 'bwf_optin_entries' . ' AS optin WHERE optin.funnel_id=%d', $funnel_id );
			}

			return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		}

		/**
		 * @param $funnel_id
		 * @param $cid
		 *
		 * @return array|object|null
		 */
		public function get_all_contacts_records( $funnel_id, $cid ) {
			global $wpdb;

			$funnel_id = absint( $funnel_id );
			$cid       = absint( $cid );
			$query     = $wpdb->prepare(
				"SELECT optin.step_id as 'object_id', optin.data as 'data', DATE_FORMAT(optin.date, '%%Y-%%m-%%dT%%TZ') as 'date', p.post_title as 'object_name', 'optin' as 'type' FROM " . $wpdb->prefix . 'bwf_optin_entries as optin LEFT JOIN ' . $wpdb->prefix . 'posts as p ON optin.step_id = p.id WHERE optin.funnel_id = %d AND optin.cid = %d ORDER BY optin.date ASC',
				$funnel_id,
				$cid
			);

			$data     = $wpdb->get_results( $query );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;

		}

		public function get_contacts_optin_records( $cid, $entry_ids ) {
			global $wpdb;
			$cid = absint( $cid );

			// Handle entry_ids - can be comma-separated string or array
			if ( is_string( $entry_ids ) ) {
				$entry_ids_array = array_map( 'absint', array_filter( explode( ',', $entry_ids ) ) );
			} else {
				$entry_ids_array = array_map( 'absint', (array) $entry_ids );
			}

			if ( empty( $entry_ids_array ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $entry_ids_array ), '%d' ) );
			$query_args   = array_merge( $entry_ids_array, array( $cid ) );

			$query = $wpdb->prepare(
				"SELECT optin.id, optin.funnel_id as fid, optin.email as email, optin.step_id as 'object_id', optin.data as 'data', DATE_FORMAT(optin.date, '%%Y-%%m-%%d %%T') as 'date', COALESCE( p.post_title, '' ) as 'object_name', 'optin' as 'type' FROM " . $wpdb->prefix . 'bwf_optin_entries as optin LEFT JOIN ' . $wpdb->prefix . "posts as p ON optin.step_id = p.id WHERE optin.id IN ( $placeholders ) AND optin.cid = %d ORDER BY optin.date ASC",
				...$query_args
			);

			$data     = $wpdb->get_results( $query );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;

		}

		/**
		 * @param $cid
		 *
		 * @return array|object|null
		 */
		public function get_all_contact_record_by_cid( $cid ) {
			global $wpdb;
			$cid   = absint( $cid );
			$query = $wpdb->prepare(
				"SELECT optin.step_id as 'object_id', optin.data as 'data', DATE_FORMAT(optin.date, '%%Y-%%m-%%dT%%TZ') as 'date', p.post_title as 'object_name', 'optin' as 'type' FROM " . $wpdb->prefix . 'bwf_optin_entries as optin LEFT JOIN ' . $wpdb->prefix . 'posts as p ON optin.step_id = p.id WHERE optin.cid = %d ORDER BY optin.date ASC',
				$cid
			);

			$data     = $wpdb->get_results( $query );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;

		}

		/**
		 * @param $limit
		 * @param $order
		 * @param $order_by
		 * @param $can_union
		 *
		 * @return string
		 */
		public function get_timeline_data_query( $limit, $order = 'DESC', $order_by = 'date', $can_union = true ) {
			global $wpdb;

			// Whitelist allowed ORDER BY columns to prevent SQL injection
			$allowed_order_by = array( 'date', 'id', 'step_id', 'funnel_id', 'cid' );
			$order_by         = in_array( $order_by, $allowed_order_by, true ) ? $order_by : 'date';

			// Whitelist ORDER direction
			$order = strtoupper( $order );
			$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

			// Sanitize LIMIT - ensure it's a positive integer
			$limit_clause = '';
			if ( '' !== $limit && is_numeric( $limit ) ) {
				$limit_clause = ' LIMIT ' . absint( $limit );
			}

			if ( $can_union ) {
				return "SELECT stats.step_id as id, stats.funnel_id as 'fid', stats.cid as 'cid', '0' as 'order_id', '0' as 'total_revenue', 'optin' as 'type', posts.post_title as 'post_title', stats.date as date FROM " . $wpdb->prefix . 'bwf_optin_entries AS stats LEFT JOIN ' . $wpdb->prefix . 'posts AS posts ON stats.step_id=posts.ID ORDER BY ' . $order_by . ' ' . $order . $limit_clause;

			} else {
				return "SELECT stats.step_id as id, stats.funnel_id as 'fid', stats.cid as 'cid', '0' as 'order_id', '0' as 'total_revenue', 'optin' as 'type', posts.post_title as 'post_title', contact.f_name as f_name, contact.l_name as l_name, stats.date as date FROM " . $wpdb->prefix . 'bwf_optin_entries AS stats LEFT JOIN ' . $wpdb->prefix . 'posts AS posts ON stats.step_id=posts.ID LEFT JOIN ' . $wpdb->prefix . "bwf_contact AS contact ON contact.id=cid WHERE contact.id != '' ORDER BY " . $order_by . ' ' . $order . $limit_clause;

			}

		}

		/**
		 * @param $cids
		 * @param $funnel_id
		 *
		 * @return array|false[]|true
		 */
		public function delete_contact( $cids, $funnel_id = 0 ) {
			global $wpdb;

			// Sanitize all cids as integers
			$cids      = array_map( 'absint', (array) $cids );
			$cids      = array_filter( $cids ); // Remove zeros
			$cid_count = count( $cids );

			if ( empty( $cid_count ) ) {
				return true;
			}

			$placeholders = implode( ',', array_fill( 0, $cid_count, '%d' ) );
			$funnel_id    = absint( $funnel_id );

			if ( $funnel_id > 0 ) {
				$query = $wpdb->prepare(
					'DELETE FROM ' . $wpdb->prefix . "bwf_optin_entries WHERE cid IN ($placeholders) AND fid = %d",
					...array_merge( $cids, array( $funnel_id ) )
				);
			} else {
				$query = $wpdb->prepare(
					'DELETE FROM ' . $wpdb->prefix . "bwf_optin_entries WHERE cid IN ($placeholders)",
					...$cids
				);
			}

			$wpdb->query( $query );

			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return true;

		}

		/**
		 * @param $funnel_id
		 * @param $start_date
		 * @param $end_date
		 * @param $is_interval
		 *
		 * @return string
		 */
		public function get_contacts_by_funnel_id( $funnel_id, $start_date, $end_date, $is_interval = '' ) {
			global $wpdb;
			$date           = ( '' !== $start_date && '' !== $end_date ) ? " AND `date` >= '" . esc_sql( $start_date ) . "' AND `date` < '" . esc_sql( $end_date ) . "' " : '';
			$funnel_query   = ( 0 === intval( $funnel_id ) ) ? ' AND funnel_id != ' . $funnel_id . ' ' : ' AND funnel_id = ' . esc_sql( $funnel_id ) . ' ';
			$interval_param = ! empty( $is_interval ) ? ', date as p_date ' : '';

			return 'SELECT DISTINCT cid as contacts ' . $interval_param . ' FROM `' . $wpdb->prefix . 'bwf_optin_entries` WHERE 1=1 ' . $date . ' ' . $funnel_query;
		}

		/**
		 * @param $funnel_id
		 */
		public function reset_analytics( $funnel_id ) {
			global $wpdb;
			$query = $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'bwf_optin_entries WHERE funnel_id=%d', $funnel_id );
			$wpdb->query( $query );
		}

		/**
		 * @param $entry_ids
		 *
		 * @return array|false[]|void
		 */
		public function delete_optin_entries( $entry_ids ) {
			global $wpdb;

			// Convert to array and sanitize as integers
			if ( is_string( $entry_ids ) ) {
				$entry_ids_array = array_map( 'absint', array_filter( explode( ',', $entry_ids ) ) );
			} else {
				$entry_ids_array = array_map( 'absint', array_filter( (array) $entry_ids ) );
			}

			if ( empty( $entry_ids_array ) ) {
				return;
			}

			$placeholders = implode( ',', array_fill( 0, count( $entry_ids_array ), '%d' ) );
			$query        = $wpdb->prepare(
				'DELETE FROM ' . $wpdb->prefix . "bwf_optin_entries WHERE id IN ($placeholders)",
				...$entry_ids_array
			);
			$wpdb->query( $query );

			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			do_action( 'wffn_delete_optin_entries', $entry_ids_array );

		}

	}
}
