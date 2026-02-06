<?php

class BWFAN_API_Get_Connectors extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/connectors';
	}

	public function process_api_call() {
		$connectors = BWFAN_Core()->connectors->get_connectors_for_listing();
		if ( ! bwfan_is_autonami_connector_active() ) {
			$connectors = array_map( function ( $connector ) {
				$name = $connector['name'];
				$name = strtolower( str_replace( [ ' ', '/' ], [ '-', '' ], $name ) ) . '.png';
				if ( isset( $connector['logo'] ) && ! empty( $connector['logo'] ) ) {
					if ( wp_http_validate_url( $connector['logo'] ) ) {
						return $connector;
					}

					$name = $connector['logo'] . '.png';
				}

				$connector['logo'] = BWFAN_PLUGIN_URL . '/includes/connectors-logo/' . $name;

				return $connector;
			}, $connectors );
		}

		return $this->success_response( $connectors, '' );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Get_Connectors' );
