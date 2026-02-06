<?php

if ( ! class_exists( 'BWFAN_Model_Templates' ) && BWFAN_Common::is_pro_3_0() ) {

	class BWFAN_Model_Templates extends BWFAN_Model {
		static $primary_key = 'ID';

		/**
		 * Get templates from db
		 *
		 * @param $offset
		 * @param $limit
		 * @param $search
		 * @param $id
		 * @param $get_template
		 * @param $mode
		 *
		 * @return array|object|stdClass[]
		 */
		public static function bwfan_get_templates( $offset, $limit, $search, $id, $get_template = true, $mode = '' ) {
			global $wpdb;
			$column = '*';
			if ( ! $get_template ) {
				$column = 'ID, title, mode,  created_at, updated_at';
			}
			$query = "SELECT $column FROM {table_name} WHERE 1=1 AND type = 1 AND canned = 1";

			if ( ! empty( $id ) ) {
				$id = array_filter( array_map( 'intval', $id ) );
				if ( ! empty( $id ) ) {
					$placeholders = array_fill( 0, count( $id ), '%d' );
					$query        .= $wpdb->prepare( " AND ID in ( " . implode( ',', $placeholders ) . " )", $id );
				}
			}
			if ( ! empty( $mode ) ) {
				$query .= $wpdb->prepare( " AND mode = %d", $mode );
			}
			if ( ! empty( $search ) ) {
				$query .= $wpdb->prepare( " AND title LIKE %s", "%" . esc_sql( $search ) . "%" );
			}
			$query .= ' ORDER BY updated_at DESC';
			if ( intval( $limit ) > 0 ) {
				$offset = ! empty( $offset ) ? intval( $offset ) : 0;
				$query  .= $wpdb->prepare( " LIMIT %d, %d", $offset, $limit );
			}

			$result = self::get_results( $query );

			$result = is_array( $result ) && ! empty( $result ) ? $result : array();

			return $result;
		}

		/**
		 * Get layouts from db
		 *
		 * @param $offset
		 * @param $limit
		 * @param $search
		 * @param $id
		 * @param bool $get_template
		 *
		 * @return array|object
		 */
		public static function bwfan_get_layouts( $offset, $limit, $search, $id ) {
			global $wpdb;

			$query = "SELECT * FROM {table_name} WHERE 1=1 AND type = 1 AND canned = 0 AND mode = 6";

			if ( ! empty( $id ) ) {
				$id = array_filter( array_map( 'intval', $id ) );
				if ( ! empty( $id ) ) {
					$placeholders = array_fill( 0, count( $id ), '%d' );
					$query        .= $wpdb->prepare( " AND ID in ( " . implode( ',', $placeholders ) . " )", $id );
				}
			}
			if ( ! empty( $search ) ) {
				$query .= $wpdb->prepare( " AND title LIKE %s", "%" . esc_sql( $search ) . "%" );
			}
			$query .= ' ORDER BY updated_at DESC';
			if ( intval( $limit ) > 0 ) {
				$offset = ! empty( $offset ) ? intval( $offset ) : 0;
				$query  .= $wpdb->prepare( " LIMIT %d, %d", $offset, $limit );
			}

			$result = self::get_results( $query );

			$result = is_array( $result ) && ! empty( $result ) ? $result : array();

			return $result;
		}

		/**
		 * Get templates count from db
		 *
		 * @param $search
		 * @param $id
		 * @param $mode
		 *
		 * @return int
		 */
		public static function bwfan_get_templates_count( $search = '', $id = [], $mode = 0 ) {
			global $wpdb;
			$table = $wpdb->prefix . 'bwfan_templates';

			$query = 'SELECT count(ID) FROM ' . $table . ' WHERE 1=1 AND type = 1 AND canned = 1';

			if ( ! empty( $id ) ) {
				$id = array_filter( array_map( 'intval', $id ) );
				if ( ! empty( $id ) ) {
					$placeholders = array_fill( 0, count( $id ), '%d' );
					$query        .= $wpdb->prepare( " AND ID in ( " . implode( ',', $placeholders ) . " )", $id );
				}
			}
			if ( ! empty( $mode ) ) {
				$query .= $wpdb->prepare( " AND mode = %d", $mode );
			}
			if ( ! empty( $search ) ) {
				$query .= $wpdb->prepare( " AND title LIKE %s", "%" . esc_sql( $search ) . "%" );
			}

			$result = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return $result ? intval( $result ) : 0;
		}

		/**
		 * Get layout count from db
		 *
		 * @param $search
		 * @param $id
		 *
		 * @return int
		 */
		public static function bwfan_get_layouts_count( $search, $id ) {
			global $wpdb;
			$table = $wpdb->prefix . 'bwfan_templates';

			$query = 'SELECT count(ID) FROM ' . $table . ' WHERE 1=1 AND type = 1 AND canned = 0 AND mode=6';

			if ( ! empty( $id ) ) {
				$id = array_filter( array_map( 'intval', $id ) );
				if ( ! empty( $id ) ) {
					$placeholders = array_fill( 0, count( $id ), '%d' );
					$query        .= $wpdb->prepare( " AND ID in ( " . implode( ',', $placeholders ) . " )", $id );
				}
			}
			if ( ! empty( $search ) ) {
				$query .= $wpdb->prepare( " AND title LIKE %s", "%" . esc_sql( $search ) . "%" );
			}

			$result = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return $result ? intval( $result ) : 0;
		}

		/**
		 * Check if template already exists
		 *
		 * @param $field
		 * @param $data
		 *
		 * @return int
		 */
		public static function bwfan_check_template_exists( $field, $data ) {
			global $wpdb;

			$query            = 'SELECT COUNT(ID) FROM ' . self::_table();
			$string_with_dash = "$data - %";
			$query            .= $wpdb->prepare( " WHERE ( {$field} = %s OR {$field} LIKE %s ) AND canned = %d LIMIT 0,1", $data, esc_sql( $string_with_dash ), 1 );
			$result           = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return $result;
		}

		/**
		 * Check if layout already exists
		 *
		 * @param $field
		 * @param $data
		 *
		 * @return int
		 */
		public static function bwfan_check_layout_exists( $field, $data ) {
			global $wpdb;

			$query            = 'SELECT COUNT(ID) FROM ' . self::_table();
			$string_with_dash = "$data - %";
			$query            .= $wpdb->prepare( " WHERE ( {$field} = %s OR {$field} LIKE %s ) AND canned = %d LIMIT 0,1", $data, esc_sql( $string_with_dash ), 0 );
			$result           = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return $result;
		}

		/**
		 * Insert new template to db
		 *
		 * @param $data
		 *
		 * @return int|void
		 */
		public static function bwfan_create_new_template( $data ) {
			if ( empty( $data ) ) {
				return;
			}

			self::insert( $data );
			$id = absint( self::insert_id() );

			return $id;
		}

		/**
		 * Delete template
		 *
		 * @param $id
		 *
		 * @return bool
		 */
		public static function bwf_delete_template( $id ) {
			if ( empty( $id ) ) {
				return false;
			}

			global $wpdb;
			$template_data = [
				'canned' => 0,
			];
			$table_name    = self::_table();
			if ( is_array( $id ) ) {
				/**Update multiple rows */
				$id = array_filter( array_map( 'intval', $id ) );
				if ( ! empty( $id ) ) {
					$placeholders = array_fill( 0, count( $id ), '%d' );
					$query        = $wpdb->prepare( "UPDATE $table_name SET `canned` = 0 WHERE ID in ( " . implode( ',', $placeholders ) . " )", $id );
					self::update_multiple( $query );
				}
			} else {
				$delete_template = self::update( $template_data, array(
					'id' => absint( $id ),
				) );

				if ( false === $delete_template ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Delete layout
		 *
		 * @param $id
		 *
		 * @return bool
		 */
		public static function bwf_delete_layout( $id ) {
			global $wpdb;
			$table_name    = self::_table();
			$delete_layout = $wpdb->delete( $table_name, array( 'ID' => $id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( false === $delete_layout ) {
				return false;
			}

			return true;
		}

		/**
		 * Fetch template by id
		 *
		 * @param $id
		 * @param $canned
		 *
		 * @return array|mixed|stdClass
		 */
		public static function bwfan_get_template( $id, $canned = 1 ) {
			global $wpdb;
			$query = $wpdb->prepare( 'SELECT * FROM {table_name} WHERE type = 1 AND canned = %d AND ID=%d', $canned, $id );

			$result = self::get_results( $query );

			return is_array( $result ) && ! empty( $result ) ? $result[0] : array();
		}

		/**
		 * Update Template data by id
		 *
		 * @param $id
		 * @param $data
		 *
		 * @return bool
		 */
		public static function bwfan_update_template( $id, $data ) {
			if ( ! is_array( $data ) ) {
				return false;
			}

			return ! ! self::update( $data, array(
				'id' => absint( $id ),
			) );
		}

		/**
		 * Return template id
		 */
		public static function get_first_template_id() {
			global $wpdb;
			$table = "{$wpdb->prefix}bwfan_templates";
			$query = " SELECT MIN(`id`) FROM $table WHERE `type` = 1 AND `canned` = 1 ";

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Clone given template ID
		 *
		 * @param $template_id
		 *
		 * @return array
		 */
		public static function clone_template( $template_id ) {
			$status   = 404;
			$message  = __( 'Unable to find template with the given id.', 'wp-marketing-automations' );
			$template = self::get_specific_rows( 'id', $template_id );
			if ( ! empty( $template ) ) {
				$create_time = current_time( 'mysql', 1 );
				$template    = $template[0];
				unset( $template['ID'] );
				$template['title'] = $template['title'] . ' ( Copy )';

				$template['created_at'] = $create_time;
				$template['updated_at'] = $create_time;

				self::insert( $template );
				$new_template_id = self::insert_id();

				if ( $new_template_id ) {
					$status  = 200;
					$message = __( 'Template cloned', 'wp-marketing-automations' );
				}
			}

			return array(
				'status'  => $status,
				'message' => $message,
			);
		}

		/**
		 * Get templates by ids
		 *
		 * @param $tids
		 * @param array $columns
		 *
		 * @return array
		 */
		public static function get_templates_by_ids( $tids, $columns = [] ) {
			global $wpdb;

			if ( empty( $tids ) ) {
				return [];
			}

			if ( empty( $columns ) ) {
				$columns = [ 'ID', 'subject', 'template', 'type' ];
			}

			$placeholders = array_fill( 0, count( $tids ), '%d' );
			$placeholders = implode( ', ', $placeholders );
			$query        = "SELECT " . implode( ', ', $columns ) . " FROM {$wpdb->prefix}bwfan_templates WHERE `ID` IN( $placeholders )";
			$result       = $wpdb->get_results( $wpdb->prepare( $query, $tids ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL

			$data = [];
			foreach ( $result as $template ) {
				$data[ $template['ID'] ] = $template;
			}

			return $data;
		}

		/**
		 * Get templates
		 *
		 * @param $offset
		 *
		 * @return array|object|stdClass[]|null
		 */
		public static function get_templates( $offset = '' ) {
			global $wpdb;
			$where = '';
			$args  = [];
			if ( ! empty( $offset ) ) {
				$where = " WHERE `id` > %d ";
				$args  = [ intval( $offset ) ];
			}

			$query = "SELECT * FROM {$wpdb->prefix}bwfan_templates $where";

			return $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		}
	}
}