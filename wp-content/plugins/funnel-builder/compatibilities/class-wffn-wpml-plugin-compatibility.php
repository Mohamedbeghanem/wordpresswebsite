<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WFFN_Compatibility_With_WPML_plugin
 */
if ( ! class_exists( 'WFFN_Compatibility_With_WPML_plugin' ) ) {
	#[AllowDynamicProperties]
	class WFFN_Compatibility_With_WPML_plugin extends WFFN_REST_Controller {
		protected $namespace = 'funnelkit-app';
		public $is_language_support = true;
		public function __construct() {
			add_filter( 'wffn_funnel_next_link', [ $this, 'wpml_funnel_next_link_function' ], 10, 1 );
			add_filter( 'wffn_filter_upsells', array( $this, 'filter_upsells_by_language' ), 10, 2 );
			add_filter( 'wffn_filter_thankyou_by_language', array( $this, 'filter_thankyou_by_language' ), 10, 2 );
			add_action( 'rest_api_init', [ $this, 'register_endpoint' ], 12 );
		}

		/**
		 * Registers REST API endpoints for multilingual functionality
		 */
		public function register_endpoint() {
			register_rest_route( $this->namespace, '/multilingual/funnel-step-languages/', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_funnel_step_languages' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_funnel_step_languages' ),
					'permission_callback' => array( $this, 'get_write_api_permission_check' ),
				),
			) );
		}

		public function get_write_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'multilingual', 'write' );
		}

		public function get_read_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'multilingual', 'read' );
		}

		/**
		 * Retrieves funnel step languages information for the WPML
		 *
		 * @param WP_REST_Request $request Request object containing funnel ID
		 *
		 * @return WP_REST_Response Response with language information for each funnel step
		 */
		public static function get_funnel_step_languages( $request ) {
			try {
				$funnel_id = $request->get_param( 'fid' );
				$funnel    = self::getFunnelData( $funnel_id );
				if ( empty( $funnel['steps'] ) ) {
					wp_send_json_error( [ 'message' => 'No funnel steps found.' ] );
					exit;
				}
				$funnel_metadata  = [
					'id'          => $funnel['id'] ?? null,
					'title'       => $funnel['title'] ?? null,
					'description' => $funnel['description'] ?? null,
					'count_data'  => $funnel['count_data'] ?? null,
				];
				$active_languages = apply_filters( 'wpml_active_languages', null, [ 'orderby' => 'name', 'order' => 'ASC' ] );

				$common_language_options = array_map( fn( $lang ) => [
					'code'         => $lang['code'],
					'display_name' => $lang['display_name'] ?? $active_languages[ $lang['code'] ]['native_name'],
				], $active_languages );
				$default_language        = apply_filters( 'wpml_default_language', null );

				$formatted_steps = self::formatSteps( $funnel['steps'] );
				$enable_value = WFFN_Core()->get_dB()->get_meta( $funnel_id, 'enable_translation' );
				$enable = ( $enable_value === null ) ? false : ( $enable_value === 'yes' );

				return rest_ensure_response( [
					'result'  => [
						'items'            => $formatted_steps,
						'base_language'    => $default_language,
						'language_options' => $common_language_options,
						'funnel_data'      => $funnel_metadata,
						'enable'           => $enable,
					],
					'status'  => true,
					'message' => __( 'Steps Languages found.', 'funnel-builder' ),
				] );
			} catch ( Exception|Error $e ) {
				return rest_ensure_response( [
					'result'  => [],
					'status'  => false,
					'message' => __( $e->getMessage(), 'funnel-builder' ),
				] );
			}
		}

		/**
		 * Retrieves funnel data by funnel ID.
		 * Fetches the funnel data and decodes it into an associative array.
		 *
		 * @param string $fid Funnel ID.
		 *
		 * @return array The decoded funnel data.
		 */
		private static function getFunnelData( $fid ) {
			$funnel = WFFN_REST_Funnels::get_instance();
			$funnel = $funnel->get_funnel_data( $fid, true );

			return json_decode( wp_json_encode( $funnel ), true );
		}

		/**
		 * Formats the funnel steps with necessary language and translation information.
		 * This function processes each step and formats it for the response.
		 *
		 * @param array $steps The funnel steps to format.
		 *
		 * @return array The formatted funnel steps with language options and translations.
		 */
		private static function formatSteps( $steps ) {
			$formatted_steps = [];
			foreach ( $steps as $step ) {
				$formatted_step    = self::formatFunnelStep( $step );
				$formatted_steps[] = $formatted_step;
			}

			return $formatted_steps;
		}

		/**
		 * Formats a single funnel step with language options and translations.
		 * This function retrieves and formats the funnel step data, including its substeps.
		 *
		 * @param array $step The funnel step data.
		 *
		 * @return array The formatted funnel step with language and translation data.
		 */
		private static function formatFunnelStep( $step ) {
			$post_id = $step['id'];

			$selected_language = self::getSelectedLanguage( $post_id );
			$translations      = self::getTranslations( $post_id );

			$formatted_step = [
				'id'                => $step['id'],
				'type'              => $step['type'],
				'title'             => $step['_data']['title'] ?? '',
				'substeps'          => self::formatSubsteps( $step['substeps'] ),
				'selected_language' => $selected_language,
				'translations'      => $translations,
			];

			return $formatted_step;
		}

		/**
		 * Retrieves translations for a given post (funnel step) using WPML filters.
		 * This function fetches all translations of a post, excluding the post itself.
		 *
		 * @param int $post_id The ID of the post (funnel step).
		 *
		 * @return array Associative array of translations with language code as key and post ID as value.
		 */
		private static function getTranslations( $post_id ) {
			$translations = [];

			$current_language = self::getSelectedLanguage( $post_id );

			$default_language = apply_filters( 'wpml_default_language', null );

			if ( $current_language !== $default_language ) {
				$current_type     = apply_filters( 'wpml_element_type', get_post_type( $post_id ) );
				$current_trid     = apply_filters( 'wpml_element_trid', false, $post_id, $current_type );
				$all_translations = apply_filters( 'wpml_get_element_translations', array(), $current_trid, $current_type );

				if ( isset( $all_translations[ $default_language ] ) && ! empty( $all_translations[ $default_language ]->element_id ) ) {
					return $translations;
				}
			}

			$current_type     = apply_filters( 'wpml_element_type', get_post_type( $post_id ) );
			$current_trid     = apply_filters( 'wpml_element_trid', false, $post_id, $current_type );
			$all_translations = apply_filters( 'wpml_get_element_translations', array(), $current_trid, $current_type );

			foreach ( $all_translations as $lang => $translated_data ) {
				if ( is_object( $translated_data ) ) {
					$translated_post_id = (int) $translated_data->element_id;

					if ( $translated_post_id !== $post_id ) {
						$translations[ $lang ] = $translated_post_id;
					}
				}
			}

			return $translations;
		}

		/**
		 * Formats the substeps for a given funnel step.
		 * This function returns an array of substep IDs and titles, formatted for the response.
		 *
		 * @param array $substeps Array of substeps for a given funnel step.
		 *
		 * @return array Formatted substeps including ID and title.
		 */
		private static function formatSubsteps( $substeps ) {
			$formatted_substeps = [];
			if ( ! empty( $substeps ) && is_array( $substeps ) ) {
				foreach ( $substeps as $substep_key => $substep_group ) {
					$formatted_substeps[ $substep_key ] = [];
					foreach ( $substep_group as $substep ) {
						$formatted_substeps[ $substep_key ][] = [
							'id'    => $substep['id'],
							'title' => $substep['_data']['title'] ?? ''
						];
					}
				}
			}

			return $formatted_substeps;
		}

		/**
		 * Gets language information for a specific post ID
		 *
		 * @param int $post_id The post ID to get language data for
		 *
		 * @return array Array with language options and selected language
		 */

		public static function getSelectedLanguage( $post_id ) {
			$post_language_details = apply_filters( 'wpml_post_language_details', null, $post_id );

			return $post_language_details['language_code'] ?? '';
		}

		/**
		 * Updates language associations for funnel steps
		 *
		 * @param WP_REST_Request $request Request object containing steps data with language information
		 *
		 * @return WP_REST_Response Response indicating success or failure
		 */
		public static function update_funnel_step_languages( $request ) {
			try {
				$params        = $request->get_json_params();
				$steps         = $params['steps'] ?? [];
				$funnel_id     = $request->get_param( 'fid' );
				$enable_status = $params['enable'] ?? 'yes';
				if ( $funnel_id ) {
					self::updateFunnelEnableStatus( $funnel_id, $enable_status );
				}
				if ( empty( $steps ) ) {
					return rest_ensure_response( [
						'status'  => false,
						'message' => __( 'No steps provided.', 'funnel-builder' ),
					] );
				}

				$step_map = [];
				foreach ( $steps as $step ) {
					if ( isset( $step['id'] ) ) {
						$step_map[ $step['id'] ] = $step;
					}
				}

				$translation_groups = [];
				$group_counter      = 0;
				$step_to_group      = [];

				foreach ( $steps as $step ) {
					$step_id = $step['id'] ?? null;
					if ( ! $step_id ) {
						continue;
					}

					if ( isset( $step_to_group[ $step_id ] ) ) {
						continue;
					}

					$group_id                        = 'group_' . $group_counter ++;
					$translation_groups[ $group_id ] = [
						'type'  => $step['type'] ?? '',
						'steps' => [ $step_id ]
					];
					$step_to_group[ $step_id ]       = $group_id;

					if ( isset( $step['translations'] ) && is_array( $step['translations'] ) ) {
						foreach ( $step['translations'] as $lang => $trans_id ) {//phpcs:ignore
							if ( ! empty( $trans_id ) && $trans_id !== $step_id ) {
								$translation_groups[ $group_id ]['steps'][] = $trans_id;
								$step_to_group[ $trans_id ]                 = $group_id;
							}
						}
					}
				}

				$trid_mapping = [];

				foreach ( $translation_groups as $group_id => $group ) {
					$type         = $group['type'];
					$is_trid_type = in_array( $type, [ 'landing', 'optin', 'wc_checkout', 'optin_ty' ], true );

					if ( $is_trid_type && ! empty( $group['steps'] ) ) {
						$first_step_id = $group['steps'][0];
						$post_type     = apply_filters( 'wpml_element_type', get_post_type( $first_step_id ) );
						$existing_trid = apply_filters( 'wpml_element_trid', null, $first_step_id, $post_type );

						$trid_mapping[ $group_id ] = $existing_trid ?: $first_step_id;
					}
				}

				foreach ( $steps as $step ) {
					$step_id = $step['id'] ?? null;
					if ( isset( $step_id ) ) {
						$step_map[ $step_id ] = $step;
					}
					if ( ! $step_id ) {
						continue;
					}

					$group_id = $step_to_group[ $step_id ] ?? null;
					if ( ! $group_id ) {
						continue;
					}

					self::processStepWithGroup( $step, $group_id, $trid_mapping, $step_map );
				}

				return rest_ensure_response( [
					'status'  => true,
					'message' => __( 'Funnel step languages updated successfully.', 'funnel-builder' ),
				] );
			} catch ( Exception|Error $e ) {
				return rest_ensure_response( [
					'status'  => false,
					'message' => __( $e->getMessage(), 'funnel-builder' ),
				] );
			}
		}

		/**
		 * Updates the enable status for a funnel
		 *
		 * @param int $funnel_id The ID of the funnel
		 * @param bool $enable_status Whether the funnel is enabled
		 *
		 * @return bool Success or failure
		 */
		private static function updateFunnelEnableStatus( $funnel_id, $enable_status ) {

			$status_value = is_bool( $enable_status ) ? ( $enable_status ? 'yes' : 'no' ) : $enable_status;

			$result = WFFN_Core()->get_dB()->update_meta( $funnel_id, 'enable_translation', $status_value );

			return $result !== false;
		}

		/**
		 * Processes a step with its translation group information
		 *
		 * @param array $step The step data
		 * @param string $group_id The translation group ID
		 * @param array $trid_mapping The TRID mapping for translation groups
		 */
		private static function processStepWithGroup( $step, $group_id, $trid_mapping, $step_map ) {
			$post_id           = $step['id'] ?? null;
			$selected_language = $step['selected_language'] ?? null;
			$type              = $step['type'] ?? null;
			$substeps          = $step['substeps'] ?? [];

			if ( empty( $post_id ) || empty( $selected_language ) || empty( $type ) ) {
				return;
			}

			$post_type       = apply_filters( 'wpml_element_type', get_post_type( $post_id ) );
			$should_map_trid = in_array( $type, [ 'landing', 'optin', 'wc_checkout', 'optin_ty' ], true );

			$source_language = null;
			if ( ! empty( $step['translations'] ) ) {
				$source_language = null;
			} else {
				foreach ( $step_map ?? [] as $other_step ) {
					if ( isset( $other_step['translations'] ) && is_array( $other_step['translations'] ) ) {
						foreach ( $other_step['translations'] as $lang => $trans_id ) {//phpcs:ignore
							if ( $trans_id === $post_id && $other_step['selected_language'] !== $selected_language ) {
								$source_language = $other_step['selected_language'];
								break 2;
							}
						}
					}
				}
			}

			do_action( 'wpml_set_element_language_details', [
				'element_id'           => $post_id,
				'element_type'         => $post_type,
				'trid'                 => $should_map_trid ? ( $trid_mapping[ $group_id ] ?? null ) : null,
				'language_code'        => $selected_language,
				'source_language_code' => $source_language
			] );

			update_post_meta( $post_id, '_wpml_post_translation_editor_native', 'yes' );

			if ( isset( $step['translations'] ) && is_array( $step['translations'] ) ) {
				foreach ( $step['translations'] as $lang_code => $translated_post_id ) {
					if ( ! empty( $translated_post_id ) && $translated_post_id !== $post_id ) {
						do_action( 'wpml_set_element_language_details', [
							'element_id'           => $translated_post_id,
							'element_type'         => $post_type,
							'trid'                 => $should_map_trid ? ( $trid_mapping[ $group_id ] ?? null ) : null,
							'language_code'        => $lang_code,
							'source_language_code' => $selected_language
						] );

						update_post_meta( $translated_post_id, '_wpml_post_translation_editor_native', 'yes' );
					}
				}
			}

			if ( $type === 'wc_upsells' && ! empty( $substeps ) ) {
				self::processSubsteps( $substeps, $selected_language );
			}
		}

		/**
		 * Processes substeps for a funnel step of type 'wc_upsells' and updates language details.
		 * This function sets the language for each substep and updates language details for translations.
		 *
		 * @param array $substeps The substeps for the funnel step.
		 * @param string $selected_language The selected language for the funnel step.
		 */
		private static function processSubsteps( $substeps, $selected_language ) {
			foreach ( $substeps as $substep_group ) {
				foreach ( $substep_group as $substep ) {
					$substep_id = $substep['id'] ?? null;
					if ( ! $substep_id ) {
						continue;
					}

					do_action( 'wpml_set_element_language_details', [
						'element_id'    => $substep_id,
						'element_type'  => apply_filters( 'wpml_element_type', get_post_type( $substep_id ) ),
						'language_code' => $selected_language
					] );

					update_post_meta( $substep_id, '_wpml_post_translation_editor_native', 'yes' );
				}
			}
		}

		/**
		 * Filters the next link URL to maintain language continuity in the funnel
		 *
		 * @param int $current_step_id Current step ID
		 *
		 * @return string|bool URL of the next step in the same language or false if not found
		 */
		public function wpml_funnel_next_link_function( $current_step_id ) {

			try {
				$funnel_data = WFFN_Core()->data->get_session_funnel();
				if ( empty( $funnel_data ) || ! isset( $funnel_data->steps ) || empty( $funnel_data->steps ) ) {
					return false;
				}
				$current_language = self::getCurrentLanguage( $current_step_id );

				if ( ! $current_language ) {
					return false;
				}

				$current_translation_ids = self::getCurrentStepTranslations( $current_step_id );
				$next_step_id            = self::getNextStep( $current_step_id, $current_translation_ids, $current_language, $funnel_data->steps );
				if ( $next_step_id ) {
					return $this->get_translation_permalink( $next_step_id, $current_language );
				}

			} catch ( Exception|Error $e ) {
				return $current_step_id;
			}

			return false;
		}

		/**
		 * Retrieves the current step's language using WPML filters.
		 * This function checks WPML language details for the given step.
		 *
		 * @param int $current_step_id The ID of the current step.
		 *
		 * @return string|null The language code of the current step, or null if not found.
		 */
		private static function getCurrentLanguage( $current_step_id ) {

			$current_lang_details = apply_filters( 'wpml_post_language_details', null, $current_step_id );
			if ( $current_lang_details && isset( $current_lang_details['language_code'] ) ) {
				return $current_lang_details['language_code'];
			}


			return null;
		}

		/**
		 * Retrieves the translations for the current step.
		 * This function fetches all translation IDs for the current funnel step.
		 *
		 * @param int $current_step_id The ID of the current step.
		 *
		 * @return array An array of translation IDs for the current step.
		 */
		private static function getCurrentStepTranslations( $current_step_id ) {
			$current_translation_ids = [];
			if ( function_exists( 'apply_filters' ) ) {
				$current_type         = apply_filters( 'wpml_element_type', get_post_type( $current_step_id ) );
				$current_trid         = apply_filters( 'wpml_element_trid', false, $current_step_id, 'post_' . $current_type );
				$current_translations = apply_filters( 'wpml_get_element_translations', array(), $current_trid, $current_type );

				foreach ( $current_translations as $translation ) {
					if ( isset( $translation->element_id ) ) {
						$current_translation_ids[] = (int) $translation->element_id;
					}
				}
			}

			return $current_translation_ids;
		}

		/**
		 * Finds the next valid step in the funnel, ensuring language continuity or falling back to another language.
		 * This function looks for the next step in the funnel, considering the current step's language and processed steps.
		 *
		 * @param int $current_step_id The ID of the current step.
		 * @param array $current_translation_ids The translation IDs of the current step.
		 * @param string $current_language The current language of the funnel step.
		 * @param array $steps The list of all funnel steps.
		 *
		 * @return int|null The ID of the next valid step, or null if no valid next step is found.
		 */
		private static function getNextStep( $current_step_id, $current_translation_ids, $current_language, $steps ) {
			$next_step_same_language = null;
			$next_step_fallback      = null;
			$current_step_index      = array_search( $current_step_id, array_column( $steps, 'id' ), true );

			$current_step_type = null;
			foreach ( $steps as $step ) {
				if ( $step['id'] === $current_step_id ) {
					$current_step_type = $step['type'];
					break;
				}
			}

			$next_step_type = null;
			for ( $i = $current_step_index + 1; $i < count( $steps ); $i ++ ) {
				if ( $steps[ $i ]['type'] !== $current_step_type && ! in_array( $steps[ $i ]['id'], $current_translation_ids, true ) ) {
					$next_step_type = $steps[ $i ]['type'];
					break;
				}
			}

			if ( ! $next_step_type ) {
				return null;
			}

			for ( $i = $current_step_index + 1; $i < count( $steps ); $i ++ ) {
				$next_step   = $steps[ $i ];
				$get_next_id = $next_step['id'];

				if ( in_array( $get_next_id, $current_translation_ids, true ) ) {
					continue;
				}

				if ( $next_step['type'] !== $next_step_type ) {
					continue;
				}

				if ( $next_step_fallback === null ) {
					$next_step_fallback = $get_next_id;
				}

				$next_language = self::getLanguageForStep( $get_next_id );
				if ( $next_language === $current_language ) {
					$next_step_same_language = $get_next_id;
					break;
				}

				$translations_of_next = self::getTranslationsForStep( $get_next_id );
				foreach ( $translations_of_next as $lang_code => $translation ) {
					if ( $lang_code === $current_language && isset( $translation->element_id ) ) {
						$next_step_same_language = $translation->element_id;
						break 2;
					}
				}
			}

			return $next_step_same_language !== null ? $next_step_same_language : $next_step_fallback;
		}

		/**
		 * Retrieves translations for a specific funnel step.
		 * This function fetches the translations for a given step using WPML filters.
		 *
		 * @param int $post_id The ID of the funnel step.
		 *
		 * @return array An array of translation IDs for the funnel step.
		 */
		private static function getTranslationsForStep( $post_id ) {
			$translations = [];
			if ( function_exists( 'apply_filters' ) ) {
				$type              = apply_filters( 'wpml_element_type', get_post_type( $post_id ) );
				$trid              = apply_filters( 'wpml_element_trid', false, $post_id, 'post_' . $type );
				$translations_data = apply_filters( 'wpml_get_element_translations', array(), $trid, $type );

				foreach ( $translations_data as $translation ) {
					if ( isset( $translation->element_id ) ) {
						$translations[] = (int) $translation->element_id;
					}
				}
			}

			return $translations;
		}

		/**
		 * Retrieves the language for a specific funnel step.
		 * This function uses WPML filters to get the language details for a given step.
		 *
		 * @param int $post_id The ID of the funnel step.
		 *
		 * @return string|null The language code of the funnel step, or null if not found.
		 */
		private static function getLanguageForStep( $post_id ) {
			if ( function_exists( 'apply_filters' ) ) {
				$lang_details = apply_filters( 'wpml_post_language_details', null, $post_id );

				return isset( $lang_details['language_code'] ) ? $lang_details['language_code'] : null;
			}

			return null;
		}

		/**
		 * Gets permalink for a post in a specific language
		 *
		 * @param int $post_id Post ID
		 * @param string $language_code Language code
		 *
		 * @return string Permalink URL for the post in the specified language
		 */
		public function get_translation_permalink( $post_id, $language_code ) {
			global $sitepress;


			$current_language = $sitepress->get_current_language();

			$sitepress->switch_lang( $language_code );

			$permalink = get_permalink( $post_id );

			$sitepress->switch_lang( $current_language );

			return $permalink;
		}

		/**
		 * Filters thank you pages to match checkout page language
		 *
		 * @param array $thankyou_page_ids Array of thank you page IDs
		 * @param array $current_step Current step information
		 *
		 * @return array Filtered thank you page IDs that match the current language
		 */
		public function filter_thankyou_by_language( $thankyou_page_ids, $current_step ) {

			try {
				if ( empty( $thankyou_page_ids ) || ! function_exists( 'apply_filters' ) ) {
					return $thankyou_page_ids;
				}
				$checkout_id           = $current_step['id'];
				$checkout_lang_details = apply_filters( 'wpml_post_language_details', null, $checkout_id );
				if ( ! $checkout_lang_details || ! isset( $checkout_lang_details['language_code'] ) ) {
					return $thankyou_page_ids;
				}
				$checkout_language     = $checkout_lang_details['language_code'];
				$matching_thankyou_ids = [];
				foreach ( $thankyou_page_ids as $ty_id ) {
					$ty_lang_details = apply_filters( 'wpml_post_language_details', null, $ty_id );
					if ( $ty_lang_details && isset( $ty_lang_details['language_code'] ) && $ty_lang_details['language_code'] === $checkout_language ) {
						$matching_thankyou_ids[] = $ty_id;
					}
				}

			} catch ( Exception|Error $e ) {
				return $thankyou_page_ids;
			}

			return ! empty( $matching_thankyou_ids ) ? $matching_thankyou_ids : $thankyou_page_ids;
		}

		/**
		 * Filters upsell offers to match current step language
		 *
		 * @param array $upsells Array of upsell step data
		 * @param array $current_step Current step information
		 *
		 * @return array Filtered upsells that match the current language
		 */
		public function filter_upsells_by_language( $upsells, $current_step ) {

			try {
				$step_id           = $current_step['id'];
				$step_lang_details = apply_filters( 'wpml_post_language_details', null, $step_id );
				if ( $step_lang_details && isset( $step_lang_details['language_code'] ) ) {
					$step_language             = $step_lang_details['language_code'];
					$language_matching_upsells = [];

					foreach ( $upsells as $upsell ) {
						$upsell_id           = $upsell['id'];
						$upsell_lang_details = apply_filters( 'wpml_post_language_details', null, $upsell_id );

						if ( $upsell_lang_details && isset( $upsell_lang_details['language_code'] ) && $upsell_lang_details['language_code'] === $step_language ) {
							$language_matching_upsells[] = $upsell;
						}
					}


				}
			} catch ( Exception|Error $e ) {
				return $upsells;
			}

			return ! empty( $language_matching_upsells ) ? $language_matching_upsells : $upsells;
		}

		public function get_plugin_nicename() {
			return 'WPML';
		}
	}

	WFFN_Plugin_Compatibilities::register( new WFFN_Compatibility_With_WPML_plugin(), 'wpml' );
}