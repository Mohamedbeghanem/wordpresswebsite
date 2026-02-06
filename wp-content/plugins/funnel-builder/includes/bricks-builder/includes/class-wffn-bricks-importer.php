<?php

namespace FunnelKit;

use WFFN_Import_Export;
use WFFN_Template_Importer;
if ( ! class_exists( '\FunnelKit\WFFN_Bricks_Importer' ) ) {
	class WFFN_Bricks_Importer implements \WFFN_Import_Export {
		public function __construct() {
			add_action( 'woofunnels_module_template_removed', array( $this, 'delete_bricks_data' ) );
		}

		/**
		 * Imports a module with the given module ID and export content.
		 *
		 * @param int $module_id The ID of the module to import.
		 * @param string $export_content The export content of the module.
		 *
		 * @return mixed  The status of the import process.
		 */
		public function import( $module_id, $export_content = '' ) {
			//phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
			$status = $this->import_template_single( $module_id, $export_content );

			return $status;
		}

		/**
		 *  Import single template
		 *
		 * @param int $post_id post ID.
		 */
		public function import_template_single( $post_id, $template_data ) {
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => '',
			) );

			if ( empty( $template_data ) ) {
				$this->clear_cache();
				$this->delete_bricks_data( $post_id );

				return true;
			}

			if ( ! is_array( $template_data ) && is_string( $template_data ) ) {
				try {
					$template_data = json_decode( $template_data, true );
				} catch ( \Exception $error ) {
					return false;
				}
			}

			if ( empty( $template_data ) ) {
				return false;
			}

			$elements = array();
			$area     = 'content';
			$meta_key = BRICKS_DB_PAGE_CONTENT;

			if ( ! empty( $template_data[ $area ] ) ) {
				$elements = $template_data[ $area ];
			}

			if ( isset( $template_data['pageSettings'] ) ) {
				update_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, $template_data['pageSettings'] );
			}

			// STEP: Save final template elements
			$elements = \Bricks\Helpers::sanitize_bricks_data( $elements );

			// Add backslashes to element settings (needed for '_content' HTML entities, and Custom CSS) @since 1.7.1
			foreach ( $elements as $index => $element ) {
				$element_settings = ! empty( $element['settings'] ) ? $element['settings'] : array();

				foreach ( $element_settings as $setting_key => $setting_value ) {
					if ( is_string( $setting_value ) ) {
						$elements[ $index ]['settings'][ $setting_key ] = addslashes( $setting_value );
					}
				}
			}

			// STEP: Generate element IDs (@since 1.9.8)
			$elements = \Bricks\Helpers::generate_new_element_ids( $elements );

			// Update content.
			update_post_meta( $post_id, $meta_key, apply_filters( 'wffn_import_bricks_content', $elements, $post_id ) );
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => '',
			) );
			$this->clear_cache();

			return true;
		}

		public function clear_cache() {

		}

		/**
		 * Export the module data.
		 *
		 * @param int $module_id The ID of the module.
		 * @param string $slug The slug of the module.
		 *
		 * @return array The exported data.
		 */
		public function export( $module_id, $slug ) {//phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$data = array();

			if ( is_array( get_post_meta( $module_id, BRICKS_DB_PAGE_CONTENT, true ) ) ) {
				$elements        = get_post_meta( $module_id, BRICKS_DB_PAGE_CONTENT, true );
				$data['content'] = $elements;
			}

			$page_settings = get_post_meta( $module_id, BRICKS_DB_PAGE_SETTINGS, true );
			if ( $page_settings ) {
				$data['pageSettings'] = $page_settings;
			}

			return $data;
		}

		/**
		 * Deletes the bricks data associated with a specific post.
		 *
		 * @param int $post_id The ID of the post to delete bricks data for.
		 *
		 * @return void
		 */
		public function delete_bricks_data( $post_id ) {
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => '',
			) );

			delete_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT );
			delete_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS );
		}


	}

	$response = Bricks_Integration::check_builder_status();
	if ( class_exists( 'WFFN_Template_Importer' ) && true === $response['found'] ) {
		WFFN_Template_Importer::register( 'bricks', new WFFN_Bricks_Importer() );
	}
}
