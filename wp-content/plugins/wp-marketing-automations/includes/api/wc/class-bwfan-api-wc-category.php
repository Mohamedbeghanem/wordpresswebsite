<?php

/**
 * Bwfan Api Get Wc Category
 *
 * @since 1.0.0
 */
class BWFAN_Api_Get_WC_Category extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/wc-category/';
		$this->request_args = array(
			'search' => array(
				'description' => __( 'Search from name', 'wp-marketing-automations' ),
				'type'        => 'string',
			),
			'ids'    => array(
				'description' => __( 'Comma seperated ids to search for', 'wp-marketing-automations' ),
				'type'        => 'string',
			),
		);

	}

	/**
	 * Process Api Call
	 *
	 * @since 1.0.0
	 */
	public function process_api_call() {
		// check if woocommerce is active
		if ( ! bwfan_is_woocommerce_active() ) {
			return $this->error_response( __( 'WooCommerce is not active', 'wp-marketing-automations' ), null, 400 );
		}
		$search        = ! empty( $this->get_sanitized_arg( 'search', 'text_field' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$ids           = $this->get_sanitized_arg( 'ids', 'text_field' );
		$category_list = $this->get_all_categories( $search, $ids );

		$this->response_code = 200;

		return $this->success_response( $category_list, count( $category_list ) > 0 ? __( 'Successfully fetched categories', 'wp-marketing-automations' ) : __( 'No category found.', 'wp-marketing-automations' ) );
	}

	/**
	 * Get All Categories
	 *
	 * @since 1.0.0
	 */
	public function get_all_categories( $search, $ids ) {
		$param = [
			'taxonomy' => 'product_cat',
			'offset'   => 0,
			'search'   => $search,
		];

		if ( ! empty( $ids ) ) {
			$ids = array_filter( array_map( 'intval', explode( ',', $ids ) ) );
			if ( ! empty( $ids ) ) {
				$param['include'] = $ids;
			}
		} else {
			$param['number'] = 10;
		}

		$categories = get_terms( $param );

		$category_data = [];

		foreach ( $categories as $category ) {
			if ( ! $category instanceof WP_Term ) {
				continue;
			}
			$category_data[] = [
				'key'   => $category->term_id,
				'value' => $category->name,
			];
		}

		return $category_data;
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Get_WC_Category' );