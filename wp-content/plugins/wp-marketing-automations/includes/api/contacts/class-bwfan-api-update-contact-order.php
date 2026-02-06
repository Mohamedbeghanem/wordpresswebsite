<?php

/**
 * Class BWFAN_API_Update_Contact_Order for syncing contact order data
 */
class BWFAN_API_Update_Contact_Order extends BWFAN_API_Base {
	public static $ins;

	/**
	 * To create an instance of the current class
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * setting up the data
	 */
	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::READABLE;
		$this->route         = '/contact/(?P<contact_id>[\\d]+)/resync-order/';
		$this->response_code = 200;
	}

	/**
	 * setting up the default id for the contact
	 * @return int[]
	 */
	public function default_args_values() {
		return array(
			'contact_id' => 0,
		);
	}

	/**
	 * this will update the order details of the particular contact based on the contact_id
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function process_api_call() {
		$cid = $this->get_sanitized_arg( 'contact_id', 'key' );

		//Check contact id is founded
		if ( empty( $cid ) ) {
			$this->error_response( __( 'Contact ID no found', 'wp-marketing-automations' ), '', 404 );
		}

		// Check if contact exist
		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->error_response( __( 'No Contact found', 'wp-marketing-automations' ), '', 404 );
		}

		// Check for dependencies
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'as_has_scheduled_action' ) ) {
			return $this->error_response( __( 'Dependencies Missing', 'wp-marketing-automations' ), '', 404 );
		}

		$args = [ 'cid' => $cid ];
		$hook = 'bwf_reindex_contact_orders';

		// If already scheduled
		if ( as_has_scheduled_action( $hook, $args, 'funnelkit' ) ) {
			return $this->success_response( [], __( 'Action already scheduled for the user', 'wp-marketing-automations' ) );
		}

		// Start the indexing for 10 seconds
		$start_time = time();
		$ins        = WooFunnels_DB_Updater::get_instance();

		do {
			$response = $ins->bwf_reindex_contact_orders( $cid );
			if ( 1 === intval( $response ) || false === $response ) {
				break;
			}
		} while ( ( time() - $start_time ) < 10 );

		switch ( $response ) {
			case 0:
			case '0': // If not completed in 10 seconds, schedule the action
				$schedule_id = as_schedule_recurring_action( time(), 60, $hook, $args, 'funnelkit' );

				// Unable to schedule action
				if ( ! $schedule_id ) {
					return $this->error_response( __( 'Unable to schedule action for syncing.', 'wp-marketing-automations' ), '', 404 );
				}

				return $this->success_response( [
					'schedule_id' => $schedule_id,
				], __( 'Syncing order has been started, data will be update in a while.', 'wp-marketing-automations' ) );
			case 1:
			case '1': // Completed successfully
				break;
			default: // Unknown error
				return $this->error_response( __( 'Unknown error occurred.', 'wp-marketing-automations' ), '', 404 );
		}

		// Otherwise, return success response with contact data
		$new_contact = $contact->get_customer_as_array();

		return $this->success_response( [
			'wc' => $new_contact,
		], __( 'Contact orders synced successfully', 'wp-marketing-automations' ) );
	}
}

BWFAN_API_Loader::register( 'BWFAN_API_Update_Contact_Order' );