<?php

class BWFAN_API_Get_Dashboard_Data extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/dashboard';
	}

	public function default_args_values() {
		return array();
	}

	public function process_api_call() {
		$response            = $this->prepare_item_for_response();
		$this->response_code = 200;

		return $this->success_response( $response );
	}

	/**
	 * @return array
	 */
	public function prepare_item_for_response() {
		$force    = filter_input( INPUT_GET, 'force' );
		$force    = ( 'false' === $force ) ? false : true;
		$new_data = [];

		/** Check if worker call is late */
		$last_run = bwf_options_get( 'fk_core_worker_let' );
		if ( '' !== $last_run && ( ( time() - $last_run ) > BWFAN_Common::get_worker_delay_timestamp() ) ) {
			/** Worker is running late */
			$new_data['worker_delayed'] = time() - $last_run;
		}

		/** Check basic worker last run time and status code check */
		$resp = BWFAN_Common::validate_core_worker( $force );
		if ( isset( $resp['response_code'] ) ) {
			$new_data['response_code'] = $resp['response_code'];
		}

		/** Dashboard call */
		$lite_key = 'bwfan_dashboard_report_lite';
		$pro_key  = 'bwfan_dashboard_report_pro';
		$exp      = BWFAN_Common::get_admin_analytics_cache_lifespan();

		if ( 'false' === $force ) {
			/** Check for cached data */
			if ( ! bwfan_is_autonami_pro_active() ) {
				/** Lite version active */
				$data = get_transient( $lite_key );
				if ( ! empty( $data ) ) {
					return array_merge( $data, $new_data );
				}
			} else {
				/** Pro version active */
				$data = get_transient( $pro_key );
				if ( ! empty( $data ) ) {
					return array_merge( $data, $new_data );
				}
			}
		}

		$recovered_carts  = [];
		$recent_abandoned = [];
		$lost_carts       = [];

		if ( function_exists( 'bwfan_is_woocommerce_active' ) && bwfan_is_woocommerce_active() ) {
			$recovered_carts  = BWFAN_Recoverable_Carts::get_recovered_carts( '', 0, 5 );
			$recovered_carts  = isset( $recovered_carts['items'] ) ? $this->get_recovered( $recovered_carts['items'] ) : [];
			$recent_abandoned = BWFAN_Automations::get_recent_abandoned();
			$lost_carts       = BWFAN_Automations::get_recent_abandoned( 2 );
		}

		$unsubsribers      = BWFAN_Dashboards::get_recent_unsubsribers();
		$unsubsribers      = array_map( function ( $unsubscribe ) {
			$unsubscribe['type'] = 'unsubscribe';

			return $unsubscribe;
		}, $unsubsribers );
		$new_contacts      = BWFAN_Dashboards::get_recent_contacts();
		$new_contacts      = array_map( function ( $contact ) {
			$contact['type'] = 'contact';

			return $contact;
		}, $new_contacts );
		$recent_activities = array_merge( $new_contacts, $unsubsribers );
		$recovered_carts   = array_map( function ( $data ) {
			$data['type'] = 1;

			return $data;
		}, ( array ) $recovered_carts );
		$recent_abandoned  = array_map( function ( $data ) {
			$data['type'] = 2;

			return $data;
		}, ( array ) $recent_abandoned );
		$lost_carts        = array_map( function ( $data ) {
			$data['type'] = 3;

			return $data;
		}, ( array ) $lost_carts );

		$carts = array_merge( $recovered_carts, $recent_abandoned, $lost_carts );
		uasort( $carts, function ( $a, $b ) {
			return $a['created_on'] >= $b['created_on'] ? - 1 : 1;
		} );
		$carts = array_values( $carts );
		$carts = count( $carts ) > 5 ? array_slice( $carts, 0, 5 ) : $carts;

		$data = [
			'carts' => $carts,
		];
		$data = array_merge( $data, $new_data );

		$additional_info = [
			'grab_totals' => true,
			'only_count'  => true
		];

		$contacts_count   = BWFCRM_Contact::get_contacts( '', 0, 0, [], $additional_info );
		$get_total_sents  = BWFAN_Dashboards::get_total_engagement_sents( '', '', '', '' );
		$get_total_orders = BWFAN_Dashboards::get_total_orders( '', '', '', '' );

		$analytics_data = [
			'total_contact' => ! isset( $contacts_count['total_count'] ) ? 0 : $contacts_count['total_count'],
			'email_sents'   => ! isset( $get_total_sents[0]['email_sents'] ) ? 0 : $get_total_sents[0]['email_sents'],
			'sms_sent'      => ! isset( $get_total_sents[0]['sms_sent'] ) ? 0 : $get_total_sents[0]['sms_sent'],
			'total_orders'  => ! isset( $get_total_orders[0]['total_orders'] ) ? 0 : $get_total_orders[0]['total_orders'],
			'total_revenue' => ! isset( $get_total_orders[0]['total_revenue'] ) ? 0 : $get_total_orders[0]['total_revenue'],
		];

		$top_automations = BWFCRM_Automations::get_top_automations();

		if ( ! bwfan_is_autonami_pro_active() ) {
			uasort( $recent_activities, function ( $a, $b ) {
				return $a['creation_date'] >= $b['creation_date'] ? - 1 : 1;
			} );
			$recent_activities = array_values( $recent_activities );
			$recent_activities = count( $recent_activities ) > 10 ? array_slice( $recent_activities, 0, 9 ) : $recent_activities;
			$data              = array_merge( $data, [
				'pro_active'        => false,
				'analytics_data'    => $analytics_data,
				'top_automations'   => $top_automations['top_automations'],
				'top_broadcast'     => [],
				'recent_activities' => $recent_activities,
			] );
			set_transient( $lite_key, $data, $exp );
			BWFAN_Common::validate_scheduled_recurring_actions();

			return $data;
		}

		$top_broadcast     = BWFCRM_Campaigns::get_top_broadcast();
		$top_broadcast_sms = BWFCRM_Campaigns::get_top_broadcast( 2 );

		$recent_conversions = BWFAN_Dashboards::get_recent_conversions();
		$recent_conversions = array_map( function ( $conversion ) {
			$conversion['type'] = 'conversion';

			return $conversion;
		}, $recent_conversions );

		$recent_activities = array_merge( $recent_activities, $recent_conversions );
		uasort( $recent_activities, function ( $a, $b ) {
			return $a['creation_date'] >= $b['creation_date'] ? - 1 : 1;
		} );
		$recent_activities = array_values( $recent_activities );
		$recent_activities = count( $recent_activities ) > 10 ? array_slice( $recent_activities, 0, 9 ) : $recent_activities;

		$data = array_merge( $data, [
			'pro_active'        => true,
			'analytics_data'    => $analytics_data,
			'top_automations'   => $top_automations['top_automations'],
			'top_broadcast'     => $top_broadcast['top_broadcast'],
			'top_broadcast_sms' => $top_broadcast_sms['top_broadcast'],
			'recent_activities' => $recent_activities,
		] );
		set_transient( $pro_key, $data, $exp );
		BWFAN_Common::validate_scheduled_recurring_actions();

		return $data;
	}

	public function get_recovered( $recovered_carts ) {
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
				'currency'   => BWFAN_Automations::get_currency( $item->get_currency() ),
				'id'         => $item->get_meta( '_woofunnel_cid' ),
			];
		}

		return $result;
	}

	public function get_full_name( $item ) {
		if ( ! $item instanceof WC_Order ) {
			return '';
		}
		$buyer = '';
		if ( $item->get_billing_first_name() || $item->get_billing_last_name() ) {
			/* translators: 1: first name 2: last name */
			$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $item->get_billing_first_name(), $item->get_billing_last_name() ) ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		} elseif ( $item->get_billing_company() ) {
			$buyer = trim( $item->get_billing_company() );
		} elseif ( $item->get_customer_id() ) {
			$user  = get_user_by( 'id', $item->get_customer_id() );
			$buyer = ucwords( $user->display_name );
		}

		return apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $item );
	}

	public function get_items( $item ) {
		$names = [];
		foreach ( $item->get_items() as $value ) {
			$names[] = $value->get_name();
		}

		return $names;
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Dashboard_Data' );
