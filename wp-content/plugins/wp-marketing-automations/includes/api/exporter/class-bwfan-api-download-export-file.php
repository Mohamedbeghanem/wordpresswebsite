<?php

class BWFAN_API_Download_Export_File extends BWFAN_API_Base {
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
		$this->route  = '/export/download/';
	}

	public function process_api_call() {
		$type    = $this->get_sanitized_arg( 'type', 'text_field' );
		$user_id = $this->get_sanitized_arg( 'user_id', 'text_field' );

		$user_data = get_user_meta( $user_id, 'bwfan_single_export_status', true );
		if ( ! isset( $user_data[ $type ] ) || ! isset( $user_data[ $type ]['url'] ) ) {
			$this->response_code = 404;
			$response            = __( 'Unable to download the exported file', 'wp-marketing-automations' );

			return $this->error_response( $response );
		}

		$filename = $user_data[ $type ]['url'];
		if ( file_exists( $filename ) ) {
			// Define header information
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Expires: 0' );
			header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
			header( 'Content-Length: ' . filesize( $filename ) );
			header( 'Pragma: public' );

			// Clear system output buffer
			flush();

			// Read the size of the file
			readfile( $filename );
			exit;
		}
		wp_die();
	}

	/**
	 * Rest api permission callback
	 *
	 * @return bool
	 */
	public function rest_permission_callback( WP_REST_Request $request ) {
		$query_params = $request->get_query_params();
		if ( isset( $query_params['bwf-nonce'] ) && $query_params['bwf-nonce'] === get_option( 'bwfan_unique_secret', '' ) ) {
			return true;
		}

		return false;
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Download_Export_File' );
