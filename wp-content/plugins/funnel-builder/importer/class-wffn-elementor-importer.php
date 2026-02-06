<?php

/**
 * Elementor template library local source.
 *
 * Elementor template library local source handler class is responsible for
 * handling local Elementor templates saved by the user locally on his site.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'WFFN_Elementor_Importer' ) ) {
	class WFFN_Elementor_Importer extends Elementor\TemplateLibrary\Source_Local implements WFFN_Import_Export {
		public function __construct() {
			add_action( 'woofunnels_module_template_removed', [ $this, 'delete_elementor_data' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'fb_dequeue_scripts_if_page_is_not_built_using_elementor' ], 12 );

		}

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
		public function import_template_single( $post_id, $content ) {
			wp_update_post( [
				'ID'           => $post_id,
				'post_content' => '',
			] );

			delete_post_meta( $post_id, '_elementor_data' );
			delete_post_meta( $post_id, '_elementor_version' );
			delete_post_meta( $post_id, '_et_pb_use_builder' );

			if ( empty( $content ) ) {
				$this->clear_cache();

				return true;
			}

			if ( ! is_array( $content ) && is_string( $content ) ) {
				try {
					$content = json_decode( $content, true );
				} catch ( Exception $error ) {
					return false;
				}
			}

			if ( isset( $content['content'] ) ) {
				$content = $content['content'];
			}

			if ( empty( $content ) ) {

				return false;
			}
			// Update content.

			$content = apply_filters( 'wffn_import_elementor_content', $content, $post_id );
			$content = wp_slash( wp_json_encode( $content ) );

			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_data', $content );

			$response = WFFN_Common::check_builder_status( 'elementor' );
			if ( true === $response['found'] && empty( $response['error'] ) ) {
				update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
			}

			$this->clear_cache();

			return true;
		}

		public function clear_cache() {
			$this->generate_kit();
			Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		public function export( $module_id, $slug ) {//phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$data = get_post_meta( $module_id, '_elementor_data', true );

			return $data;
		}

		public function delete_elementor_data( $post_id ) {
			wp_update_post( [ 'ID' => $post_id, 'post_content' => '' ] );
			delete_post_meta( $post_id, '_elementor_version' );
			delete_post_meta( $post_id, '_elementor_template_type' );
			delete_post_meta( $post_id, '_elementor_edit_mode' );
			delete_post_meta( $post_id, '_elementor_data' );
			delete_post_meta( $post_id, '_elementor_controls_usage' );
			delete_post_meta( $post_id, '_elementor_css' );
		}

		public function generate_kit() {
			if ( is_null( Elementor\Plugin::$instance ) || ! Elementor\Plugin::$instance->kits_manager instanceof Elementor\Core\Kits\Manager ) {
				return;
			}
			$kit = Elementor\Plugin::$instance->kits_manager->get_active_kit();
			if ( $kit->get_id() ) {
				return;
			}
			$created_default_kit = Elementor\Plugin::$instance->kits_manager->create_default();
			if ( ! $created_default_kit ) {
				return;
			}
			update_option( Elementor\Core\Kits\Manager::OPTION_ACTIVE, $created_default_kit );
		}


		/**
		 * Dequeues Elementor scripts and styles for FunnelKit Builder pages
		 *
		 * This function prevents loading unnecessary Elementor assets on FunnelKit Builder pages
		 * that are using canvas or boxed templates but not built with Elementor. This helps
		 * improve page load performance.
		 *
		 * The function checks:
		 * 1. If Elementor locations exist
		 * 2. If current post exists and is valid
		 * 3. If post type matches FunnelKit Builder types
		 * 4. If page template is canvas or boxed
		 * 5. If page is NOT built with Elementor
		 *
		 * @return void
		 * @uses \ElementorPro\Plugin::instance()
		 * @uses get_post_meta()
		 * @uses wp_dequeue_script()
		 * @uses wp_dequeue_style()
		 *
		 * @global WP_Post $post Current post object
		 *
		 * @since 3.10.2
		 */
		function fb_dequeue_scripts_if_page_is_not_built_using_elementor() {
			global $post;

			try {
				if ( ! class_exists( '\ElementorPro\Plugin', false ) ) {
					return;
				}

				if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
					return;
				}
				if ( version_compare( ELEMENTOR_PRO_VERSION, '3.29.0', '<' ) ) {
					return;
				}

				// Get Elementor theme builder locations
				$locations = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' )->get_locations_manager()->get_locations();

				if ( empty( $locations ) ) {
					return;
				}

				//Check if either global header or footer set using theme builder
				if ( ( array_key_exists( 'header', $locations ) || array_key_exists( 'footer', $locations ) ) ) {
					// Validate post object
					if ( is_null( $post ) || ! $post instanceof WP_Post ) {
						return;
					}

					// Check if post-type is a FunnelKit Builder type
					if ( ! in_array( $post->post_type, array(
						'wffn_landing',    // Landing pages
						'wffn_ty',         // Thank you pages
						'wffn_optin',      // Optin pages
						'wffn_oty',        // Optin thank you pages
						'wfacp_checkout',  // Checkout pages
						'wfocu_offer',     // One click upsell offers
					), true ) ) {
						return;
					}

					// Check if using canvas or boxed template
					$page_template = get_post_meta( $post->ID, '_wp_page_template', true );

					if ( false !== strpos( $page_template, '-canvas.php' ) || false !== strpos( $page_template, '-boxed.php' ) ) {

						// Return if page is built with Elementor
						if ( \Elementor\Plugin::$instance->documents->get( $post->ID )->is_built_with_elementor() ) {
							return;
						}

						// Dequeue Elementor scripts and styles since page is not built with Elementor
						wp_dequeue_script( 'elementor-frontend' );
						wp_dequeue_style( 'elementor-frontend' );
						wp_dequeue_script( 'elementor-pro-frontend' );
						wp_dequeue_style( 'elementor-pro-frontend' );
						wp_dequeue_script( 'elementor-sticky' );
						wp_dequeue_script( 'elementor-waypoints' );
						wp_dequeue_script( 'elementor-frontend-modules' );
					}
				}
			} catch ( Exception|Error $e ) {
				WFFN_Core()->logger->log( 'Error in : ' . __FUNCTION__ . '---' . $e->getMessage() );
			}

		}
	}


	$response = WFFN_Common::check_builder_status( 'elementor' );
	if ( class_exists( 'WFFN_Template_Importer' ) && true === $response['found'] ) {
		WFFN_Template_Importer::register( 'elementor', new WFFN_Elementor_Importer() );
	}
}
