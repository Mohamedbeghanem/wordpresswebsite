<?php

namespace FunnelKit;

use WFACP_Common;
use WFACP_Template_Importer;
use Exception;
if ( ! class_exists( '\FunnelKit\WFACP_Bricks_Importer' ) && interface_exists( '\WFACP_Import_Export' ) ) {
	#[\AllowDynamicProperties]
	class WFACP_Bricks_Importer implements \WFACP_Import_Export {
		private $is_multi = 'no';
		private $slug = '';
		public $delete_page_meta = true;
		private $settings_file = '';
		private $builder = 'bricks';

		public function __construct() {
			// DO NOT DELETE
		}

		public function import( $aero_id, $slug, $is_multi = 'no' ) {
			$this->slug     = $slug;
			$this->is_multi = $is_multi;

			if ( 'brick_1' === $slug ) {
				wp_update_post( array(
					'ID'           => $aero_id,
					'post_content' => '',
				) );

				update_post_meta( $aero_id, '_wp_page_template', 'wfacp-canvas.php' );
				update_post_meta( $aero_id, BRICKS_DB_PAGE_CONTENT, array() );

				return array( 'status' => true );
			}

			$templates           = WFACP_Core()->template_loader->get_templates( $this->builder );
			$this->settings_file = isset( $templates[ $slug ], $templates[ $slug ]['settings_file'] ) ? $templates[ $slug ]['settings_file'] : '';
			if ( $templates[ $slug ] && isset( $templates[ $slug ]['build_from_scratch'] ) ) {
				$this->save_data( $aero_id );

				return array( 'status' => true );
			}


			$data = WFACP_Core()->importer->get_remote_template( $slug, $this->builder );


			if ( isset( $data['error'] ) ) {
				return $data;
			}

			/**
			 * Translation of Elementor Templates 
			 */
			if ( isset( $data['data'] ) ) {

				if(defined( 'WFFN_PRO_FILE' ) || defined( 'WFFN_BASIC_FILE' )){
					$translation_list = WFACP_Common::get_translation_field_aero_checkout_domain();
					$translation_list = array_filter( $translation_list, function ( $key, $val ) {
						return ! ( $key === $val );
					}, ARRAY_FILTER_USE_BOTH );

					foreach ( $translation_list as $key => $value ) {
						if ( false !== strpos( $data['data'], $key ) ) {

							$data['data'] = str_replace( $key, $value, $data['data'] ); 
						}
					}

					$translation_list = WFACP_Common::get_translation_field_funnel_buider_domain();
					$translation_list = array_filter( $translation_list, function ( $key, $val ) {


						return ! ( $key === $val );
					}, ARRAY_FILTER_USE_BOTH );

  
					foreach ( $translation_list as $key => $value ) {
						if ( false !== strpos( $data['data'], $key ) ) {
							$data['data'] = str_replace( $key, $value, $data['data'] );
						}
					}

				}else{

					if (method_exists('WFACP_Common', 'translation_string_to_check')) {
						$data['data'] = WFACP_Common::translation_string_to_check( $data['data'] );
					}
				}

			}



			$content = $data['data'];
			if ( ! empty( $content ) ) {
				$status = $this->import_aero_template( $aero_id, $content );


				return [ 'status' => $status ];
			}

			return [ 'error' => __( 'Something Went wrong', 'woofunnels-aero-checkout' ) ];
		}


		public function export( $aero_id, $slug ) {  //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$data = array();

			if ( is_array( get_post_meta( $aero_id, BRICKS_DB_PAGE_CONTENT, true ) ) ) {
				$elements        = get_post_meta( $aero_id, BRICKS_DB_PAGE_CONTENT, true );
				$data['content'] = $elements;
			}

			$page_settings = get_post_meta( $aero_id, BRICKS_DB_PAGE_SETTINGS, true );
			if ( $page_settings ) {
				$data['pageSettings'] = $page_settings;
			}

			return $data;
		}


		/**
		 *  Import single template
		 *
		 * @param int $post_id post ID.
		 */
		public function import_aero_template( $post_id, $template_data ) {
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => '',
			) );

			if ( ! is_array( $template_data ) && is_string( $template_data ) ) {
				try {
					$template_data = json_decode( $template_data, true );
				} catch ( Exception $error ) {
					return array( 'error' => $error->getMessage() );
				}
			}

			if ( ! is_array( $template_data ) ) {
				return false;
			}

			if ( empty( $template_data ) ) {
				return false;
			}

			$this->save_data( $post_id, $template_data );

			return true;
		}


		private function save_data( $post_id, $template_data = '' ) {
			if ( true === $this->delete_page_meta ) {
				$this->delete_template_data( $post_id );
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

					/**
					 * New Setting Created
					 */
					if ( $setting_key == 'enable_quantity_box' ) {
						/**
						 * Mini Cart
						 */
						$elements[ $index ]['settings']['mini_cart_enable_strike_through_price'] = false;
						$elements[ $index ]['settings']['mini_cart_enable_low_stock_trigger']    = false;
						$elements[ $index ]['settings']['mini_cart_low_stock_message']           = __( '{{quantity}} LEFT IN STOCK', 'woofunnels-aero-checkout' );
						$elements[ $index ]['settings']['mini_cart_enable_saving_price_message'] = false;
						$elements[ $index ]['settings']['mini_cart_saving_price_message']        = __( 'You saved {{saving_amount}} ({{saving_percentage}}) on this order', 'woofunnels-aero-checkout' );

					}

					if ( $setting_key == 'enable_callapse_order_summary' ) {
						/**
						 * Collapsible Order Summary
						 */
						$elements[ $index ]['settings']['collapsible_mini_cart_enable_strike_through_price'] = false;
						$elements[ $index ]['settings']['collapsible_mini_cart_enable_low_stock_trigger']    = false;
						$elements[ $index ]['settings']['collapsible_mini_cart_low_stock_message']           = __( '{{quantity}} LEFT IN STOCK', 'woofunnels-aero-checkout' );
						$elements[ $index ]['settings']['collapsible_mini_cart_enable_saving_price_message'] = false;
						$elements[ $index ]['settings']['collapsible_mini_cart_saving_price_message']        = __( 'You saved {{saving_amount}} ({{saving_percentage}}) on this order', 'woofunnels-aero-checkout' );
					}

					if ( $setting_key == 'order_summary_enable_product_image' ) {
						/**
						 *  Order Summary
						 */
						$elements[ $index ]['settings']['order_summary_field_enable_strike_through_price'] = false;
						$elements[ $index ]['settings']['order_summary_field_enable_low_stock_trigger']    = false;
						$elements[ $index ]['settings']['order_summary_field_low_stock_message']           = __( '{{quantity}} LEFT IN STOCK', 'woofunnels-aero-checkout' );
						$elements[ $index ]['settings']['order_summary_field_enable_saving_price_message'] = false;
						$elements[ $index ]['settings']['order_summary_field_saving_price_message']        = __( 'You saved {{saving_amount}} ({{saving_percentage}}) on this order', 'woofunnels-aero-checkout' );
					}

				}
			}


			// STEP: Generate element IDs (@since 1.9.8)
			$elements = \Bricks\Helpers::generate_new_element_ids( $elements );


			// Update content.
			update_post_meta( $post_id, $meta_key, apply_filters( 'wffn_import_bricks_content', $elements, $post_id ) );

			if ( ! empty( $this->settings_file ) ) {
				$file_path = WFACP_PLUGIN_DIR . '/importer/checkout-settings/' . $this->settings_file;
				WFACP_Common::import_checkout_settings( $post_id, $file_path );
			}

			if ( defined( 'BRICKS_VERSION' ) ) {
				update_post_meta( $post_id, '_bricks_version', BRICKS_VERSION );
			}

			$this->clear_cache();
		}

		private function delete_template_data( $post_id ) {
			WFACP_Common::delete_page_layout( $post_id );
		}

		public function clear_cache() {

			if ( class_exists( '\Bricks\Assets_Files' ) ) {
				\Bricks\Assets_Files::regenerate_css_files();
			}
		}

	}

	if ( class_exists( 'WFACP_Template_Importer' ) ) {
		WFACP_Template_Importer::register( 'bricks', new WFACP_Bricks_Importer() );
	}
}
