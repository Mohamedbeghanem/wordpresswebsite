<?php

if ( ! class_exists( 'WFFN_Oxygen_Importer' ) ) {
	class WFFN_Oxygen_Importer implements WFFN_Import_Export {

		public function __construct() {
			add_action( 'woofunnels_module_template_removed', [ $this, 'delete_oxy_data' ] );
		}

		public function import( $module_id, $content = '' ) {

			if ( ! empty( $content ) && ( false === strpos( $content, '<script' ) ) ) {
				update_post_meta( $module_id, WFFN_Common::oxy_get_meta_prefix('ct_other_template'), '-1' );
				update_post_meta( $module_id, WFFN_Common::oxy_get_meta_prefix('ct_builder_shortcodes'), $content );

				$this->clear_oxy_page_cache_css( $module_id );

			} else {
				delete_post_meta( $module_id, 'ct_other_template' );
			}

			return [ 'success' => true ];
		}

		public function import_template_single( $module_id, $content ) {//phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			return [ 'success' => false ];

		}

		public function export( $module_id, $slug ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			return get_post_meta( $module_id, WFFN_Common::oxy_get_meta_prefix('ct_builder_shortcodes'), true );
		}

		public function delete_oxy_data( $post_id ) {
			delete_post_meta( $post_id, WFFN_Common::oxy_get_meta_prefix('ct_other_template') );
			delete_post_meta( $post_id, WFFN_Common::oxy_get_meta_prefix('ct_builder_shortcodes') );
			delete_post_meta( $post_id, WFFN_Common::oxy_get_meta_prefix('ct_page_settings') );
			delete_post_meta( $post_id, WFFN_Common::oxy_get_meta_prefix('ct_builder_json') );
		}

		public function clear_oxy_page_cache_css( $post_id ) {

			if ( function_exists( 'oxygen_vsb_cache_universal_css' ) && function_exists( 'oxygen_vsb_delete_css_file' ) && get_option( "oxygen_vsb_universal_css_cache" ) == 'true' ) { //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				/**
				 * generate universal css when oxygen cache setting is enabled and delete previous css
				 */
				oxygen_vsb_delete_css_file( $post_id );

				oxygen_vsb_cache_universal_css();
			} elseif ( function_exists( 'oxygen_vsb_cache_page_css' ) ) {

				/**
				 * generate oxygen css
				 */
				oxygen_vsb_cache_page_css( $post_id );
			}
		}

	}

	if ( class_exists( 'WFFN_Template_Importer' ) ) {
		WFFN_Template_Importer::register( 'oxy', new WFFN_Oxygen_Importer() );
	}
}