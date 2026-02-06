<?php
/**
 * bwfan_templates table class
 *
 */

if ( ! class_exists( 'BWFAN_DB_Table_Templates' ) && BWFAN_Common::is_pro_3_0() ) {
	class BWFAN_DB_Table_Templates extends BWFAN_DB_Tables_Base {
		public $table_name = 'bwfan_templates';

		/**
		 * Get table's columns
		 *
		 * @return string[]
		 */
		public function get_columns() {
			return [
				"ID",
				"subject",
				"template",
				"type",
				"title",
				"mode",
				"data",
				"canned",
				"created_at",
				"updated_at",
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
			`subject` varchar(255) default NULL,
			`template` longtext,
			`type` tinyint(1) unsigned not null default 1 COMMENT '1 - Email 2 - SMS',
			`title` varchar(255) default NULL,
			`mode` tinyint(1) NOT NULL default 1 COMMENT '1 - text only 2 - wc 3 - raw html 4 - drag and drop',
			`data` longtext,
			`canned` tinyint(1) default 0,
			`created_at` datetime NOT NULL default '0000-00-00 00:00:00',
			`updated_at` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (`ID`),
			KEY `type` (`type`),
			KEY `mode` (`mode`),
			KEY `canned` (`canned`)
		) $collate;";
		}
	}
}
