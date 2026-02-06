<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Class WFOB_Contacts_Analytics
 */
if ( ! class_exists( 'WFOB_Contacts_Analytics' ) ) {

	class WFOB_Contacts_Analytics extends WFFN_REST_Controller {

		/**
		 * instance of class
		 *
		 * @var null
		 */
		private static $ins = null;

		/**
		 * WFOB_Contacts_Analytics constructor.
		 */
		public function __construct() {
		}

		/**
		 * @return WFOB_Contacts_Analytics|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * @param $start_date
		 * @param $end_date
		 * @param string $limit
		 *
		 * @return array
		 */
		public function get_top_bumps( $start_date, $end_date, $limit_str = '' ) {
			global $wpdb;

			// Sanitize limit - ensure it's a positive integer
			$limit_clause = '';
			if ( ! empty( $limit_str ) && is_numeric( $limit_str ) ) {
				$limit_clause = ' LIMIT ' . absint( $limit_str );
			}

			// Build query with prepared statements for dates
			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$start_date = sanitize_text_field( $start_date );
				$end_date   = sanitize_text_field( $end_date );
				$query      = $wpdb->prepare(
					'SELECT v.bid as id, IFNULL(s.revenue, 0) as revenue, IFNULL(s.conversion, 0) as conversion, p.post_title as title, p.post_type as post_type, v.view_count as views
					FROM (SELECT bid, COUNT(id) as view_count FROM `' . $wpdb->prefix . 'wfob_stats` WHERE date >= %s AND date < %s GROUP BY bid) as v
					LEFT JOIN ' . $wpdb->prefix . 'posts as p ON p.id = v.bid
					LEFT JOIN (SELECT bid, sum(total) as revenue, COUNT(id) as conversion FROM `' . $wpdb->prefix . 'wfob_stats` as bmp WHERE date >= %s AND date < %s AND bmp.converted = 1 GROUP BY bid) as s ON s.bid = v.bid
					ORDER BY revenue DESC' . $limit_clause,
					$start_date,
					$end_date,
					$start_date,
					$end_date
				);
			} else {
				$query = 'SELECT v.bid as id, IFNULL(s.revenue, 0) as revenue, IFNULL(s.conversion, 0) as conversion, p.post_title as title, p.post_type as post_type, v.view_count as views
					FROM (SELECT bid, COUNT(id) as view_count FROM `' . $wpdb->prefix . 'wfob_stats` GROUP BY bid) as v
					LEFT JOIN ' . $wpdb->prefix . 'posts as p ON p.id = v.bid
					LEFT JOIN (SELECT bid, sum(total) as revenue, COUNT(id) as conversion FROM `' . $wpdb->prefix . 'wfob_stats` as bmp WHERE bmp.converted = 1 GROUP BY bid) as s ON s.bid = v.bid
					ORDER BY revenue DESC' . $limit_clause;
			}

			$data     = $wpdb->get_results( $query );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}
			if ( ! empty( $data ) && is_array( $data ) ) {
				foreach ( $data as $key => $item ) {
					$data[ $key ]->conversion_rate = $this->get_percentage( absint( $item->views ), $item->conversion );
					$data[ $key ]->type            = $item->post_type;
					$funnel_id                     = get_post_meta( $item->id, '_bwf_in_funnel', true );
					$data[ $key ]->fid             = $funnel_id;
					if ( $funnel_id ) {
						$funnel_name               = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT title FROM {$wpdb->prefix}bwf_funnels WHERE id = %d",
								$funnel_id
							)
						);
						$data[ $key ]->funnel_name = $funnel_name;
					} else {
						$data[ $key ]->funnel_name = '';
					}
				}
			}
			return $data;

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
				"SELECT bump.oid as order_id, bump.bid as 'object_id', bump.iid as 'item_ids', bump.total as 'total_revenue', p.post_title as 'object_name', bump.converted as 'is_converted', DATE_FORMAT(bump.date, '%%Y-%%m-%%dT%%TZ') as 'date', 'bump' as 'type' FROM " . $wpdb->prefix . 'wfob_stats AS bump LEFT JOIN ' . $wpdb->prefix . 'posts as p ON bump.bid = p.id WHERE bump.converted = 1 AND bump.fid = %d AND bump.cid = %d ORDER BY bump.date ASC',
				$funnel_id,
				$cid
			);

			$order_data = $wpdb->get_results( $query );
			$db_error   = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			if ( ! is_array( $order_data ) || count( $order_data ) === 0 ) {
				return $item_data;
			}

			$all_item_ids = array();

			/** merge all items ids in one array */
			if ( is_array( $order_data ) && count( $order_data ) > 0 ) {
				foreach ( $order_data as &$i_array ) {
					$i_array->item_ids = ( isset( $i_array->item_ids ) && '' != $i_array->item_ids ) ? json_decode( $i_array->item_ids ) : array();
					if ( is_array( $i_array->item_ids ) && count( $i_array->item_ids ) > 0 ) {
						$all_item_ids = array_merge( $all_item_ids, $i_array->item_ids );
					}
				}
			}

			if ( is_array( $all_item_ids ) && count( $all_item_ids ) > 0 ) {
				/**
				 * get order item product name and quantity by items ids
				 */
				$all_item_ids = array_map( 'absint', $all_item_ids );
				$all_item_ids = array_filter( $all_item_ids );

				if ( ! empty( $all_item_ids ) ) {
					$placeholders = implode( ',', array_fill( 0, count( $all_item_ids ), '%d' ) );
					$item_query   = $wpdb->prepare(
						"SELECT oi.order_item_id as 'item_id', oi.order_item_name as 'product_name', oim.meta_value as 'qty' FROM " . $wpdb->prefix . 'woocommerce_order_items as oi LEFT JOIN ' . $wpdb->prefix . "woocommerce_order_itemmeta as oim ON oi.order_item_id = oim.order_item_id WHERE oi.order_item_id IN ($placeholders) AND oi.order_item_type = 'line_item' AND oim.meta_key = '_qty' GROUP BY oi.order_item_id",
						...$all_item_ids
					);
					$item_data    = $wpdb->get_results( $item_query );
					$db_error     = WFFN_Common::maybe_wpdb_error( $wpdb );
					if ( true === $db_error['db_error'] ) {
						return $db_error;
					}
				}
			}

			foreach ( $order_data as &$order ) {
				$product_titles = array();
				$qty            = 0;
				if ( is_array( $order->item_ids ) && count( $order->item_ids ) > 0 && is_array( $item_data ) && count( $item_data ) > 0 ) {
					foreach ( $order->item_ids as $item_id ) {
						$search = array_search( intval( $item_id ), array_map( 'intval', wp_list_pluck( $item_data, 'item_id' ) ), true );
						if ( false !== $search && isset( $item_data[ $search ] ) ) {
							$product_titles[] = $item_data[ $search ]->product_name;
							$qty             += absint( $item_data[ $search ]->qty );
						}
					}
				}
				unset( $order->item_ids );
				$order->product_name = implode( ', ', $product_titles );
				$order->product_qty  = $qty;
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
				"SELECT conv.funnel_id as fid, conv.source as order_id, conv.step_id as 'object_id', conv.bump_total as 'total_revenue', p.post_title as 'object_name', CASE WHEN conv.bump_total > 0 AND conv.bump_accepted != '' THEN 1 ELSE 0 END as 'is_converted', DATE_FORMAT(conv.timestamp, '%%Y-%%m-%%d %%T') as 'date', 'bump' as 'type' FROM " . $wpdb->prefix . 'bwf_conversion_tracking AS conv LEFT JOIN ' . $wpdb->prefix . "posts as p ON conv.step_id = p.id WHERE conv.type = 2 AND conv.bump_total > 0 AND conv.bump_accepted != '' AND conv.source IN ($placeholders) AND conv.contact_id = %d ORDER BY conv.timestamp ASC",
				...$query_args
			);

			$data     = $wpdb->get_results( $query );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;

		}

		public function get_bumps_by_order_id( $order_id ) {
			global $wpdb;

			$query    = $wpdb->prepare( "SELECT conv.step_id as 'id', p.post_title as 'bump_name', '' as 'bump_products', CASE WHEN conv.bump_total > 0 AND conv.bump_accepted != '' THEN 'Yes' ELSE 'No' END as 'bump_converted', conv.source as 'bump_order_id', conv.bump_total as 'bump_total' FROM " . $wpdb->prefix . 'bwf_conversion_tracking' . ' AS conv LEFT JOIN ' . $wpdb->prefix . 'posts' . ' as p ON conv.step_id = p.id WHERE conv.type = 2 AND conv.source=%s order by conv.timestamp asc', $order_id );
			$data     = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
				"SELECT conv.source as order_id, conv.step_id as 'object_id', conv.bump_total as 'total_revenue', p.post_title as 'object_name', CASE WHEN conv.bump_total > 0 AND conv.bump_accepted != '' THEN 1 ELSE 0 END as 'is_converted', DATE_FORMAT(conv.timestamp, '%%Y-%%m-%%dT%%TZ') as 'date', 'bump' as 'type' FROM " . $wpdb->prefix . 'bwf_conversion_tracking AS conv LEFT JOIN ' . $wpdb->prefix . "posts as p ON conv.step_id = p.id WHERE conv.type = 2 AND conv.bump_total > 0 AND conv.bump_accepted != '' AND conv.contact_id = %d ORDER BY conv.timestamp ASC",
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

			return "SELECT conv.step_id as id, conv.funnel_id as 'fid', conv.contact_id as 'cid', conv.source as 'order_id', CONVERT( conv.bump_total USING utf8) as 'total_revenue', 'bump' as 'type', posts.post_title as 'post_title', conv.timestamp as date FROM " . $wpdb->prefix . 'bwf_conversion_tracking AS conv LEFT JOIN ' . $wpdb->prefix . "posts AS posts ON conv.step_id=posts.ID WHERE conv.type = 2 AND conv.bump_total > 0 AND conv.bump_accepted != '' ORDER BY " . $order_by . ' ' . $order . $limit_clause;

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
			// It should only contain 'conv.timestamp' column comparisons with date values
			$date_query = str_replace( '{{COLUMN}}', 'conv.timestamp', $date_query );

			// Security: Only allow safe date query patterns
			// Expected format: "conv.timestamp >= 'YYYY-MM-DD' AND conv.timestamp < 'YYYY-MM-DD'"
			if ( ! empty( $date_query ) && ! preg_match( '/^conv\.timestamp\s*[<>=]+\s*\'[\d\-\s:]+\'(\s+AND\s+conv\.timestamp\s*[<>=]+\s*\'[\d\-\s:]+\')*$/i', $date_query ) ) {
				$date_query = '1=1'; // Fallback to safe condition if pattern doesn't match
			}

			$date_condition = ! empty( $date_query ) ? $date_query : '1=1';

			$query = 'SELECT funnel.id as fid, funnel.title as title, stats.total as total FROM ' . $wpdb->prefix . 'bwf_funnels AS funnel
			JOIN ( SELECT funnel_id, SUM( bump_total ) as total FROM ' . $wpdb->prefix . "bwf_conversion_tracking as conv
			WHERE funnel_id != 0 AND type = 2 AND bump_total > 0 AND bump_accepted != '' AND " . $date_condition . ' GROUP BY funnel_id ) as stats ON funnel.id = stats.funnel_id WHERE 1 = 1 GROUP BY funnel.id ORDER BY total DESC' . $limit_clause;

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
					'DELETE FROM ' . $wpdb->prefix . "wfob_stats WHERE cid IN ($placeholders) AND fid = %d",
					...array_merge( $cids, array( $funnel_id ) )
				);
			} else {
				$query = $wpdb->prepare(
					'DELETE FROM ' . $wpdb->prefix . "wfob_stats WHERE cid IN ($placeholders)",
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
			$query = $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'wfob_stats WHERE fid = %d', $funnel_id );
			$wpdb->query( $query );
		}
	}
}
