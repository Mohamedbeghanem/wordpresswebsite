<?php

/**
 * Get Automation Merger Points
 *
 * @package WP Marketing Automations
 */
class BWFAN_API_Get_Automation_Merger_Points extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/automation/(?P<automation_id>[\\d]+)/merger-points';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation ID to retrieve', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id', 'text_field' );
		$arg_data      = $this->args;

		/** Get automation */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}

		/** Step data */
		if ( ! empty( $arg_data['steps'] ) ) {
			$steps = $arg_data['steps'];
		}

		/** Link data */
		if ( ! empty( $arg_data['links'] ) ) {
			$links = $arg_data['links'];
		}

		/** Node count */
		if ( isset( $arg_data['count'] ) && intval( $arg_data['count'] ) > 0 ) {
			$count = intval( $arg_data['count'] );
		}

		/** Update automation data */
		if ( ! empty( $steps ) && ! empty( $links ) ) {
			$automation_obj->update_automation_meta_data( [], $steps, $links, $count );
		}

		$automation_obj->fetch_automation_metadata( false );

		$automation_meta = $automation_obj->get_automation_meta_data();

		if ( ! empty( $automation_meta['merger_points'] ) ) {
			$this->response_code = 200;

			return $this->success_response( [ 'merger_points' => $automation_meta['merger_points'] ], __( 'Merger Points Updated', 'wp-marketing-automations' ) );
		}


	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Automation_Merger_Points' );