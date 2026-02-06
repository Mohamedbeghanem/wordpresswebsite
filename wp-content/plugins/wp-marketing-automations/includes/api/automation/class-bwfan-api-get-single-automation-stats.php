<?php

class BWFAN_API_Get_Single_Automation_Stats extends BWFAN_API_Base {

	public static $ins;
	private $automation_obj = null;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/automations-stats/(?P<automation_id>[\\d]+)/';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation id to stats', 'wp-marketing-automations' ),
				'type'        => 'string',
			),
		);

	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/** Customer journey Api call */
	public function process_api_call() {
		$aid = empty( $this->get_sanitized_arg( 'automation_id' ) ) ? 0 : $this->get_sanitized_arg( 'automation_id' );

		if ( empty( $aid ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations' ), null, 400 );
		}

		/** Initiate automation object */
		$this->automation_obj = BWFAN_Automation_V2::get_instance( $aid );

		/** Check for automation exists */
		if ( ! empty( $this->automation_obj->error ) ) {
			return $this->error_response( [], $this->automation_obj->error );
		}
		$data = [
			'start' => [
				'queued'    => 0,
				'active'    => $this->automation_obj->get_active_count(),
				'completed' => $this->automation_obj->get_complete_count(),
			]
		];

		$step_ids = BWFAN_Model_Automation_Step::get_automation_step_ids( $aid );
		if ( empty( $step_ids ) ) {
			return $this->success_response( $data, __( 'Automation stats found', 'wp-marketing-automations' ) );
		}
		$step_ids = array_column( $step_ids, 'ID' );

		$completed_steps = BWFAN_Model_Automation_Contact_Trail::get_bulk_step_count( $step_ids );
		$completed_sids  = empty( $completed_steps ) ? [] : array_column( $completed_steps, 'sid' );

		$queued_steps = BWFAN_Model_Automation_Contact_Trail::get_bulk_step_count( $step_ids, 2 );
		$queued_sids  = empty( $queued_steps ) ? [] : array_column( $queued_steps, 'sid' );

		$skipped_steps = BWFAN_Model_Automation_Contact_Trail::get_bulk_step_count( $step_ids, 4 );
		$skipped_sids  = empty( $skipped_steps ) ? [] : array_column( $skipped_steps, 'sid' );

		$failed_steps = BWFAN_Model_Automation_Contact_Trail::get_bulk_step_count( $step_ids, 3 );
		$failed_sids  = empty( $failed_steps ) ? [] : array_column( $failed_steps, 'sid' );

		foreach ( $step_ids as $sid ) {
			$index           = array_search( $sid, $completed_sids );
			$completed_count = ( false !== $index && isset( $completed_steps[ $index ]['count'] ) ) ? $completed_steps[ $index ]['count'] : 0;

			$index        = array_search( $sid, $queued_sids );
			$queued_count = ( false !== $index && isset( $queued_steps[ $index ]['count'] ) ) ? $queued_steps[ $index ]['count'] : 0;

			$index         = array_search( $sid, $skipped_sids );
			$skipped_count = ( false !== $index && isset( $skipped_steps[ $index ]['count'] ) ) ? $skipped_steps[ $index ]['count'] : 0;

			$index        = array_search( $sid, $failed_sids );
			$failed_count = ( false !== $index && isset( $failed_steps[ $index ]['count'] ) ) ? $failed_steps[ $index ]['count'] : 0;

			$data[ $sid ] = [
				'queued'    => $queued_count,
				'active'    => 0,
				'completed' => $completed_count,
				'skipped'   => $skipped_count,
				'failed'    => $failed_count,
			];
		}
		$meta        = $this->automation_obj->get_automation_meta_data();
		$split_steps = isset( $meta['split_steps'] ) ? $meta['split_steps'] : [];

		$data = $this->get_split_steps_stats( $split_steps, $data );
		/** Get path stats */
//		$path_stats = $this->get_split_path_stats( $aid, $split_steps );

		return $this->success_response( $data, __( 'Automation stats found', 'wp-marketing-automations' ) );
	}


	public function get_split_steps_stats( $split_steps, $data ) {
		/** Get split step's step ids */
		$split_step_ids = [];
		foreach ( $split_steps as $split_id => $paths ) {
			foreach ( $paths as $step_ids ) {
				$current_step_ids            = isset( $split_step_ids[ $split_id ] ) ? $split_step_ids[ $split_id ] : [];
				$split_step_ids[ $split_id ] = array_merge( $current_step_ids, $step_ids );
			}
		}


		$split_steps_data = [];
		foreach ( $split_step_ids as $split_id => $step_ids ) {
			$step_data                                  = BWFAN_Model_Automation_Step::get( $split_id );
			$created_at                                 = isset( $step_data['created_at'] ) ? strtotime( $step_data['created_at'] ) : '';
			$split_steps_data[ $split_id ]['completed'] = BWFAN_Model_Automation_Contact_Trail::get_bulk_step_count( $step_ids, 1, $created_at );
			$split_steps_data[ $split_id ]['queued']    = BWFAN_Model_Automation_Contact_Trail::get_bulk_step_count( $step_ids, 2, $created_at );
		}

		foreach ( $split_steps_data as $steps ) {
			foreach ( $steps as $key => $step_data ) {
				if ( ! is_array( $step_data ) ) {
					continue;
				}
				foreach ( $step_data as $s_data ) {
					if ( ! isset( $data[ $s_data['sid'] ] ) ) {
						continue;
					}
					$data[ $s_data['sid'] ][ $key ] = $s_data['count'];
				}
			}
		}

		return $data;
	}


	public function get_split_path_stats( $aid, $split_steps ) {
		$path_stats = [];
		foreach ( $split_steps as $split_id => $paths ) {
			$step_data     = BWFAN_Model_Automation_Step::get_step_data_by_id( $split_id );
			$after_date    = isset( $step_data['created_at'] ) ? $step_data['created_at'] : '';
			$split_node_id = $this->automation_obj->get_steps_node_id( $split_id );
			foreach ( $paths as $path => $step_ids ) {
				$path_num                 = str_replace( 'p-', '', $path );
				$path_name                = $split_node_id . '-path-' . $path_num;
				$path_stats[ $path_name ] = $this->get_path_stats( $aid, $step_ids, $after_date );
				$contact_count            = BWFAN_Model_Automation_Contact_Trail::get_path_contact_count( $split_id, $path_num );

				$path_stats[ $path_name ][] = [
					'l' => 'Contacts',
					'v' => intval( $contact_count ),
				];

			}
		}

		return $path_stats;
	}

	/**
	 * Get path's stats
	 *
	 * @param $automation_id
	 * @param $step_ids
	 *
	 * @return array|WP_Error
	 */
	public function get_path_stats( $automation_id, $step_ids, $after_date ) {
		$data = BWFAN_Model_Engagement_Tracking::get_automation_step_analytics( $automation_id, $step_ids, $after_date );

		$open_rate        = isset( $data['open_rate'] ) ? number_format( $data['open_rate'], 2 ) : 0;
		$click_rate       = isset( $data['click_rate'] ) ? number_format( $data['click_rate'], 2 ) : 0;
		$revenue          = isset( $data['revenue'] ) ? floatval( $data['revenue'] ) : 0;
		$unsubscribes     = isset( $data['unsbuscribers'] ) ? absint( $data['unsbuscribers'] ) : 0;
		$conversions      = isset( $data['conversions'] ) ? absint( $data['conversions'] ) : 0;
		$sent             = isset( $data['sent'] ) ? absint( $data['sent'] ) : 0;
		$open_count       = isset( $data['open_count'] ) ? absint( $data['open_count'] ) : 0;
		$click_count      = isset( $data['click_count'] ) ? absint( $data['click_count'] ) : 0;
		$contacts_count   = isset( $data['contacts_count'] ) ? absint( $data['contacts_count'] ) : 1;
		$rev_per_person   = empty( $contacts_count ) || empty( $revenue ) ? 0 : number_format( $revenue / $contacts_count, 2 );
		$unsubscribe_rate = empty( $contacts_count ) || empty( $unsubscribes ) ? 0 : ( $unsubscribes / $contacts_count ) * 100;

		/** Get click rate from total opens */
		$click_to_open_rate = ( empty( $click_count ) || empty( $open_count ) ) ? 0 : number_format( ( $click_count / $open_count ) * 100, 2 );

		$tiles = [
			[
				'l' => __( 'Sent', 'wp-marketing-automations' ),
				'v' => empty( $sent ) ? '-' : $sent,
			],
			[
				'l' => __( 'Open Rate', 'wp-marketing-automations' ),
				'v' => empty( $open_count ) ? '-' : $open_rate . '% (' . $open_count . ')',
			],
			[
				'l' => __( 'Click Rate', 'wp-marketing-automations' ),
				'v' => empty( $click_count ) ? '-' : $click_rate . '% (' . $click_count . ')',
			],
			[
				'l' => __( 'Click to Open Rate', 'wp-marketing-automations' ),
				'v' => empty( $click_to_open_rate ) ? '-' : $click_to_open_rate . '%',
			]
		];

		if ( bwfan_is_woocommerce_active() ) {

			$currency_symbol = get_woocommerce_currency_symbol();
			$revenue         = empty( $revenue ) ? '' : html_entity_decode( $currency_symbol . $revenue );
			$rev_per_person  = empty( $rev_per_person ) ? '' : html_entity_decode( $currency_symbol . $rev_per_person );

			$revenue_tiles = [
				[
					'l' => __( 'Revenue', 'wp-marketing-automations' ),
					'v' => empty( $conversions ) ? '-' : $revenue . ' (' . $conversions . ')',
				],
				[
					'l' => __( 'Revenue/Contact', 'wp-marketing-automations' ),
					'v' => empty( $rev_per_person ) ? '-' : $rev_per_person,
				]
			];

			$tiles = array_merge( $tiles, $revenue_tiles );
		}

		$tiles[] = [
			'l' => __( 'Unsubscribe Rate', 'wp-marketing-automations' ),
			'v' => empty( $unsubscribes ) ? '-' : number_format( $unsubscribe_rate, 2 ) . '% (' . $unsubscribes . ')',
		];

		return $tiles;
	}

}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Single_Automation_Stats' );