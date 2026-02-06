<?php

if ( ! class_exists( 'BWFAN_Model_Contact_Note' ) && BWFAN_Common::is_pro_3_0() ) {

	class BWFAN_Model_Contact_Note extends BWFAN_Model {

		/**
		 * @param $contact_id
		 * @param int $offset
		 * @param int $limit
		 *
		 * @return array
		 */
		public static function get_contact_notes( $contact_id, $offset = 0, $limit = 0 ) {
			if ( empty( $offset ) && empty( $limit ) ) {
				$query = "SELECT * FROM {table_name} WHERE `cid`='" . $contact_id . "' ORDER BY `created_date` DESC";
			} else {
				$query = "SELECT * FROM {table_name} WHERE `cid`='" . $contact_id . "' ORDER BY `created_date` DESC LIMIT $offset,$limit";
			}

			return self::get_results( $query );
		}

		/**
		 * Update contact note
		 *
		 * @param $contact_id
		 * @param $notes
		 * @param $note_id
		 *
		 * @return bool|int|mysqli_result|resource|null
		 */
		public static function update_contact_note( $contact_id, $notes, $note_id ) {
			$data = $notes;

			$where = array(
				'id'  => $note_id,
				'cid' => $contact_id,
			);

			return self::update( $data, $where );
		}
	}
}