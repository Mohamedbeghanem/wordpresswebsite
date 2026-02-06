<?php

class BWFAN_API_Get_Node_Analytics extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/automation/(?P<automation_id>[\\d]+)/analytics/(?P<step_id>[\\d]+)';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation ID', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
			'step_id'       => array(
				'description' => __( 'Step ID ', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id' );
		$step_id       = $this->get_sanitized_arg( 'step_id' );
		$mode          = $this->get_sanitized_arg( 'mode' );
		$mode          = ! empty( $mode ) ? $mode : 'email';

		if ( empty( $automation_id ) || empty( $step_id ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations' ), null, 400 );
		}

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}
		$data = [
			'status' => false,
			'data'   => [],
		];
		switch ( $mode ) {
			case 'email':
			case 'sms':
				$data = $this->get_step_mail_sms_analytics( $automation_id, $step_id, $mode );
				break;
			case 'split':
				$data = $this->get_split_step_analytics( $automation_obj, $automation_id, $step_id );
				break;
			default:
				$data = apply_filters( 'bwfan_automation_node_analytics', $data, $automation_id, $step_id, $mode );
		}

		if ( ! $data['status'] ) {
			return $this->error_response( ! empty( $automation_data['message'] ) ? $automation_data['message'] : __( 'Automation step analytics not found.', 'wp-marketing-automations' ), null, 400 );
		}

		$this->response_code = 200;

		return $this->success_response( $data['data'], ! empty( $automation_data['message'] ) ? $automation_data['message'] : __( 'Step analytics fetched successfully.', 'wp-marketing-automations' ) );
	}

	/**
	 * Returns email/sms analytics
	 *
	 * @param $automation_id
	 * @param $step_id
	 * @param $mode
	 *
	 * @return array
	 */
	public function get_step_mail_sms_analytics( $automation_id, $step_id, $mode ) {
		if ( class_exists( 'BWFAN_Model_Engagement_Tracking' ) || method_exists( 'BWFAN_Model_Engagement_Tracking', 'get_automation_step_analytics' ) ) {
			$data = BWFAN_Model_Engagement_Tracking::get_automation_step_analytics( $automation_id, $step_id );
		}

		$open_rate        = isset( $data['open_rate'] ) ? number_format( $data['open_rate'], 1 ) : 0;
		$click_rate       = isset( $data['click_rate'] ) ? number_format( $data['click_rate'], 1 ) : 0;
		$revenue          = isset( $data['revenue'] ) ? floatval( $data['revenue'] ) : 0;
		$unsubscribes     = isset( $data['unsubscribers'] ) ? absint( $data['unsubscribers'] ) : 0;
		$conversions      = isset( $data['conversions'] ) ? absint( $data['conversions'] ) : 0;
		$sent             = isset( $data['sent'] ) ? absint( $data['sent'] ) : 0;
		$open_count       = isset( $data['open_count'] ) ? absint( $data['open_count'] ) : 0;
		$click_count      = isset( $data['click_count'] ) ? absint( $data['click_count'] ) : 0;
		$contacts_count   = isset( $data['contacts_count'] ) ? absint( $data['contacts_count'] ) : 1;
		$rev_per_person   = empty( $contacts_count ) || empty( $revenue ) ? 0 : number_format( $revenue / $contacts_count, 1 );
		$unsubscribe_rate = empty( $contacts_count ) || empty( $unsubscribes ) ? 0 : ( $unsubscribes / $contacts_count ) * 100;

		/** Tile for sms */
		if ( 'sms' === $mode ) {
			$tiles = [
				[
					'label' => __( 'Sent', 'wp-marketing-automations' ),
					'value' => $sent,
				],
				[
					'label' => __( 'Click Rate', 'wp-marketing-automations' ),
					'value' => $click_rate . '% (' . $click_count . ')',
				]
			];
		} else {
			/** Get click rate from total opens */
			$click_to_open_rate = ( empty( $click_count ) || empty( $open_count ) ) ? 0 : number_format( ( $click_count / $open_count ) * 100, 1 );

			$tiles = [
				[
					'label' => __( 'Sent', 'wp-marketing-automations' ),
					'value' => $sent,
				],
				[
					'label' => __( 'Open Rate', 'wp-marketing-automations' ),
					'value' => $open_rate . '% (' . $open_count . ')',
				],
				[
					'label' => __( 'Click Rate', 'wp-marketing-automations' ),
					'value' => $click_rate . '% (' . $click_count . ')',
				],
				[
					'label' => __( 'Click to Open Rate', 'wp-marketing-automations' ),
					'value' => $click_to_open_rate . '%',
				]
			];
		}

		if ( bwfan_is_woocommerce_active() ) {

			$currency_symbol = get_woocommerce_currency_symbol();
			$revenue         = html_entity_decode( $currency_symbol . $revenue );
			$rev_per_person  = html_entity_decode( $currency_symbol . $rev_per_person );

			$revenue_tiles = [
				[
					'label' => __( 'Revenue', 'wp-marketing-automations' ),
					'value' => $revenue . ' (' . $conversions . ')',
				],
				[
					'label' => __( 'Revenue/Contact', 'wp-marketing-automations' ),
					'value' => $rev_per_person,
				]
			];

			$tiles = array_merge( $tiles, $revenue_tiles );
		}

		$tiles[] = [
			'label' => __( 'Unsubscribe Rate', 'wp-marketing-automations' ),
			'value' => number_format( $unsubscribe_rate, 2 ) . '% (' . $unsubscribes . ')',
		];


		return [
			'status' => true,
			'data'   => [
				'analytics' => [],
				'tile'      => $tiles,
			]
		];
	}

	public function get_split_step_analytics( $automation_obj, $automation_id, $step_id ) {
		$automation_meta = $automation_obj->get_automation_meta_data();
		$split_steps     = isset( $automation_meta['split_steps'] ) ? $automation_meta['split_steps'] : [];
		$data            = BWFAN_Model_Automation_Step::get_step_data( $step_id );
		$started_at      = $data['created_at'];
		$paths           = $split_steps[ $step_id ];
		$path_stats      = [ 'started_at' => $started_at ];
		foreach ( $paths as $path => $step_ids ) {
			$path_num      = str_replace( 'p-', '', $path );
			$stats         = $this->get_path_stats( $automation_id, $step_ids );
			$contact_count = BWFAN_Model_Automation_Contact_Trail::get_path_contact_count( $step_id, $path_num );
			$stats[0]      = [
				'l' => 'Contacts',
				'v' => ! empty( $contact_count ) ? intval( $contact_count ) : '-',
			];

			$path_stats['paths'][] = [
				'path'  => $path_num,
				'stats' => $stats
			];

		}

		return [
			'status' => true,
			'data'   => [
				'data' => $path_stats
			]
		];
	}

	/**
	 * @param $split_steps
	 * @param $current_step
	 *
	 * @return mixed|string
	 */
	private function get_split_step_creation_itme( $split_steps, $current_step ) {
		if ( empty( $split_steps ) ) {
			return '';
		}
		$split_step_id = 0;
		foreach ( $split_steps as $split_id => $paths ) {
			foreach ( $paths as $step_ids ) {
				if ( ! in_array( intval( $current_step ), array_map( 'intval', $step_ids ), true ) ) {
					continue;
				}
				$split_step_id = $split_id;
				break;
			}
			if ( intval( $split_step_id ) > 0 ) {
				break;
			}
		}

		if ( 0 === intval( $split_step_id ) ) {
			return '';
		}
		$split_data = BWFAN_Model_Automation_Step::get( $split_id );

		return isset( $split_data['created_at'] ) ? $split_data['created_at'] : '';;
	}

	/**
	 * Get path's stats
	 *
	 * @param $automation_id
	 * @param $step_ids
	 *
	 * @return array|WP_Error
	 */
	public function get_path_stats( $automation_id, $step_ids, $after_date = '' ) {
		$data = BWFAN_Model_Engagement_Tracking::get_automation_step_analytics( $automation_id, $step_ids, $after_date );

		$open_rate        = isset( $data['open_rate'] ) ? number_format( $data['open_rate'], 1 ) : 0;
		$click_rate       = isset( $data['click_rate'] ) ? number_format( $data['click_rate'], 1 ) : 0;
		$revenue          = isset( $data['revenue'] ) ? floatval( $data['revenue'] ) : 0;
		$unsubscribes     = isset( $data['unsubscribers'] ) ? absint( $data['unsubscribers'] ) : 0;
		$conversions      = isset( $data['conversions'] ) ? absint( $data['conversions'] ) : 0;
		$sent             = isset( $data['sent'] ) ? absint( $data['sent'] ) : 0;
		$open_count       = isset( $data['open_count'] ) ? absint( $data['open_count'] ) : 0;
		$click_count      = isset( $data['click_count'] ) ? absint( $data['click_count'] ) : 0;
		$contacts_count   = isset( $data['contacts_count'] ) ? absint( $data['contacts_count'] ) : 1;
		$rev_per_person   = empty( $contacts_count ) || empty( $revenue ) ? 0 : number_format( $revenue / $contacts_count, 1 );
		$unsubscribe_rate = empty( $contacts_count ) || empty( $unsubscribes ) ? 0 : ( $unsubscribes / $contacts_count ) * 100;

		/** Get click rate from total opens */
		$click_to_open_rate = ( empty( $click_count ) || empty( $open_count ) ) ? 0 : number_format( ( $click_count / $open_count ) * 100, 1 );

		$tiles = [
			[
				'l' => __( 'Contact', 'wp-marketing-automations' ),
				'v' => '',
			],
			[
				'l' => __( 'Sent', 'wp-marketing-automations' ),
				'v' => empty( $sent ) ? '-' : $sent,
			],
			[
				'l' => __( 'Opened', 'wp-marketing-automations' ),
				'v' => empty( $open_count ) ? '-' : $open_count . ' (' . $open_rate . '%)',
			],
			[
				'l' => __( 'Clicked', 'wp-marketing-automations' ),
				'v' => empty( $click_count ) ? '-' : $click_count . ' (' . $click_rate . '%)',
			],
			[
				'l' => __( 'Click to Open', 'wp-marketing-automations' ),
				'v' => empty( $click_to_open_rate ) ? '-' : $click_to_open_rate . '%',
			]
		];
		if ( bwfan_is_woocommerce_active() ) {

			$currency_symbol = get_woocommerce_currency_symbol();
			$revenue         = empty( $revenue ) ? '' : html_entity_decode( $currency_symbol . $revenue );
			$rev_per_person  = empty( $rev_per_person ) ? '' : html_entity_decode( $currency_symbol . $rev_per_person );

			$revenue_tiles = [
				[
					'l' => __( 'Rev.', 'wp-marketing-automations' ),
					'v' => empty( $conversions ) ? '-' : $revenue . ' (' . $conversions . ')',
				],
				[
					'l' => __( 'Rev./Contact', 'wp-marketing-automations' ),
					'v' => empty( $rev_per_person ) ? '-' : $rev_per_person,
				]
			];

			$tiles = array_merge( $tiles, $revenue_tiles );
		}

		$tiles[] = [
			'l' => __( 'Unsubscribed', 'wp-marketing-automations' ),
			'v' => empty( $unsubscribes ) ? '-' : $unsubscribes . ' (' . number_format( $unsubscribe_rate, 1 ) . '%)',
		];

		return $tiles;
	}

}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Node_Analytics' );