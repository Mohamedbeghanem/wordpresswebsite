<?php

class BWFAN_API_Contact_Listing extends BWFAN_API_Base {
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
		$this->route              = '/v3/contacts/listing';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;

	}

	/** Set default order & order by value */
	public function default_args_values() {
		return [
			'order'    => 'desc',
			'limit'    => 25,
			'offset'   => 0,
			'order_by' => 'last_modified',
		];
	}

	public function process_api_call() {
		/** checking if search present in params */
		$search             = $this->get_sanitized_arg( 'search', 'text_field' );
		$filters_collection = empty( $this->args['filters'] ) ? array() : $this->args['filters'];

		$get_wc_data          = $this->get_sanitized_arg( 'get_wc', 'bool' );
		$grab_totals          = $this->get_sanitized_arg( 'grab_totals', 'bool' );
		$only_count           = $this->get_sanitized_arg( 'only_count', 'bool' );
		$contact_mode         = $this->get_sanitized_arg( 'fetch_base', 'text_field' );
		$exclude_unsubs       = $this->get_sanitized_arg( 'exclude_unsubs', 'bool' );
		$exclude_unsubs_lists = $this->get_sanitized_arg( 'exclude_unsubs_lists', 'bool' );
		$grab_custom_fields   = $this->get_sanitized_arg( 'grab_custom_fields', 'bool' );
		$order                = $this->get_sanitized_arg( 'order', 'text_field' );
		$order_by             = $this->get_sanitized_arg( 'order_by', 'text_field' );
		$additional_info      = array(
			'grab_totals'          => $grab_totals,
			'only_count'           => $only_count,
			'fetch_base'           => $contact_mode,
			'exclude_unsubs'       => $exclude_unsubs,
			'exclude_unsubs_lists' => $exclude_unsubs_lists,
			'grab_custom_fields'   => $grab_custom_fields,
		);

		if ( ! empty( $order ) && ! empty( $order_by ) ) {
			$additional_info['order']    = $order;
			$additional_info['order_by'] = $order_by;
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$additional_info['customer_data'] = $get_wc_data;
		}

		$normalized_filters = [];
		$filter_match       = 'all';
		if ( bwfan_is_autonami_pro_active() ) {
			$filter_match       = isset( $filters_collection['match'] ) && ! empty( $filters_collection['match'] ) ? $filters_collection['match'] : 'all';
			$filter_match       = ( 'any' === $filter_match ? ' OR ' : ' AND ' );
			$normalized_filters = BWFCRM_Filters::_normalize_input_filters( $filters_collection );
		}

		$contacts = BWFCRM_Model_Contact::get_contact_listing( $search, $this->pagination->limit, $this->pagination->offset, $normalized_filters, $additional_info, $filter_match );

		$this->count_data  = BWFAN_Common::get_contact_data_counts();
		$this->total_count = $contacts['total'];

		$this->response_code = 200;

		return $this->success_response( $contacts['contacts'] );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Contact_Listing' );
