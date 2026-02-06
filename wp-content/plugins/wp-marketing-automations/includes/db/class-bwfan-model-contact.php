<?php
if ( ! class_exists( 'BWFCRM_Model_Contact' ) && BWFAN_Common::is_pro_3_0() ) {

	class BWFCRM_Model_Contact extends BWFAN_Model {
		static $primary_key = 'ID';

		private static $filter_queries = array();
		private static $filter_queries_params = array();
		private static $logs = array();

		public static $wc_filters = array(
			'total_order_value',
			'total_order_count',
			'l_order_date',
			'l_order_days',
			'f_order_date',
			'aov',
			'purchased_products_cats',
			'purchased_products_tags',
			'purchased_products',
			'used_coupons',
			'has_purchased',
			'has_used_any_coupons',
		);

		protected static function _table() {
			global $wpdb;

			return $wpdb->prefix . 'bwf_contact';
		}

		/**
		 * Get first contact id
		 *
		 * @param $id
		 *
		 * @return string|null
		 */
		public static function get_first_contact_id( $id = 0 ) {
			global $wpdb;
			$where = $id ? $wpdb->prepare( "AND id > %d", $id ) : '';
			$query = 'SELECT MIN(id) from ' . self::_table() . ' WHERE 1=1 ' . $where;

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_contacts( $search = '', $limit = 25, $offset = 0, $normalized_filters = array(), $additional_info = array(), $filter_match = ' AND ' ) {
			$grab_totals = is_array( $additional_info ) && isset( $additional_info['grab_totals'] ) && true === $additional_info['grab_totals'];
			$only_count  = is_array( $additional_info ) && isset( $additional_info['only_count'] ) && true === $additional_info['only_count'];

			/** Fallback for limit */
			if ( false === $only_count && empty( $limit ) ) {
				$limit = 25;
			}

			global $wpdb;

			self::set_log( array(
				'search'             => $search,
				'limit'              => $limit,
				'offset'             => $offset,
				'normalized_filters' => $normalized_filters,
				'additional_info'    => $additional_info,
				'filter_match'       => $filter_match,
			) );

			/** Get Contacts */
			$sql_queries = self::_get_contacts_sql( $search, $limit, $offset, $normalized_filters, $additional_info, $filter_match );

			self::set_log( 'contact sql: ' . $sql_queries['base'] );

			$total_count = '';
			if ( (bool) $grab_totals || (bool) $only_count ) {
				/** Total Count (For Pagination) */
				$total_count = $wpdb->get_var( $sql_queries['total'] ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( (bool) $only_count ) {
					return array(
						'contacts' => array(),
						'total'    => $total_count,
					);
				}
			}

			$time     = microtime( true );
			$contacts = $wpdb->get_results( $sql_queries['base'], ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$time     = microtime( true ) - $time;
			self::set_log( 'Time to execute the query: ' . $time . ' secs' );

			self::log();

			/** In case there is DB error and no contacts */
			if ( empty( $contacts ) && ! empty( $wpdb->last_error ) ) {
				BWFAN_Common::log_test_data( $wpdb->last_error, 'collation-issue', true );
				BWFAN_Fix_Collation::maybe_fix_collation_issue();
				$contacts = $wpdb->get_results( $sql_queries['base'], ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			if ( empty( $contacts ) ) {
				$error_text = ob_get_clean();

				return ! empty( $error_text ) ? $error_text : array();
			}

			/** Set Contacts in Cache */
			$ins = new WooFunnels_Contact();
			foreach ( $contacts as $contact ) {
				$ins->set_cache_object( 'cid', $contact['id'], (object) $contact );
			}
			unset( $ins );

			return array(
				'contacts' => $contacts,
				'total'    => $total_count,
			);
		}

		public static function _get_contacts_sql( $search = '', $limit = 25, $offset = 0, $filters = array(), $additional_info = array(), $filter_match = ' AND ', $column_preference = false ) {
			$should_send_wc = is_array( $additional_info ) && isset( $additional_info['customer_data'] ) && true === $additional_info['customer_data'];
			$should_send_cf = is_array( $additional_info ) && isset( $additional_info['grab_custom_fields'] ) && true === $additional_info['grab_custom_fields'];
			$contact_mode   = is_array( $additional_info ) && isset( $additional_info['fetch_base'] ) ? absint( $additional_info['fetch_base'] ) : 1;
			$exclude_unsubs = is_array( $additional_info ) && isset( $additional_info['exclude_unsubs'] ) && true === $additional_info['exclude_unsubs'];
			$order_by       = is_array( $additional_info ) && isset( $additional_info['order_by'] ) && ! empty( $additional_info['order_by'] ) ? $additional_info['order_by'] : 'last_modified';
			$order          = is_array( $additional_info ) && isset( $additional_info['order'] ) && ! empty( $additional_info['order'] ) ? $additional_info['order'] : 'DESC';

			$start_id       = is_array( $additional_info ) && isset( $additional_info['start_id'] ) && ! empty( $additional_info['start_id'] ) ? absint( $additional_info['start_id'] ) : 0;
			$end_id         = is_array( $additional_info ) && isset( $additional_info['end_id'] ) && ! empty( $additional_info['end_id'] ) ? absint( $additional_info['end_id'] ) : 0;
			$exclude_end_id = is_array( $additional_info ) && isset( $additional_info['exclude_end_id'] ) && true === $additional_info['exclude_end_id'];

			$exclude_ids = is_array( $additional_info ) && isset( $additional_info['exclude_ids'] ) && is_array( $additional_info['exclude_ids'] ) ? $additional_info['exclude_ids'] : array();
			$exclude_ids = array_map( 'absint', $exclude_ids );
			$exclude_ids = implode( ',', $exclude_ids );

			$include_ids = is_array( $additional_info ) && isset( $additional_info['include_ids'] ) && is_array( $additional_info['include_ids'] ) ? $additional_info['include_ids'] : array();
			$include_ids = array_map( 'absint', $include_ids );
			$include_ids = implode( ',', $include_ids );

			global $wpdb;
			$contact_table        = $wpdb->prefix . 'bwf_contact';
			$customer_table       = $wpdb->prefix . 'bwf_wc_customers';
			$contact_fields_table = $wpdb->prefix . 'bwf_contact_fields';

			/** Extract status filter, and unset the status filter if unsubscribe value */
			$status_filter = array();
			if ( ! empty( $filters ) && isset( $filters['c'] ) && ! empty( $filters['c'] ) ) {
				/** Extract the status filter */
				$status_filter = array_filter( $filters['c'], function ( $v ) {
					return ( isset( $v['key'] ) && 'status' === $v['key'] );
				}, ARRAY_FILTER_USE_BOTH );

				/** Remove un-subscribe status filter from Contact (c) filters */
				if ( ! empty( $status_filter ) ) {
					$status_filter = reset( $status_filter );
					$filters['c']  = array_filter( $filters['c'], function ( $v ) {
						return ( 'status' !== $v['key'] || 3 !== absint( $v['value'] ) );
					}, ARRAY_FILTER_USE_BOTH );

					if ( isset( $additional_info['include_unsubscribe'] ) && empty( $additional_info['include_unsubscribe'] ) ) {
						unset( $additional_info['include_unsubscribe'] );
					}
				}

				/** Unset contact filters array if there are no items, to prevent buggy SQL ANDs */
				if ( empty( $filters['c'] ) ) {
					unset( $filters['c'] );
				}
			}
			/** Excludes Un-subscribers or not */
			$unsubs_query = self::_get_unsubscribers_query( $status_filter, $exclude_unsubs, $additional_info );

			/** Contact (wp) and Customer (wc) SQL Queries */
			$filter_query = bwfan_is_autonami_pro_active() ? self::_get_filters_sql( $filters, $filter_match ) : [];

			/** Include soft bounce, unverified and unsubscribe status. */
			if ( ! empty( $additional_info['include_soft_bounce'] ) || ! empty( $additional_info['include_unverified'] ) || ! empty( $additional_info['include_unsubscribe'] ) ) {
				$filter_query = self::get_modified_status_query( $filter_query, $additional_info, $unsubs_query );
				$unsubs_query = ! empty( $additional_info['include_unsubscribe'] ) ? '' : $unsubs_query;
			}

			/** Columns needed and JOINS */
			$wp_wc_columns = $should_send_wc ? ' c.*, wc.aov, wc.id as customer_id, wc.l_order_date,wc.f_order_date,wc.total_order_count, wc.total_order_value, wc.purchased_products, wc.purchased_products_cats, wc.purchased_products_tags, wc.used_coupons' : ' c.*';
			$join_wc_query = ( isset( $filters['wc'] ) || $should_send_wc ) ? "LEFT JOIN $customer_table as wc ON c.id = wc.cid" : '';
			$join_cf_query = '';

			/**
			 * $should_send_cf - passed from contact export as need to show the data from contact field table
			 */
			if ( $should_send_cf || isset( $filters['cm'] ) ) {
				$cf_columns = self::_get_contact_fields_columns_sql();
				if ( ! empty( $cf_columns ) ) {
					$wp_wc_columns = $should_send_cf ? $wp_wc_columns . ", " . $cf_columns : $wp_wc_columns;
					$join_cf_query = "LEFT JOIN $contact_fields_table as `cm` ON c.id = cm.cid";
				}
			}

			if ( isset( $filters['custom'] ) ) {
				$join_cf_query = apply_filters( 'bwfan_contact_sql_join_query', $join_cf_query, $filters['custom'] );
			}

			$group_by_query = '';
			if ( isset( $filters['custom'] ) ) {
				$group_by_query = apply_filters( 'bwfan_contact_sql_group_by_query', $group_by_query, $filters['custom'] );
				$group_by_query = ! empty( $group_by_query ) ? 'GROUP BY ' . $group_by_query : '';
			}

			/** Checking column preferences  */
			if ( true !== $column_preference ) {
				/** Columns needed and JOINS */
				$wp_wc_columns = $should_send_wc ? ' c.*, wc.aov, wc.id as customer_id, wc.l_order_date, wc.f_order_date, wc.total_order_count, wc.total_order_value, wc.purchased_products, wc.purchased_products_cats, wc.purchased_products_tags, wc.used_coupons' : ' c.*';
				$join_wc_query = ( isset( $filters['wc'] ) || $should_send_wc ) ? "LEFT JOIN $customer_table as wc ON c.id = wc.cid" : '';
				$join_cf_query = '';
				if ( $should_send_cf || isset( $filters['cm'] ) ) {
					$contact_field_columns = self::_get_contact_fields_columns_sql();
					$wp_wc_columns         = $should_send_cf && ! empty( $contact_field_columns ) ? "$wp_wc_columns, " . $contact_field_columns : $wp_wc_columns;
					$join_cf_query         = "LEFT JOIN $contact_fields_table as `cm` ON c.id = cm.cid";
				}

				if ( isset( $filters['custom'] ) ) {
					$join_cf_query = apply_filters( 'bwfan_contact_sql_join_query', $join_cf_query, $filters['custom'] );
				}

			} else {
				$join_cf_query = '';
				if ( isset( $filters['custom'] ) ) {
					$join_cf_query = apply_filters( 'bwfan_contact_sql_join_query', $join_cf_query, $filters['custom'] );
				}
			}

			/** Implode Filters SQL */
			$filter_query = implode( $filter_match, $filter_query );
			$filter_query = ! empty( $filter_query ) ? "AND ( $filter_query ) " : '';

			/** Filter for final WHERE SQL */
			$filter_query = apply_filters( 'bwfan_contact_sql_final_where_query', $filter_query );

			$search_query = '';
			if ( ! empty( trim( $search ) ) ) {
				$search = trim( $search );
				/** Search Contact by f_name, l_name, contact_no (phone), email */
				$search_query = "AND ( c.email like '%" . esc_sql( $search ) . "%' OR c.f_name like '%" . esc_sql( $search ) . "%' OR c.l_name LIKE '%" . esc_sql( $search ) . "%' OR c.contact_no LIKE '%" . esc_sql( $search ) . "%' )";

				/** Get f_name and l_name from search string and append in query */
				if ( false !== strpos( $search, ' ' ) ) {
					$search_arr   = explode( ' ', $search );
					$first_name   = isset( $search_arr[0] ) ? $search_arr[0] : '';
					$last_name    = ! empty( end( $search_arr ) ) ? end( $search_arr ) : '';
					$search_query .= ! empty( $first_name ) && ! empty( $last_name ) ? " OR ( c.f_name like '%" . esc_sql( $first_name ) . "%' AND c.l_name like '%" . esc_sql( $last_name ) . "%' )" : '';
				}
			}

			/** Check if one of Email or Phone must not empty */
			$empty_email_check = ( 2 === $contact_mode ) ? "AND ( c.contact_no != '' AND c.contact_no IS NOT NULL )" : "AND ( c.email != '' AND c.email IS NOT NULL )";
			/** Order, Order By, Limit, Offset */
			$order_column_alias = in_array( $order_by, self::$wc_filters ) ? 'wc' : 'c';
			$order_by_query     = "ORDER BY {$order_column_alias}.{$order_by} {$order}";
			$pagination_query   = empty( $limit ) ? '' : "limit $offset, $limit";

			/** Exclude Contacts from this query */
			$exclude_ids_query = ! empty( $exclude_ids ) ? "AND c.id NOT IN ({$exclude_ids})" : '';

			/** Include Contacts into this query */
			$include_ids_query = ! empty( $include_ids ) ? "AND c.id IN ({$include_ids})" : '';

			/** Start ID and End ID queries */
			$start_id_query  = ! empty( $start_id ) ? "AND c.id > $start_id" : '';
			$end_id_operator = ( true === $exclude_end_id ) ? '<' : '<=';
			$end_id_query    = ! empty( $end_id ) ? "AND c.id $end_id_operator $end_id" : '';

			if ( true === $column_preference ) {
				$queries           = self::get_column_preferences_query( $join_cf_query, $join_wc_query, $status_filter, $filters, $additional_info );
				$base_query        = $queries['base_query'] . " $join_cf_query WHERE 1=1 $empty_email_check $exclude_ids_query $include_ids_query $start_id_query $end_id_query $filter_query $search_query $unsubs_query $group_by_query $order_by_query $pagination_query";
				$total_count_query = $queries['count_query'] . " $join_cf_query WHERE 1=1 $empty_email_check $exclude_ids_query $include_ids_query $start_id_query $end_id_query $filter_query $search_query $unsubs_query ";

				return array(
					'base'           => $base_query,
					'total'          => $total_count_query,
					'needs_to_added' => $queries['needs_to_added']
				);
			}
			$base_query = "SELECT $wp_wc_columns FROM $contact_table as c $join_wc_query $join_cf_query WHERE 1=1 $empty_email_check $exclude_ids_query $include_ids_query $start_id_query $end_id_query $filter_query $search_query $unsubs_query $group_by_query $order_by_query $pagination_query";

			$total_count_query = "SELECT COUNT(DISTINCT c.id) FROM $contact_table as c $join_wc_query $join_cf_query WHERE 1=1 $empty_email_check $exclude_ids_query $include_ids_query $start_id_query $end_id_query $filter_query $search_query $unsubs_query";

			return array(
				'base'  => $base_query,
				'total' => $total_count_query,
			);
		}

		public static function _get_unsubscribers_query( $filter, $exclude_unsubs = false, $additional_info = [] ) {
			$exclude_unsub_query = "  NOT EXISTS ";
			$include_unsub_query = " EXISTS ";
			global $wpdb;
			$email_query      = "(SELECT 1 FROM {$wpdb->prefix}bwfan_message_unsubscribe AS unsub WHERE c.email = unsub.recipient )";
			$contact_no_query = "(SELECT 1 FROM {$wpdb->prefix}bwfan_message_unsubscribe AS unsub1 WHERE c.contact_no = unsub1.recipient )";

			if ( true === $exclude_unsubs ) {
				return "AND " . "( $exclude_unsub_query  $email_query AND $exclude_unsub_query $contact_no_query )";
			}

			if ( ! empty( $additional_info['include_unsubscribe'] ) ) {
				return "OR " . "( $include_unsub_query  $email_query AND $exclude_unsub_query $contact_no_query )";
			}

			/** Has to be valid filter */
			if ( ! is_array( $filter ) || ! isset( $filter['rule'] ) ) {
				return '';
			}

			$filter_value = isset( $filter['value'] ) ? absint( $filter['value'] ) : '';
			/** when "status is not unsubscribed" */
			if ( 'is_not' === $filter['rule'] && 3 === $filter_value ) {
				return "AND " . "( $exclude_unsub_query  $email_query AND $exclude_unsub_query $contact_no_query )";
			}

			/** Include Un-subscribers when "status is unsubscribed" */
			if ( 'is' === $filter['rule'] && 3 === $filter_value ) {
				return "AND " . "( $include_unsub_query  $email_query OR $include_unsub_query $contact_no_query )";
			}

			/** Exclude Un-subscribers if any status other than 3 (unsubscribed) and rule = 'is' */
			if ( 'is' === $filter['rule'] && in_array( $filter_value, array( 0, 1, 2 ) ) ) {
				return "AND " . "( $exclude_unsub_query  $email_query AND $exclude_unsub_query $contact_no_query )";
			}

			return '';
		}

		public static function _get_filters_sql( $filters, $filter_match ) {
			$filter_query = array();
			foreach ( $filters as $filter_group_key => $filter_group ) {
				foreach ( $filter_group as $filter ) {
					/** Skip if filter doesn't have any or empty value */
					if ( ! isset( $filter['value'] ) ) {
						continue;
					}

					switch ( true ) {
						case $filter['type'] === BWFCRM_Filters::$TYPE_JSON_ARRAY:
							self::_set_json_array_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_STRING:
							self::_set_string_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_STRING_EXACT:
							self::_set_string_exact_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_DATE:
						case bwfan_is_autonami_pro_active() && version_compare( BWFAN_PRO_VERSION, '3.4.0', '>' ) && $filter['type'] === BWFCRM_Filters::$TYPE_TIME:
						case bwfan_is_autonami_pro_active() && version_compare( BWFAN_PRO_VERSION, '3.4.0', '>' ) && $filter['type'] === BWFCRM_Filters::$TYPE_DATETIME:
							self::_set_date_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_BOOL:
							self::_set_bool_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_NUMBER:
							self::_set_number_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_NUMBER_EXACT:
							self::_set_number_exact_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_CURRENCY:
							self::_set_currency_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_DATE_RELATIVE:
							self::_set_date_relative_filter_sql( $filter, $filter_group_key );
							break;
						case $filter['type'] === BWFCRM_Filters::$TYPE_CUSTOM_FILTER:
							self::_set_custom_filter_sql( $filter, $filter_group_key );
							break;
					}
				}

				if ( ! empty( self::$filter_queries ) ) {
					/** remove duplicate filter queries */
					self::$filter_queries = array_unique( self::$filter_queries );
					$filter_query[]       = implode( $filter_match, self::$filter_queries );
					self::$filter_queries = array();
				}
			}

			return $filter_query;
		}

		public static function _get_contact_fields_columns_sql() {
			$contact_fields = array_merge( array_keys( BWFCRM_Fields::$contact_fields ), array_keys( BWFCRM_Fields::$contact_address_fields ) );
			$db_fields      = BWFAN_Model_Fields::get_fields_by_multiple_slugs( $contact_fields );
			if ( empty( $db_fields ) ) {
				return '';
			}
			$columns = [];
			foreach ( $db_fields as $slug => $db_field ) {
				$columns[] = "cm.f{$db_field['ID']} AS '$slug'";
			}

			return implode( ', ', $columns );
		}

		public static function _set_json_array_filter_sql( $filter, $filter_group_key ) {
			$filter_value = $filter['value'];
			/** Checking if value is not array and group key is from custom field */
			if ( ! is_array( $filter_value ) || 'cm' === $filter_group_key ) {
				$filter_value = array( $filter_value );
			}

			foreach ( $filter_value as $val_item ) {
				$val_item = ! is_array( $val_item ) ? explode( ',', $val_item ) : $val_item;
				$val_item = array_map( 'trim', $val_item );
				$key      = $filter['key'];
				if ( 'is_blank' === $filter['rule'] ) {
					self::$filter_queries[] = "( {$filter_group_key}.{$key} IS NULL OR {$filter_group_key}.{$key} LIKE '[]' )";
					continue;
				}

				if ( 'is_not_blank' === $filter['rule'] ) {
					self::$filter_queries[] = "( {$filter_group_key}.{$key} IS NOT NULL AND {$filter_group_key}.{$key} NOT LIKE '[]' )";
					continue;
				}
				$rule     = in_array( $filter['rule'], array( 'contains', 'is', 'any', 'all' ) ) ? 'LIKE' : 'NOT LIKE';
				$sub_rule = ( 'LIKE' === $rule ) && ( 'all' !== $filter['rule'] ) ? 'OR' : 'AND';

				/** Filter hook to modify query */
				$modified = apply_filters( 'bwfan_modify_json_query', false, $val_item, $filter, $filter_group_key );

				if ( false === $modified ) {
					$val_item = array_map( function ( $val ) use ( $rule, $key, $sub_rule, $filter_group_key ) {
						return "$filter_group_key.$key $rule '%\"$val\"%'";
					}, $val_item );
				} else {
					/** If query is modified then passed modified query */
					$val_item = $modified;
				}

				$val_item = array_filter( $val_item );
				$val_item = implode( " {$sub_rule} ", $val_item );

				$if_column_exists = ( 'NOT LIKE' === $rule ) ? "{$filter_group_key}.{$key} IS NULL OR" : '';
				$val_item         = ! empty( $if_column_exists ) ? "($if_column_exists ($val_item))" : "($val_item)";

				/** Group query set, not need to go further, so continue */
				self::$filter_queries[] = $val_item;
			}
		}

		public static function _set_string_filter_sql( $filter, $filter_group_key ) {
			$filter_value = $filter['value'];
			$filter_rule  = $filter['rule'];
			$filter_key   = $filter['key'];

			if ( strpos( $filter_value, ',' ) ) {
				$filter_value = explode( ',', $filter_value );
			}

			switch ( $filter_rule ) {
				case 'is':
					$filter_rule = is_array( $filter_value ) ? 'IN' : '=';
					break;
				case 'is_not':
					$filter_rule = is_array( $filter_value ) ? 'NOT IN' : '!=';
					break;
				case 'contains':
					$filter_rule  = 'LIKE';
					$filter_value = is_array( $filter_value ) ? array_map( function ( $fil_val ) use ( $filter_group_key, $filter_key ) {
						$fil_val = trim( $fil_val );

						return "{$filter_group_key}.{$filter_key} LIKE '%$fil_val%'";
					}, $filter_value ) : "%{$filter_value}%";
					break;
				case 'not_contains':
					$filter_rule  = 'NOT LIKE';
					$filter_value = is_array( $filter_value ) ? array_map( function ( $fil_val ) use ( $filter_group_key, $filter_key ) {
						$fil_val = trim( $fil_val );

						return "{$filter_group_key}.{$filter_key} NOT LIKE '%$fil_val%'";
					}, $filter_value ) : "%{$filter_value}%";
					break;
				case 'starts_with':
					$filter_rule  = 'LIKE';
					$filter_value = is_array( $filter_value ) ? array_map( function ( $fil_val ) use ( $filter_group_key, $filter_key ) {
						$fil_val = trim( $fil_val );

						return "{$filter_group_key}.{$filter_key} LIKE '$fil_val%'";
					}, $filter_value ) : "{$filter_value}%";
					break;
				case 'ends_with':
					$filter_rule  = 'LIKE';
					$filter_value = is_array( $filter_value ) ? array_map( function ( $fil_val ) use ( $filter_group_key, $filter_key ) {
						$fil_val = trim( $fil_val );

						return "{$filter_group_key}.{$filter_key} LIKE '%$fil_val'";
					}, $filter_value ) : "%{$filter_value}";
					break;
				case 'is_blank':
					$filter_rule  = '=';
					$filter_value = '';
					break;
				case 'is_not_blank':
					$filter_rule  = '!=';
					$filter_value = '';
					break;
				default:
					return;
			}

			$if_column_exists = '';
			if ( in_array( $filter['rule'], array( 'not_contains', 'is_not', 'is_blank' ) ) ) {
				$if_column_exists = "{$filter_group_key}.{$filter_key} IS NULL OR";
			}

			if ( 'is_not_blank' === $filter['rule'] ) {
				$if_column_exists = "{$filter_group_key}.{$filter_key} IS NOT NULL AND";
			}

			if ( is_array( $filter_value ) && in_array( $filter['rule'], array( 'contains', 'not_contains', 'starts_with', 'ends_with' ), true ) ) {
				$filter_value           = array_filter( $filter_value );
				$sub_rule               = ( 'LIKE' === $filter_rule ) ? 'OR' : 'AND';
				$filter_value           = implode( " {$sub_rule} ", $filter_value );
				self::$filter_queries[] = "($if_column_exists $filter_value)";

				return;
			}

			if ( is_array( $filter_value ) ) {
				$filter_value = "('" . implode( "','", array_map( 'trim', $filter_value ) ) . "')";
			} else {
				$filter_value = "'$filter_value'";
			}

			self::$filter_queries[] = "($if_column_exists $filter_group_key.$filter_key $filter_rule $filter_value)";
		}

		public static function _set_string_exact_filter_sql( $filter, $filter_group_key ) {
			self::_set_string_filter_sql( $filter, $filter_group_key );
		}

		public static function _set_number_exact_filter_sql( $filter, $filter_group_key ) {
			self::_set_number_filter_sql( $filter, $filter_group_key );
		}

		public static function _set_date_filter_sql( $filter, $filter_group_key, $null_checking = true ) {
			$filter_key = $filter['key'];
			if ( empty( $filter['value'] ) ) {
				return '';
			}

			if ( is_array( $filter['value'] ) && 1 === count( $filter['value'] ) ) {
				$filter['value'] = $filter['value'][0];
			}

			$format  = 'Y-m-d';
			$is_null = $filter_rule = '';
			if ( bwfan_is_autonami_pro_active() && version_compare( BWFAN_PRO_VERSION, '3.4.0', '>' ) && intval( $filter['type'] ) === BWFCRM_Filters::$TYPE_DATETIME ) {
				$format = 'Y-m-d H:i:s';
			}
			if ( bwfan_is_autonami_pro_active() && version_compare( BWFAN_PRO_VERSION, '3.4.0', '>' ) && intval( $filter['type'] ) == BWFCRM_Filters::$TYPE_TIME ) {
				$format = 'H:i';
			}

			if ( is_array( $filter['value'] ) ) {
				/** For 'between' rule */
				$filter_before_date = bwfcrm_maybe_get_datetime( $filter['value'][0], $format );
				$filter_after_date  = bwfcrm_maybe_get_datetime( $filter['value'][1], $format );

				$filter_before_date = date( $format, $filter_before_date->getTimestamp() );
				$filter_after_date  = date( $format, $filter_after_date->getTimestamp() );

				if ( 'Y-m-d' === $format ) {
					$filter_before_date = "$filter_before_date 00:00:00";
					$filter_after_date  = "$filter_after_date 23:59:59";
				}

				$filter_rule = '=';
			} else {
				$filter_value = bwfcrm_maybe_get_datetime( $filter['value'], $format );

				$filter_date = date( $format, $filter_value->getTimestamp() );

				if ( 'Y-m-d' === $format ) {
					$filter_before_date = "$filter_date 00:00:00";
					$filter_after_date  = "$filter_date 23:59:59";
				} else {
					$filter_after_date = $filter_before_date = $filter_date;
				}

				switch ( $filter['rule'] ) {
					case 'is':
						$filter_rule = '=';
						break;
					case 'is_not':
						$filter_rule = '!=';
						$is_null     = "$filter_group_key.$filter_key IS NULL OR";
						break;
					case 'before':
						$filter_rule = '<';
						break;
					case 'after':
						$filter_rule = '>';
						break;
					case 'on_after':
						$filter_rule = '>=';
						break;
					case 'on_before':
						$filter_rule = '<=';
						break;
				}
			}
			if ( ! empty( $filter_rule ) && ( '=' !== $filter_rule || 'Y-m-d' !== $format ) ) {
				/** For 'more_than' and 'less_than' rules */
				$date_value = ( ( '>' === $filter_rule || '>=' === $filter_rule ) ? $filter_after_date : $filter_before_date );
				$where      = "($is_null $filter_group_key.$filter_key $filter_rule '$date_value')";
			} else {
				/** For 'is' and 'between' rules */
				$where = "($filter_group_key.$filter_key >= '$filter_before_date' AND $filter_group_key.$filter_key <= '$filter_after_date')";
			}

			self::$filter_queries[] = $where;

			return $where;
		}

		public static function _set_bool_filter_sql( $filter, $filter_group_key ) {
			$filter_value = $filter['value'];
			$filter_key   = $filter['key'];
			switch ( $filter_key ) {
				case 'status':
					$filter_value           = ( ( 'yes' === $filter_value ) ? 1 : 0 );
					self::$filter_queries[] = "$filter_group_key.$filter_key = $filter_value";

					return;
				case 'has_purchased':
					if ( 'yes' === $filter_value ) {
						self::$filter_queries[] = 'wc.total_order_count > 0';
					} else {
						self::$filter_queries[] = '(wc.total_order_count = 0 OR wc.total_order_count IS NULL )';
					}

					return;
				case 'has_used_any_coupons':
					if ( 'yes' === $filter_value ) {
						self::$filter_queries[] = "(wc.used_coupons IS NOT NULL AND wc.used_coupons != '' AND wc.used_coupons != '[]')";
					} else {
						self::$filter_queries[] = "(wc.used_coupons IS NULL OR wc.used_coupons = '' OR wc.used_coupons = '[]')";
					}

					return;
			}
		}

		public static function _set_number_filter_sql( $filter, $filter_group_key ) {
			$filter_value = $filter['value'];
			$filter_rule  = $filter['rule'];
			$filter_key   = $filter['key'];

			/** Only on Status filter */
			/** Include Un-subscribers if any status other than 3 (unsubscribed) and rule = 'is_not' */
			$include_unsub_query_or = '';
			if ( 'status' === $filter_key && 'is_not' === $filter_rule && in_array( $filter_value, array( 0, 1, 2 ) ) ) {
				/** Using OR query because contact maybe not fall within status (0,1,2), but is present in unsubscribed table */ global $wpdb;
				$include_unsub_query_or = "OR EXISTS (SELECT 1 FROM {$wpdb->prefix}bwfan_message_unsubscribe AS unsub WHERE c.email = unsub.recipient )";
			}

			if ( 'between' === $filter_rule ) {
				if ( is_array( $filter_value ) && 2 === count( $filter_value ) ) {
					sort( $filter_value );
					self::$filter_queries[] = "($filter_group_key.$filter_key >= '$filter_value[0]' AND $filter_group_key.$filter_key <= '$filter_value[1]')";
				}

				return;
			}

			switch ( $filter_rule ) {
				case 'is':
					if ( ! is_array( $filter_value ) && strpos( $filter_value, ',' ) ) {
						$filter_value = explode( ',', $filter_value );
					}
					$filter_rule = is_array( $filter_value ) ? 'IN' : '=';
					break;
				case 'is_not':
					if ( ! is_array( $filter_value ) && strpos( $filter_value, ',' ) ) {
						$filter_value = explode( ',', $filter_value );
					}
					$filter_rule = is_array( $filter_value ) ? 'NOT IN' : '!=';
					break;
				case 'more_than':
					$filter_rule = '>';
					break;
				case 'more_than_equal':
					$filter_rule = '>=';
					break;
				case 'less_than':
					$filter_rule = '<';
					break;
				case 'less_then_equal':
					$filter_rule = '<=';
					break;
				case 'is_blank':
					$filter_rule  = '=';
					$filter_value = 0;
					break;
				case 'is_not_blank':
					$filter_rule  = '!=';
					$filter_value = 0;
					break;
			}

			$if_column_exists = '';
			if ( in_array( $filter['rule'], array( 'less_than', 'is_not', 'is_blank', 'less_than_equal' ) ) ) {
				$if_column_exists = "{$filter_group_key}.{$filter_key} IS NULL OR";
			}

			if ( 'is_not_blank' === $filter['rule'] ) {
				$if_column_exists = "{$filter_group_key}.{$filter_key} IS NOT NULL AND";
			}

			/** check for property in case value 0 and rule is equal */
			if ( in_array( $filter['rule'], array( 'less_than', 'is' ) ) && 0 === absint( $filter_value ) ) {
				$if_column_exists = "{$filter_group_key}.{$filter_key} IS NULL OR";
			}

			/** check for property in case value 0 and rule is not equal */
			if ( 'is_not' === $filter['rule'] && 0 === absint( $filter_value ) ) {
				$if_column_exists = '';
			}

			if ( is_array( $filter_value ) ) {
				$filter_value = "('" . implode( "','", array_map( 'trim', $filter_value ) ) . "')";
			}

			if ( ! is_array( $filter_value ) ) {
				self::$filter_queries[] = "($if_column_exists $filter_group_key.$filter_key $filter_rule $filter_value $include_unsub_query_or)";

				return;
			}

			foreach ( $filter_value as $values ) {
				$values                 = trim( $values );
				self::$filter_queries[] = "($if_column_exists $filter_group_key.$filter_key $filter_rule $values $include_unsub_query_or)";
			}
		}

		public static function _set_currency_filter_sql( $filter, $filter_group_key ) {
			$filter_value = $filter['value'];
			$filter_rule  = $filter['rule'];
			$filter_key   = $filter['key'];

			switch ( $filter_rule ) {
				case 'more_than':
					$filter_rule = '>';
					break;
				case 'more_than_equal':
					$filter_rule = '>=';
					break;
				case 'less_than':
					$filter_rule = '<';
					break;
				case 'less_then_equal':
					$filter_rule = '<=';
					break;
				default:
					return;
			}

			$if_column_exists = '';
			if ( in_array( $filter['rule'], array( 'less_than', 'less_than_equal' ) ) ) {
				$if_column_exists = "{$filter_group_key}.{$filter_key} IS NULL OR";
			}

			if ( ! is_array( $filter_value ) ) {
				self::$filter_queries[] = "($if_column_exists $filter_group_key.$filter_key $filter_rule $filter_value)";

				return;
			}
			foreach ( $filter_value as $values ) {
				$values                 = trim( $values );
				self::$filter_queries[] = "($if_column_exists $filter_group_key.$filter_key $filter_rule $values)";
			}
		}

		public static function _set_date_relative_filter_sql( $filter, $filter_group_key ) {
			$filter_key = $filter['key'];
			if ( empty( $filter['value'] ) ) {
				return;
			}

			$filter_value = '';
			if ( ! is_array( $filter['value'] ) ) {
				$filter_value = absint( $filter['value'] );
				$date         = $datetime = new DateTime( 'now', wp_timezone() );
				$date->modify( "-$filter_value days" );
				$filter_value = $date->format( 'Y-m-d' );
			} else {
				$filter_value = array();

				$from = absint( $filter['value'][0] );
				$date = $datetime = new DateTime( 'now', wp_timezone() );
				$date->modify( "-$from days" );
				$filter_value[] = $date->format( 'Y-m-d' );

				$to   = absint( $filter['value'][1] );
				$date = $datetime = new DateTime( 'now', wp_timezone() );
				$date->modify( "-$to days" );
				$filter_value[] = $date->format( 'Y-m-d' );
			}

			switch ( $filter['rule'] ) {
				case 'over':
					$filter_rule = '<';
					break;
				case 'past':
					$filter_rule = '>=';
					break;
				case 'between':
					$filter_rule = 'between';
					break;
				default:
					return;
			}

			if ( ! is_array( $filter_value ) ) {
				self::$filter_queries[] = "($filter_group_key.$filter_key $filter_rule '$filter_value')";
			} else {
				self::$filter_queries[] = "($filter_group_key.$filter_key >= '" . $filter_value[1] . "' AND $filter_group_key.$filter_key <= '" . $filter_value[0] . "')";
			}
		}

		public static function _set_custom_filter_sql( $filter, $filter_group_key ) {
			$filter_value = $filter['value'];
			$filter_rule  = $filter['rule'];
			$filter_key   = $filter['key'];

			$filter_query = apply_filters( 'bwfan_contact_sql_where_query', '', $filter_key, $filter_rule, $filter_value );
			if ( empty( $filter_query ) ) {
				return;
			}

			self::$filter_queries[] = $filter_query;
		}

		public static function get_automations( $contact_id ) {
			if ( 0 === $contact_id ) {
				return array();
			}

			global $wpdb;
			$query               = "Select automation_id, time from {$wpdb->prefix}bwfan_contact_automations where contact_id ='" . $contact_id . "' ORDER BY time DESC";
			$contact_automations = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $contact_automations ) ) {
				return array();
			}

			return $contact_automations;
		}

		/**
		 * @param $contact_id
		 *
		 * @return bool
		 */
		public static function delete_contact( $contact_id ) {
			$contact_id = absint( $contact_id );
			if ( ! $contact_id ) {
				return false;
			}

			global $wpdb;

			$contact_table                 = $wpdb->prefix . 'bwf_contact';
			$contact_meta_table            = $wpdb->prefix . 'bwf_contact_meta';
			$contact_customer_table        = $wpdb->prefix . 'bwf_wc_customers';
			$contact_fields_table          = $wpdb->prefix . 'bwf_contact_fields';
			$contact_note_table            = $wpdb->prefix . 'bwfan_contact_note';
			$contact_automations_table     = $wpdb->prefix . 'bwfan_contact_automations';
			$contact_conversions_table     = $wpdb->prefix . 'bwfan_conversions';
			$contact_engagement_table      = $wpdb->prefix . 'bwfan_engagement_tracking';
			$contact_engagement_meta_table = $wpdb->prefix . 'bwfan_engagement_trackingmeta';
			$contact_unsubscribe_table     = $wpdb->prefix . 'bwfan_message_unsubscribe';
			$automation_contact            = $wpdb->prefix . 'bwfan_automation_contact';
			$automation_complete_contact   = $wpdb->prefix . 'bwfan_automation_complete_contact';
			$automation_contact_trail      = $wpdb->prefix . 'bwfan_automation_contact_trail';

			$contact_email = $wpdb->get_col( $wpdb->prepare( "select email from {$contact_table} where id=%d", $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$contact_phone = $wpdb->get_col( $wpdb->prepare( "select contact_no from {$contact_table} where id=%d", $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$delete_contact = $wpdb->delete( $contact_table, array( 'id' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $delete_contact ) {
				/** If no contact then return false */
				return false;
			}

			$wpdb->delete( $contact_meta_table, array( 'contact_id' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $contact_customer_table, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $contact_fields_table, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $contact_note_table, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			/** check if the automation run for v1 */
			if ( BWFAN_Common::is_automation_v1_active() ) {
				$wpdb->delete( $contact_automations_table, array( 'contact_id' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			$wpdb->delete( $contact_conversions_table, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			/** Delete complete and active Automation by contact id */
			$wpdb->delete( $automation_contact, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $automation_complete_contact, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $automation_contact_trail, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_woofunnel_cid' AND meta_value = %d", $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_woofunnel_custid' AND meta_value = %d", $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$engagement_meta_query = $wpdb->prepare( "DELETE $contact_engagement_meta_table
				FROM $contact_engagement_meta_table
				INNER JOIN $contact_engagement_table ON $contact_engagement_table.id = $contact_engagement_meta_table.eid
				WHERE $contact_engagement_table.cid = %d", $contact_id );

			/** delete from engagement meta and engagement table */
			$wpdb->query( $engagement_meta_query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $contact_engagement_table, array( 'cid' => $contact_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			/** delete from the unsubscribe table */

			if ( is_array( $contact_email ) && ! empty( $contact_email[0] ) ) {
				$wpdb->delete( $contact_unsubscribe_table, array( 'recipient' => $contact_email[0] ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			if ( is_array( $contact_phone ) && ! empty( $contact_phone[0] ) ) {
				$wpdb->delete( $contact_unsubscribe_table, array( 'recipient' => $contact_phone[0] ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			return true;
		}

		/**
		 * @param $contact_ids
		 *
		 * @return bool
		 */
		public static function delete_multiple_contacts( $contact_ids ) {
			if ( empty( $contact_ids ) ) {
				return false;
			}
			$contact_ids = array_map( 'absint', $contact_ids );
			$status      = true;
			foreach ( $contact_ids as $contact_id ) {
				if ( intval( $contact_id ) > 0 ) {
					$status = self::delete_contact( $contact_id );
				}
			}

			return $status;
		}

		public static function get_contact_aov( $contact_id ) {
			global $wpdb;
			if ( empty( $contact_id ) ) {
				return 0;
			}

			$contact_id = intval( $contact_id );

			$contact_orders_total_results = [];
			if ( method_exists( 'BWF_WC_Compatibility', 'is_hpos_enabled' ) && BWF_WC_Compatibility::is_hpos_enabled() ) {
				$contact_orders_total_query   = $wpdb->prepare( "SELECT count(wc_stats.order_id) as orders, SUM(wc_stats.total_sales) as total 
									FROM {$wpdb->prefix}wc_order_stats as wc_stats 
									LEFT JOIN {$wpdb->prefix}wc_orders_meta pm 
									ON wc_stats.order_id =pm.order_id 
									WHERE wc_stats.status = 'wc-completed' 
									and pm.meta_key='_woofunnel_cid'
									and pm.meta_value=%d", $contact_id );
				$contact_orders_total_results = $wpdb->get_results( $contact_orders_total_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			if ( empty( $contact_orders_total_results ) ) {
				$contact_orders_total_query   = $wpdb->prepare( "SELECT count(wc_stats.order_id) as orders, SUM(wc_stats.total_sales) as total 
									FROM {$wpdb->prefix}wc_order_stats as wc_stats 
									LEFT JOIN {$wpdb->prefix}postmeta pm 
									ON wc_stats.order_id =pm.post_id 
									WHERE wc_stats.status = 'wc-completed' 
									and pm.meta_key='_woofunnel_cid'
									and pm.meta_value=%d", $contact_id );
				$contact_orders_total_results = $wpdb->get_results( $contact_orders_total_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			$aov_contact_order_total = 0;

			if ( isset( $contact_orders_total_results[0]['orders'] ) && ! empty( $contact_orders_total_results[0]['orders'] ) && isset( $contact_orders_total_results[0]['total'] ) && ! empty( $contact_orders_total_results[0]['total'] ) ) {
				$aov_contact_order_total = ( $contact_orders_total_results[0]['total'] / $contact_orders_total_results[0]['orders'] );
			}

			return $aov_contact_order_total;
		}

		/**
		 * @param $contact_id
		 *
		 * @return array|object
		 */
		public static function get_bump_details( $contact_id ) {
			if ( empty( $contact_id ) ) {
				return array();
			}

			global $wpdb;
			$bump_data['accepted'] = 0;
			$bump_data['revenue']  = 0;

			$bump_query   = $wpdb->prepare( "SELECT bump.bid as 'object_id',
						bump.total as 'total_revenue', 
						'bump' as 'type' FROM {$wpdb->prefix}wfob_stats AS bump WHERE bump.cid='%d' AND bump.converted=1", $contact_id );
			$bump_results = $wpdb->get_results( $bump_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $bump_results ) ) {
				return $bump_data;
			}

			$bump_data['accepted'] = count( $bump_results );
			$total_revenue_array   = array_column( $bump_results, 'total_revenue' );
			$bump_data['revenue']  = number_format( array_sum( $total_revenue_array ), 2 );

			return $bump_data;
		}

		/**
		 * @param $contact_id
		 *
		 * @return array|object
		 */
		public static function get_upstroke_details( $contact_id ) {
			if ( empty( $contact_id ) ) {
				return array();
			}
			global $wpdb;
			$upstroke_data['accepted'] = 0;

			$upstroke_query  = $wpdb->prepare( "SELECT event.action_type_id,event.value 
						   FROM {$wpdb->prefix}wfocu_event as event LEFT JOIN {$wpdb->prefix}wfocu_session as session 
						   ON event.sess_id = session.id 
						   WHERE (event.action_type_id = 4) AND session.cid='%d'", $contact_id );
			$upstroke_result = $wpdb->get_results( $upstroke_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $upstroke_result ) ) {
				return $upstroke_data;
			}

			$upstroke_data['accepted'] = count( $upstroke_result );
			$total_revenue_array       = array_column( $upstroke_result, 'value' );
			$upstroke_data['revenue']  = array_sum( $total_revenue_array );

			return $upstroke_data;
		}

		/**
		 * @param $contact_id
		 * @param $note_id
		 *
		 * @return bool
		 */
		public static function delete_notes( $contact_id, $note_id ) {
			if ( empty( $contact_id ) ) {
				return false;
			}

			global $wpdb;
			$contact_note_table = $wpdb->prefix . 'bwfan_contact_note';
			$where              = array();

			if ( empty( $note_id ) ) {
				$where['cid'] = $contact_id;
			} else {
				$where['id']  = $note_id;
				$where['cid'] = $contact_id;
			}

			$delete_contact_notes = $wpdb->delete( $contact_note_table, $where ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $delete_contact_notes ) {
				return false;
			}

			return true;
		}

		/**
		 * @param $contact_id
		 *
		 * @return mixed
		 */
		public static function get_checkout_details( $contact_id ) {
			global $wpdb;

			$query         = $wpdb->prepare( "SELECT  count(aero.wfacp_id) as total_orders, sum(aero.total_revenue) as 'total_revenue' FROM {$wpdb->prefix}bwf_contact AS contact JOIN {$wpdb->prefix}wfacp_stats AS aero ON contact.id=aero.cid WHERE aero.cid=%d ORDER BY aero.date DESC", $contact_id );
			$checkout_data = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$return = array();

			$return['orders']  = ( isset( $checkout_data[0] ) && isset( $checkout_data[0]['total_orders'] ) ) ? absint( $checkout_data[0]['total_orders'] ) : 0;
			$return['revenue'] = ( isset( $checkout_data[0] ) && isset( $checkout_data[0]['total_revenue'] ) ) ? $checkout_data[0]['total_revenue'] : 0;

			return $return;
		}

		/**
		 * @param $contact_id
		 *
		 * @return int|mixed
		 */
		public static function get_optin_details( $contact_id ) {
			global $wpdb;
			$optin_submission = 0;

			$query      = $wpdb->prepare( "SELECT count(optin.id) as total_submission FROM {$wpdb->prefix}bwf_contact AS contact JOIN {$wpdb->prefix}bwf_optin_entries AS optin ON contact.id=optin.cid WHERE optin.cid=%d ORDER BY optin.date DESC", $contact_id );
			$optin_data = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! isset( $optin_data[0]['total_submission'] ) || empty( $optin_data[0]['total_submission'] ) ) {
				return $optin_submission;
			}

			return $optin_data[0]['total_submission'];
		}

		/**
		 * @param int $limit
		 * @param int $offset
		 * @param false $wc_data_send
		 *
		 * @return array
		 */
		public static function get_recent_contacts( $limit = 10, $offset = 0, $wc_data_send = false ) {
			$additional_info = array(
				'order_by'       => 'creation_date',
				'order'          => 'DESC',
				'exclude_unsubs' => true,
				'customer_data'  => $wc_data_send,
			);
			// $saved_last_id   = get_option( 'bwfan_show_contacts_from' );
			// if ( ! empty( $saved_last_id ) ) {
			// $additional_info['start_id'] = $saved_last_id;
			// }

			return self::get_contacts( '', $limit, $offset, array(), $additional_info );
		}

		/**
		 * @param int $limit
		 * @param int $offset
		 * @param false $wc_data_send
		 *
		 * @return array
		 */
		public static function get_recent_unsubscribers( $limit = 10, $offset = 0, $wc_data_send = false ) {
			return self::get_contacts( '', $limit, $offset, array(), array(
				'order_by'       => 'last_modified',
				'order'          => 'DESC',
				'exclude_unsubs' => false,
				'customer_data'  => $wc_data_send,
			) );
		}

		/**
		 * @param int $limit
		 * @param int $offset
		 * @param false $wc_get_data
		 *
		 * @return array
		 */
		public static function get_recent_abandoned( $limit = 10, $offset = 0, $wc_get_data = false ) {
			global $wpdb;

			$contact_table  = $wpdb->prefix . 'bwf_contact';
			$cart_table     = $wpdb->prefix . 'bwfan_abandonedcarts';
			$customer_table = $wpdb->prefix . 'bwf_wc_customers';
			$wp_wc_columns  = $wc_get_data ? 'c.*, wc.id as customer_id, wc.l_order_date, wc.f_order_date, wc.total_order_count, wc.total_order_value, wc.purchased_products, wc.purchased_products_cats, wc.purchased_products_tags, wc.used_coupons' : 'c.*';
			$join_wc_query  = $wc_get_data ? "LEFT JOIN $customer_table as wc ON c.id = wc.cid" : '';
			$cart_join      = "LEFT JOIN $contact_table as c ON ab.email = c.email";
			$base_query     = $wpdb->prepare( "SELECT $wp_wc_columns FROM $cart_table as ab $cart_join $join_wc_query WHERE 1=1 and ab.status IN (0,1,3,4) and c.id IS NOT NULL ORDER BY ab.last_modified DESC LIMIT %d,%d", $offset, $limit );
			$contacts       = $wpdb->get_results( $base_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! is_array( $contacts ) || 0 === count( $contacts ) ) {
				return array(
					'contacts' => array(),
					'total'    => 0,
				);
			}

			$total_count = count( $contacts );

			return array(
				'contacts' => $contacts,
				'total'    => $total_count,
			);
		}

		public static function get_last_contact_id() {
			global $wpdb;
			$table_name = self::_table();
			$query      = "SELECT max(id) as last_id FROM $table_name";

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function set_log( $log ) {
			if ( empty( $log ) ) {
				return;
			}
			self::$logs[] = array(
				't' => microtime( true ),
				'd' => $log,
			);
		}

		public static function log() {
			if ( ! is_array( self::$logs ) || 0 === count( self::$logs ) ) {
				return;
			}

			if ( false === apply_filters( 'bwfan_allow_contact_query_logging', BWFAN_Common::is_log_enabled( 'bwfan_contact_query_logging' ) ) ) {
				return;
			}
			add_filter( 'bwfan_before_making_logs', '__return_true' );
			BWFAN_Core()->logger->log( print_r( self::$logs, true ), 'fka-contacts-query' );

			self::$logs = [];
		}

		/**
		 * Get contacts with column preferences
		 *
		 */
		public static function get_contact_listing( $search = '', $limit = 25, $offset = 0, $normalized_filters = array(), $additional_info = array(), $filter_match = ' AND ' ) {
			global $wpdb;
			/** Get Contacts */
			$sql_queries = self::_get_contacts_sql( $search, $limit, $offset, $normalized_filters, $additional_info, $filter_match, true );
			self::set_log( $sql_queries['base'] );

			$time     = microtime( true );
			$contacts = $wpdb->get_results( $sql_queries['base'], ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$time     = microtime( true ) - $time;
			self::set_log( 'Time to execute the query: ' . $time . ' secs' );
			self::log();

			/** In case there is DB error and no contacts */
			if ( empty( $contacts ) && ! empty( $wpdb->last_error ) ) {
				BWFAN_Common::log_test_data( $wpdb->last_error, 'collation-issue', true );
				BWFAN_Fix_Collation::maybe_fix_collation_issue();
				$contacts = $wpdb->get_results( $sql_queries['base'], ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			$contacts = self::prepared_data( $contacts, $sql_queries['needs_to_added'] );

			return array(
				'contacts' => $contacts,
				'total'    => $wpdb->get_var( $sql_queries['total'] ) //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			);
		}

		/**
		 * Preparing the data for get contacts with column preference
		 *
		 * @param $contacts
		 * @param $columns_needs_to_add
		 *
		 * @return array|void[]
		 */
		public static function prepared_data( $contacts, $columns_needs_to_add ) {
			if ( empty( $contacts ) ) {
				return [];
			}

			/** Set woocommerce currency */
			if ( method_exists( 'BWF_Plugin_Compatibilities', 'get_currency_symbol' ) ) {
				BWF_Plugin_Compatibilities::get_currency_symbol( get_option( 'woocommerce_currency' ) );
			}

			return array_map( function ( $contact ) use ( $columns_needs_to_add ) {
				foreach ( $contact as $slug => $value ) {
					$date_columns = [ 'dob', 'last-login', 'last-open', 'last-click', 'last-sent' ];
					if ( false !== strpos( $slug, 'date' ) || in_array( $slug, $date_columns, true ) ) {
						$contact[ $slug ] = self::get_formatted_date( $value );
						continue;
					}

					if ( false !== strpos( $slug, 'bwf_cf' ) ) {
						$field_id   = str_replace( 'bwf_cf', '', $slug );
						$field_type = BWFAN_Model_Fields::get_field_type( $field_id );

						switch ( absint( $field_type ) ) {
							case BWFCRM_Fields::$TYPE_DATE :
								$value = self::get_formatted_date( $value );
								break;
							case BWFCRM_Fields::$TYPE_DATETIME :
								$value = self::get_formatted_date( $value, true );
								break;
							case BWFCRM_Fields::$TYPE_TIME :
								$value = self::get_formatted_time( $value );
								break;
							case BWFCRM_Fields::$TYPE_CHECKBOX :
								$options = ! empty( $value ) ? json_decode( $value, true ) : [];
								$value   = [];
								if ( ! empty( $options ) ) {
									foreach ( $options as $option ) {
										$value[] = [
											'id'   => $option,
											'name' => $option,
										];
									}
								}
								break;
						}

						$contact[ $slug ] = $value;
					}
				}

				/** if contact is un-subscribed then status set as 3 */
				if ( isset( $contact['status'] ) ) {
					$email             = isset( $contact['email'] ) ? $contact['email'] : '';
					$contact_no        = isset( $contact['contact_no'] ) ? $contact['contact_no'] : '';
					$is_unsubscribed   = self::is_contact_unsubscribed( $email, $contact_no );
					$contact['status'] = ! empty( $is_unsubscribed ) ? 3 : $contact['status'];
				}

				/** Get Purchased Products titles */
				if ( isset( $contact['purchased_products'] ) ) {
					$purchased_products            = ! empty( $contact['purchased_products'] ) ? json_decode( $contact['purchased_products'], true ) : [];
					$contact['purchased_products'] = bwfan_is_woocommerce_active() ? array_filter( array_map( function ( $product_id ) {
						$product = wc_get_product( $product_id );
						if ( ! $product instanceof WC_Product ) {
							return false;
						}
						$pid = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

						return ! $product->is_type( 'variable' ) ? array(
							'id'   => $pid,
							'name' => wp_strip_all_tags( $product->get_name( 'edit' ) ),
							'link' => get_edit_post_link( $pid ),
						) : false;
					}, array_unique( $purchased_products ) ) ) : [];
					sort( $contact['purchased_products'] );
				}

				/** Get Purchased Products Cats */
				if ( isset( $contact['purchased_products_cats'] ) ) {
					$purchased_products_cats            = ! empty( $contact['purchased_products_cats'] ) ? json_decode( $contact['purchased_products_cats'], true ) : [];
					$purchased_products_cats            = array_map( function ( $term_id ) {
						$cat = get_term( absint( $term_id ) );

						return $cat instanceof WP_Term ? array(
							'id'   => $cat->term_id,
							'name' => wp_strip_all_tags( $cat->name ),
							'link' => get_edit_term_link( $cat ),
						) : false;
					}, array_unique( $purchased_products_cats ) );
					$contact['purchased_products_cats'] = array_values( array_filter( $purchased_products_cats ) );
				}

				/** Get Purchased Products Tags */
				if ( isset( $contact['purchased_products_tags'] ) ) {
					$purchased_products_tags            = ! empty( $contact['purchased_products_tags'] ) ? json_decode( $contact['purchased_products_tags'], true ) : [];
					$purchased_products_tags            = array_map( function ( $term_id ) {
						$tag = get_term( absint( $term_id ) );

						return $tag instanceof WP_Term ? array(
							'id'   => $tag->term_id,
							'name' => wp_strip_all_tags( $tag->name ),
							'link' => get_edit_term_link( $tag ),
						) : false;
					}, array_unique( $purchased_products_tags ) );
					$contact['purchased_products_tags'] = array_values( array_filter( $purchased_products_tags ) );
				}

				/** Get Used coupons */
				if ( isset( $contact['used_coupons'] ) ) {
					$contact['used_coupons'] = isset( $contact['used_coupons'] ) ? json_decode( $contact['used_coupons'], true ) : [];
					$contact['used_coupons'] = array_map( function ( $coupon ) {

						return [
							'id'   => $coupon,
							'name' => $coupon,
						];
					}, $contact['used_coupons'] );
				}
				/** Get list and tags */
				if ( isset( $contact['tags'] ) ) {
					$tags            = json_decode( $contact['tags'], true );
					$contact['tags'] = ! empty( $tags ) ? BWFCRM_Tag::get_tags( $tags ) : [];
				}
				if ( isset( $contact['lists'] ) ) {
					$lists            = json_decode( $contact['lists'], true );
					$contact['lists'] = ! empty( $lists ) ? BWFCRM_Lists::get_lists( $lists ) : [];
				}

				if ( bwfan_is_woocommerce_active() && isset( $contact['total_order_value'] ) ) {
					$contact['total_order_value'] = ! empty( $contact['total_order_value'] ) ? wc_price( $contact['total_order_value'] ) : '';
				}

				if ( bwfan_is_woocommerce_active() && isset( $contact['aov'] ) ) {
					$contact['aov'] = ! empty( $contact['aov'] ) ? wc_price( $contact['aov'] ) : '';
				}

				/** Link trigger */
				if ( class_exists( 'BWFAN_Model_Link_Triggers' ) && isset( $contact['link-trigger-click'] ) && ! empty( $contact['link-trigger-click'] ) ) {
					$contact['link-trigger-click'] = json_decode( $contact['link-trigger-click'] );
					$data                          = BWFAN_Model_Link_Triggers::get_link_triggers( '', '', 0, 0, false, $contact['link-trigger-click'] );
					if ( isset( $data['links'] ) ) {
						$contact['links'] = array_map( function ( $link ) {
							return array(
								'id'   => $link['ID'],
								'name' => $link['title'],
								'link' => admin_url( 'admin.php?page=autonami&path=/link-trigger/' . $link['ID'] )
							);
						}, $data['links'] );
						unset( $contact['link-trigger-click'] );
					}
				}

				if ( ! empty( $columns_needs_to_add ) && is_array( $columns_needs_to_add ) ) {
					foreach ( $columns_needs_to_add as $column ) {
						$value = '';
						switch ( true ) {
							case 'has_purchased' === $column :
								$value = ! empty( $contact['purchased_products'] );
								break;
							case 'has_used_any_coupons' === $column :
								$value = ! empty( $contact['used_coupons'] );
								break;
						}

						$contact[ $column ] = $value;
					}
				}

				return apply_filters( 'bwfcrm_modify_contact_columns_preferences_data', $contact );
			}, $contacts );
		}

		public static function get_formatted_date( $date, $time = false ) {
			if ( empty( $date ) || ! strtotime( $date ) ) {
				return '';
			}

			if ( true === $time ) {
				$format = BWFAN_Common::bwfan_get_date_format() . ' ' . BWFAN_Common::bwfan_get_time_format();
			} else {
				$format = BWFAN_Common::bwfan_get_date_format();
			}

			return wp_date( $format, strtotime( $date ), new DateTimeZone( 'UTC' ) );
		}

		/**
		 * Get formated time
		 *
		 * @param $time
		 *
		 * @return false|string
		 */
		public static function get_formatted_time( $time ) {
			if ( empty( $time ) || ! strtotime( $time ) ) {
				return '';
			}

			return wp_date( BWFAN_Common::bwfan_get_time_format(), strtotime( $time ), new DateTimeZone( 'UTC' ) );
		}

		/**
		 * Get query for get contacts with columns preference
		 */
		public static function get_column_preferences_query( $join_cf_query, $join_wc_query, $status_filter, $filters, $additional_info ) {
			global $wpdb;
			$contact_table        = $wpdb->prefix . 'bwf_contact';
			$customer_table       = $wpdb->prefix . 'bwf_wc_customers';
			$contact_fields_table = $wpdb->prefix . 'bwf_contact_fields';
			$unsubscribe_table    = $wpdb->prefix . 'bwfan_message_unsubscribe';

			$columns = self::get_columns_for_query();

			$contact_details = isset( $columns['contact_details'] ) && is_array( $columns['contact_details'] ) ? $columns['contact_details'] : [];
			$segments        = isset( $columns['segments'] ) && is_array( $columns['segments'] ) ? $columns['segments'] : [];
			$custom_fields   = isset( $columns['contact_custom_fields'] ) && is_array( $columns['contact_custom_fields'] ) ? $columns['contact_custom_fields'] : [];
			$custom_fields   = apply_filters( 'bwfcrm_contact_custom_columns_query_update', $custom_fields );
			$wc_columns      = isset( $columns['woocommerce'] ) && is_array( $columns['woocommerce'] ) ? $columns['woocommerce'] : [];
			$geography       = isset( $columns['geography'] ) && is_array( $columns['geography'] ) ? $columns['geography'] : [];
			$engagement      = isset( $columns['engagement'] ) && is_array( $columns['engagement'] ) ? $columns['engagement'] : [];

			$total_columns = array_merge( $contact_details, $custom_fields );
			$total_columns = array_merge( $total_columns, $segments );
			$total_columns = array_merge( $total_columns, $wc_columns );
			$total_columns = array_merge( $total_columns, $geography );
			$total_columns = array_merge( $total_columns, $engagement );

			$default_columns = "c.id,c.f_name,c.l_name,c.email,c.contact_no,c.creation_date";
			$join_query      = '';
			/** If Join is already in custom filter query */
			if ( false === strpos( $join_cf_query, 'bwf_contact_fields' ) ) {
				$join_query = " LEFT JOIN $contact_fields_table as `cm` ON c.id = cm.cid ";
			}

			if ( false === strpos( $join_cf_query, 'bwf_wc_customers' ) ) {
				$join_query .= ( ! empty( $wc_columns ) || isset( $filters['wc'] ) ) || ( empty( $wc_columns ) && ! empty( $join_wc_query ) ) ? " LEFT JOIN $customer_table as wc ON c.id = wc.cid" : '';
			}

			$query_columns = apply_filters( 'bwfcrm_contact_columns_preferences', array( 'total_columns' => $total_columns, 'join_query' => $join_query ), $columns, $join_cf_query );
			$join_query    = isset( $query_columns['join_query'] ) ? $query_columns['join_query'] : $join_query;
			$total_columns = isset( $query_columns['total_columns'] ) ? $query_columns['total_columns'] : $total_columns;

			$total_columns = isset( $total_columns ) ? implode( ',', array_filter( $total_columns ) ) : '';
			$total_columns = ! empty( $total_columns ) ? $default_columns . ',' . $total_columns : $default_columns;

			return [
				'base_query'     => "SELECT $total_columns FROM $contact_table AS c $join_query ",
				'count_query'    => "SELECT COUNT(DISTINCT c.id) FROM $contact_table as c $join_query ",
				'needs_to_added' => $columns['need_to_add']
			];
		}

		/**
		 * Get preferences columns for query
		 *
		 */
		public static function get_columns_for_query() {
			$saved_format = BWFAN_Common::get_contact_columns();

			if ( empty( $saved_format ) ) {
				return [ 'need_to_add' => [] ];
			}

			$columns = [];

			$modified_columns    = [
				'has_purchased'        => 'purchased_products',
				'has_used_any_coupons' => 'used_coupons',
			];
			$system_fields       = [ 'address-1', 'address-2', 'city', 'postcode', 'company', 'gender', 'dob', 'link-trigger-click', 'last-login', 'last-open', 'last-click', 'last-sent' ];
			$need_to_add_columns = [];
			$added_columns       = [];

			foreach ( $saved_format as $fields ) {
				foreach ( $fields as $slug => $field ) {
					if ( 'label' === $slug || 'groupKey' === $slug || 'groupLabel' === $slug ) {
						continue;
					}

					/** If system and default field is not exists in DB */
					if ( in_array( $slug, $system_fields, true ) && empty( BWFCRM_Fields::get_field_id_by_slug( $slug ) ) ) {
						continue;
					}

					/** Get need to add and modify columns */
					if ( in_array( $slug, array_keys( $modified_columns ), true ) ) {
						$need_to_add_columns[] = $slug;
					}

					/** Default columns so no need to add */
					if ( in_array( $slug, [ 'f_name', 'l_name', 'email', 'creation_date', 'contact_no' ], true ) ) {
						continue;
					}

					switch ( true ) {
						case 'contact_details' === $fields['groupKey'] || 'segments' === $fields['groupKey']:
							if ( 'creation_days' === $slug ) {
								$slug = " DATEDIFF(now(), c.creation_date) as `creation_days` ";
								break;
							}
							if ( 'company' === $slug || 'dob' === $slug || 'gender' === $slug ) {
								$slug = "cm.f" . BWFCRM_Fields::get_field_id_by_slug( $slug ) . " AS `$slug`";
								break;
							}
							$slug = "c." . $slug;
							break;
						case 'geography' === $fields['groupKey'] :
							if ( 'state' !== $slug && 'country' !== $slug ) {
								$slug = "cm.f" . BWFCRM_Fields::get_field_id_by_slug( $slug ) . " AS `$slug`";
							} else {
								$slug = "c." . $slug;
							}

							break;
						case 'engagement' === $fields['groupKey'] :
							if ( 'links' === $slug ) {
								$slug = 'link-trigger-click';
							}
							$slug = "cm.f" . BWFCRM_Fields::get_field_id_by_slug( $slug ) . " AS `$slug`";
							break;
						case 'woocommerce' === $fields['groupKey'] :
							if ( 'l_order_days' === $slug ) {
								$slug = ! in_array( $slug, $added_columns ) ? " DATEDIFF(now(), wc.l_order_date) as `l_order_days` " : '';
								break;
							}
							if ( 'f_order_days' === $slug ) {
								$slug = ! in_array( $slug, $added_columns ) ? " DATEDIFF(now(), wc.f_order_date) as `f_order_days` " : '';
								break;
							}
							if ( isset( $modified_columns[ $slug ] ) ) {
								$slug = $modified_columns[ $slug ];
							}
							$slug = ! in_array( $slug, $added_columns ) ? "wc." . $slug : '';
							break;
						case 'contact_custom_fields' === $fields['groupKey'] :
							$custom_field_slug = str_replace( 'bwf_cf', '', $slug );
							/** if column is not exists in bwf_contact_fields table */
							if ( empty( BWF_Model_Contact_Fields::column_already_exists( $custom_field_slug ) ) ) {
								$slug = '';
								break;
							}

							$slug = "cm.f" . $custom_field_slug . " AS `$slug`";
							break;
					}

					if ( empty( $slug ) ) {
						continue;
					}
					$added_columns[]                  = $slug;
					$columns[ $fields['groupKey'] ][] = apply_filters( 'bwfcrm_contact_columns_group', $slug, $fields );
				}
			}

			$columns['need_to_add'] = $need_to_add_columns;

			return $columns;
		}

		/**
		 * Check contact email in unsubscribe table
		 *
		 * @param $email
		 * @param $contact_no
		 *
		 * @return array|false|object|string|null
		 */
		public static function is_contact_unsubscribed( $email, $contact_no ) {
			if ( empty( $email ) && empty( $contact_no ) ) {
				return false;
			}

			$data = [
				'recipient' => [ $email, $contact_no ],
			];

			return BWFAN_Model_Message_Unsubscribe::get_message_unsubscribe_row( $data );
		}

		/**
		 * Get modified status query
		 *
		 * @param $filter_query
		 * @param $additional_info
		 * @param $unsubs_query
		 *
		 * @return array|array[]|string[]|string[][]
		 */
		public static function get_modified_status_query( $filter_query, $additional_info, $unsubs_query ) {
			if ( empty( $filter_query ) ) {
				return [];
			}

			return array_map( function ( $filter ) use ( $additional_info, $unsubs_query ) {
				if ( false !== strpos( $filter, 'c.status = 1' ) ) {
					/** Include soft bounce status in query */
					$filter = ! empty( $additional_info['include_soft_bounce'] ) ? str_replace( [
						'c.status = 1 )',
						'c.status = 0 )'
					], [
						'c.status = 1 OR c.status = ' . BWFCRM_Contact::$STATUS_SOFT_BOUNCED . ' )',
						'c.status = 0 OR c.status = ' . BWFCRM_Contact::$STATUS_SOFT_BOUNCED . ' )'
					], $filter ) : $filter;

					/** Include unverified status in query */
					$filter = ! empty( $additional_info['include_unverified'] ) ? str_replace( [
						'c.status = 1 )',
						'c.status = 4 )'
					], [
						'c.status = 1 OR c.status = ' . BWFCRM_Contact::$STATUS_NOT_OPTED_IN . ' )',
						'c.status = 4 OR c.status = ' . BWFCRM_Contact::$STATUS_NOT_OPTED_IN . ' )'
					], $filter ) : $filter;

					/** Include unsubscribed status in query */
					$filter = ! empty( $additional_info['include_unsubscribe'] ) ? str_replace( [ 'c.status = 1 )', 'c.status = 4 )', 'c.status = 0 )' ], [
						'c.status = 1 ' . $unsubs_query . ' )',
						'c.status = 4 ' . $unsubs_query . ' )',
						'c.status = 0 ' . $unsubs_query . ' )'
					], $filter ) : $filter;
				}

				return $filter;
			}, $filter_query );
		}

		/**
		 * save last modified
		 *
		 * @param $cid
		 *
		 * @return bool|int|mysqli_result|null
		 */
		public static function save_last_modified( $cid ) {
			global $wpdb;

			return $wpdb->update( "{$wpdb->prefix}bwf_contact", [ 'last_modified' => current_time( 'mysql', 1 ) ], [ 'id' => $cid ] ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Get contact row by id
		 *
		 * @param $id
		 * @param $type
		 *
		 * @return array|object|stdClass|void|null
		 */
		public static function get_contact_id( $id, $type = 'object' ) {
			global $wpdb;
			$query = "SELECT * FROM {$wpdb->prefix}bwf_contact WHERE `id` = %d";

			$type = 'object' === $type ? OBJECT : ARRAY_A;

			return $wpdb->get_row( $wpdb->prepare( $query, $id ), $type ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Maybe set contact object as cache so that when WooFunnels_Contact object is created it can pick the value from cache.
		 *
		 * @param $contact_id
		 * @param $contact_row
		 *
		 * @return void
		 */
		public static function maybe_set_cache_contact_row( $contact_id, $contact_row ) {
			if ( empty( $contact_id ) || empty( $contact_row ) || ! is_object( $contact_row ) || ! isset( $contact_row->status ) ) {
				return;
			}

			$obj = BWF_Contacts::get_instance();

			if ( ! isset( $obj->cached_contact_obj['cid'] ) ) {
				$obj->cached_contact_obj['cid'] = [];
			}

			$obj->cached_contact_obj['cid'][ $contact_id ] = $contact_row;
		}
	}
}