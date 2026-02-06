<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

/**
 * This class contain data for experiments
 * Class WFFN_Funnels_DB
 */
if ( ! class_exists( 'WFFN_Funnels_DB' ) ) {
	class WFFN_Funnels_DB {

		static $primary_key = 'id';
		static $count = 20;
		static $query = [];

		static function init() {
		}

		public function clear_cache() {
			self::$query = [];
		}

		public function get( $value ) {
			global $wpdb;
			$sql = self::_fetch_sql( $value );
			if ( true === apply_filters( 'wffn_funnel_data_cache', true ) && isset( self::$query[ md5( $sql ) ] ) ) {
				return self::$query[ md5( $sql ) ];
			}
			$result                     = $wpdb->get_row( $sql, ARRAY_A );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			self::$query[ md5( $sql ) ] = $result;

			return $result;
		}

		private static function _fetch_sql( $value ) {
			global $wpdb;
			$sql = sprintf( 'SELECT * FROM %s WHERE %s = %%s', self::_table(), static::$primary_key );

			return $wpdb->prepare( $sql, $value );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		private static function _table() {
			global $wpdb;
			$table_name = 'bwf_funnels';

			return $wpdb->prefix . $table_name;
		}

		private static function _tablemeta() {
			global $wpdb;
			$table_name = 'bwf_funnelmeta';

			return $wpdb->prefix . $table_name;
		}

		public function insert( $data ) {
			global $wpdb;
			$wpdb->insert( self::_table(), $data );
		}

		public function update( $data, $where ) {
			global $wpdb;

			return $wpdb->update( self::_table(), $data, $where );
		}

		public function delete( $value ) {
			global $wpdb;
			$sql      = sprintf( 'DELETE FROM %s WHERE %s = %%s', self::_table(), static::$primary_key );
			$meta_sql = sprintf( 'DELETE FROM %s WHERE bwf_funnel_id = %%s', self::_tablemeta() );
			$wpdb->query( $wpdb->prepare( $meta_sql, $value ) );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			return $wpdb->query( $wpdb->prepare( $sql, $value ) );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		public function insert_id() {
			global $wpdb;

			return $wpdb->insert_id;
		}

		public function now() {
			return current_time( 'mysql' );
		}

		public function time_to_date( $time ) {
			return gmdate( 'Y-m-d H:i:s', $time );
		}

		public function date_to_time( $date ) {
			return strtotime( $date . ' GMT' );
		}

		public function num_rows() {
			global $wpdb;

			return $wpdb->num_rows;
		}

		public function get_specific_rows( $where_key, $where_value ) {
			global $wpdb;
			$where_key   = esc_sql( $where_key );
			$where_value = esc_sql( $where_value );

			return $wpdb->get_results( 'SELECT * FROM ' . self::_table() . " WHERE $where_key = '$where_value'", ARRAY_A );//phpcs:ignore
		}


		public function get_results( $query ) {
			global $wpdb;
			$query = str_replace( '{table_name}', self::_table(), $query );
			$query = str_replace( '{table_name_meta}', self::_tablemeta(), $query );

			return $wpdb->get_results( $query, ARRAY_A );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		public function get_row( $query ) {
			global $wpdb;
			$query = str_replace( '{table_name}', self::_table(), $query );
			$query = str_replace( '{table_name_meta}', self::_tablemeta(), $query );

			return $wpdb->get_row( $query, ARRAY_A );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}


		public function delete_multiple( $query ) {
			global $wpdb;
			$query = str_replace( '{table_name}', self::_table(), $query );
			$query = str_replace( '{table_name_meta}', self::_tablemeta(), $query );
			$wpdb->query( $query );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		public function update_multiple( $query ) {
			global $wpdb;
			$query = str_replace( '{table_name}', self::_table(), $query );
			$query = str_replace( '{table_name_meta}', self::_tablemeta(), $query );
			$wpdb->query( $query );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		public function get_last_error() {
			global $wpdb;

			return $wpdb->last_error;
		}

		/**
		 * @param $object_id
		 * @param $meta_key
		 * @param $meta_value
		 *
		 * @return void
		 */
		public function update_meta( $object_id, $meta_key, $meta_value ) {
			include_once plugin_dir_path( WFFN_PLUGIN_FILE ) . 'admin/db/class-wffn-db-tables.php';
			$tables = WFFN_DB_Tables::get_instance();
			$tables->define_tables();
			update_metadata( 'bwf_funnel', $object_id, $meta_key, $meta_value );

		}



		/**
		 * Delete a metadata
		 * @param $object_id
		 * @param $meta_key
		 * @param $meta_value
		 *
		 * @return void
		 */
		public function delete_meta( $object_id, $meta_key ) {
			include_once plugin_dir_path( WFFN_PLUGIN_FILE ) . 'admin/db/class-wffn-db-tables.php';
			$tables = WFFN_DB_Tables::get_instance();
			$tables->define_tables();
			delete_metadata( 'bwf_funnel', $object_id, $meta_key );

		}

		/**
		 *  Get contact meta for a given contact id and meta key
		 *
		 * @param $object_id
		 * @param $meta_key
		 *
		 * @return array|false|mixed
		 */
		public function get_meta( $object_id, $meta_key = '' ) {
			include_once plugin_dir_path( WFFN_PLUGIN_FILE ) . 'admin/db/class-wffn-db-tables.php';
			$tables = WFFN_DB_Tables::get_instance();
			$tables->define_tables();

			return get_metadata( 'bwf_funnel', $object_id, $meta_key, true );
		}

	}

	WFFN_Funnels_DB::init();
}
