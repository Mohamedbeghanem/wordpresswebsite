<?php

class BWFAN_Model_Abandonedcarts extends BWFAN_Model {
	public static $primary_key = 'ID';

	public static function get_abandoned_data( $where = '', $offset = '', $per_page = '', $order_by = 'ID', $output = OBJECT ) {
		global $wpdb;

		$limit_string = '';
		if ( '' !== $offset ) {
			$limit_string = "LIMIT {$offset}";
		}
		if ( '' !== $per_page && '' !== $limit_string ) {

			$limit_string .= ',' . $per_page;
		}

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bwfan_abandonedcarts {$where} ORDER BY {$order_by} DESC {$limit_string}", $output ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function delete_abandoned_cart_row( $data ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return;
		}

		global $wpdb;
		$where      = '';
		$count      = count( $data );
		$i          = 0;
		$table_name = $wpdb->prefix . 'bwfan_abandonedcarts';

		foreach ( $data as $key => $value ) {
			$i ++;

			if ( 'string' === gettype( $value ) ) {
				$where .= '`' . $key . '` = ' . "'" . $value . "'";
			} else {
				$where .= '`' . $key . '` = ' . $value;
			}

			if ( $i < $count ) {
				$where .= ' AND ';
			}
		}

		return $wpdb->query( 'DELETE FROM ' . $table_name . " WHERE $where" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Check if any carts available for execution.
	 *
	 * @param $abandoned_time
	 *
	 * @return bool
	 */
	public static function maybe_run( $abandoned_time = 0 ) {
		global $wpdb;
		$table          = self::_table();
		$abandoned_time = intval( $abandoned_time );
		$query          = "SELECT `ID` FROM {$table} WHERE `status` IN (0, 4)";
		if ( $abandoned_time > 0 ) {
			$query .= $wpdb->prepare( " AND TIMESTAMPDIFF(MINUTE,last_modified,UTC_TIMESTAMP) >= %d", $abandoned_time );
		}
		$count = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return ! empty( $count );
	}


	/**
	 * Get duplicate cart in last 12 hrs
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_duplicate_entry() {
		global $wpdb;
		$start_time = date( 'Y-m-d H:i:s', strtotime( '-12 hours' ) );

		$query = "SELECT GROUP_CONCAT(`ID`) AS `pkey` FROM `{$wpdb->prefix}bwfan_abandonedcarts` WHERE `created_time` > %s AND (`status` = 0 OR `status` = 3) GROUP BY `email` HAVING COUNT(`email`) > 1";
		$query = $wpdb->prepare( $query, $start_time );

		$result = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $result ) ) {
			return [];
		}
		$result    = array_column( $result, 'pkey' );
		$final_ids = [];
		foreach ( $result as $item ) {
			$values = array_map( 'trim', explode( ',', $item ) );
			$values = array_map( 'intval', $values );
			array_pop( $values );
			$final_ids = array_merge( $final_ids, $values );
		};

		return $final_ids;
	}
}
