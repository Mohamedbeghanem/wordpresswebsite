<?php

/**
 * Class BWFAN_Api_Block_Migrator
 *
 * @package wp-marketing-automations
 */
class BWFAN_Api_Block_Migrator extends BWFAN_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/block-migrator';
	}


	public function process_api_call() {
		$unlayer_json = isset( $this->args['unlayer_data'] ) ? $this->args['unlayer_data'] : [];
		$domain       = isset( $this->args['domain'] ) ? $this->args['domain'] : '';
		$key          = isset( $this->args['key'] ) ? $this->args['key'] : '';

		if ( empty( $unlayer_json ) || empty( $domain ) || empty( $key ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Required data not found', 'wp-marketing-automations' ) );
		}
		if ( is_multisite() ) {
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			if ( is_array( $active_plugins ) && ( in_array( BWFAN_PLUGIN_BASENAME, apply_filters( 'active_plugins', $active_plugins ), true ) || array_key_exists( BWFAN_PLUGIN_BASENAME, apply_filters( 'active_plugins', $active_plugins ) ) ) && ! is_main_site() ) {
				$domain = get_site_url( get_main_site_id() );
			}
		}

		$body = [
			'domain'       => urldecode( $domain ),
			'key'          => $key,
			'unlayer_data' => $unlayer_json
		];

		$request = wp_remote_post( 'https://license.funnelkit.com/?wc-api=am-software-api&request=migrate_email', [
			'method'  => 'POST',
			'body'    => json_encode( $body ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		] );

		$result = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 || empty( $result ) || ! isset( $result['status'] ) || ! $result['status'] ) {
			$message = $result['message'] ?? __( 'Error occurred while migrating block', 'wp-marketing-automations' );
			$data    = [
				'status' => false,
			];

			if ( isset( $result['license_error'] ) ) {
				$data['license_error'] = ! empty( $result['license_error'] ) ? $result['license_error'] : __( 'License error occurred', 'wp-marketing-automations' );
			}

			return $this->success_response( $data, $message );
		}

		if ( empty( $result['block_data'] || empty( $result['block_data']['body'] ) || empty( $result['block_data']['setting'] ) ) ) {
			return $this->success_response( [
				'status' => false,
			], __( 'Error occurred while migrating block, data not found.', 'wp-marketing-automations' ) );
		}

		return $this->success_response( [
			'status'     => true,
			'block_data' => $result['block_data']
		], __( 'Data fetched successfully', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_Api_Block_Migrator' );