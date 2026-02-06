<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

/**
 * Class WFFN_DB_Optin
 */
if ( ! class_exists( 'WFFN_DB_Optin' ) ) {
	#[AllowDynamicProperties]
	class WFFN_DB_Optin {
		/**
		 * @var $ins
		 */
		public static $ins;

		/**
		 * @var $optin_tbl
		 */
		public $optin_tbl;

		/**
		 * WFFN_DB_Optin constructor.
		 */
		public function __construct() {
			$this->optin_tbl = 'bwf_optin_entries';
		}

		/**
		 * @return WFFN_DB_Optin
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self;
			}

			return self::$ins;
		}

		/**
		 * Inserting a new row in bwf_optins table
		 *
		 * @param $optin
		 *
		 * @return int
		 * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
		 */
		public function insert_optin( $optin ) {
			global $wpdb;
			$optin_data = array(
				'step_id'   => $optin['step_id'],
				'funnel_id' => $optin['funnel_id'],
				'cid'       => $optin['cid'],
				'opid'      => $optin['opid'],
				'email'     => $optin['email'],
				'data'      => wp_json_encode( $optin['data'] ),
				'date'      => current_time( 'mysql' ),
			);
			$table      = $wpdb->prefix . 'bwf_optin_entries';
			$inserted   = $wpdb->insert( $table, $optin_data );

			$lastId = 0;
			if ( $inserted ) {
				$lastId = $wpdb->insert_id;
			}
			if ( ! empty( $wpdb->last_error ) ) {
				WFFN_Core()->logger->log( 'Get last error in insert_contact: ' . print_r( $wpdb->last_error, true ) . ' posted data ' . print_r( $optin_data, true ), 'wffn', true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			return $lastId;
		}


		/**
		 * Get contact for given opid if it exists
		 */
		public function get_contact_by_opid( $opid ) {
			global $wpdb;
			$table = $wpdb->prefix . $this->optin_tbl;

			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table` WHERE `opid` = %s", sanitize_text_field( $opid ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is hardcoded class property
		}

		/**
		 * Get contact for given id if it exists
		 */
		public function get_contact( $id ) {
			global $wpdb;
			$table = $wpdb->prefix . $this->optin_tbl;

			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table` WHERE `id` = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is hardcoded class property
		}


	}

	WFFN_DB_Optin::get_instance();
}