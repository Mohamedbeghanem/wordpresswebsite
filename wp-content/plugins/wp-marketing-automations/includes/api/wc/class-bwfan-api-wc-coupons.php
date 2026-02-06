<?php

/**
 * Bwfan Api Get Wc Coupons
 *
 * @since 1.0.0
 */
class BWFAN_Api_Get_WC_Coupons extends BWFAN_API_Base {

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
		$this->route        = '/wc-coupons/';
		$this->request_args = array(
			'search' => array(
				'description' => __( 'Search from name', 'wp-marketing-automations' ),
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
		$search           = ! empty( $this->get_sanitized_arg( 'search', 'text_field' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$exclude_autonami = ! empty( $this->get_sanitized_arg( 'exclude_autonami', 'bool' ) ) ? $this->get_sanitized_arg( 'exclude_autonami', 'bool' ) : true;
		$coupons          = $this->get_all_coupons( $search, $exclude_autonami );

		$this->response_code = 200;

		return $this->success_response( $coupons, count( $coupons ) > 0 ? __( 'Successfully fetched coupons', 'wp-marketing-automations' ) : __( 'No coupon found.', 'wp-marketing-automations' ) );
	}

	/**
	 * Get all WC coupons
	 *
	 * @param $search
	 * @param $exclude_autonami
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_all_coupons( $search, $exclude_autonami = false ) {
		$args = array(
			'posts_per_page' => 10,
			'orderby'        => 'name',
			'order'          => 'asc',
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			's'              => $search
		);

		if ( $exclude_autonami ) {
			$args['meta_query'] = [
				[
					'key'     => '_is_bwfan_coupon',
					'compare' => 'NOT EXISTS', // this will exclude the coupons where _is_bwfan_coupon meta key exists
				]
			];
		}

		$coupon_posts = get_posts( $args );

		$coupon_data = [];
		foreach ( $coupon_posts as $coupon_post ) {
			$expiry      = false;
			$coupon      = new WC_Coupon( $coupon_post->ID );
			$expiry_date = $coupon->get_date_expires();
			if ( $expiry_date ) {
				$timezone        = $coupon->get_date_expires()->getTimezone(); // get timezone
				$expiry_datetime = new WC_DateTime( $expiry_date->date( 'Y-m-d' ) );
				$now_datetime    = new WC_DateTime();

				$expiry_datetime->setTimezone( $timezone );
				$now_datetime->setTimezone( $timezone );

				$expiry = $now_datetime->getTimestamp() > $expiry_datetime->getTimestamp();
			}
			$coupon_data[] = [
				'key'     => $coupon_post->post_title,
				'value'   => $coupon_post->ID,
				'expired' => $expiry,
			];
		}

		return $coupon_data;
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Get_WC_Coupons' );
