<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
if ( ! class_exists( 'WFFN_Notification_Metrics_Controller' ) ) {
	/**
	 * Class WFFN_Notification_Metrics_Controller
	 */
	class WFFN_Notification_Metrics_Controller {
		protected $data = array();
		protected $date_params = array();
		private $frequency = '';

		/**
		 * Constructor.
		 *
		 * @param array $date_params
		 */
		public function __construct( $date_params = array(), $frequency = 'weekly' ) {
			$this->date_params                       = wp_parse_args( $date_params, array(
				'from_date'          => date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) ),// @codingStandardsIgnoreLine
				'to_date'            => date( 'Y-m-d 23:59:59', strtotime( '-1 day' ) ),// @codingStandardsIgnoreLine
				'from_date_previous' => date( 'Y-m-d 00:00:00', strtotime( '-2 day' ) ),// @codingStandardsIgnoreLine
				'to_date_previous'   => date( 'Y-m-d 23:59:59', strtotime( '-2 day' ) ),// @codingStandardsIgnoreLine
			) );
			$this->date_params['from_date']          = date( 'Y-m-d H:i:s', strtotime( $this->date_params['from_date'] ) );// @codingStandardsIgnoreLine
			$this->date_params['to_date']            = date( 'Y-m-d 23:59:00', strtotime( $this->date_params['to_date'] ) );// @codingStandardsIgnoreLine
			$this->date_params['from_date_previous'] = date( 'Y-m-d H:i:s', strtotime( $this->date_params['from_date_previous'] ) );// @codingStandardsIgnoreLine
			$this->date_params['to_date_previous']   = date( 'Y-m-d 23:59:00', strtotime( $this->date_params['to_date_previous'] ) );// @codingStandardsIgnoreLine
			$this->frequency                         = $frequency;
		}


		/**
		 * Get metrics data.
		 *
		 * @return array
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Prepare data by using WFFN_REST_API_Dashboard_EndPoint instance with both current and previous date ranges.
		 */
		public function prepare_data() {
			$dashboard_endpoint        = WFFN_REST_API_Dashboard_EndPoint::get_instance();
			$current_date_params       = [
				'after'  => $this->date_params['from_date'],
				'before' => $this->date_params['to_date'],
			];
			$current_overview_response = $dashboard_endpoint->get_overview_data( $current_date_params, true );
			$current_overview_data     = $current_overview_response->get_data();

			$previous_date_params       = [
				'after'  => $this->date_params['from_date_previous'],
				'before' => $this->date_params['to_date_previous'],
			];
			$previous_overview_response = $dashboard_endpoint->get_overview_data( $previous_date_params, true );
			$previous_overview_data     = $previous_overview_response->get_data();

			$this->data['metrics'] = [];

			if ( ! empty( $current_overview_data['data'] ) && ! empty( $previous_overview_data['data'] ) ) {
				$metrics = [ 'total_orders', 'total_contacts', 'revenue', 'bump_revenue', 'upsell_revenue', 'average_order_value' ];
				foreach ( $metrics as $metric ) {
					$current_value                    = $current_overview_data['data'][ $metric ];
					$previous_value                   = $previous_overview_data['data'][ $metric ];
					$percentage_change                = $previous_value > 0 ? ( ( $current_value - $previous_value ) / $previous_value ) * 100 : 0;
					$this->data['metrics'][ $metric ] = [
						'text'                       => __( ucfirst( str_replace( '_', ' ', $metric ) ), 'Funnelkit' ),
						'previous_text'              => sprintf( __( '- Previous %s', 'FunnelKit' ), $this->get_frequency_text() ),
						'count'                      => round( $current_value, 2 ),
						'count_suffix'               => in_array( $metric, [ 'revenue', 'upsell_revenue', 'bump_revenue', 'average_order_value' ] ) ? $this->get_currency() : '', // @codingStandardsIgnoreLine
						'previous_count'             => $previous_value,
						'percentage_change'          => sprintf( '%s%%', round( $percentage_change, 2 ) ),
						'percentage_change_positive' => $percentage_change >= 0,
					];
				}
			}
		}

		/**
		 * Check if there is any meaningful data to send in the metrics.
		 *
		 * @return bool
		 */
		public function is_valid() {
			$is_valid = false;
			if ( isset( $this->data['metrics']['total_orders'] ) ) {
				$total_orders = $this->data['metrics']['total_orders'];
				if ( $total_orders['count'] > 0 ) {
					$is_valid = true;
				}
			}

			return $is_valid;
		}

		/**
		 * Retrieves the frequency text based on the provided key and capitalized option.
		 */
		protected function get_frequency_text( $capitalized = false ) {
			$frequencies = [
				'weekly'  => $capitalized ? 'Week' : 'week',
				'monthly' => $capitalized ? 'Month' : 'month',
			];

			return isset( $frequencies[ $this->frequency ] ) ? __( $frequencies[ $this->frequency ], 'Funnelkit' ) : ( $capitalized ? '' : '' );
		}

		private function get_currency() {

			if ( function_exists( 'get_woocommerce_currency' ) ) {
				return get_woocommerce_currency();
			}

			return '';
		}

	}
}