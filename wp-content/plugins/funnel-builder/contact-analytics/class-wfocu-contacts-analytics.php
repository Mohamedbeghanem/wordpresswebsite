<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Class WFOCU_Contacts_Analytics
 */
if ( ! class_exists( 'WFOCU_Contacts_Analytics' ) ) {

	class WFOCU_Contacts_Analytics extends WFFN_REST_Controller {

		/**
		 * instance of class
		 *
		 * @var null
		 */
		private static $ins = null;

		/**
		 * WFOCU_Contacts_Analytics constructor.
		 */
		public function __construct() {
		}

		/**
		 * @return WFOCU_Contacts_Analytics|null
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
		public function get_top_upsells( $start_date, $end_date, $limit_str = '' ) {
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
					'SELECT v.object_id as id, IFNULL(s.revenue, 0) as revenue, IFNULL(s.conversion, 0) as conversion,
					p.post_title as title, p.post_type as post_type, v.view_count as views
					FROM (SELECT object_id, COUNT(id) as view_count FROM `' . $wpdb->prefix . 'wfocu_event` WHERE action_type_id = 2 AND timestamp >= %s AND timestamp < %s GROUP BY object_id) as v
					LEFT JOIN ' . $wpdb->prefix . 'posts as p ON p.id = v.object_id
					LEFT JOIN (SELECT object_id, SUM(value) as revenue, COUNT(id) as conversion FROM `' . $wpdb->prefix . 'wfocu_event` WHERE action_type_id = 4 AND timestamp >= %s AND timestamp < %s GROUP BY object_id) as s ON s.object_id = v.object_id
					ORDER BY revenue DESC' . $limit_clause,
					$start_date,
					$end_date,
					$start_date,
					$end_date
				);
			} else {
				$query = 'SELECT v.object_id as id, IFNULL(s.revenue, 0) as revenue, IFNULL(s.conversion, 0) as conversion,
					p.post_title as title, p.post_type as post_type, v.view_count as views
					FROM (SELECT object_id, COUNT(id) as view_count FROM `' . $wpdb->prefix . 'wfocu_event` WHERE action_type_id = 2 GROUP BY object_id) as v
					LEFT JOIN ' . $wpdb->prefix . 'posts as p ON p.id = v.object_id
					LEFT JOIN (SELECT object_id, SUM(value) as revenue, COUNT(id) as conversion FROM `' . $wpdb->prefix . 'wfocu_event` WHERE action_type_id = 4 GROUP BY object_id) as s ON s.object_id = v.object_id
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
					$upsell_id                     = get_post_meta( $item->id, '_funnel_id', true );
					$funnel_id                     = get_post_meta( $upsell_id, '_bwf_in_funnel', true );
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
				"SELECT session.order_id as order_id, event.object_id, event.action_type_id, event.value as 'total_revenue', event_meta.meta_value as 'item_ids', DATE_FORMAT(event.timestamp, '%%Y-%%m-%%dT%%TZ') as 'date', p.post_title as 'object_name', 'upsell' as 'type' FROM " . $wpdb->prefix . 'wfocu_event as event
				LEFT JOIN ' . $wpdb->prefix . 'wfocu_session as session ON event.sess_id=session.id
				LEFT JOIN ' . $wpdb->prefix . 'posts as p ON event.object_id=p.id
				LEFT JOIN ' . $wpdb->prefix . "wfocu_event_meta as event_meta ON event.id=event_meta.event_id
				WHERE (event.action_type_id=4 OR event.action_type_id=6 OR event.action_type_id=7 OR event.action_type_id=9) AND session.fid = %d AND session.cid = %d AND event_meta.meta_key = '_items_added' ORDER BY session.timestamp ASC",
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
				"SELECT session.fid as fid, session.order_id as order_id, event.object_id, event.action_type_id, event.value, DATE_FORMAT(event.timestamp, '%%Y-%%m-%%d %%T') as 'date', p.post_title as 'object_name', 'upsell' as 'type' FROM " . $wpdb->prefix . 'wfocu_event as event LEFT JOIN ' . $wpdb->prefix . 'wfocu_session as session ON event.sess_id = session.id LEFT JOIN ' . $wpdb->prefix . "posts as p ON event.object_id = p.id WHERE (event.action_type_id = 4 OR event.action_type_id = 6 OR event.action_type_id = 7 OR event.action_type_id = 9) AND session.order_id IN ($placeholders) AND session.cid = %d ORDER BY session.timestamp ASC",
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
				"SELECT session.order_id as order_id, event.object_id, event.action_type_id, event.value, DATE_FORMAT(event.timestamp, '%%Y-%%m-%%dT%%TZ') as 'date', p.post_title as 'object_name', 'upsell' as 'type' FROM " . $wpdb->prefix . 'wfocu_event as event LEFT JOIN ' . $wpdb->prefix . 'wfocu_session as session ON event.sess_id = session.id LEFT JOIN ' . $wpdb->prefix . 'posts as p ON event.object_id = p.id WHERE (event.action_type_id = 4 OR event.action_type_id = 6 OR event.action_type_id = 7 OR event.action_type_id = 9) AND session.cid = %d ORDER BY session.timestamp ASC',
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
		public function export_upsell_offer_by_order_id( $order_id ) {
			global $wpdb;
			$order_id = absint( $order_id );

			$query = $wpdb->prepare(
				"SELECT event.object_id as 'id', session.order_id as 'order_id', p.post_title as 'offer_name', (CASE WHEN action_type_id = 4 THEN 'Yes' WHEN action_type_id = 6 THEN 'No' ELSE '' END) AS `offer_converted`, event.value as 'offer_total' FROM " . $wpdb->prefix . 'wfocu_event as event LEFT JOIN ' . $wpdb->prefix . 'wfocu_session as session ON event.sess_id = session.id LEFT JOIN ' . $wpdb->prefix . 'posts as p ON event.object_id = p.id WHERE (event.action_type_id = 4 OR event.action_type_id = 6 OR event.action_type_id = 7 OR event.action_type_id = 9) AND session.order_id = %d ORDER BY session.timestamp ASC',
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

			return "SELECT stats.object_id as id, sess.fid as 'fid', sess.cid as 'cid', sess.order_id as 'order_id', CONVERT( stats.value USING utf8) as 'total_revenue', 'upsell' as 'type', posts.post_title as 'post_title', stats.timestamp as date FROM " . $wpdb->prefix . 'wfocu_event AS stats LEFT JOIN ' . $wpdb->prefix . 'wfocu_session AS sess ON stats.sess_id=sess.id LEFT JOIN ' . $wpdb->prefix . 'posts AS posts ON stats.object_id=posts.ID WHERE ( stats.action_type_id = 4) AND sess.cid IS NOT NULL ORDER BY ' . $order_by . ' ' . $order . $limit_clause;

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
			// It should only contain 'sess.timestamp' column comparisons with date values
			$date_query = str_replace( '{{COLUMN}}', 'sess.timestamp', $date_query );

			// Security: Only allow safe date query patterns
			// Expected format: "sess.timestamp >= 'YYYY-MM-DD' AND sess.timestamp < 'YYYY-MM-DD'"
			if ( ! empty( $date_query ) && ! preg_match( '/^sess\.timestamp\s*[<>=]+\s*\'[\d\-\s:]+\'(\s+AND\s+sess\.timestamp\s*[<>=]+\s*\'[\d\-\s:]+\')*$/i', $date_query ) ) {
				$date_query = '1=1'; // Fallback to safe condition if pattern doesn't match
			}

			$date_condition = ! empty( $date_query ) ? $date_query : '1=1';

			$query = 'SELECT funnel.id as fid, funnel.title as title, stats.total as total FROM ' . $wpdb->prefix . 'bwf_funnels AS funnel
			JOIN ( SELECT sess.fid as fid, SUM(ev.value) as total FROM ' . $wpdb->prefix . 'wfocu_event as ev
			LEFT JOIN ' . $wpdb->prefix . 'wfocu_session as sess on sess.id = ev.sess_id
			WHERE ev.action_type_id = 4 AND sess.fid != 0 AND sess.total > 0 AND ' . $date_condition . ' GROUP BY fid ) as stats ON funnel.id = stats.fid WHERE 1=1 GROUP BY funnel.id ORDER BY total DESC' . $limit_clause;

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

			if ( ! class_exists( 'WFOCU_Core' ) ) {
				return true;
			}

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
					'SELECT id FROM ' . $wpdb->prefix . "wfocu_session WHERE cid IN ($placeholders) AND fid = %d",
					...array_merge( $cids, array( $funnel_id ) )
				);
			} else {
				$query = $wpdb->prepare(
					'SELECT id FROM ' . $wpdb->prefix . "wfocu_session WHERE cid IN ($placeholders)",
					...$cids
				);
			}

			$sess_ids = $wpdb->get_results( $query, ARRAY_A );
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			if ( is_array( $sess_ids ) && count( $sess_ids ) > 0 ) {
				foreach ( $sess_ids as $sess_id ) {
					WFOCU_Core()->session_db->delete( $sess_id['id'] );
				}
			}

			return true;
		}

		/**
		 * @param $funnel_id
		 */
		public function reset_analytics( $funnel_id ) {
			global $wpdb;
			if ( ! class_exists( 'WFOCU_Core' ) ) {
				return;
			}

			$funnel_id = absint( $funnel_id );

			$query    = $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'wfocu_session WHERE fid = %d', $funnel_id );
			$sess_ids = $wpdb->get_results( $query, ARRAY_A );
			if ( is_array( $sess_ids ) && count( $sess_ids ) > 0 ) {
				foreach ( $sess_ids as $sess_id ) {
					WFOCU_Core()->session_db->delete( $sess_id['id'] );
				}
			}
			$query = $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'wfocu_session WHERE fid = %d', $funnel_id );
			$wpdb->query( $query );

			$all_upsell_funnel_ids = array();
			$funnel                = new WFFN_Funnel( $funnel_id );
			$rest_API              = WFFN_REST_API_EndPoint::get_instance();
			if ( $funnel instanceof WFFN_Funnel && 0 < $funnel->get_id() ) {
				$get_steps = $funnel->get_steps();
				$get_steps = $rest_API::get_instance()->maybe_add_ab_variants( $get_steps );
				foreach ( $get_steps as $step ) {
					if ( $step['type'] === 'wc_upsells' ) {
						array_push( $all_upsell_funnel_ids, $step['id'] );
					}
				}
			}

			if ( count( $all_upsell_funnel_ids ) > 0 ) {
				// Sanitize all IDs as integers
				$all_upsell_funnel_ids = array_map( 'absint', $all_upsell_funnel_ids );
				$all_upsell_funnel_ids = array_filter( $all_upsell_funnel_ids );

				if ( ! empty( $all_upsell_funnel_ids ) ) {
					$placeholders = implode( ',', array_fill( 0, count( $all_upsell_funnel_ids ), '%d' ) );
					$delete_query = $wpdb->prepare(
						'DELETE eventss FROM ' . $wpdb->prefix . 'wfocu_event as eventss
						INNER JOIN ' . $wpdb->prefix . "wfocu_event_meta AS events_meta__funnel_id ON ( eventss.ID = events_meta__funnel_id.event_id )
						AND ( ( events_meta__funnel_id.meta_key = '_funnel_id' AND events_meta__funnel_id.meta_value IN ($placeholders) ) )",
						...$all_upsell_funnel_ids
					);
					$wpdb->query( $delete_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $delete_query is prepared above
				}
			}
		}
	}
}
