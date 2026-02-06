<?php

if ( ! class_exists( 'BWFAN_Model_Terms' ) && BWFAN_Common::is_pro_3_0() ) {
	class BWFAN_Model_Terms extends BWFAN_Model {
		static $primary_key = 'ID';

		/** @var array $terms_cache */
		private static $terms_cache = [];

		private static $query_cache = [];

		/**
		 * function to return term_data using term slug
		 **/
		static function get_term_by_name( $term_slug, $type = 1 ) {
			$query     = "SELECT * FROM `{table_name}` WHERE `name` LIKE '" . esc_sql( $term_slug ) . "' AND `type` = '" . $type . "'";
			$query_md5 = md5( $query );

			if ( isset( self::$query_cache[ $query_md5 ] ) && ! empty( self::$query_cache[ $query_md5 ] ) ) {
				$term_data = self::$query_cache[ $query_md5 ];
			} else {
				$term_data = self::get_results( $query );

				self::$query_cache[ $query_md5 ] = $term_data;
			}

			return is_array( $term_data ) && ! empty( $term_data ) ? $term_data[0] : array();
		}


		/**
		 * function to return term_data using term slug
		 **/
		static function search_term( $term_slug, $type = 1 ) {
			$query     = "SELECT * FROM `{table_name}` WHERE `name` LIKE '%" . esc_sql( $term_slug ) . "%' AND `type` = '" . $type . "'";
			$term_data = self::get_results( $query );

			return is_array( $term_data ) && ! empty( $term_data ) ? $term_data : array();
		}


		/**
		 * @param int $type
		 * @param int $offset
		 * @param int $limit
		 * @param string $search
		 * @param array $ids
		 * @param string $search_nature
		 * @param bool $use_cache
		 *
		 * @return array|object
		 */
		static function get_terms( $type = 1, $offset = 0, $limit = 10, $search = '', $ids = array(), $search_nature = '', $use_cache = false ) {
			$found_terms = [];

			/** Cache Implementation: BEGIN */
			/**
			 * Retrieving terms from cache is only possible when there is Exact names of terms to be searched.
			 * OR, we have IDs to retrieve
			 * Means either Term Name or ID is needed to retrieve from cache.
			 *
			 * Also don't use cache in case we have pagination parameters,
			 * which makes it difficult to populate terms from both cache & DB for a particular page
			 */
			$eligible_for_cache = ( 'exact' === $search_nature && ! empty( $search ) ) || ( ! empty( $ids ) && is_array( $ids ) );
			if ( $use_cache && $eligible_for_cache ) {
				$offset = 0;
				$limit  = 1000;

				$result = self::_get_terms_from_cache( $search, $search_nature, $ids, $type );
				if ( empty( $result['ids_not_found'] ) && empty( $result['search_not_found'] ) ) {
					return $result['found_terms'];
				}

				$search      = $result['search_not_found'];
				$ids         = $result['ids_not_found'];
				$found_terms = $result['found_terms'];
			}
			/** Cache Implementation: END */

			global $wpdb;

			$search_terms = '';
			$limit_query  = '';
			$order        = ' ORDER BY `ID` DESC';
			if ( ! empty( $search ) ) {
				if ( ! is_array( $search ) ) {
					$search       = BWFAN_Common::get_formatted_value_for_dbquery( $search );
					$search_terms = ( 'exact' === $search_nature ? " AND name = '$search'" : "AND name LIKE '%" . esc_sql( $search ) . "%'" );
				} else if ( 'exact' === $search_nature ) {
					$search       = array_map( function ( $value ) {
						return BWFAN_Common::get_formatted_value_for_dbquery( $value );
					}, $search );
					$search       = implode( "','", $search );
					$search_terms = " AND name IN ('$search')";
				} else {
					$search_terms = array_map( function ( $s_term ) {
						$s_term = BWFAN_Common::get_formatted_value_for_dbquery( $s_term );

						return "name LIKE '%" . esc_sql( $s_term ) . "%'";
					}, $search );
					$search_terms = implode( ' OR ', $search_terms );
					$search_terms = " AND ($search_terms)";
				}
				$order = ' ORDER BY `name` ASC';
			}

			if ( ! empty( $ids ) ) {
				$search_terms .= " AND ID IN(" . implode( ',', $ids ) . ")";
			}

			if ( ! empty( $limit ) ) {
				$offset      = ! empty( $offset ) ? $offset : 0;
				$limit_query = " LIMIT $offset,$limit";
			}

			$type_query = empty( $type ) ? '' : " AND type='$type'";

			$query     = "SELECT * FROM `{$wpdb->prefix}bwfan_terms` WHERE 1=1 $type_query $search_terms $order $limit_query";
			$term_data = self::get_results( $query );
			$term_data = is_array( $term_data ) && ! empty( $term_data ) ? $term_data : array();

			/** Store the retrieved terms into the cache */
			if ( $use_cache && $eligible_for_cache ) {
				self::$terms_cache = array_merge( self::$terms_cache, $term_data );

				return array_merge( $found_terms, $term_data );
			}

			return $term_data;
		}

		private static function _get_terms_from_cache( $search, $search_nature, $ids, $type ) {
			$found_terms      = [];
			$search_not_found = [];
			$ids_not_found    = [];
			if ( empty( $ids ) && ( 'exact' !== $search_nature || empty( $search ) ) ) {
				return [
					'found_terms'      => $found_terms,
					'search_not_found' => $search_not_found,
					'ids_not_found'    => $ids_not_found
				];
			}

			/**
			 * In search with exact words (not wildcard words eg %s%), we can get terms by term name.
			 */
			if ( 'exact' === $search_nature && ! empty( $search ) ) {
				if ( ! is_array( $search ) ) {
					$search = [ $search ];
				}

				foreach ( $search as $index => $s_term ) {
					$found_term = false;
					foreach ( self::$terms_cache as $c_term ) {
						if ( ! is_array( $c_term ) || ! isset( $c_term['name'] ) || $s_term !== $c_term['name'] || $c_term['type'] !== $type ) {
							continue;
						}

						$found_term = $c_term;
						break;
					}

					if ( false === $found_term || ! is_array( $found_term ) ) {
						$search_not_found[] = $s_term;
					} else {
						$found_terms[] = $found_term;
					}
				}
			}

			/**
			 * In case we have ids for terms to retrieve, we can get terms by term IDs from cache array.
			 */
			if ( ! empty( $ids ) && is_array( $ids ) ) {
				foreach ( $ids as $term_id ) {
					$found_term = false;
					foreach ( self::$terms_cache as $c_term ) {
						if ( ! is_array( $c_term ) || ! isset( $c_term['name'] ) || absint( $term_id ) !== absint( $c_term['ID'] ) ) {
							continue;
						}

						$found_term = $c_term;
						break;
					}

					if ( false === $found_term || ! is_array( $found_term ) ) {
						$ids_not_found[] = absint( $term_id );
					} else {
						$found_terms[] = $found_term;
					}
				}
			}

			return [
				'found_terms'      => $found_terms,
				'search_not_found' => $search_not_found,
				'ids_not_found'    => $ids_not_found
			];
		}


		public static function checking_term( $term_id, $type = 1 ) {

			$query       = "SELECT ID from {table_name} where ID='" . $term_id . "' and type='" . $type . "'";
			$term_result = self::get_results( $query );

			return $term_result;
		}

		public static function get_all( $type = 1 ) {
			$query        = "select ID, name from {table_name} where type='" . $type . "'";
			$term_results = self::get_results( $query );

			return $term_results;
		}

		/**
		 * @param $id
		 *
		 * @return bool
		 */
		public static function delete_term( $id ) {
			global $wpdb;
			$term_table  = $wpdb->prefix . 'bwfan_terms';
			$delete_term = $wpdb->delete( $term_table, array( 'ID' => $id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( false === $delete_term ) {
				return false;
			}

			return true;

		}

		static function insert_multiple( $values, $keys, $format = [] ) {
			if ( ( ! is_array( $keys ) || empty( $keys ) ) || ( ! is_array( $values ) || empty( $values ) ) ) {
				return false;
			}

			global $wpdb;

			$values = array_map( function ( $value ) use ( $keys ) {
				$return = array();
				foreach ( $keys as $key ) {
					if ( is_numeric( $value[ $key ] ) && 'name' !== $key ) {
						$return[] = absint( $value[ $key ] );
					}
					if ( is_string( $value[ $key ] ) ) {
						$formatted_value = BWFAN_Common::get_formatted_value_for_dbquery( $value[ $key ] );
						$return[]        = "'" . $formatted_value . "'";
					}
				}

				return '(' . implode( ',', $return ) . ')';
			}, $values );
			$values = implode( ', ', $values );

			$keys  = '(' . implode( ', ', $keys ) . ')';
			$query = 'INSERT INTO ' . self::_table() . ' ' . $keys . ' VALUES ' . $values;

			return $wpdb->query( $wpdb->prepare( "$query ", $values ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * @param int $type
		 * @param string $search
		 * @param array $ids
		 * @param string $search_nature
		 *
		 * @return int
		 */
		static function get_terms_count( $type = 1, $search = '', $ids = array(), $search_nature = '' ) {
			global $wpdb;

			$search_terms = '';
			if ( ! empty( $search ) ) {
				if ( ! is_array( $search ) ) {
					$search_terms = ( 'exact' === $search_nature ? " AND name = '$search'" : "AND name LIKE '%" . esc_sql( $search ) . "%'" );
					$search_terms = 'name_with_dash' === $search_nature ? " AND ( name = '$search' OR name LIKE '" . esc_sql( $search ) . " - %' ) " : $search_terms;
				} else if ( 'exact' === $search_nature ) {
					$search       = implode( "','", $search );
					$search_terms = " AND name IN ('$search')";
				} else {
					$search_terms = array_map( function ( $s_term ) {
						return "name LIKE '%" . esc_sql( $s_term ) . "%'";
					}, $search );
					$search_terms = implode( ' OR ', $search_terms );
					$search_terms = " AND ($search_terms)";
				}
			}

			if ( ! empty( $ids ) ) {
				$search_terms .= " AND ID IN(" . implode( ',', $ids ) . ")";
			}

			$query = "SELECT COUNT(ID) from {$wpdb->prefix}bwfan_terms where type='$type' $search_terms ";

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Get term ids
		 * Create terms if 0 id passed
		 *
		 * @param $terms
		 * @param $type
		 *
		 * @return array
		 */
		public static function get_crm_term_ids( $terms = [], $type = 1 ) {
			if ( empty( $terms ) || ! in_array( $type, [ BWFCRM_Term_Type::$TAG, BWFCRM_Term_Type::$LIST ], true ) ) {
				return [];
			}

			foreach ( $terms as $k => $term ) {
				if ( ! is_array( $term ) || 0 < intval( $term['id'] ) ) {
					continue;
				}

				$name = ( isset( $term['name'] ) && ! empty( $term['name'] ) ) ? $term['name'] : '';
				$name = ( empty( $name ) && isset( $term['value'] ) && ! empty( $term['value'] ) ) ? $term['value'] : $name;
				if ( empty( $name ) ) {
					continue;
				}

				/** check if term exists */
				$term_row = self::get_term_by_name( $name, $type );
				if ( ! empty( $term_row ) ) {
					/** term found */
					$terms[ $k ]['id'] = $term_row['ID'];
					continue;
				}

				/** create term */
				$id = self::create_single_term( $name, $type );
				if ( ! empty( $id ) ) {
					$terms[ $k ]['id'] = $id;
				}
			}

			$ids = array_column( $terms, 'id' );
			$ids = array_unique( $ids );
			sort( $ids );

			return $ids;
		}

		/**
		 * Get term objects
		 *
		 * @param $terms
		 *
		 * @return array
		 */
		public static function get_term_objects( $terms = [] ) {
			if ( empty( $terms ) ) {
				return [];
			}

			$term_obj = array_map( function ( $term_id ) {
				$db_row = BWFAN_Model_Terms::get( $term_id );

				if ( ! is_array( $db_row ) ) {
					return false;
				}

				return ( BWFCRM_Term_Type::$TAG === absint( $db_row['type'] ) ? ( new BWFCRM_Tag( $db_row ) ) : ( new BWFCRM_Lists( $db_row ) ) );
			}, $terms );

			return array_filter( $term_obj );
		}

		/**
		 * Create a term
		 *
		 * @param $term_name
		 * @param $type
		 *
		 * @return false|int term id
		 */
		public static function create_single_term( $term_name, $type ) {
			global $wpdb;

			$data = array(
				'name'       => $term_name,
				'type'       => $type,
				'created_at' => current_time( 'mysql', 1 ),
			);
			self::insert( $data );
			if ( empty( $wpdb->last_error ) ) {
				return $wpdb->insert_id;
			}

			return false;
		}

		/**
		 * Return first term id by type
		 *
		 * @param $type
		 *
		 * @return int|mixed
		 */
		public static function get_first_term_by_type( $type = 1 ) {
			$query     = "SELECT ID FROM `{table_name}` WHERE `type` = '" . $type . "' ORDER BY `ID` ASC LIMIT 1";
			$term_data = self::get_results( $query );

			return is_array( $term_data ) && isset( $term_data[0] ) && ! empty( $term_data ) && isset( $term_data[0]['ID'] ) ? $term_data[0]['ID'] : 0;
		}
	}
}