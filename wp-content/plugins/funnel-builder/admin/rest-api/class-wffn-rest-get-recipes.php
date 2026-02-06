<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class WFFN_REST_Recipes
 *
 * * @extends WP_REST_Controller
 */
if ( ! class_exists( 'WFFN_REST_Recipes' ) ) {
	#[AllowDynamicProperties]
	class WFFN_REST_Recipes extends WP_REST_Controller {

		public static $_instance = null;

		/**
		 * Route base.
		 *
		 * @var string
		 */

		protected $namespace = 'funnelkit-app';
		protected $rest_base = 'automation';
		protected $response_code = 200;
		protected $total_count = 0;

		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Register the routes for taxes.
		 */
		public function register_routes() {


			register_rest_route( $this->namespace, '/' . $this->rest_base . '/recent-carts', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_recent_carts' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
					'args'                => [],
				),
			) );
		}

		public function get_read_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'funnel', 'read' );
		}


		public function get_recent_carts() {
			$recent_abandoned = $recovered_carts = [];
			$recovered_count  = $abandoned_count = 0;

			if ( function_exists( 'bwfan_is_woocommerce_active' ) && bwfan_is_woocommerce_active() ) {
				$recovered_carts  = self::get_recovered_carts( 0, 10 );
				$recovered_count  = $recovered_carts['total_count'] ?? 0;
				$recovered_carts  = isset( $recovered_carts['items'] ) ? $this->get_formatted_recovered_cart( $recovered_carts['items'] ) : [];
				$ab_carts         = self::get_recent_abandoned();
				$recent_abandoned = $ab_carts['ab_carts'] ?? [];
				$abandoned_count  = $ab_carts['total_count'] ?? 0;

			}

			$carts = array_merge( $recovered_carts, $recent_abandoned );
			uasort( $carts, function ( $a, $b ) {
				return $a['created_on'] >= $b['created_on'] ? - 1 : 1;
			} );
			$carts             = array_values( $carts );
			$carts             = count( $carts ) > 10 ? array_slice( $carts, 0, 10 ) : $carts;
			$this->total_count = $recovered_count + $abandoned_count;

			return $this->success_response( $carts, __( 'Got recent carts.', 'funnel-builder' ) );
		}


		public function success_response( $result_array, $message = '' ) {
			$response                = WFFN_Common::format_success_response( $result_array, $message, $this->response_code );
			$response['total_count'] = $this->total_count;

			$response['has_cart_automation'] = $this->get_cart_automations();

			return rest_ensure_response( $response );
		}


		public function get_cart_automations() {
			global $wpdb;

			$table = $wpdb->prefix . 'bwfan_automations';
			$event = 'ab_cart_abandoned';
			$v     = 2;

			$results = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $table WHERE `event` = %s AND `v` = %d", $event, $v ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			return !empty($results);
		}

		/**
		 * Fetch recent 10 abandoned cart
		 *
		 * @param $limit
		 *
		 * @return array|stdClass[]
		 */
		public static function get_recent_abandoned( $limit = 10 ) {
			global $wpdb;
			$abandoned_table = $wpdb->prefix . 'bwfan_abandonedcarts';
			$contact_table   = $wpdb->prefix . 'bwf_contact';

			$query  = "SELECT abandon.email, abandon.status, abandon.checkout_data, abandon.total AS revenue, abandon.currency, COALESCE(con.id, 0) AS id, COALESCE(con.f_name, '') AS f_name, COALESCE(con.l_name, '') AS l_name,abandon.created_time AS created_on from $abandoned_table AS abandon LEFT JOIN $contact_table AS con ON abandon.email = con.email WHERE abandon.status IN (0,1,2,3,4) ORDER BY abandon.ID DESC LIMIT %d OFFSET 0";
			$result = $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

			$abandoned_carts = array_map( function ( $cart ) {
				if ( empty( $cart['f_name'] ) || empty( $cart['l_name'] ) ) {
					$checkout_data  = ! empty( $cart['checkout_data'] ) ? json_decode( $cart['checkout_data'], true ) : [];
					$cart['f_name'] = empty( $cart['f_name'] ) && ! empty( $checkout_data['fields']['billing_first_name'] ) ? $checkout_data['fields']['billing_first_name'] : $cart['f_name'];
					$cart['l_name'] = empty( $cart['l_name'] ) && ! empty( $checkout_data['fields']['billing_last_name'] ) ? $checkout_data['fields']['billing_last_name'] : $cart['l_name'];
				}

				if ( isset( $cart['checkout_data'] ) ) {
					unset( $cart['checkout_data'] );
				}

				if ( ! isset( $cart['currency'] ) ) {
					return $cart;
				}
				$cart['type']     = isset( $cart['status'] ) && 2 === intval( $cart['status'] ) ? 3 : 2;
				$cart['currency'] = class_exists( 'BWFAN_Automations' ) ? BWFAN_Automations::get_currency( $cart['currency'] ) : [];

				return $cart;
			}, $result );

			$count_query = "SELECT COUNT(`ID`) FROM $abandoned_table  WHERE `status` IN (0,1,2,3,4)";
			$total_count = $wpdb->get_var( $count_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			return [
				'ab_carts'    => $abandoned_carts,
				'total_count' => intval( $total_count ),
			];
		}

		/**
		 * Get recovered cart
		 *
		 * @param $offset
		 * @param $limit
		 *
		 * @return array
		 */
		public static function get_recovered_carts( $offset = '', $limit = '' ) {
			global $wpdb;
			$left_join = '';

			$post_statuses = apply_filters( 'bwfan_recovered_cart_excluded_statuses', array(
				'wc-pending',
				'wc-failed',
				'wc-cancelled',
				'wc-refunded',
				'trash',
				'draft'
			) );
			$post_status   = "('" . implode( "','", array_filter( $post_statuses ) ) . "')";
			$found_posts   = array();

			if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
				$query = $wpdb->prepare( "SELECT p.id as id FROM {$wpdb->prefix}wc_orders as p LEFT JOIN {$wpdb->prefix}wc_orders_meta as m ON p.id = m.order_id WHERE p.type = %s AND p.status NOT IN $post_status AND m.meta_key = %s ORDER BY p.date_created_gmt DESC LIMIT $offset,$limit", 'shop_order', '_bwfan_ab_cart_recovered_a_id' ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$query = $wpdb->prepare( "SELECT p.ID as id FROM {$wpdb->prefix}posts as p LEFT JOIN {$wpdb->prefix}postmeta as m ON p.ID = m.post_id $left_join WHERE p.post_type = %s AND p.post_status NOT IN $post_status AND m.meta_key = %s ORDER BY p.post_modified DESC LIMIT $offset,$limit", 'shop_order', '_bwfan_ab_cart_recovered_a_id' ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
			$recovered_carts = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL

			if ( empty( $recovered_carts ) ) {
				return array();
			}
			$items = array();

			foreach ( $recovered_carts as $recovered_cart ) {
				if ( function_exists( 'wc_get_order' ) ) {
					$items[] = wc_get_order( $recovered_cart['id'] );
				}
			}

			$found_posts['items'] = $items;

			if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
				$count_query                = $wpdb->prepare( "SELECT DISTINCT COUNT(p.id) FROM {$wpdb->prefix}wc_orders as p LEFT JOIN {$wpdb->prefix}wc_orders_meta as m ON p.id = m.order_id WHERE p.type = %s AND p.status NOT IN $post_status AND m.meta_key = %s ", 'shop_order', '_bwfan_ab_cart_recovered_a_id' ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$found_posts['total_count'] = $wpdb->get_var( $count_query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL

				return $found_posts;
			}

			$count_query                = $wpdb->prepare( "SELECT COUNT(p.ID) FROM {$wpdb->prefix}posts as p LEFT JOIN {$wpdb->prefix}postmeta as m ON p.ID = m.post_id $left_join WHERE p.post_type = %s AND p.post_status NOT IN $post_status AND m.meta_key = %s ", 'shop_order', '_bwfan_ab_cart_recovered_a_id' ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$found_posts['total_count'] = $wpdb->get_var( $count_query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL

			return $found_posts;
		}

		/**
		 * Formatted recovered cart
		 *
		 * @param $recovered_carts
		 *
		 * @return array
		 */
		public function get_formatted_recovered_cart( $recovered_carts ) {
			if ( empty( $recovered_carts ) ) {
				return [];
			}
			$result = [];
			foreach ( $recovered_carts as $item ) {
				if ( ! $item instanceof WC_Order ) {
					continue;
				}
				$order_date = $item->get_date_created();
				$result[]   = [
					'order_id'   => $item->get_id(),
					'f_name'     => $item->get_billing_first_name(),
					'l_name'     => $item->get_billing_last_name(),
					'email'      => $item->get_billing_email(),
					'created_on' => ( $order_date instanceof WC_DateTime ) ? ( $order_date->date( 'Y-m-d H:i:s' ) ) : '',
					'revenue'    => $item->get_total(),
					'currency'   => class_exists( 'BWFAN_Automations' ) ? BWFAN_Automations::get_currency( $item->get_currency() ) : [],
					'id'         => $item->get_meta( '_woofunnel_cid' ),
					'type'       => 1,
				];
			}

			return $result;
		}


	}


}

return WFFN_REST_Recipes::get_instance();