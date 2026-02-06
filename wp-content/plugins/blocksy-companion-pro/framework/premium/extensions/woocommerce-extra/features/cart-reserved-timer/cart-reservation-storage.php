<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class CartReservationStorage {
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'blocksy_cart_reservations';
	}

	private function table_exists() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$wpdb->esc_like($this->table)
			)
		);

		return $result === $this->table;
	}

	public function create_table() {
		global $wpdb;
		$table_name = $this->table;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(255) NOT NULL,
			cart_data LONGTEXT NULL,
			last_modified DATETIME NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY session_id_unique (session_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	public function set_cart_reservation($session_id, $cart_data, $last_modified) {
		global $wpdb;

		if (! $this->table_exists()) {
			$this->create_table();
		}

		if (empty($cart_data)) {
			$this->remove_cart_reservation($session_id);
			return;
		}

		$encoded_cart = maybe_serialize($cart_data);
		$table = esc_sql($this->table);
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM %i WHERE session_id = %s",
				$table,
				$session_id
			)
		);

		if ($existing) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$this->table,
				[
					'cart_data' => $encoded_cart,
					'last_modified' => $last_modified,
				],
				[ 'session_id' => $session_id ]
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$this->table,
				[
					'session_id' => $session_id,
					'cart_data' => $encoded_cart,
					'last_modified' => $last_modified,
					'created_at' => current_time('mysql'),
				]
			);
		}
	}

	public function remove_cart_reservation($session_id) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete($this->table, [ 'session_id' => $session_id ]);
	}

	public function get_all_active_reservations() {
		global $wpdb;

		if (! $this->table_exists()) {
			return [];
		}

		$woo_reserved_timer_time = blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_time', 10);
		$total_seconds = $woo_reserved_timer_time * 60;

		$current_timestamp = current_time('timestamp', true);
		$current_time = gmdate('Y-m-d H:i:s', $current_timestamp);
		$table = esc_sql($this->table);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i
				WHERE TIMESTAMPDIFF(SECOND, last_modified, %s) < %d",
				$table,
				$current_time,
				$total_seconds
			)
		);
	}

	public function clear_expired() {
		global $wpdb;

		if (! $this->table_exists()) {
			return;
		}

		$woo_reserved_timer_time = blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_time', 10);
		$total_seconds = $woo_reserved_timer_time * 60;
		$table = esc_sql($this->table);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i
				WHERE TIMESTAMPDIFF(SECOND, last_modified, UTC_TIMESTAMP()) >= %d",
				$table,
				$total_seconds
			)
		);
	}

	public function get_current_reservation($session_id) {
		global $wpdb;
		
		if (! $this->table_exists()) {
			return null;
		}

		$table = esc_sql($this->table);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE session_id = %s LIMIT 1",
				$table,
				$session_id
			)
		);

		if ($result) {
			$result->cart_data = maybe_unserialize($result->cart_data);
		}

		return $result;
	}
}
