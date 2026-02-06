<?php

class BWFAN_Api_Get_Tags extends BWFAN_API_Base {

	public static $ins;
	public $tags = array();
	public $total_count = 0;
	public $count_data = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/v3/tags';
		$this->request_args = array(
			'search' => array(
				'description' => __( 'Search from name', 'wp-marketing-automations' ),
				'type'        => 'string',
			),
			'ids'    => array(
				'description' => __( 'Search from tag ids', 'wp-marketing-automations' ),
				'type'        => 'string'
			),
		);
	}

	public function default_args_values() {
		return array( 'ids' => '', 'search' => '' );
	}

	public function process_api_call() {
		/** if isset search param then get the tag by name **/
		$search  = $this->get_sanitized_arg( 'search', 'text_field' );
		$tag_ids = empty( $this->args['ids'] ) ? array() : explode( ',', $this->args['ids'] );
		$limit   = $this->get_sanitized_arg( 'limit', 'text_field' );
		$offset  = $this->get_sanitized_arg( 'offset', 'text_field' );

		$tag_data = BWFCRM_Tag::get_tags( $tag_ids, $search, $offset, $limit, ARRAY_A );
		if ( ! is_array( $tag_data ) ) {
			$tag_data = array();
		}
		$tags_count = BWFAN_Model_Terms::get_terms_count( 1, $search, $tag_ids );

		$this->total_count   = $tags_count;
		$this->tags          = $tag_data;
		$this->count_data    = BWFAN_Common::get_contact_data_counts();
		$this->response_code = 200;

		return $this->success_response( $tag_data );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Get_Tags' );
