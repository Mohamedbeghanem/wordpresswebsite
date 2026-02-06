<?php
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WFACP_template_Bricks' ) ) {
	#[AllowDynamicProperties]
	final class WFACP_template_Bricks extends WFACP_Template_Common {
		private static $ins = null;

		protected function __construct() {
			parent::__construct();
			$this->template_dir  = __DIR__;
			$this->template_slug = 'bricks';
			$this->template_type = 'bricks';
			$this->css_default_classes();

			add_action( 'wfacp_before_process_checkout_template_loader', array( $this, 'get_ajax_exchange_keys' ) );
			add_filter( 'wc_get_template', array( $this, 'replace_native_checkout_form' ), 999, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ), 999 );

			add_action( 'wfacp_after_checkout_page_found', array( $this, 'reset_session' ) );
			add_filter( 'wfacp_forms_field', array( $this, 'hide_product_switcher' ), 10, 2 );

			add_filter( 'wfacp_cart_show_product_thumbnail', array( $this, 'display_order_summary_thumb' ), 10 );
			add_action( 'process_wfacp_html', array( $this, 'layout_order_summary' ), 55, 4 );

			add_filter( 'wfacp_html_fields_order_summary', '__return_false' );

			add_action( 'wfacp_internal_css', array( $this, 'get_elementor_localize_data' ), 9 );

			add_action( 'wfacp_before_form', array( $this, 'element_start_before_the_form' ), 9 );
			add_action( 'wfacp_after_form', array( $this, 'element_end_after_the_form' ), 9 );

			add_action( 'wfacp_checkout_preview_form_start', array( $this, 'element_start_before_the_form' ), 9 );
			add_action( 'wfacp_checkout_preview_form_end', array( $this, 'element_end_after_the_form' ), 9 );

			add_filter( 'wfacp_css_js_deque', array( $this, 'remove_theme_styling' ), 10, 4 );
			add_action( 'wp_head', array( $this, 'wfacp_header_print_in_head' ), 999 );
			add_action( 'wp_footer', array( $this, 'wfacp_footer_before_print_scripts' ), - 1 );

			add_action( 'wp_footer', array( $this, 'wfacp_footer_after_print_scripts' ), 999 );
			add_action( 'wfacp_before_sidebar_content', array( $this, 'add_order_summary_to_sidebar' ), 11 );
			add_filter( 'wfacp_show_form_coupon', '__return_true', 10 );

			add_filter( 'wfacp_mini_cart_hide_coupon', array( $this, 'enable_collapsed_coupon_field' ), 10 );

			add_filter( 'wfacp_order_summary_cols_span', array( $this, 'change_col_span_for_order_summary' ) );
			add_filter( 'wfacp_order_total_cols_span', array( $this, 'change_col_span_for_order_summary' ) );

			add_filter( 'wfacp_for_mb_style', array( $this, 'get_product_switcher_mobile_style' ) );
			add_action( 'wfacp_checkout_preview_form_start', array( $this, 'add_checkout_preview_div_start' ) );
			add_action( 'wfacp_checkout_preview_form_end', array( $this, 'add_checkout_preview_div_end' ) );
			add_action( 'wp', array( $this, 'run_divi_styling' ) );

			add_action( 'wfacp_before_progress_bar', array( $this, 'before_cart_link' ) );
			add_action( 'wfacp_before_breadcrumb', array( $this, 'before_cart_link' ) );

			add_action( 'wfacp_after_next_button', array( $this, 'before_return_to_cart_link' ) );

			add_action( 'woocommerce_before_checkout_form', array( $this, 'add_form_steps' ), 999 );
			add_action( 'woocommerce_before_checkout_form', array( $this, 'display_progress_bar' ), 999 );
			add_filter( 'woocommerce_order_button_html', array( $this, 'add_class_change_place_order' ), 11 );

			add_filter( 'wfacp_change_back_btn', array( $this, 'change_back_step_label' ), 11, 3 );
			add_filter( 'wfacp_blank_back_text', array( $this, 'add_blank_back_text' ), 11, 3 );
			add_filter( 'wfacp_form_coupon_widgets_enable', '__return_true' );
			add_action( 'wfacp_footer_before_print_scripts', array( $this, 'activate_theme_hook' ) );

			/* Coupon button text */
			add_action( 'wfacp_collapsible_apply_coupon_button_text', array( $this, 'get_collapsible_coupon_button_text' ) );
			add_action( 'wfacp_form_apply_coupon_button_text', array( $this, 'get_form_coupon_button_text' ) );
			add_action( 'wfacp_sidebar_apply_coupon_button_text', array( $this, 'get_mini_cart_coupon_button_text' ) );

			/*
			Button Icon */
			/* for step one */
			add_action( 'wfacp_before_step_next_button_single_step', array( $this, 'display_button_icon_step_1' ) );

			/* for step Two */
			add_action( 'wfacp_before_step_next_button_two_step', array( $this, 'display_button_icon_step_2' ) );

			/*--------------------------------Primary Color Handling -------------------------------------------*/
			add_action( 'wfacp_internal_css', array( $this, 'primary_colors' ), 10 );

			add_filter( 'wfacp_show_product_thumbnail_collapsible_show', '__return_true' );


			/**
			 * Mini Cart Strike Through Discounted Price
			 */

			add_filter( 'wfacp_order_summary_field_enable_strike_through_price', [ $this, 'order_summary_field_enable_strike_through_price' ] );
			add_filter( 'wfacp_collapsible_mini_cart_enable_strike_through_price', [ $this, 'collapsible_mini_cart_enable_strike_through_price' ] );
			add_filter( 'wfacp_mini_cart_enable_strike_through_price', [ $this, 'mini_cart_enable_strike_through_price' ] );

			/**
			 * Display Low Stock Trigger Message
			 */
			add_action( 'wfacp_mini_cart_after_product_title', [ $this, 'mini_cart_low_stock_trigger' ] );
			add_action( 'wfacp_order_summary_field_after_product_title', [ $this, 'order_summary_field_after_product_title' ] );
			add_action( 'wfacp_collapsible_mini_cart_after_product_title', [ $this, 'collapsible_mini_cart_field_after_product_title' ] );

			/**
			 * Display Saving Price Row After Order Total in mini cart
			 */
			add_action( 'wfacp_mini_cart_woocommerce_review_order_after_order_total', [ $this, 'mini_cart_saving_price' ], 9999 );
			add_action( 'wfacp_order_summary_field_woocommerce_review_order_after_order_total', [ $this, 'order_summary_field_saving_price' ], 9999 );
			add_action( 'wfacp_collapsible_mini_cart_woocommerce_review_order_after_order_total', [ $this, 'collapsible_mini_cart_saving_price' ], 9999 );

		}

		public static function get_instance() {
			if ( is_null( self::$ins ) ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * Retrieves the exchange keys for AJAX requests.
		 *
		 * This method retrieves the exchange keys for AJAX requests from the WFACP_Common class.
		 * It checks if the 'bricks' key is set in the exchange keys array and retrieves the form data and mini cart data accordingly.
		 *
		 * @return void
		 */
		public function get_ajax_exchange_keys() {
			$keys = WFACP_Common::$exchange_keys;

			if ( is_array( $keys ) && ! empty ( $keys ) && isset( $keys['bricks'] ) && is_array( $keys['bricks'] ) && ! empty( $keys['bricks'] ) ) {
				$form_id         = $keys['bricks']['wfacp_form'];
				$this->form_data = WFACP_Common::get_session( $form_id );
				if ( isset( $keys['bricks']['wfacp_form_summary'] ) ) {
					$mini_cart_form_id    = $keys['bricks']['wfacp_form_summary'];
					$this->mini_cart_data = WFACP_Common::get_session( $mini_cart_form_id );
				}
			}
		}

		/**
		 * Retrieves the localized data for the checkout template.
		 *
		 * This method extends the parent class's `get_localize_data` method and adds additional
		 * localized data specific to the Funnel Builder Bricks Integration plugin.
		 *
		 * @return array The localized data for the checkout template.
		 */
		public function get_localize_data() {
			$data = parent::get_localize_data();

			$data['exchange_keys']['bricks'] = FunnelKit\Bricks_Integration::get_locals();

			return $data;
		}

		public function set_selected_template( $data ) {
			parent::set_selected_template( $data );
			$this->template_slug = $data['slug'];
		}

		public function css_default_classes() {
			$css_class         = array(
				'billing_email'      => array(
					'class' => 'wfacp-col-full',
				),
				'product_switching'  => array(
					'class' => 'wfacp-col-full',
				),
				'billing_first_name' => array(
					'class' => 'wfacp-col-left-half',
				),
				'billing_last_name'  => array(
					'class' => 'wfacp-col-left-half',
				),
				'address'            => array(
					'class' => 'wfacp-col-left-half',
				),
				'billing_company'    => array(
					'class' => 'wfacp-col-full',
				),
				'billing_address_1'  => array(
					'class' => 'wfacp-col-left-half',
				),
				'billing_address_2'  => array(
					'class' => 'wfacp-col-left-half',
				),

				'billing_country'  => array(
					'class' => 'wfacp-col-left-third',
				),
				'billing_city'     => array(
					'class' => 'wfacp-col-left-half',
				),
				'billing_postcode' => array(
					'class' => 'wfacp-col-left-third',
				),

				'billing_state' => array(
					'class' => 'wfacp-col-left-third',
				),
				'billing_phone' => array(
					'class' => 'wfacp-col-full',
				),

				'shipping_email'      => array(
					'class' => 'wfacp-col-full',
				),
				'shipping_first_name' => array(
					'class' => 'wfacp-col-left-half',
				),
				'shipping_last_name'  => array(
					'class' => 'wfacp-col-left-half',
				),
				'shipping_company'    => array(
					'class' => 'wfacp-col-full',
				),
				'shipping_address_1'  => array(
					'class' => 'wfacp-col-left-half',
				),
				'shipping_address_2'  => array(
					'class' => 'wfacp-col-left-half',
				),
				'shipping_country'    => array(
					'class' => 'wfacp-col-left-third',
				),
				'shipping_city'       => array(
					'class' => 'wfacp-col-left-half',
				),
				'shipping_postcode'   => array(
					'class' => 'wfacp-col-left-third',
				),
				'shipping_state'      => array(
					'class' => 'wfacp-col-left-third',
				),
				'shipping_phone'      => array(
					'class' => 'wfacp-col-full',
				),
				'order_comments'      => array(
					'class' => 'wfacp-col-full',
				),
			);
			$this->css_classes = apply_filters( 'wfacp_default_form_classes', $css_class );
		}

		public function replace_native_checkout_form( $template, $template_name ) {
			if ( 'checkout/form-checkout.php' === $template_name ) {
				return $this->wfacp_get_form();
			}

			return $template;
		}


		public function enqueue_style() {

			wp_enqueue_style( 'elementor-style', plugin_dir_url( WFACP_PLUGIN_FILE ) . 'assets/css/wfacp-form.min.css', array(), WFACP_VERSION, false );

			if ( is_rtl() ) {
				wp_enqueue_style( 'layout1-style-rtl', plugin_dir_url( WFACP_PLUGIN_FILE ) . 'assets/css/wfacp-form-style-rtl.css', '', WFACP_VERSION, false );
			}
		}

		public function get_view( $template ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		}

		public function remove_theme_styling( $bool, $path, $url, $currentEle ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			if ( false !== strpos( $url, '/themes/' ) ) {
				return false;
			}

			return $bool;
		}

		/**
		 * Prints the header content in the <head> section of the checkout template.
		 *
		 * This function triggers the 'wfacp_header_print_in_head' action hook, allowing other code to add custom header content.
		 */
		public function wfacp_header_print_in_head() {
			do_action( 'wfacp_header_print_in_head' );
		}

		/**
		 * Calls the 'wfacp_footer_before_print_scripts' action hook.
		 *
		 * This function is responsible for executing any callbacks hooked to the 'wfacp_footer_before_print_scripts' action hook.
		 * It is typically used to add custom scripts or perform additional actions before printing scripts in the footer of the checkout template.
		 *
		 * @since 1.0.0
		 */
		public function wfacp_footer_before_print_scripts() {
			do_action( 'wfacp_footer_before_print_scripts' );
		}

		/**
		 * Checks if a specific feature is enabled.
		 *
		 * @param string $feature The name of the feature to check.
		 *
		 * @return bool Returns true if the feature is enabled, false otherwise.
		 */
		private function is_feature_enabled( $feature ) {
			return isset( $this->mini_cart_data[ $feature ] ) ? $this->mini_cart_data[ $feature ] : false;
		}

		/**
		 * Activates the theme hook.
		 *
		 * This function checks if the 'flatsome_mobile_menu' function exists and adds it as an action to the 'wp_footer' hook.
		 * This is typically used to activate the mobile menu functionality in the Flatsome theme.
		 *
		 * @return void
		 */
		public function activate_theme_hook() {
			if ( function_exists( 'flatsome_mobile_menu' ) ) {
				add_action( 'wp_footer', 'flatsome_mobile_menu' );
			}
		}

		/**
		 * Retrieves the mini cart heading.
		 *
		 * @return string The mini cart heading.
		 */
		public function mini_cart_heading() {
			return isset( $this->mini_cart_data['mini_cart_heading'] ) ? $this->mini_cart_data['mini_cart_heading'] : '';
		}

		/**
		 * Retrieves the value of the 'enable_product_image' property from the mini cart data.
		 *
		 * @return bool Returns the value of the 'enable_product_image' property. If the property is not set, returns false.
		 */
		public function mini_cart_allow_product_image() {
			return $this->is_feature_enabled( 'enable_product_image' );
		}

		/**
		 * Determines whether the quantity box is allowed in the mini cart.
		 *
		 * @return bool True if the quantity box is allowed, false otherwise.
		 */
		public function mini_cart_allow_quantity_box() {
			return $this->is_feature_enabled( 'enable_quantity_box' );
		}

		/**
		 * Determines whether the mini cart allows item deletion.
		 *
		 * @return bool Returns true if the feature to enable item deletion is enabled, false otherwise.
		 */
		public function mini_cart_allow_deletion() {
			return $this->is_feature_enabled( 'enable_delete_item' );
		}

		/**
		 * Determines whether the mini cart allows applying a coupon.
		 *
		 * @return bool True if the feature is enabled, false otherwise.
		 */
		public function mini_cart_allow_coupon() {
			return $this->is_feature_enabled( 'enable_coupon' );
		}

		/**
		 * Determines if the mini cart should enable the collapsible coupon feature.
		 *
		 * @return bool True if the feature is enabled, false otherwise.
		 */
		public function mini_cart_collapse_enable_coupon_collapsible() {
			return $this->is_feature_enabled( 'enable_coupon_collapsible' );
		}

		/**
		 * Determines whether to display the image in the collapsible order summary.
		 *
		 * @return bool True if the feature is enabled, false otherwise.
		 */
		public function display_image_in_collapsible_order_summary() {
			return isset( $this->form_data['order_summary_enable_product_image_collapsed'] ) && $this->form_data['order_summary_enable_product_image_collapsed'];
		}

		/**
		 * Change the column span for the order summary.
		 *
		 * This function is responsible for modifying the column span attribute for the order summary section in the checkout template.
		 *
		 * @param string $colspan_attr1 The original column span attribute.
		 *
		 * @return string The modified column span attribute.
		 */
		public function change_col_span_for_order_summary( $colspan_attr1 ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			return '';
		}

		/**
		 * Retrieves the mobile style for the product switcher.
		 *
		 * This method checks if the 'product_switcher_mobile_style' key is set in the form data and returns its value if it is not empty.
		 * If the key is not set or its value is empty, it calls the parent class method to get the default mobile style.
		 *
		 * @return string The mobile style for the product switcher.
		 */
		public function get_product_switcher_mobile_style() {
			if ( isset( $this->form_data['product_switcher_mobile_style'] ) && $this->form_data['product_switcher_mobile_style'] !== '' ) {
				return $this->form_data['product_switcher_mobile_style'];
			}

			return parent::get_product_switcher_mobile_style();
		}

		/**
		 * Adds a body class for the WFACP Bricks template.
		 *
		 * This function adds the 'wfacp_bricks_template' class to the body classes array.
		 * It extends the parent class's add_body_class() method to include the additional class.
		 *
		 * @param array $classes The array of body classes.
		 *
		 * @return array The modified array of body classes.
		 */
		public function add_body_class( $classes ) {
			$classes   = parent::add_body_class( $classes );
			$classes[] = 'wfacp_bricks_template';

			return $classes;
		}

		/**
		 * Wrap Order preview form in Embed form div start style
		 */
		public function add_checkout_preview_div_start() {
			echo '<div id="wfacp-e-form">';
		}

		/**
		 * Wrap Order preview form in Embed form div start style
		 */
		public function add_checkout_preview_div_end() {
			echo '</div>';
		}

		/**
		 * Runs the Divi styling for the checkout template.
		 *
		 * This function checks if the 'et_divi_add_customizer_css' function exists and calls it to add customizer CSS for Divi.
		 * This is useful for applying Divi styling to the checkout template.
		 */
		public function run_divi_styling() {
			if ( function_exists( 'et_divi_add_customizer_css' ) ) {
				et_divi_add_customizer_css();
			}
		}

		/**
		 * Cart Link before the step bar
		 */
		public function before_cart_link() {
			$is_global_checkout = WFACP_Core()->public->is_checkout_override();

			if ( $is_global_checkout === false ) {
				return;
			}

			if ( ! isset( $this->form_data['step_cart_link_enable'] ) || ! $this->form_data['step_cart_link_enable'] ) {
				return;
			}

			if ( ! isset( $this->form_data['select_type'] ) ) {
				return;
			}

			$select_type = $this->form_data['select_type'];
			$key         = 'step_cart_' . $select_type . '_link';

			if ( ! isset( $this->form_data[ $key ] ) ) {
				return;
			}

			$cartName = $this->form_data[ $key ];

			$cart_page_id = wc_get_page_id( 'cart' );
			$cartURL      = $cart_page_id ? get_permalink( $cart_page_id ) : '';

			echo "<li class='df_cart_link wfacp_bred_visited'><a class='wfacp_cart_link wfacp_breadcrumb_link' href='$cartURL'>$cartName</a></li>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Renders the "Return to Cart" link before the checkout form.
		 *
		 * This function checks if the global checkout is enabled and if the "Return to Cart" link is enabled in the form data.
		 * If both conditions are met, it renders the link with the specified text and URL.
		 *
		 * @param string $current_action The current checkout action.
		 *
		 * @return void
		 */
		public function before_return_to_cart_link( $current_action ) {
			$is_global_checkout = WFACP_Core()->public->is_checkout_override();

			if ( $is_global_checkout === false ) {
				return;
			}

			if ( ! isset( $this->form_data['step_cart_link_enable'] ) || ! $this->form_data['step_cart_link_enable'] ) {
				return;
			}

			if ( ! isset( $this->form_data['return_to_cart_text'] ) || ! $this->form_data['return_to_cart_text'] ) {
				return;
			}

			if ( $current_action !== 'single_step' ) {
				return;
			}

			$cart_page_id = wc_get_page_id( 'cart' );
			$cartURL      = $cart_page_id ? get_permalink( $cart_page_id ) : '';
			?>

            <div class="btm_btn_sec wfacp_back_cart_link">
                <div class="wfacp-back-btn-wrap">
                    <a href="<?php echo apply_filters( 'wfacp_return_to_cart_link', $cartURL ); ?>"><?php echo $this->form_data['return_to_cart_text']; ?></a> <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
			<?php
		}

		/**
		 * Adds form steps and progress bar to the checkout template.
		 *
		 * @return void
		 */
		public function add_form_steps() {
			$number_of_steps = $this->get_step_count();
			$steps_arr       = array( 'single_step', 'two_step', 'third_step' );

			$devices = array();

			if ( $number_of_steps <= 1 || ! isset( $this->form_data['enable_progress_bar'] ) || ! $this->form_data['enable_progress_bar'] ) {
				return;
			}

			// TODO: Need to do more work on it to conditionally enable for mobile and tablet.
			if ( $this->form_data['enable_progress_bar'] ) {
				$devices[] = 'wfacp_desktop';
			}

			if ( isset( $this->form_data['enable_progress_bar:tablet_portrait'] ) && $this->form_data['enable_progress_bar:tablet_portrait'] ) {
				$devices[] = 'wfacp_tablet';
			}

			if ( isset( $this->form_data['enable_progress_bar:mobile_landscape'] ) && $this->form_data['enable_progress_bar:mobile_landscape'] ) {
				$devices[] = 'wfacp_mobile_landscape';
			}

			if ( isset( $this->form_data['enable_progress_bar:mobile_portrait'] ) && $this->form_data['enable_progress_bar:mobile_portrait'] ) {
				$devices[] = 'wfacp_mobile';
			}

			$deviceClass = implode( ' ', $devices );

			if ( empty( $deviceClass ) ) {
				$deviceClass = 'wfacp_not_active';
			}

			$select_type = $this->form_data['select_type'];

			echo "<div class='$deviceClass $select_type' >"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( isset( $this->form_data['select_type'] ) && 'bredcrumb' === $this->form_data['select_type'] ) {
				if ( isset( $this->set_bredcrumb_data['progress_data'] ) && is_array( $this->set_bredcrumb_data['progress_data'] ) && $this->set_bredcrumb_data['progress_data'] > 0 ) {
					$progress_form_data = $this->set_bredcrumb_data['progress_data'];

					printf( '<div class="%s">', 'wfacp_steps_wrap wfacp_breadcrumb_wrap_here' );
					echo '<div class=wfacp_steps_sec id="wfacp_steps_sec">';

					echo '<ul>';

					do_action( 'wfacp_before_breadcrumb', $progress_form_data );

					$active_breadcrumb = apply_filters( 'wfacp_el_bread_crumb_active_class_key', 0, $this );
					foreach ( $progress_form_data as $key => $value ) {
						$active        = '';
						$bread_visited = '';
						if ( $active_breadcrumb > $key ) {
							$bread_visited = 'wfacp_bred_visited';
						}
						if ( $key === $active_breadcrumb ) {
							$active = 'wfacp_bred_active wfacp_bred_visited';
						}

						$step       = ( isset( $steps_arr[ $key ] ) ) ? $steps_arr[ $key ] : '';
						$text_class = ( ! empty( $value ) ) ? 'wfacp_step_text_have' : 'wfacp_step_text_nohave';
						echo "<li class='wfacp_step_$key wfacp_bred $bread_visited $active $step' step='$step'>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
                        <a href='javascript:void(0)' class="<?php echo $text_class; ?> wfacp_breadcrumb_link" data-text="<?php echo sanitize_title( $value ); ?>"><?php echo $value; ?></a> <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php

						echo '</li>';
					}

					do_action( 'wfacp_after_breadcrumb' );

					echo '</ul></div></div>';
				}
			}

			echo '</div>';
		}


		/**
		 * Displays the progress bar on the checkout template.
		 *
		 * This method checks if the progress bar is set in the steps data and if the select type is set to 'progress_bar' in the form data.
		 * If both conditions are met, it echoes the progress bar HTML.
		 */
		public function display_progress_bar() {
			if ( isset( $this->stepsData['progress_bar'] ) ) {
				if ( isset( $this->form_data['select_type'] ) && 'progress_bar' === $this->form_data['select_type'] ) {
					echo $this->stepsData['progress_bar']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}

		/**
		 * Adds a class and changes the place order button HTML.
		 *
		 * @param string $btn_html The HTML of the place order button.
		 *
		 * @return string The modified HTML of the place order button.
		 */
		public function add_class_change_place_order( $btn_html ) {
			$stepCount = $this->get_step_count();

			if ( ! empty( $_GET['woo-paypal-return'] ) && ! empty( $_GET['token'] ) && ! empty( $_GET['PayerID'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $btn_html;
			}

			$output = '';

			$key = 'payment_button_back_' . $stepCount . '_text';

			$black_backbtn_cls = '';
			if ( isset( $this->form_data[ $key ] ) && $this->form_data[ $key ] === '' ) {
				$black_backbtn_cls = 'wfacp_back_link_empty';
			}

			/* Button Icon list */

			$class = $this->add_button_icon( 'place_order' );

			$this->button_icon_subheading_styling( $class, $this->current_step );

			$output .= sprintf( '<div class="wfacp-order-place-btn-wrap %s">', trim( $black_backbtn_cls ) );
			$output .= $btn_html;

			$output .= '</div>';

			if ( $stepCount > 1 ) {
				if ( ! isset( $this->form_data[ $key ] ) ) {
					return $btn_html;
				}
				$back_btn_text = $this->form_data[ $key ];

				$last_step = 'single_step';
				if ( $this->current_step === 'third_step' ) {
					$last_step = 'two_step';
				}

				if ( $back_btn_text !== '' ) {
					$output .= "<div class='place_order_back_btn wfacp_none_class '><a class='wfacp_back_page_button' data-next-step='" . $last_step . "' data-current-step='" . $this->current_step . "' href='javascript:void(0)'>" . $back_btn_text . '</a> </div>';
				}
			}

			return $output;
		}

		/* Coupon Button Text */
		public function get_collapsible_coupon_button_text() {
			if ( isset( $this->form_data['collapse_coupon_button_text'] ) && '' !== $this->form_data['collapse_coupon_button_text'] ) {
				return $this->form_data['collapse_coupon_button_text'];
			}

			return parent::get_coupon_button_text();
		}

		/**
		 * Retrieves the text for the coupon button in the form.
		 *
		 * If the form data contains a custom text for the coupon button, it returns that text.
		 * Otherwise, it calls the parent class method to get the default coupon button text.
		 *
		 * @return string The text for the coupon button.
		 */
		public function get_form_coupon_button_text() {

			if ( isset( $this->form_data['form_coupon_button_text'] ) && '' !== $this->form_data['form_coupon_button_text'] ) {
				return $this->form_data['form_coupon_button_text'];
			}

			return parent::get_coupon_button_text();
		}

		/**
		 * Retrieves the text for the mini cart coupon button.
		 *
		 * @return string The text for the mini cart coupon button.
		 */
		public function get_mini_cart_coupon_button_text() {
			// Check if the mini cart coupon button text is set and not empty, then return it
			if ( ! empty( $this->mini_cart_data['mini_cart_coupon_button_text'] ) ) {
				return $this->mini_cart_data['mini_cart_coupon_button_text'];
			}

			// Fallback to the parent's coupon button text if not set
			return parent::get_coupon_button_text();
		}

		/**
		 * Resets the session variables related to order total widgets and minimum cart widgets.
		 *
		 * This function clears the session variables 'wfacp_order_total_widgets' and 'wfacp_min_cart_widgets',
		 * effectively resetting them to an empty array.
		 *
		 * @return void
		 */
		public function reset_session() {
			WFACP_Common::set_session( 'wfacp_order_total_widgets', array() );
			WFACP_Common::set_session( 'wfacp_min_cart_widgets', array() );
		}

		/**
		 * Retrieves the Elementor localize data for the checkout template.
		 *
		 * This function retrieves the necessary data for localizing the checkout template in Elementor.
		 * It checks if the 'wfacp_make_button_sticky_on_mobile' field is set in the form data and adds it to the $localData array.
		 * Finally, it localizes the 'wfacp_checkout_js' script with the 'wfacp_elementor_data' object.
		 *
		 * @return void
		 */
		public function get_elementor_localize_data() {
			$localData = array();
			if ( isset( $this->form_data['wfacp_make_button_sticky_on_mobile'] ) && true === $this->form_data['wfacp_make_button_sticky_on_mobile'] ) {
				$localData['wfacp_make_button_sticky_on_mobile'] = "yes";
			}

			wp_localize_script( 'wfacp_checkout_js', 'wfacp_elementor_data', $localData );
		}

		/**
		 * This function is responsible for rendering the start of an element before the form in the template file.
		 *
		 * @return void
		 */
		public function element_start_before_the_form() {
			$template_slug = $this->get_template_slug();

			if ( strpos( $template_slug, 'brick_1' ) !== false ) {
				echo "<div id=wfacp-e-form><div id='wfacp-sec-wrapper'>";
				$this->breadcrumb_start();
				$template       = wfacp_template();
				$label_position = '';
				if ( isset( $this->form_data['wfacp_label_position'] ) ) {
					$label_position = $this->form_data['wfacp_label_position'];
				}

				if ( is_array( $this->form_data ) ) {
					$enable_callapse_order_summary = false;
					$mbDevices                     = array( 'wfacp_collapsible_order_summary_wrap', $label_position );

					if ( isset( $this->form_data['enable_callapse_order_summary'] ) && $this->form_data['enable_callapse_order_summary'] ) {
						$mbDevices[]                   = 'wfacp_desktop';
						$enable_callapse_order_summary = true;
					}

					if ( isset( $this->form_data['enable_callapse_order_summary:tablet_portrait'] ) && $this->form_data['enable_callapse_order_summary:tablet_portrait'] ) {
						$mbDevices[]                   = 'wfacp_tablet';
						$enable_callapse_order_summary = true;
					}

					if ( isset( $this->form_data['enable_callapse_order_summary:mobile_landscape'] ) && $this->form_data['enable_callapse_order_summary:mobile_landscape'] ) {
						$mbDevices[]                   = 'wfacp_mobile_landscape';
						$enable_callapse_order_summary = true;
					}

					if ( isset( $this->form_data['enable_callapse_order_summary:mobile_portrait'] ) && $this->form_data['enable_callapse_order_summary:mobile_portrait'] ) {
						$mbDevices[]                   = 'wfacp_mobile';
						$enable_callapse_order_summary = true;
					}

					$deviceClass = implode( ' ', $mbDevices );

					if ( empty( $deviceClass ) ) {
						$deviceClass = 'wfacp_not_active';
					}

					if ( $enable_callapse_order_summary ) {
						echo "<div class='" . $deviceClass . "'>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						$template->get_mobile_mini_cart( $this->form_data );
						echo '</div>';
					}
				}

				echo "<div class='" . implode( ' ', array( 'wfacp-form', $label_position ) ) . "'>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Closes the HTML elements after the form in the checkout template.
		 *
		 * This function checks the template slug and if it contains 'brick_1', it closes the necessary HTML elements.
		 *
		 * @return void
		 */
		public function element_end_after_the_form() {
			$template_slug = $this->get_template_slug();
			if ( strpos( $template_slug, 'brick_1' ) !== false ) {
				echo '</div></div></div>';
			}
		}

		/**
		 * Renders the breadcrumb section of the checkout template.
		 *
		 * This function generates the breadcrumb section of the checkout template based on the form data and settings.
		 * It determines the number of steps, the progress bar type, and the devices to display the progress bar on.
		 * It also retrieves the step headings, subheadings, and progress bar texts from the form data.
		 * The breadcrumb section is then rendered based on the retrieved data.
		 *
		 * @since 1.0.0
		 */
		public function breadcrumb_start() {
			$number_of_steps    = $this->get_step_count();
			$step_form_data     = array();
			$progress_form_data = array();

			if ( empty( $this->form_data['enable_progress_bar'] ) && empty( $this->form_data['enable_progress_bar:tablet_portrait'] ) && empty( $this->form_data['enable_progress_bar:mobile_landscape'] ) && empty( $this->form_data['enable_progress_bar:mobile_portrait'] ) ) {
				return;
			}

			$cls = 'wfacp_one_step';
			if ( $number_of_steps === 2 ) {
				$cls = 'wfacp_two_step';
			} elseif ( $number_of_steps === 3 ) {
				$cls = 'wfacp_three_step';
			}

			$progress_bar_type = isset( $this->form_data['select_type'] ) ? $this->form_data['select_type'] : '';
			$devices           = array( $progress_bar_type );

			if ( isset( $this->form_data['enable_progress_bar'] ) && $this->form_data['enable_progress_bar'] ) {
				$devices[] = 'wfacp_desktop';
			}

			if ( isset( $this->form_data['enable_progress_bar:tablet_portrait'] ) && $this->form_data['enable_progress_bar:tablet_portrait'] ) {
				$devices[] = 'wfacp_tablet';
			}

			if ( isset( $this->form_data['enable_progress_bar:mobile_landscape'] ) && $this->form_data['enable_progress_bar:mobile_landscape'] ) {
				$devices[] = 'wfacp_mobile_landscape';
			}

			if ( isset( $this->form_data['enable_progress_bar:mobile_portrait'] ) && $this->form_data['enable_progress_bar:mobile_portrait'] ) {
				$devices[] = 'wfacp_mobile';
			}

			$deviceClass = implode( ' ', $devices );
			$wrapClass   = array();

			if ( ! empty( $cls ) ) {
				$wrapClass[] = $cls;
			}

			if ( empty( $deviceClass ) ) {
				$deviceClass = 'wfacp_not_active';
			}

			$wrapClass[] = $deviceClass;

			$stepWrapClass = '';
			if ( is_array( $wrapClass ) && count( $wrapClass ) > 0 ) {
				$stepWrapClass = implode( ' ', $wrapClass );
			}

			ob_start();
			echo "<div class='$stepWrapClass'>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			for ( $i = 0; $i < $number_of_steps; $i ++ ) {
				$tab_heading_key    = '';
				$tab_subheading_key = '';

				$progress_bar_text = '';

				if ( 'tab' === $progress_bar_type ) {
					$tab_heading_key    = 'step_' . $i . '_heading';
					$tab_subheading_key = 'step_' . $i . '_subheading';
				}

				if ( $tab_heading_key !== '' && is_array( $this->form_data ) && isset( $this->form_data[ $tab_heading_key ] ) ) {
					$step_form_data[ $i ]['heading'] = $this->form_data[ $tab_heading_key ];
				}
				if ( $tab_subheading_key !== '' && is_array( $this->form_data ) && isset( $this->form_data[ $tab_subheading_key ] ) ) {
					$step_form_data[ $i ]['subheading']   = $this->form_data[ $tab_subheading_key ];
					$this->set_bredcrumb_data['tab_data'] = $step_form_data;
				}
				if ( 'tab' !== $progress_bar_type ) {
					$progress_bar_text = 'step_' . $i . '_progress_bar';
				}

				if ( isset( $this->form_data['select_type'] ) && $this->form_data['select_type'] === 'bredcrumb' ) {
					$progress_bar_text = 'step_' . $i . '_bredcrumb';
				}

				if ( $progress_bar_text !== '' && is_array( $this->form_data ) && isset( $this->form_data[ $progress_bar_text ] ) ) {
					$progress_form_data[]                      = $this->form_data[ $progress_bar_text ];
					$this->set_bredcrumb_data['progress_data'] = $progress_form_data;
				}
			}

			if ( ( is_array( $step_form_data ) && count( $step_form_data ) > 0 ) ) {
				?>

                <div class="wfacp_form_steps">
                    <div class="wfacp-payment-title wfacp-hg-by-box">
                        <div class="wfacp-payment-tab-wrapper">
							<?php
							$count          = 1;
							$count_of_steps = sizeof( $step_form_data );
							$steps          = array( 'single_step', 'two_step', 'third_step' );

							$addfull_width = 'full_width_cls';
							if ( $count_of_steps === 2 ) {
								$addfull_width = 'wfacpef_two_step';
							}
							if ( $count_of_steps === 3 ) {
								$addfull_width = 'wfacpef_third_step';
							}
							$active_breadcrumb = apply_filters( 'wfacp_el_bread_crumb_active_class_key', 0, $this );
							foreach ( $step_form_data as $key => $value ) {
								if ( isset( $steps[ $key ] ) ) {
									$steps_count_here = $steps[ $key ];
								}

								$active        = '';
								$bread_visited = '';
								if ( $count === 2 ) {
									$page_class = 'two_step';
								} elseif ( $count === 3 ) {
									$page_class = 'third_step';
								} else {
									$page_class = 'single_step';
								}

								if ( $active_breadcrumb > $key ) {
									$bread_visited = 'visited_cls';
								}
								if ( $key === $active_breadcrumb ) {
									$active = 'wfacp-active visited_cls';
								}

								$activeClass = apply_filters( 'wfacp_embed_active_progress_bar', $active, $count, $number_of_steps );
								?>
                                <div class="wfacp-payment-tab-list <?php echo $activeClass . ' ' . $page_class . ' ' . $addfull_width . ' ' . $bread_visited; ?>  wfacp-tab<?php echo $count; ?>" step="<?php echo $steps_count_here; ?>"> <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <div class="wfacp-order2StepNumber"><?php echo $count; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                    <div class="wfacp-order2StepHeaderText">
										<?php if ( ! empty( $value['heading'] ) ) : ?>
                                            <div class="wfacp-order2StepTitle wfacp-order2StepTitleS1 wfacp_tcolor"><?php echo esc_html( $value['heading'] ); ?></div>
										<?php endif; ?>
										<?php if ( ! empty( $value['subheading'] ) ) : ?>
                                            <div class="wfacp-order2StepSubTitle wfacp-order2StepSubTitleS1 wfacp_tcolor"><?php echo esc_html( $value['subheading'] ); ?></div>
										<?php endif; ?>
                                    </div>
                                </div>
								<?php
								++ $count;
							}
							?>
                        </div>
                    </div>
                </div>
				<?php
			}

			$steps_arr = array( 'single_step', 'two_step', 'third_step' );
			if ( 'progress_bar' === $progress_bar_type ) {
				if ( ( is_array( $progress_form_data ) && count( $progress_form_data ) > 0 ) ) {
					echo '<div class="wfacp_custom_breadcrumb wfacp_custom_breadcrumb_el">';
					echo '<div class=wfacp_steps_wrap>';
					echo '<div class=wfacp_steps_sec>';

					echo '<ul>';

					do_action( 'wfacp_before_' . $progress_bar_type, $progress_form_data );

					foreach ( $progress_form_data as $key => $value ) {
						$active = '';

						if ( $key === 0 ) {
							$active = 'wfacp_bred_active wfacp_bred_visited';
						}

						$step = ( isset( $steps_arr[ $key ] ) ) ? $steps_arr[ $key ] : '';

						$active = apply_filters( 'wfacp_layout_9_active_progress_bar', $active, $step );

						echo "<li class='wfacp_step_$key wfacp_bred $active $step' step='$step' ><a href='javascript:void(0)' class='wfacp_step_text_have' data-text='" . sanitize_title( $value ) . "'>$value</a> </li>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					do_action( 'wfacp_after_breadcrumb' );
					echo '</ul></div></div></div>';
				}
			}
			echo '</div>';
			$result = ob_get_clean();

			$this->stepsData[ $progress_bar_type ] = $result;

			if ( 'progress_bar' !== $progress_bar_type ) {
				echo $result; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Retrieves the title for the mobile mini cart collapsible section.
		 *
		 * If the cart collapse title is set in the form data, it returns that value.
		 * Otherwise, it calls the parent class method to retrieve the title.
		 *
		 * @return string The title for the mobile mini cart collapsible section.
		 */
		public function get_mobile_mini_cart_collapsible_title() {
			if ( isset( $this->form_data['cart_collapse_title'] ) && '' !== $this->form_data['cart_collapse_title'] ) {
				return $this->form_data['cart_collapse_title'];
			}

			return parent::get_mobile_mini_cart_collapsible_title();
		}

		/**
		 * Retrieves the title for expanding the mobile mini cart.
		 *
		 * If the cart_expanded_title is set in the form data and is not empty, it returns that value.
		 * Otherwise, it calls the parent class's get_mobile_mini_cart_expand_title() method to get the title.
		 *
		 * @return string The title for expanding the mobile mini cart.
		 */
		public function get_mobile_mini_cart_expand_title() {
			if ( isset( $this->form_data['cart_expanded_title'] ) && '' !== $this->form_data['cart_expanded_title'] ) {
				return $this->form_data['cart_expanded_title'];
			}

			return parent::get_mobile_mini_cart_expand_title();
		}

		/**
		 * Determines whether to use the own template for checkout.
		 *
		 * @return bool Returns true if the own template should be used, false otherwise.
		 */
		public function use_own_template() {
			return false;
		}

		/**
		 * Calls the 'wfacp_footer_after_print_scripts' action hook.
		 *
		 * This function is used to trigger the 'wfacp_footer_after_print_scripts' action hook,
		 * allowing other functions or plugins to perform additional actions after printing scripts
		 * in the footer of the checkout template.
		 *
		 * @since 1.0.0
		 */
		public function wfacp_footer_after_print_scripts() {
			do_action( 'wfacp_footer_after_print_scripts' );
		}

		/**
		 * Adds the order summary to the sidebar.
		 */
		public function add_order_summary_to_sidebar() {

			if ( wfacp_pro_dependency() ) {
				include WFACP_BUILDER_DIR . '/customizer/templates/layout_9/views/template-parts/order-summary.php';
			}

		}

		/**
		 * Enable or disable the collapsed coupon field.
		 *
		 * This function checks if the 'collapse_enable_coupon' key is set in the form data.
		 * If it is set, the value of the 'collapse_enable_coupon' key is returned.
		 * If it is not set, false is returned.
		 *
		 * @return bool The value of the 'collapse_enable_coupon' key if set, false otherwise.
		 */
		public function enable_collapsed_coupon_field() {
			if ( isset( $this->form_data['collapse_enable_coupon'] ) ) {
				return $this->form_data['collapse_enable_coupon'];
			}

			return false;
		}

		/**
		 * Retrieves the value of the "collapse_enable_coupon_collapsible" property.
		 *
		 * This method checks if the "collapse_enable_coupon_collapsible" property is set in the form data.
		 * If it is set, the method returns its value. Otherwise, it returns false.
		 *
		 * @return bool The value of the "collapse_enable_coupon_collapsible" property.
		 */
		public function collapse_enable_coupon_collapsible() {
			if ( isset( $this->form_data['collapse_enable_coupon_collapsible'] ) ) {
				return $this->form_data['collapse_enable_coupon_collapsible'];
			}

			return false;
		}

		/**
		 * Retrieves the value of the "collapse_order_quantity_switcher" key from the form data.
		 *
		 * @return bool The value of the "collapse_order_quantity_switcher" key, or false if not set.
		 */
		public function collapse_order_quantity_switcher() {
			if ( isset( $this->form_data['collapse_order_quantity_switcher'] ) ) {
				return $this->form_data['collapse_order_quantity_switcher'];
			}

			return false;
		}

		/**
		 * Retrieves the value of the "collapse_order_delete_item" key from the form data.
		 *
		 * @return bool The value of the "collapse_order_delete_item" key, or false if not set.
		 */
		public function collapse_order_delete_item() {
			if ( isset( $this->form_data['collapse_order_delete_item'] ) ) {
				return $this->form_data['collapse_order_delete_item'];
			}

			return false;
		}

		/**
		 * Retrieves the CSS classes for a specific field in a checkout template.
		 *
		 * @param string $template_slug The slug of the checkout template.
		 * @param string $field_index The index of the field.
		 *
		 * @return string The CSS classes for the field.
		 */
		protected function get_field_css_ready( $template_slug, $field_index ) {
			if ( '' === $field_index ) {
				return '';
			}

			$field_key_index    = 'wfacp_' . $template_slug . '_' . $field_index . '_field';
			$field_custom_class = 'wfacp_' . $template_slug . '_' . $field_index . '_field_class';
			$field_class        = '';

			if ( isset( $this->form_data[ $field_key_index ] ) ) {
				$field_class = $this->form_data[ $field_key_index ];
			}

			if ( isset( $this->form_data[ $field_custom_class ] ) ) {
				$field_class .= ' ' . $this->form_data[ $field_custom_class ];
			}

			return $field_class;
		}

		/**
		 * Retrieves the payment method heading for the checkout template.
		 *
		 * If a custom payment method heading text is set in the form data, it will be returned.
		 * Otherwise, the parent's payment_heading() method will be called to retrieve the default heading.
		 *
		 * @return string The payment method heading text.
		 */
		public function payment_heading() {
			if ( isset( $this->form_data['wfacp_payment_method_heading_text'] ) && '' !== trim( $this->form_data['wfacp_payment_method_heading_text'] ) ) {
				return trim( $this->form_data['wfacp_payment_method_heading_text'] );
			}

			return parent::payment_heading();
		}

		/**
		 * Retrieves the payment sub-heading for the checkout template.
		 *
		 * This method checks if the 'wfacp_payment_method_subheading' key is set in the form data.
		 * If it is set, the method returns the trimmed value of the key.
		 * Otherwise, it calls the parent class's payment_sub_heading() method to retrieve the default payment sub-heading.
		 *
		 * @return string The payment sub-heading.
		 */
		public function payment_sub_heading() {
			if ( isset( $this->form_data['wfacp_payment_method_subheading'] ) ) {
				return trim( $this->form_data['wfacp_payment_method_subheading'] );
			}

			return '';
		}

		/**
		 * Retrieves the payment description for the checkout template.
		 *
		 * If the 'text_below_placeorder_btn' field is set in the form data, it returns the trimmed value of that field.
		 * Otherwise, it calls the parent class method to retrieve the payment description.
		 *
		 * @return string The payment description.
		 */
		public function get_payment_desc() {
			if ( isset( $this->form_data['text_below_placeorder_btn'] ) ) {
				return trim( $this->form_data['text_below_placeorder_btn'] );
			}

			return parent::get_payment_desc();
		}

		/**
		 * Retrieves the payment button text for the checkout template.
		 *
		 * @param [type] $name
		 * @param [type] $current_action
		 *
		 * @return void|string
		 */
		public function change_single_step_label( $name, $current_action ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			if ( isset( $this->form_data['wfacp_payment_button_1_text'] ) && '' !== trim( $this->form_data['wfacp_payment_button_1_text'] ) ) {
				return trim( $this->form_data['wfacp_payment_button_1_text'] );
			}

			return $name;
		}

		/**
		 * Retrieves the text for the second payment button in the checkout template.
		 *
		 * @param string $name The original text of the second payment button.
		 * @param string $current_action The current action in the checkout process.
		 *
		 * @return string The modified text of the second payment button.
		 */
		public function change_two_step_label( $name, $current_action ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			if ( isset( $this->form_data['wfacp_payment_button_2_text'] ) && '' !== trim( $this->form_data['wfacp_payment_button_2_text'] ) ) {
				return trim( $this->form_data['wfacp_payment_button_2_text'] );
			}

			return $name;
		}

		/**
		 * Changes the text of the place order button.
		 *
		 * This function is used to modify the text of the place order button in the checkout template.
		 * It checks if certain GET parameters are present and returns the original text if they are.
		 * Otherwise, it retrieves the order total and appends it to the custom text specified in the form data.
		 * The modified text is then assigned to the $place_order_btn_text property and returned.
		 *
		 * @param string $text The original text of the place order button.
		 *
		 * @return string The modified text of the place order button.
		 */
		public function change_place_order_button_text( $text ) {
			if ( ! empty( $_GET['woo-paypal-return'] ) && ! empty( $_GET['token'] ) && ! empty( $_GET['PayerID'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $text;
			}

			$order_total = '';
			if ( ! empty( $this->form_data['enable_price_in_place_order_button'] ) ) {
				$order_total = '&nbsp;&nbsp;' . WFACP_Common::wfacp_order_total( array() );
			}

			if ( isset( $this->form_data['wfacp_payment_place_order_text'] ) && '' !== trim( $this->form_data['wfacp_payment_place_order_text'] ) ) {
				$text = trim( $this->form_data['wfacp_payment_place_order_text'] ) . $order_total;
			}

			$this->place_order_btn_text = $text;

			return $text;
		}

		/**
		 * Retrieves the text for the payment button.
		 *
		 * If a custom payment button text is set in the form data, it will be returned.
		 * Otherwise, the default text "Place order" will be returned.
		 *
		 * @return string The text for the payment button.
		 */
		public function payment_button_text() {
			if ( isset( $this->form_data['wfacp_payment_place_order_text'] ) && '' !== trim( $this->form_data['wfacp_payment_place_order_text'] ) ) {
				return trim( $this->form_data['wfacp_payment_place_order_text'] );
			}

			return __( 'Pladdce order' );
		}

		/**
		 * Retrieves the payment button alignment for the checkout template.
		 *
		 * This method checks if the payment button alignment is set in the form data.
		 * If it is set and not empty, it returns the trimmed value.
		 * Otherwise, it calls the parent method to retrieve the default payment button alignment.
		 *
		 * @return string The payment button alignment.
		 */
		public function payment_button_alignment() {
			if ( isset( $this->form_data['wfacp_form_payment_button_alignment'] ) && '' !== trim( $this->form_data['wfacp_form_payment_button_alignment'] ) ) {
				return trim( $this->form_data['wfacp_form_payment_button_alignment'] );
			}

			return parent::payment_button_alignment();
		}

		/**
		 * Changes the label of the back button in the checkout template.
		 *
		 * This function takes three parameters: $text, $next_action, and $current_action.
		 * It determines the value of $i based on the value of $current_action and returns
		 * the corresponding label for the back button.
		 *
		 * @param string $text The default label of the back button.
		 * @param string $next_action The next action in the checkout process.
		 * @param string $current_action The current action in the checkout process.
		 *
		 * @return string The modified label of the back button.
		 */
		public function change_back_step_label( $text, $next_action, $current_action ) {
			$i = 1;
			if ( 'third_step' === $current_action ) {
				$i = 3;
			} elseif ( 'two_step' === $current_action ) {
				$i = 2;
			}
			$key = 'payment_button_back_' . $i . '_text';

			if ( isset( $this->form_data[ $key ] ) ) {
				return trim( $this->form_data[ $key ] );
			}

			return $text;
		}

		/**
		 * Adds a blank back text to the label based on the step and current step.
		 *
		 * @param string $label The label to be modified.
		 * @param string $step The current step of the checkout process.
		 * @param string $current_step The current step of the checkout process.
		 *
		 * @return string The modified label with a blank back text or the original label.
		 */
		public function add_blank_back_text( $label, $step, $current_step ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$i = 1;
			if ( 'third_step' === $step ) {
				$i = 3;
			} elseif ( 'two_step' === $step ) {
				$i = 2;
			}
			$key = 'payment_button_back_' . $i . '_text';

			if ( isset( $this->form_data[ $key ] ) && $this->form_data[ $key ] === '' ) {
				return 'wfacp_back_link_empty';
			}

			return $label;
		}

		/**
		 * Adds mini cart fragments to the existing fragments array.
		 *
		 * This function is responsible for adding mini cart fragments to the existing fragments array.
		 * It retrieves the mini cart widgets from the session and iterates through each widget to get the mini cart fragments.
		 * The mini cart fragments are then added to the existing fragments array.
		 *
		 * @param array $fragments The existing fragments array.
		 *
		 * @return array The updated fragments array.
		 */
		public function add_mini_cart_fragments( $fragments ) {
			$min_cart_key     = 'wfacp_mini_cart_widgets_' . $this->get_template_type();
			$min_cart_widgets = WFACP_Common::get_session( $min_cart_key );
			if ( ! empty( $min_cart_widgets ) ) {
				$min_cart_widgets = array_unique( $min_cart_widgets );
				foreach ( $min_cart_widgets as $widget_id ) {
					$fragments = $this->get_mini_cart_fragments( $fragments, $widget_id );
				}
			}

			return $fragments;
		}

		/*
		 * Hide product switcher if client use product switcher as widget
		 */
		public function hide_product_switcher( $fields, $key ) {
			$wfacp_id = WFACP_Common::get_id();
			if ( 'product_switching' === $key ) {
				$us_as_widget = get_post_meta( $wfacp_id, '_wfacp_el_product_switcher_us_a_widget', true );
				if ( 'yes' === $us_as_widget ) {
					$fields = array();
				}
			}

			return $fields;
		}

		/**
		 * Displays the order summary thumbnail.
		 *
		 * This function checks if the 'order_summary_enable_product_image' option is set in the form data.
		 * If it is set, it returns true. Otherwise, it returns the provided status.
		 *
		 * @param bool $status The current status.
		 *
		 * @return bool The updated status.
		 */
		public function display_order_summary_thumb( $status ) {
			if ( isset( $this->form_data['order_summary_enable_product_image'] ) ) {
				return $this->form_data['order_summary_enable_product_image'];
			}

			return $status;
		}

		/**
		 * Adds the order summary fragment to the WooCommerce checkout page.
		 *
		 * @param array $fragments The array of fragments to be updated.
		 *
		 * @return array The updated array of fragments.
		 */
		public function add_fragment_order_summary( $fragments ) {
			$input_data = $this->form_data;
			if ( wfacp_pro_dependency() ) {
				$path = WFACP_BUILDER_DIR . '/customizer/templates/layout_9';

			} else {
				$path = WFACP_PLUGIN_DIR . '/public/global/collapsible-order-summary/';

			}

			if ( isset( $this->checkout_fields['advanced'] ) && isset( $this->checkout_fields['advanced']['order_summary'] ) ) {
				ob_start();
				if ( wfacp_pro_dependency() ) {
					include WFACP_BUILDER_DIR . '/customizer/templates/layout_9/views/template-parts/main-order-summary.php';

				} else {
					include WFACP_PLUGIN_DIR . '/public/global/order-summary/order-summary.php';

				}
				$fragments['.wfacp_order_summary'] = ob_get_clean();
			}

			$mbDevices = array();
			if ( isset( $this->form_data['enable_callapse_order_summary'] ) && $this->form_data['enable_callapse_order_summary'] ) {
				$mbDevices[] = 'wfacp_desktop';
			}

			if ( isset( $this->form_data['enable_callapse_order_summary:tablet_portrait'] ) && $this->form_data['enable_callapse_order_summary:tablet_portrait'] ) {
				$mbDevices[] = 'wfacp_tablet';
			}

			if ( isset( $this->form_data['enable_callapse_order_summary:mobile_landscape'] ) && $this->form_data['enable_callapse_order_summary:mobile_landscape'] ) {
				$mbDevices[] = 'wfacp_mobile_landscape';
			}

			if ( isset( $this->form_data['enable_callapse_order_summary:mobile_portrait'] ) && $this->form_data['enable_callapse_order_summary:mobile_portrait'] ) {
				$mbDevices[] = 'wfacp_mobile';
			}

			if ( empty( $mbDevices ) ) {
				return $fragments;
			}


			if ( wfacp_pro_dependency() ) {
				ob_start();
				include $path . '/views/template-parts/order-review.php';
				$fragments['.wfacp_mb_mini_cart_sec_accordion_content .wfacp_template_9_cart_item_details'] = ob_get_clean();

				ob_start();
				include $path . '/views/template-parts/order-total.php';
				$fragments['.wfacp_mb_mini_cart_sec_accordion_content .wfacp_template_9_cart_total_details'] = ob_get_clean();

				ob_start();
				include $path . '/views/template-parts/order-total.php';
				$fragments['.wfacp_mb_mini_cart_sec_accordion_content .wfacp_mini_cart_reviews'] = ob_get_clean();

				ob_start();
				wc_cart_totals_order_total_html();
				$fragments['.wfacp_cart_mb_fragment_price'] = ob_get_clean();

				$order_summary_cart_price            = apply_filters( 'wfacp_collapsible_order_summary_cart_price', wc_price( WC()->cart->total ) );
				$fragments['.wfacp_show_price_wrap'] = '<div class="wfacp_show_price_wrap">' . do_action( 'wfacp_before_mini_price' ) . '<strong>' . $order_summary_cart_price . '</strong>' . do_action( 'wfacp_after_mini_price' ) . '</div>';

				return $fragments;
			} else {
				$path = WFACP_PLUGIN_DIR . '/public/global/collapsible-order-summary/';
				ob_start();
				include $path . '/order-review.php';
				$fragments['.wfacp_mb_mini_cart_sec_accordion_content .wfacp_template_9_cart_item_details'] = ob_get_clean();

				ob_start();
				include $path . '/order-total.php';
				$fragments['.wfacp_mb_mini_cart_sec_accordion_content .wfacp_template_9_cart_total_details'] = ob_get_clean();

				ob_start();
				wc_cart_totals_order_total_html();
				$fragments['.wfacp_cart_mb_fragment_price'] = ob_get_clean();

				$order_summary_cart_price            = apply_filters( 'wfacp_collapsible_order_summary_cart_price', wc_price( WC()->cart->total ) );
				$fragments['.wfacp_show_price_wrap'] = '<div class="wfacp_show_price_wrap">' . do_action( "wfacp_before_mini_price" ) . '<strong>' . $order_summary_cart_price . '</strong>' . do_action( 'wfacp_after_mini_price' ) . '</div>';


				return $fragments;
			}


		}

		/**
		 * Renders the layout for the order summary field.
		 *
		 * This method is responsible for rendering the layout for the order summary field in the checkout template.
		 * It sets the order summary data in the WooCommerce session and includes the main order summary template file.
		 *
		 * @param string $field The field name.
		 * @param string $key The field key.
		 * @param array $args The field arguments.
		 * @param mixed $value The field value.
		 */
		public function layout_order_summary( $field, $key, $args, $value ) {
			if ( 'order_summary' === $key ) {
				WC()->session->set( 'wfacp_order_summary_' . WFACP_Common::get_id(), $args );
				if ( wfacp_pro_dependency() ) {
					include WFACP_BUILDER_DIR . '/customizer/templates/layout_9/views/template-parts/main-order-summary.php';

				} else {
					include WFACP_PLUGIN_DIR . '/public/global/order-summary/order-summary.php';

				}
			}
		}


		/**
		 * Adds button icon and subheading for the checkout template.
		 *
		 * @param int $i The step number.
		 *
		 * @return array The array containing the button icon and subheading details.
		 */
		public function add_button_icon( $i = 1 ) {
			$black_backbtn_cls = array(
				'class' => 'bwf_button_sec',
				'step'  => $i,
			);
			$icon_position     = 'wfacp-pre-icon';

			if ( ! empty( $this->form_data[ 'enable_icon_with_place_order_' . $i ] ) ) {
				$content = 'before';
				$margin  = 'right';
				if ( $icon_position === 'wfacp-post-icon' ) {
					$content = 'after';
					$margin  = 'left';
				}

				$black_backbtn_cls['position'] = $icon_position;
				$black_backbtn_cls['content']  = $content;
				$black_backbtn_cls['margin']   = $margin;
			}

			if ( isset( $this->form_data[ 'icons_with_place_order_list_' . $i ] ) ) {
				$black_backbtn_cls['icon'] = $this->form_data[ 'icons_with_place_order_list_' . $i ];

				if ( strpos( $black_backbtn_cls['icon'], '"\"' ) === false ) {
					$black_backbtn_cls['icon'] = '"\"' . $black_backbtn_cls['icon'];
					$black_backbtn_cls['icon'] = str_replace( '"', '', $black_backbtn_cls['icon'] );
				}
			}

			/* button subheading */

			$black_backbtn_cls['button_subheading'] = '';
			if ( isset( $this->form_data[ 'step_' . $i . '_text_after_place_order' ] ) && ! empty( $this->form_data[ 'step_' . $i . '_text_after_place_order' ] ) ) {
				$black_backbtn_cls['button_subheading']          = $this->form_data[ 'step_' . $i . '_text_after_place_order' ];
				$black_backbtn_cls['button_subheading_position'] = 'after';
				if ( ! empty( $icon_position ) ) {
					if ( $icon_position === 'wfacp-pre-icon' ) {
						$black_backbtn_cls['button_subheading_position'] = 'after';
					} else {
						$black_backbtn_cls['button_subheading_position'] = 'before';
					}
				}
			}

			return $black_backbtn_cls;
		}

		/**
		 * Displays the button icon for step 1.
		 *
		 * @param string $current The current step.
		 *
		 * @return void
		 */
		public function display_button_icon_step_1( $current ) {
			$class = $this->add_button_icon( 1 );

			$this->button_icon_subheading_styling( $class, $current );
		}

		/**
		 * Displays the button icon for step 2.
		 *
		 * @param string $current The current step.
		 *
		 * @return void
		 */
		public function display_button_icon_step_2( $current ) {
			$class = $this->add_button_icon( 2 );
			$this->button_icon_subheading_styling( $class, $current );
		}

		/**
		 * This function is responsible for styling the button icon and subheading.
		 *
		 * @param array $class The class array containing the styling properties.
		 * @param string $current The current class name.
		 *
		 * @return void
		 */
		public function button_icon_subheading_styling( $class, $current ) {
			$icon                       = '';
			$content                    = '';
			$margin                     = '';
			$button_subheading          = '';
			$button_subheading_position = '';

			if ( isset( $class['icon'] ) ) {
				$icon = str_replace( 'aero-', '', $class['icon'] );
			}
			if ( isset( $class['content'] ) ) {
				$content = $class['content'];
			}
			if ( isset( $class['margin'] ) ) {
				$margin = $class['margin'];
			}
			if ( isset( $class['button_subheading'] ) ) {
				$button_subheading = $class['button_subheading'];
			}
			if ( isset( $class['button_subheading_position'] ) ) {
				$button_subheading_position = $class['button_subheading_position'];
			}
			if ( isset( $class['step'] ) ) {
				$form_step = $class['step'];
			}

			if ( ! empty( $icon ) && ! empty( $current ) && ! empty( $margin ) ) {
				if ( $form_step === 'place_order' ) {
					echo '<style>';
					echo 'body #wfacp-e-form .' . $current . ' #place_order:' . $content . "{content:'$icon';font-family: 'bwf-icons'; display: inline-block;margin-$margin:8px;position: relative;text-transform: none;top:1px;}"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</style>';
				} else {
					echo '<style>';
					echo 'body #wfacp-e-form .' . $current . ' .wfacp-next-btn-wrap button:' . $content . "{content:'$icon'; font-family: 'bwf-icons'; display: inline-block;margin-$margin:8px;position: relative;text-transform: none;top:1px;}"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</style>';
				}
			}

			if ( ! empty( $button_subheading ) && ! empty( $button_subheading_position ) ) {
				$content           = $button_subheading_position;
				$button_subheading = do_shortcode( $button_subheading );
				$content1          = 'before';
				if ( $form_step === 'place_order' ) {
					echo '<style>';
					echo 'body #wfacp-e-form .' . $current . ' #place_order:' . $content1 . '{top:4px;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '#wfacp-e-form .' . $current . ' #place_order:' . $content . '{content:' . '"' . $button_subheading . '"' . '; display: inline-block ;position: relative;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '#wfacp-e-form .' . $current . ' button#place_order' . '{display:inline-block;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '#wfacp-e-form .' . $current . ' #place_order:' . $content . '{display: block;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</style>';
				} else {
					echo '<style>';
					echo 'body #wfacp-e-form .' . $current . ' .wfacp-next-btn-wrap button:' . $content1 . '{top:4px;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '#wfacp-e-form .' . $current . ' .wfacp-next-btn-wrap button:' . $content . '{content:' . '"' . $button_subheading . '"' . ';  display: inline-block ;position: relative;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '#wfacp-e-form .' . $current . ' .wfacp-next-btn-wrap button' . '{display:inline-block;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '#wfacp-e-form .' . $current . ' .wfacp-next-btn-wrap button:' . $content . '{display: block;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</style>';
				}
			} elseif ( $form_step === 'place_order' ) {
				echo '<style>';
				echo '#wfacp-e-form .' . $current . ' #place_order' . '{-js-display: inline-flex;display: inline-flex;align-items: center;justify-content: center;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				echo '</style>';
			} else {
				echo '<style>';
				echo '#wfacp-e-form .' . $current . ' .wfacp-next-btn-wrap button' . '{-js-display: inline-flex;display: inline-flex;align-items: center;justify-content: center;}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</style>';
			}
		}

		/*------------------------------Primay Color Fields-------------------------------------*/
		public function primary_colors() {
			$template = wfacp_template();

			$primary_color_value = '';

			if ( isset( $template->form_data['default_primary_color'], $template->form_data['default_primary_color']['hex'] ) ) {
				$primary_color_value = $template->form_data['default_primary_color']['hex'];
			}

			if ( empty( $primary_color_value ) ) {
				return;
			}

			$color_selectors = array();
			$primary_color   = array(
				'{{WRAPPER}} #wfacp-e-form  #payment li.wc_payment_method input.input-radio:checked::before'                    => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form  #payment.wc_payment_method input[type=radio]:checked:before'                        => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form  button[type=submit]:not(.white):not(.black)'                                        => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form  button[type=button]:not(.white):not(.black)'                                        => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form .wfacp-coupon-section .wfacp-coupon-page .wfacp-coupon-field-btn'                    => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form input[type=checkbox]:checked'                                                        => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form #payment input[type=checkbox]:checked'                                               => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-input-wrapper .wfacp-form-control:checked' => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce input[type=checkbox]:checked'                           => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form .button.button#place_order'                                         => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form .button.wfacp_next_page_button'                                     => 'background-color:{{VALUE}};',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form  .wfacp_payment #ppcp-hosted-fields .button'                        => 'background-color:{{VALUE}};',
				'body #wfob_wrap .wfob_wrapper .wfob_bump .wfob_bump_checkbox input[type=checkbox]:checked'                     => 'background-color:{{VALUE}};',
				'#wfacp_qr_model_wrap .wfacp_qr_wrap .wfacp_qv-summary .button'                                                 => 'background-color:{{VALUE}};',
				'#wfob_qr_model_wrap .wfob_qr_wrap .button'                                                                     => 'background-color:{{VALUE}};',
				'body #wfob_qr_model_wrap .wfob_option_btn'                                                                     => 'background-color:{{VALUE}};',
			);

			$color_selectors['{{WRAPPER}} #wfacp-e-form .form-row:not(.woocommerce-invalid-required-field) .wfacp-form-control:not(.input-checkbox):focus']                                                                                               = 'border-color:{{VALUE}} ;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form  p.form-row:not(.woocommerce-invalid-required-field) .wfacp-form-control:not(.input-checkbox):focus']                                                                                             = 'box-shadow:0 0 0 1px {{VALUE}} ;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form .form-row:not(.woocommerce-invalid-required-field) .woocommerce-input-wrapper .select2-container .select2-selection--single .select2-selection__rendered:focus']                 = 'border-color:{{VALUE}} ;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .form-row:not(.woocommerce-invalid-required-field) .woocommerce-input-wrapper .select2-container .select2-selection--single .select2-selection__rendered:focus']     = 'box-shadow:0 0 0 1px {{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form .form-row:not(.woocommerce-invalid-required-field) .woocommerce-input-wrapper .select2-container .select2-selection--single:focus>span.select2-selection__rendered']             = 'box-shadow:0 0 0 1px {{VALUE}} ;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce #payment li.wc_payment_method input.input-radio:checked']                                                                                                            = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce #payment.wc_payment_method input[type=radio]:checked']                                                                                                               = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce input[type=radio]:checked']                                                                                                                                          = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form input[type=radio]:checked']                                                                                                                                                                       = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce #add_payment_method #payment ul.payment_methods li input[type=radio]:checked']                                                                                       = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form #payment ul.payment_methods li input[type=radio]:checked']                                                                                                                                        = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce input[type=radio]:checked']                                                                                                                                          = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart #payment ul.payment_methods li input[type=radio]:checked']                                                                                         = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-checkout #payment ul.payment_methods li input[type=radio]:checked']                                                                                     = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce #wfacp_checkout_form input[type=radio]:checked']                                                                                                                     = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp-form input[type=checkbox]:checked']                                                                                                                                                        = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form #payment input[type=checkbox]:checked']                                                                                                                                          = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form .woocommerce-input-wrapper .wfacp-form-control:checked']                                                                                                                         = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form input[type=checkbox]:checked']                                                                                                                                                   = 'border-color:{{VALUE}};';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .form-row:not(.woocommerce-invalid-required-field) .woocommerce-input-wrapper .select2-container .select2-selection--single:focus>span.select2-selection__rendered'] = 'border-color:{{VALUE}};';

			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form #payment li.wc_payment_method input.input-radio:checked']                      = 'border-width:5px;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form #payment.wc_payment_method input[type=radio]:checked']                         = 'border-width:5px;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form input[type=radio]:checked']                                                    = 'border-width:5px;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form #add_payment_method #payment ul.payment_methods li input[type=radio]:checked'] = 'border-width:5px;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form input[type=checkbox]:after']                                                   = 'display: block;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form input[type=checkbox]:before']                                                  = 'display: none;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form input[type=checkbox]:checked']                                                 = 'border-width: 8px;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form #payment li.wc_payment_method input.input-radio:checked::before']                               = 'display:none;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form #payment.wc_payment_method input[type=radio]:checked:before']                                   = 'display:none;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form input[type=radio]:checked:before']                                                              = 'display:none;';
			$color_selectors['{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce input[type=radio]:checked:before']                                 = 'display:none;';

			$color_selectors['body #wfob_wrap .wfob_wrapper .wfob_bump_checkbox input[type=checkbox]:checked'] = 'border-color:{{VALUE}};';
			$color_selectors['body #wfob_wrap .wfob_wrapper input[type=checkbox]:checked:after']               = 'display: block;';
			$color_selectors['body #wfob_wrap .wfob_wrapper input[type=checkbox]:checked:before']              = 'display:none;';
			$color_selectors['body #wfob_wrap .wfob_wrapper input[type=checkbox]:checked']                     = 'border-width:5px;';

			$selectors = array_merge( $primary_color, $color_selectors );

			echo '<style>';
			foreach ( $selectors as $key => $value ) {
				$key = str_replace( '{{WRAPPER}} ', 'body ', $key );

				if ( false !== strpos( $value, '{{VALUE}}' ) ) {
					echo $key . '{' . str_replace( '{{VALUE}}', $primary_color_value, $value ) . '}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo $key . '{' . $value . '}'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}

			echo '</style>';
		}


		/**
		 *
		 * Check this Mini cart Strike Through enable or disabled from editor
		 *
		 * @return mixed
		 */
		public function order_summary_field_enable_strike_through_price() {

			if ( isset( $this->form_data['order_summary_field_enable_strike_through_price'] ) && 'yes' == $this->form_data['order_summary_field_enable_strike_through_price'] ) {
				return true;
			}

			return false;
		}

		public function collapsible_mini_cart_enable_strike_through_price() {

			if ( isset( $this->form_data['collapsible_mini_cart_enable_strike_through_price'] ) && 'yes' == $this->form_data['collapsible_mini_cart_enable_strike_through_price'] ) {
				return true;
			}

			return false;
		}

		public function mini_cart_enable_strike_through_price() {


			if ( isset( $this->mini_cart_data['mini_cart_enable_strike_through_price'] ) && 'yes' == $this->mini_cart_data['mini_cart_enable_strike_through_price'] ) {
				return true;
			}

			return false;
		}

		public function mini_cart_low_stock_trigger( $_product ) {

			if ( isset( $this->mini_cart_data['mini_cart_enable_low_stock_trigger'] ) && 'yes' == $this->mini_cart_data['mini_cart_enable_low_stock_trigger'] && isset( $this->mini_cart_data['mini_cart_low_stock_message'] ) ) {

				$stock_quantity = $_product->get_stock_quantity();

				if ( $stock_quantity !== null ) {

					echo "<div class='wfacp_stocks'>" . str_replace( '{{quantity}}', $stock_quantity, $this->mini_cart_data['mini_cart_low_stock_message'] ) . "</div>";
				}
			}
		}

		public function order_summary_field_after_product_title( $_product ) {

			if ( isset( $this->form_data['order_summary_field_enable_low_stock_trigger'] ) && 'yes' == $this->form_data['order_summary_field_enable_low_stock_trigger'] && isset( $this->form_data['order_summary_field_low_stock_message'] ) ) {

				$stock_quantity = $_product->get_stock_quantity();

				if ( $stock_quantity !== null ) {

					echo "<div class='wfacp_stocks'>" . str_replace( '{{quantity}}', $stock_quantity, $this->form_data['order_summary_field_low_stock_message'] ) . "</div>";
				}
			}
		}

		public function collapsible_mini_cart_field_after_product_title( $_product ) {


			if ( isset( $this->form_data['collapsible_mini_cart_enable_low_stock_trigger'] ) && 'yes' == $this->form_data['collapsible_mini_cart_enable_low_stock_trigger'] && isset( $this->form_data['collapsible_mini_cart_low_stock_message'] ) ) {

				$stock_quantity = $_product->get_stock_quantity();

				if ( $stock_quantity !== null ) {

					echo "<div class='wfacp_stocks'>" . str_replace( '{{quantity}}', $stock_quantity, $this->form_data['collapsible_mini_cart_low_stock_message'] ) . "</div>";
				}
			}
		}

		public function mini_cart_saving_price() {

			if ( isset( $this->mini_cart_data['mini_cart_enable_saving_price_message'] ) && 'yes' == $this->mini_cart_data['mini_cart_enable_saving_price_message'] && isset( $this->mini_cart_data['mini_cart_saving_price_message'] ) ) {
				$price_message = $this->mini_cart_data['mini_cart_saving_price_message'];
				WFACP_Common::display_save_price( $price_message );
			}

		}

		public function order_summary_field_saving_price() {

			if ( isset( $this->form_data['order_summary_field_enable_saving_price_message'] ) && 'yes' == $this->form_data['order_summary_field_enable_saving_price_message'] && isset( $this->form_data['order_summary_field_saving_price_message'] ) ) {
				$price_message = $this->form_data['order_summary_field_saving_price_message'];
				WFACP_Common::display_save_price( $price_message );
			}

		}

		public function collapsible_mini_cart_saving_price() {

			if ( isset( $this->form_data['collapsible_mini_cart_enable_saving_price_message'] ) && 'yes' == $this->form_data['collapsible_mini_cart_enable_saving_price_message'] && isset( $this->form_data['collapsible_mini_cart_saving_price_message'] ) ) {
				$price_message = $this->form_data['collapsible_mini_cart_saving_price_message'];
				WFACP_Common::display_save_price( $price_message );
			}

		}

		public function enable_order_field_collapsed_by_default( $device = 'desktop' ) {
			$field_key = 'enable_order_field_collapsed';

			if ( $device === 'tablet' ) {
				$field_key .= ':tablet_portrait';
			} elseif ( $device === 'mobile' ) {
				$field_key .= ':mobile_portrait';
			}

			if ( isset( $this->form_data[ $field_key ] ) && 'yes' == $this->form_data[ $field_key ] ) {
				return true;
			}

			return false;
		}

		public function should_hide_order_summary_by_default() {
			// Check if any device has collapsed enabled
			return $this->enable_order_field_collapsed_by_default( 'desktop' ) ||
			       $this->enable_order_field_collapsed_by_default( 'tablet' ) ||
			       $this->enable_order_field_collapsed_by_default( 'mobile' );
		}
	}

	return WFACP_template_Bricks::get_instance();
}

