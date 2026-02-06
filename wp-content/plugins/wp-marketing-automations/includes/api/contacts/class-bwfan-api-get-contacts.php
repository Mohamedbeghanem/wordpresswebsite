<?php

class BWFAN_API_Get_Contacts extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/v3/contacts';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;
		$this->request_args       = array(
			'search'         => array(
				'description' => __( 'Search from email, first_name or last_name', 'wp-marketing-automations' ),
				'type'        => 'string',
			),
			'offset'         => array(
				'description' => __( 'Contacts list Offset', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
			'limit'          => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations' ),
				'type'        => 'integer',
			),
			'get_wc'         => array(
				'description' => __( 'Get WC Data as well', 'wp-marketing-automations' ),
				'type'        => 'boolean',
			),
			'grab_totals'    => array(
				'description' => __( 'Grab total contact count as well', 'wp-marketing-automations' ),
				'type'        => 'boolean',
			),
			'start_indexing' => array(
				'description' => __( 'Start Indexing of Contacts in case indexing is pending', 'wp-marketing-automations' ),
				'type'        => 'boolean',
			),
		);
	}

	public function default_args_values() {
		return array(
			'search'  => '',
			'filters' => array(),
		);
	}

	public function process_api_call() {
		$additional_info = array(
			'grab_totals'          => $this->get_sanitized_arg( 'grab_totals', 'bool' ),
			'only_count'           => $this->get_sanitized_arg( 'only_count', 'bool' ),
			'fetch_base'           => $this->get_sanitized_arg( 'fetch_base', 'text_field' ),
			'exclude_unsubs'       => $this->get_sanitized_arg( 'exclude_unsubs', 'bool' ),
			'exclude_unsubs_lists' => $this->get_sanitized_arg( 'exclude_unsubs_lists', 'bool' ),
			'grab_custom_fields'   => $this->get_sanitized_arg( 'grab_custom_fields', 'bool' ),
			'include_soft_bounce'  => $this->get_sanitized_arg( 'includeSoftBounce', 'bool' ),
			'include_unverified'   => $this->get_sanitized_arg( 'includeUnverified', 'bool' ),
		);

		/** Un-Open contacts case */
		$un_open_broadcast = $this->get_sanitized_arg( 'unopen_broadcast', 'key' );
		if ( ! empty( $un_open_broadcast ) ) {
			return $this->get_unopened_broadcast_contacts( $un_open_broadcast, $additional_info );
		}

		$order    = $this->get_sanitized_arg( 'order', 'text_field' );
		$order_by = $this->get_sanitized_arg( 'order_by', 'text_field' );
		if ( ! empty( $order ) && ! empty( $order_by ) ) {
			$additional_info['order']    = $order;
			$additional_info['order_by'] = $order_by;
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$additional_info['customer_data'] = $this->get_sanitized_arg( 'get_wc', 'bool' );
		}

		/** checking if search present in params */
		$search             = $this->get_sanitized_arg( 'search', 'text_field' );
		$filters_collection = empty( $this->args['filters'] ) ? array() : $this->args['filters'];

		if ( false === $additional_info['exclude_unsubs'] ) {
			$additional_info['exclude_unsubs'] = apply_filters( 'bwfan_force_exclude_unsubscribe_contact', false );
		}

		$contacts = BWFCRM_Contact::get_contacts( $search, $this->pagination->offset, $this->pagination->limit, $filters_collection, $additional_info );
		if ( ! is_array( $contacts ) ) {
			$this->response_code = 500;

			return $this->error_response( is_string( $contacts ) ? $contacts : __( 'Unknown error occurred', 'wp-marketing-automations' ) );
		}

		if ( isset( $contacts['total_count'] ) ) {
			$this->total_count = absint( $contacts['total_count'] );
		}

		if ( ! isset( $contacts['contacts'] ) || empty( $contacts['contacts'] ) ) {
			return $this->success_response( array() );
		}

		$this->response_code = 200;

		return $this->success_response( $contacts['contacts'] );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

	public function get_unopened_broadcast_contacts( $broadcast_id, $additional_info ) {
		$additional_info['offset'] = $this->pagination->offset;
		$additional_info['limit']  = $this->pagination->limit;

		$contacts = BWFCRM_Core()->campaigns->get_unopen_broadcast_contacts( absint( $broadcast_id ), $additional_info );

		if ( ! is_array( $contacts ) ) {
			$this->response_code = 500;

			return $this->error_response( is_string( $contacts ) ? $contacts : __( 'Unknown error occurred', 'wp-marketing-automations' ) );
		}

		if ( isset( $contacts['total_count'] ) ) {
			$this->total_count = absint( $contacts['total_count'] );
		}

		if ( ! isset( $contacts['contacts'] ) || empty( $contacts['contacts'] ) ) {
			return $this->success_response( array() );
		}

		$this->response_code = 200;

		return $this->success_response( $contacts['contacts'] );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Contacts' );
