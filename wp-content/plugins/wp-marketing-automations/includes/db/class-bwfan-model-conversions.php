<?php

if ( ! class_exists( 'BWFAN_Model_Conversions' ) && BWFAN_Common::is_pro_3_0() ) {

	class BWFAN_Model_Conversions extends BWFAN_Model {
		static $primary_key = 'ID';

		public static function get_conversions_by_source_type( $source_id, $source_type = 1, $limit = 0, $offset = 25 ) {
			global $wpdb;
			$table       = self::_table();
			$query       = "SELECT bwc.* FROM $table as bwc JOIN {$wpdb->prefix}posts as p ON bwc.wcid=p.ID WHERE bwc.oid = $source_id AND bwc.otype=$source_type ORDER BY bwc.wcid DESC LIMIT $limit OFFSET $offset";
			$conversions = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $conversions ) ) {
				return [ 'conversions' => array(), 'total' => 0 ];
			}

			$total_query = "SELECT COUNT(*) FROM $table as bwc JOIN {$wpdb->prefix}posts as p ON bwc.wcid=p.ID  WHERE bwc.oid = $source_id AND bwc.otype=$source_type";
			$total       = absint( $wpdb->get_var( $total_query ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			foreach ( $conversions as $key => $conv ) {
				$order = wc_get_order( absint( $conv['wcid'] ) );

				/** unset the conversion if order deleted or not exists */
				if ( ! $order instanceof WC_Order ) {
					unset( $conversions[ $key ] );
					continue;
				}

				$order_details = [];

				$order_details['f_name']   = $order->get_billing_first_name();
				$order_details['l_name']   = $order->get_billing_last_name();
				$order_details['email']    = $order->get_billing_email();
				$order_details['status']   = $order->get_status();
				$order_items               = $order->get_items();
				$order_details['items']    = [];
				$order_details['currency'] = BWFAN_Automations::get_currency( $order->get_currency() );
				foreach ( $order_items as $item_key => $item ) {
					$product_id   = $item->get_product_id(); // the Product id
					$variation_id = $item->get_variation_id();
					if ( ! empty( $variation_id ) ) {
						$order_details['items'][ $variation_id ] = $item->get_name();
					} else {
						$order_details['items'][ $product_id ] = $item->get_name();
					}
				}

				$conversions[ $key ]            = array_replace( $conv, $order_details );
				$conversions[ $key ]['wctotal'] = $order->get_total();
			}

			return [
				'conversions' => $conversions,
				'total'       => $total
			];
		}

		public static function get_conversions_by_oid( $oid, $contact_id, $engagements_ids = [], $type = 1 ) {
			global $wpdb;
			$table = self::_table();
			$query = "SELECT wcid,date,wctotal FROM $table WHERE otype=$type AND oid=$oid AND cid=$contact_id";
			if ( ! empty( $engagements_ids ) ) {
				$engagements_ids = implode( ', ', $engagements_ids );
				$query           .= " AND trackid IN($engagements_ids)";
			}

			return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_conversions_for_check_validity( $saved_last_conversion_id ) {
			if ( empty( absint( $saved_last_conversion_id ) ) ) {
				return [];
			}

			global $wpdb;
			$table = self::_table();
			$and   = '';
			if ( ! empty( $saved_last_conversion_id ) ) {
				$and .= " AND ID <= $saved_last_conversion_id";
			}

			$query = "SELECt ID,wcid FROM $table WHERE 1=1 $and ORDER BY ID DESC";

			return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_last_conversion_id() {
			global $wpdb;
			$table = self::_table();
			$query = "SELECT MAX(`ID`) FROM $table";

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function delete_conversions_by_track_id( $ids ) {
			if ( empty( $ids ) ) {
				return;
			}

			global $wpdb;
			$table = self::_table();

			$placeholders = array_fill( 0, count( $ids ), '%d' );
			$placeholders = implode( ', ', $placeholders );

			$query = $wpdb->prepare( "DELETE FROM {$table} WHERE trackid IN ($placeholders)", $ids );

			return $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_automation_revenue( $aid, $start_date, $end_date, $is_interval, $interval ) {
			global $wpdb;
			$table = self::_table();

			$date_col       = "date";
			$interval_query = '';
			$group_by       = '';
			$order_by       = ' ID ';

			if ( 'interval' === $is_interval ) {
				$get_interval   = BWFCRM_Dashboards::get_interval_format_query( $interval, $date_col );
				$interval_query = $get_interval['interval_query'];
				$interval_group = $get_interval['interval_group'];
				$group_by       = "GROUP BY " . $interval_group;
				$order_by       = ' time_interval ';
			}
			$base_query = "SELECT  count(ID) as conversions, SUM(wctotal) as revenue $interval_query FROM `" . $table . "` WHERE 1=1 AND oid = $aid AND otype = 1 AND `" . $date_col . "` >= '" . $start_date . "' AND `" . $date_col . "` <= '" . $end_date . "'" . $group_by . " ORDER BY " . $order_by . " ASC";

			return $wpdb->get_results( $base_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}
}