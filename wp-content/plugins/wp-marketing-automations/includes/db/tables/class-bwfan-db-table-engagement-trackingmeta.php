<?php
/**
 * bwfan_engagement_trackingmeta table class
 *
 */

if ( ! class_exists( 'BWFAN_DB_Table_Engagement_Trackingmeta' ) && BWFAN_Common::is_pro_3_0() ) {

	class BWFAN_DB_Table_Engagement_Trackingmeta extends BWFAN_DB_Tables_Base {
		public $table_name = 'bwfan_engagement_trackingmeta';

		/**
		 * Get table's columns
		 *
		 * @return string[]
		 */
		public function get_columns() {
			return [
				"ID",
				"eid",
				"meta_key",
				"meta_value",
			];
		}

		/**
		 * Get query for create table
		 *
		 * @return string
		 */
		public function get_create_table_query() {
			global $wpdb;
			$collate = $this->get_collation();

			return "CREATE TABLE {$wpdb->prefix}$this->table_name (
 		    `ID` bigint(20) unsigned NOT NULL auto_increment,
			`eid` bigint(20) unsigned NOT NULL,
			`meta_key` varchar(255) default NULL,
		  	`meta_value` longtext,
			PRIMARY KEY (`ID`),		
			KEY `eid` (`eid`)
		) $collate;";
		}
	}
}