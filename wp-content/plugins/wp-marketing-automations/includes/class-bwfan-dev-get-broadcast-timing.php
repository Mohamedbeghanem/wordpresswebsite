<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'BWFAN_DEV_Get_Broadcast_Timing' ) ) {
	final class BWFAN_DEV_Get_Broadcast_Timing {
		private static $ins = null;

		public function __construct() {
			add_action( 'admin_head', [ $this, 'broadcast_details' ] );
		}

		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		public function broadcast_details() {
			$bid = filter_input( INPUT_GET, 'admin_broadcast_id' );
			if ( empty( $bid ) ) {
				return;
			}

			$this->output_css();

			$data = $this->get_broadcast_timing( $bid );
			if ( empty( $data ) ) {
				echo "<h3>No Broadcast details found.</h3>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				exit;
			}

			$start_time = ! empty( $data['start_time'] ) ? "Start time: " . $data['start_time'] : '';
			if ( empty( $start_time ) ) {
				echo "<h3>Broadcast not started yet.</h3>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				exit;
			}

			$end_time = ! empty( $data['start_time'] ) ? "End time: " . $data['end_time'] : '';
			if ( empty( $end_time ) ) {
				echo "<h3>Broadcast is not finished yet.</h3>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				exit;
			}

			$total_sent = ! empty( $data['sent'] ) ? "Total sent: " . $data['sent'] : '';
			if ( empty( $total_sent ) ) {
				echo "<h3>No emails sent yet.</h3>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				exit;
			}

			$total_time = strtotime( $data['end_time'] ) - strtotime( $data['start_time'] );
			$per_sec    = ( $total_time > 0 && $data['sent'] > 0 ) ? absint( $data['sent'] ) / $total_time : $data['sent'];

			echo '<div class="broadcast_data_wrapper"><div class="broadcast_data"><div> '; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "$start_time<br>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "$end_time<br>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "$total_sent<br>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo "Total time in secs: "; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ( ! empty( $total_time ) ) ? $total_time : 1; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<br>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( ! empty( $total_time ) && ( $total_time > 60 ) ) {
				echo "Total time in mins: " . intval( $total_time / 60 ) . "<br>";
			}
			echo "Emails in one second: $per_sec"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo "<h3>Per second breakup</h3> </div>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$data = $this->get_broadcast_details( $bid );

			echo '<div class="broadcast_table">'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<div><div>Date Time</div><div>Count</div></div>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$diff_start = '';
			$diff_array = [];
			foreach ( $data as $v ) {
				if ( ! empty( $diff_start ) && ! empty( $v['created_at'] ) ) {
					$startd = strtotime( $diff_start );
					$endd   = strtotime( $v['created_at'] );
					$diff   = $startd - $endd;
					if ( ! empty( $diff ) && ( $diff > 1 ) ) {
						$startd  = $startd - 1;
						$endd    = $endd + 1;
						$resdata = [
							'difference' => $diff - 1,
							'end_time'   => '-',
						];
						if ( $startd !== $endd ) {
							$resdata['end_time']   = date( 'Y-m-d H:i:s', $startd );
							$resdata['start_time'] = date( 'Y-m-d H:i:s', $endd );
						} else {
							$resdata['start_time'] = date( 'Y-m-d H:i:s', $startd );
						}
						$diff_array[] = $resdata;
					}

				}
				$diff_start = $v['created_at'];
				echo "<div><div>{$v['created_at']}</div><div>{$v['count']}</div></div>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '</div>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( ! empty( $diff_array ) ) {
				echo '<table class="broadcast_table_idel"><tbody>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "<tr><td><b>Start Time</b></td><td><b>End Time</b></td><td><b>Time ( in sec )</b></td></tr>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				foreach ( $diff_array as $v ) {
					echo "<tr><td>{$v['start_time']}</td><td>{$v['end_time']}</td><td>{$v['difference']}</td></tr>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo '</tbody></table>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
			exit;
		}

		public function get_broadcast_timing( $oid ) {
			global $wpdb;
			$query = "SELECT MAX( created_at ) as end_time, MIN( created_at ) as start_time, COUNT( ID ) as sent FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE `oid` = %d and `type` = %d";

			return $wpdb->get_row( $wpdb->prepare( $query, $oid, 2 ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		}

		public function get_broadcast_details( $oid ) {
			global $wpdb;
			$limit       = isset( $_GET['limit'] ) ? (int) $_GET['limit'] : 100;
			$page        = isset( $_GET['apage'] ) ? (int) $_GET['apage'] : 0;
			$offset      = ( 0 !== $page ) ? ( $page - 1 ) * $limit : 0;
			$limit_query = " LIMIT 0, 100";
			if ( ! empty( $limit ) ) {
				$limit_query = " LIMIT {$offset}, {$limit}";
			}
			$query = "SELECT `created_at`, count(`created_at`) as `count` FROM `{$wpdb->prefix}bwfan_engagement_tracking` WHERE `type` = %d AND `oid` = %d GROUP BY `created_at` ORDER BY `created_at` DESC $limit_query";

			return $wpdb->get_results( $wpdb->prepare( $query, 2, $oid ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		}

		public function output_css() {
			?>
            <style>
                h3 {
                    margin: 20px;
                }

                .broadcast_data_wrapper {
                    display: flex;
                    flex-wrap: wrap;
                }

                .broadcast_data {
                    padding: 20px;
                    font-size: 16px;
                    line-height: 2;
                }

                .broadcast_table {
                    width: 500px;
                    line-height: 2;
                }

                .broadcast_table > div {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                }

                table.broadcast_table_idel {
                    border: 1px solid #ccc;
                    border-collapse: collapse;
                    font-size: 16px;
                    line-height: 1;
                    table-layout: fixed;
                    height: fit-content;
                }

                table.broadcast_table_idel, table.broadcast_table_idel tbody {
                    line-height: 1;
                }

                table.broadcast_table_idel tr {
                    height: 36px;
                }

                table.broadcast_table_idel td {
                    border-bottom: 1px solid #ccc;
                    min-width: 200px;
                    padding: 5px;
                    line-height: 1;
                    height: 36px;
                }
            </style>
			<?php
		}
	}

	BWFAN_DEV_Get_Broadcast_Timing::get_instance();
}