<?php


if ( ! class_exists( 'BWFAN_Model_Links' ) ) {

	class BWFAN_Model_Links extends BWFAN_Model {

		/**
		 * Check link is existing or not
		 *
		 * @param $value
		 * @param $search_by
		 * @param $return_col
		 * @param $type
		 *
		 * @return string|null
		 */
		public static function is_link_exists( $value, $search_by = 'ID', $return_col = '', $type = '' ) {
			global $wpdb;
			$type_query = '';
			$return_col = empty( $return_col ) ? "`l_hash`" : $return_col;
			$args       = [ $value ];
			if ( ! empty( $type ) ) {
				$type_query = " AND `type` = %d";
				$args[]     = $type;
			}

			$query = "SELECT {$return_col} FROM {$wpdb->prefix}bwfan_links WHERE `{$search_by}` = %s {$type_query}";

			return $wpdb->get_var( $wpdb->prepare( $query, $args ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		}

		/**
		 * Check link hash and cleaned url exists or not
		 *
		 * @param string $clean_url
		 * @param string $l_hash
		 *
		 * @return mixed
		 */
		public static function is_link_hash_exists( $clean_url = '', $l_hash = '' ) {
			if ( empty( $l_hash ) || empty( $clean_url ) ) {
				return false;
			}

			global $wpdb;

			return $wpdb->get_var( $wpdb->prepare( "SELECT `ID` FROM {$wpdb->prefix}bwfan_links WHERE `l_hash` = %s AND `clean_url` = %s", $l_hash, $clean_url ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		}

		/**
		 * Check if links exist
		 *
		 * @param string $link
		 * @param array $data
		 *
		 * @return string|bool|null
		 */
		public static function check_if_link_exists( $link = '', $data = [] ) {
			if ( empty( $link ) ) {
				return false;
			}

			global $wpdb;

			$where = " 1=1 ";

			if ( ! empty( $data['clean_url'] ) ) {
				$where .= $wpdb->prepare( " AND `clean_url` = %s", esc_sql( $data['clean_url'] ) );
			}

			if ( ! empty( $data['template_id'] ) ) {
				$where .= $wpdb->prepare( " AND `tid` = %d", intval( $data['template_id'] ) );
			}
			if ( ! empty( $data['type'] ) ) {
				$where .= $wpdb->prepare( " AND `type` = %d", intval( $data['type'] ) );
			}
			if ( ! empty( $data['oid'] ) ) {
				$where .= $wpdb->prepare( " AND `oid` = %d", intval( $data['oid'] ) );
			}
			if ( ! empty( $data['sid'] ) ) {
				$where .= $wpdb->prepare( " AND `sid` = %d", intval( $data['sid'] ) );
			}

			return $wpdb->get_var( "SELECT `l_hash` FROM {$wpdb->prefix}bwfan_links WHERE $where" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		}

		/**
		 * Get link ID by tid
		 *
		 * @param $cleaned_url
		 * @param $data
		 *
		 * @return string|null
		 */
		public static function get_link_id_by_tid( $cleaned_url, $data ) {
			if ( empty( $cleaned_url ) || empty( $data ) ) {
				return '';
			}
			$tid  = $data['tid'] ?? 0;
			$oid  = $data['oid'] ?? 0;
			$type = $data['type'] ?? 0;

			global $wpdb;
			$query = "SELECT `ID` FROM {$wpdb->prefix}bwfan_links WHERE `clean_url` = %s AND `tid` = %d AND `oid` = %d AND `type`=%d ORDER BY `ID` DESC LIMIT 1";

			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
			return $wpdb->get_var( $wpdb->prepare( $query, [
				$cleaned_url,
				$tid,
				$oid,
				$type
			] ) );
		}
	}
}
