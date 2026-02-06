<?php

if ( ! class_exists( 'BWFAN_Model_Engagement_Trackingmeta' ) && BWFAN_Common::is_pro_3_0() ) {
	class BWFAN_Model_Engagement_Trackingmeta extends BWFAN_Model {
		static $primary_key = 'ID';

		static function get_meta( $con_id, $key = '' ) {
			global $wpdb;
			$table          = self::_table();
			$meta_key_query = '';
			if ( ! empty( $key ) ) {
				$meta_key_query = "meta_key = '$key'";
			}

			return $wpdb->get_results( "SELECT meta_key, meta_value from $table WHERE $meta_key_query AND eid=$con_id", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		static function get_merge_tags( $con_id ) {
			$result = self::get_meta( $con_id, 'merge_tags' );
			if ( empty( $result ) ) {
				return array();
			}

			try {
				$merge_tags = json_decode( $result[0]['meta_value'], true );
			} catch ( Exception $e ) {
				return array();
			}

			return $merge_tags;
		}

		static function get_notification_data( $con_id ) {
			$result = self::get_meta( $con_id, 'notification_data' );
			if ( empty( $result ) ) {
				return array();
			}
			try {
				$merge_tags = json_decode( $result[0]['meta_value'], true );
			} catch ( Exception $e ) {
				return array();
			}

			return $merge_tags;
		}

		public static function delete_engagements_meta( $ids ) {
			if ( empty( $ids ) ) {
				return;
			}

			global $wpdb;
			$table = self::_table();

			$placeholders = array_fill( 0, count( $ids ), '%d' );
			$placeholders = implode( ', ', $placeholders );

			$query = $wpdb->prepare( "DELETE FROM {$table} WHERE eid IN ($placeholders)", $ids );

			return $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}
}