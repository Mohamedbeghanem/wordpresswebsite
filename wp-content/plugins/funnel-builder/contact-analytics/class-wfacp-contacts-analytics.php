<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Class WFACP_Contacts_Analytics
 */
if ( ! class_exists( 'WFACP_Contacts_Analytics' ) ) {
	class WFACP_Contacts_Analytics extends WFFN_REST_Controller {

		/**
		 * instance of class
		 *
		 * @var null
		 */
		private static $ins = null;

		/**
		 * WFACP_Contacts_Analytics constructor.
		 */
		public function __construct() {
		}

		/**
		 * @return WFACP_Contacts_Analytics|null
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
				$query  = $wpdb->prepare( 'SELECT contact.id as cid, contact.f_name, contact.l_name, contact.email, aero.date, aero.total_revenue, aero.wfacp_id FROM ' . $wpdb->prefix . 'bwf_contact' . ' AS contact JOIN ' . $wpdb->prefix . 'wfacp_stats' . ' AS aero ON contact.id=aero.cid WHERE aero.fid=%d', $funnel_id );
				$query .= $wpdb->prepare( ' AND (contact.f_name LIKE %s OR contact.email LIKE %s) group by contact.id', '%' . $search . '%', '%' . $search . '%' );

			} else {
				$query = $wpdb->prepare( 'SELECT aero.cid FROM ' . $wpdb->prefix . 'wfacp_stats' . ' AS aero WHERE aero.fid=%d', $funnel_id );
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
			$item_data = array();
			$funnel_id = absint( $funnel_id );
			$cid       = absint( $cid );

			$query = $wpdb->prepare(
				"SELECT aero.order_id as 'order_id', aero.wfacp_id as 'object_id', p.post_title as 'object_name', aero.total_revenue as 'total_revenue', DATE_FORMAT(aero.date, '%%Y-%%m-%%dT%%TZ') as 'date', 'checkout' as 'type' FROM " . $wpdb->prefix . 'wfacp_stats AS aero LEFT JOIN ' . $wpdb->prefix . 'posts as p ON aero.wfacp_id = p.id WHERE aero.fid = %d AND aero.cid = %d ORDER BY aero.date ASC',
				$funnel_id,
				$cid
			);

			$order_data = $wpdb->get_results( $query );
			$db_error   = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			if ( ! is_array( $order_data ) || count( $order_data ) === 0 ) {
				return array();
			}

			$get_order_ids = wp_list_pluck( $order_data, 'order_id' );

			if ( is_array( $get_order_ids ) && count( $get_order_ids ) > 0 ) {
				// Sanitize order IDs
				$get_order_ids = array_map( 'absint', $get_order_ids );
				$get_order_ids = array_filter( $get_order_ids );

				if ( ! empty( $get_order_ids ) ) {
					/**
					 * get order all items meta by order id for showing name and quantity
					 */
					$placeholders = implode( ',', array_fill( 0, count( $get_order_ids ), '%d' ) );
					$item_query   = $wpdb->prepare(
						"SELECT oi.order_id as 'order_id', oi.order_item_name as 'product_name', oi.order_item_id as 'item_id', oim2.meta_key as 'item_meta', oim2.meta_value as 'meta_value' FROM " . $wpdb->prefix . 'woocommerce_order_items as oi
						LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta as oim ON oi.order_item_id = oim.order_item_id
						LEFT JOIN ' . $wpdb->prefix . "woocommerce_order_itemmeta as oim2 ON oi.order_item_id = oim2.order_item_id
						WHERE oi.order_id IN ($placeholders) AND oi.order_item_type='line_item' AND oim.meta_key='_line_total' ORDER BY oi.order_id ASC",
						...$get_order_ids
					);

					$item_data = $wpdb->get_results( $item_query, ARRAY_A );
					$db_error  = WFFN_Common::maybe_wpdb_error( $wpdb );
					if ( true === $db_error['db_error'] ) {
						return $db_error;
					}
				}
			}

			/**
			 * Exclude which items purchased by bump and upsell
			 */
			$exclude_items     = array();
			$upstroke_purchase = array_keys( wp_list_pluck( $item_data, 'item_meta' ), '_upstroke_purchase', true );
			$bump_purchase     = array_keys( wp_list_pluck( $item_data, 'item_meta' ), '_bump_purchase', true );
			if ( is_array( $upstroke_purchase ) && count( $upstroke_purchase ) > 0 ) {
				$exclude_items = array_merge( $exclude_items, $upstroke_purchase );
			}
			if ( is_array( $bump_purchase ) && count( $bump_purchase ) > 0 ) {
				$exclude_items = array_merge( $exclude_items, $bump_purchase );
			}

			if ( is_array( $exclude_items ) && count( $exclude_items ) > 0 ) {
				foreach ( $exclude_items as $key_id ) {
					if ( isset( $item_data[ $key_id ] ) && isset( $item_data[ $key_id ]['item_id'] ) ) {
						$item_id = $item_data[ $key_id ]['item_id'];
						foreach ( $item_data as $key => $item ) {
							if ( absint( $item['item_id'] ) === absint( $item_id ) ) {
								unset( $item_data[ $key ] );
							}
						}
					}
				}
			}

			foreach ( $order_data as &$order ) {
				$get_names = array();
				if ( is_array( $item_data ) && count( $item_data ) > 0 ) {
					foreach ( $item_data as $item_i ) {
						if ( isset( $item_i['order_id'] ) && ( $order->order_id === $item_i['order_id'] ) && '_qty' === $item_i['item_meta'] ) {
							$get_names[ $order->order_id ]['product_name'][] = $item_i['product_name'];
							if ( isset( $get_names[ $order->order_id ]['qty'] ) ) {
								$get_names[ $order->order_id ]['qty'] += absint( $item_i['meta_value'] );
							} else {
								$get_names[ $order->order_id ]['qty'] = absint( $item_i['meta_value'] );
							}
						}
					}
					if ( isset( $get_names[ $order->order_id ] ) && isset( $get_names[ $order->order_id ]['product_name'] ) ) {
						$order->product_name = implode( ', ', array_unique( $get_names[ $order->order_id ]['product_name'] ) );
						$order->product_qty  = $get_names[ $order->order_id ]['qty'];
					} else {
						$order->product_name = '';
						$order->product_qty  = 0;
					}
				} else {
					$order->product_name = '';
					$order->product_qty  = 0;
				}
			}

			return $order_data;
		}

		public function get_contacts_revenue_records( $cid, $order_ids ) {
			global $wpdb;
			$cid = absint( $cid );

			// Handle order_ids - can be comma-separated string or array
			if ( is_string( $order_ids ) ) {
				$order_ids_array = array_map( 'absint', array_filter( explode( ',', $order_ids ) ) );
			} else {
				$order_ids_array = array_map( 'absint', (array) $order_ids );
			}

			if ( empty( $order_ids_array ) ) {
				return array();
			}

			$placeholders = implode( ',', array_fill( 0, count( $order_ids_array ), '%d' ) );
			$query_args   = array_merge( $order_ids_array, array( $cid ) );

			$query = $wpdb->prepare(
				"SELECT aero.fid as fid, aero.order_id as 'order_id', aero.wfacp_id as 'object_id', p.post_title as 'object_name', aero.total_revenue as 'total_revenue', DATE_FORMAT(aero.date, '%%Y-%%m-%%d %%T') as 'date', 'checkout' as 'type' FROM " . $wpdb->prefix . 'wfacp_stats AS aero LEFT JOIN ' . $wpdb->prefix . "posts as p ON aero.wfacp_id = p.id WHERE aero.order_id IN ($placeholders) AND aero.cid = %d ORDER BY aero.date ASC",
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
			$cid = absint( $cid );

			$query = $wpdb->prepare(
				"SELECT aero.order_id as 'order_id', aero.wfacp_id as 'object_id', p.post_title as 'object_name', aero.total_revenue as 'total_revenue', DATE_FORMAT(aero.date, '%%Y-%%m-%%dT%%TZ') as 'date', 'checkout' as 'type' FROM " . $wpdb->prefix . 'wfacp_stats AS aero LEFT JOIN ' . $wpdb->prefix . 'posts as p ON aero.wfacp_id = p.id WHERE aero.cid = %d ORDER BY aero.date ASC',
				$cid
			);

			$data     = $wpdb->get_results( $query );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			if ( ! empty( $data[0]->order_id ) ) {
				$order_products = ! empty( wc_get_order( $data[0]->order_id ) ) ? wffn_rest_funnel_modules()->get_first_item( $data[0]->order_id ) : array();
				if ( ! empty( $order_products ) ) {
					$data[0]->product_name = $order_products['title'];
					$data[0]->product_qty  = $order_products['more'];
				}
			} elseif ( ! empty( $data[0] ) ) {
				$data[0]->product_name = '';
				$data[0]->product_qty  = '';
			}

			return $data;
		}

		/**
		 * @param $order_id
		 *
		 * @return array|false|object|stdClass[]|null
		 */
		public function export_aero_data_order_id( $order_id ) {
			global $wpdb;
			$order_id = absint( $order_id );

			$query = $wpdb->prepare(
				"SELECT aero.wfacp_id as 'id', p.post_title as 'checkout_name', '' as 'checkout_products', '' as 'checkout_coupon', aero.order_id as 'checkout_order_id', aero.total_revenue as 'checkout_total' FROM " . $wpdb->prefix . 'wfacp_stats AS aero LEFT JOIN ' . $wpdb->prefix . 'posts as p ON aero.wfacp_id = p.id WHERE aero.order_id = %d ORDER BY aero.id ASC',
				$order_id
			);

			$data     = $wpdb->get_results( $query, ARRAY_A );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return false;
			}

			return $data;
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
			$funnel_query   = ( 0 === intval( $funnel_id ) ) ? ' AND fid != ' . esc_sql( $funnel_id ) . ' ' : ' AND fid = ' . esc_sql( $funnel_id ) . ' ';
			$interval_param = ! empty( $is_interval ) ? ', date as p_date ' : '';

			return 'SELECT DISTINCT cid as contacts ' . $interval_param . ' FROM `' . $wpdb->prefix . 'wfacp_stats` WHERE 1=1 ' . $date . ' ' . $funnel_query;
		}

		/**
		 * @param $funnel_id
		 * @param $start_date
		 * @param $end_date
		 * @param $is_interval
		 * @param $int_request
		 *
		 * @return array|false[]|object|stdClass|null
		 */
		public function get_total_orders( $funnel_id, $start_date, $end_date, $is_interval = '', $int_request = '' ) {
			global $wpdb;
			$funnel_id = ( $funnel_id !== '' ) ? ' AND fid = ' . esc_sql( $funnel_id ) . ' ' : ' AND fid != 0 ';
			$date      = ( '' !== $start_date && '' !== $end_date ) ? " AND `date` >= '" . esc_sql( $start_date ) . "' AND `date` < '" . esc_sql( $end_date ) . "' " : '';

			$interval_query = '';
			$group_by       = '';
			if ( class_exists( 'WFFN_REST_Controller' ) ) {

				if ( 'interval' === $is_interval ) {
					$get_interval   = $this->get_interval_format_query( $int_request, 'date' );
					$interval_query = $get_interval['interval_query'];
					$interval_group = $get_interval['interval_group'];
					$group_by       = ' GROUP BY ' . $interval_group;

				}
			}

			$query    = 'SELECT  COUNT(ID) as total_orders ' . $interval_query . ' FROM `' . $wpdb->prefix . 'wfacp_stats` WHERE 1=1 ' . $date . $funnel_id . $group_by . ' ORDER BY id ASC';
			$data     = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;
		}


		/**
		 * @param $limit
		 * @param string $order
		 * @param string $order_by
		 *
		 * @return string
		 */
		public function get_timeline_data_query( $limit, $order = 'DESC', $order_by = 'date' ) {
			global $wpdb;

			// Whitelist allowed ORDER BY columns to prevent SQL injection
			$allowed_order_by = array( 'date', 'id', 'fid', 'cid', 'order_id', 'total_revenue' );
			$order_by         = in_array( $order_by, $allowed_order_by, true ) ? $order_by : 'date';

			// Whitelist ORDER direction
			$order = strtoupper( $order );
			$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

			// Sanitize LIMIT - ensure it's a positive integer
			$limit_clause = '';
			if ( '' !== $limit && is_numeric( $limit ) ) {
				$limit_clause = ' LIMIT ' . absint( $limit );
			}

			return "SELECT stats.wfacp_id as id, stats.fid as 'fid', stats.cid as 'cid', stats.order_id as 'order_id', CONVERT( stats.total_revenue USING utf8) as 'total_revenue', 'aero' as 'type', posts.post_title as 'post_title', stats.date as date FROM " . $wpdb->prefix . 'wfacp_stats as stats LEFT JOIN ' . $wpdb->prefix . 'posts as posts ON stats.wfacp_id = posts.ID ORDER BY ' . $order_by . ' ' . $order . $limit_clause;
		}

		/**
		 * @param $limit
		 * @param $date_query
		 *
		 * @return array|false[]|object|stdClass[]|null
		 */
		public function get_top_funnels( $limit = '', $date_query = '' ) {
			global $wpdb;

			// Sanitize LIMIT - ensure it's a positive integer
			$limit_clause = '';
			if ( '' !== $limit && is_numeric( $limit ) ) {
				$limit_clause = ' LIMIT ' . absint( $limit );
			}

			// Note: $date_query is expected to be a pre-built date condition
			// It should only contain 'stats.date' column comparisons with date values
			$date_query = str_replace( '{{COLUMN}}', 'stats.date', $date_query );

			// Security: Only allow safe date query patterns
			// Expected format: "stats.date >= 'YYYY-MM-DD' AND stats.date < 'YYYY-MM-DD'"
			if ( ! empty( $date_query ) && ! preg_match( '/^stats\.date\s*[<>=]+\s*\'[\d\-\s:]+\'(\s+AND\s+stats\.date\s*[<>=]+\s*\'[\d\-\s:]+\')*$/i', $date_query ) ) {
				$date_query = '1=1'; // Fallback to safe condition if pattern doesn't match
			}

			$date_condition = ! empty( $date_query ) ? $date_query : '1=1';

			$query = 'SELECT funnel.id as fid, funnel.title as title, stats.total as total FROM ' . $wpdb->prefix . 'bwf_funnels AS funnel
			JOIN ( SELECT fid, SUM( total_revenue ) as total FROM ' . $wpdb->prefix . 'wfacp_stats as stats
			WHERE fid != 0 AND ' . $date_condition . ' GROUP BY fid ) as stats ON funnel.id = stats.fid WHERE 1 = 1 GROUP BY funnel.id ORDER BY total DESC' . $limit_clause;

			$data     = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- date_query is validated above
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;
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
					'DELETE FROM ' . $wpdb->prefix . "wfacp_stats WHERE cid IN ($placeholders) AND fid = %d",
					...array_merge( $cids, array( $funnel_id ) )
				);
			} else {
				$query = $wpdb->prepare(
					'DELETE FROM ' . $wpdb->prefix . "wfacp_stats WHERE cid IN ($placeholders)",
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
		 */
		public function reset_analytics( $funnel_id ) {
			global $wpdb;
			$query = $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'wfacp_stats WHERE fid = %d', $funnel_id );
			$wpdb->query( $query );
		}
	}
}
