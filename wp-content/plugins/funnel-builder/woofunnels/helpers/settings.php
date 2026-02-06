<?php


defined( 'ABSPATH' ) || exit;

return apply_filters( 'bwf_settings_config', array(

	'general'               => array(
		'title'    => __( 'License', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'License', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'general',
		'fields'   => apply_filters( 'bwf_settings_config_general', array(
			array(
				'key'           => 'default_selected_builder',
				'type'          => 'select',
				'label'         => __( 'Default Page Builder', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'          => '',
				'values'        => function_exists( 'wffn_rest_funnels' ) && method_exists( wffn_rest_funnels(), 'get_all_builders' ) ? array_map( function ( $id, $name ) {
					return [
						'id'   => $id,
						'name' => $name
					];
				}, array_keys( wffn_rest_funnels()->get_all_builders()['funnel'] ), wffn_rest_funnels()->get_all_builders()['funnel'] ) : [],
				'selectOptions' => [
					'hideNoneSelectedText' => true,
				],
			),
			array(

				'key'   => 'set_funnel_as_home',
				'type'  => 'html',
				'label' => __( 'Set Funnel as Homepage', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => '',
				'html' => sprintf( __( "Select the Funnel page you want to set as the Homepage. <a href='%s'>Go to WordPress Settings</a>", 'woofunnels' ), admin_url( 'options-reading.php' ) ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch, WordPress.WP.I18n.MissingTranslatorsComment
			),
		) ),
		'priority' => 1,
	),
	'permalinks'            => array(
		'title'    => __( 'Permalinks', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'Permalinks', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'permalinks',
		'fields'   => array(),
		'priority' => 5,
	),
	'funnelkit_google_maps' => array(
		'title'    => __( 'Google Maps', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'Google Maps', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'funnelkit_google_maps',
		'fields'   => array(
			array(
				'key'   => 'funnelkit_google_map_key',
				'type'  => 'text',
				'label' => 'Google Map API Key',
				// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Enter the Google Maps API key for Google Address AutoComplete on Checkout. <a href="https://funnelkit.com/google-address-autocomplete-for-woocommerce/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Settings+Google+Map+Api+Key" target="_blank">Learn More</a>', 'woofunnels' ),
				'value' => '',
			)
		),
		'priority' => 5,
	),
	'facebook_pixel'        => array(
		'title'    => __( 'Facebook Pixel', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'Facebook Pixel', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'facebook_pixel',
		'fields'   => array(
			array(
				'key'         => 'fb_pixel_key',
				'label'       => __( 'Pixel ID & Access Token', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'        => 'tracking_id',
				'placeholder' => __( '294123501257422', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'input_hint'        => __( 'Log into your Facebook ads account to find your Pixel ID.', 'woofunnels' ) . ' <a target="_blank" href="https://funnelkit.com/docs/funnel-builder/how-to/set-up-facebook-pixel/">' . __( 'Learn More', 'woofunnels' ) . '</a>', // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'secondary_fields' => array(
					array(
						'key'         => 'conversion_api_access_token',
						'type'        => 'textarea',
						'label'       => '',
						'placeholder' => __( 'Enter Access Token', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'hint'        => __( 'Send events directly from server to Facebook through the Conversion API. An access token is required to use the server-side API.', 'woofunnels' ) . '<a target="_blank" href="https://funnelkit.com/docs/funnel-builder/global-settings/facebook-conversion-api/">' . __( 'Generate Access Token', 'woofunnels' ) . '</a>', // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					),
				)
			),
			array(
				'key'         => 'conversion_api_access_token',
				'type'        => 'hidden',
			),

			array(
				'key'   => 'is_fb_conv_enable_test',
				'label' => '',
				'hint'  => __( 'Use test_event_code to verify server-side events. <strong>Uncheck this option after testing</strong>.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

				'type'    => "checklist",
				'values'  => array(
					array(
						'name'  => __( 'Test server events via test_event_code', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					)
				)
			),


			array(
				'key'         => 'conversion_api_test_event_code',
				'type'        => 'input',
				'label'       => '',
				'hint'        => __( '<a target="_blank" href="https://funnelkit.com/docs/funnel-builder/global-settings/facebook-conversion-api/#step-1-select-your-pixel-id-and-go-to-%E2%80%9Ctest-events%E2%80%9D">Learn how to get test_event_code</a>', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch, WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.UnorderedPlaceholdersText
				'placeholder' => __( 'Paste your test_event_code here', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'toggler'     => array(
					'key'   => 'is_fb_conv_enable_test',
					'value' => array( 'yes' ),
				)
			),
			array(
				'key'     => 'is_fb_conversion_api_log',
				'type'    => 'checklist',
				'label'   => '',
				'hint'    => __( 'Use this option to log API request & response. <strong>Uncheck this option after testing</strong>.  <a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) ) . '">Click here to access logs.</a>', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch, WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.NonSingularStringLiteralText
				'values'  => array(
					array(
						'name'  => __( 'Enable Purchase Event Logs', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				)
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Site Wide Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_fb_page_view_global',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'         => "checkbox",
				'hint'         => __( 'Use this option to fire PageView event on all site pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),
			array(
				'key'   => 'is_fb_add_to_cart_global',
				'label' => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire AddtoCart event when customer add items to the cart', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'   => 'is_fb_page_product_content_global',
				'label' => __( 'Enable ViewContent Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire ViewContent event on product pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'    => 'is_fb_page_view_lp',
				'label'  => __( 'Sales Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_fb_page_view_op',
				'label'  => __( 'Optin Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_fb_lead_op',
				'label'  => '',
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable Lead Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Order Bump Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),


			array(
				'key'          => 'is_fb_add_to_cart_bump',
				'type'         => 'checkbox',
				'label'        => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Fire "AddToCart" event when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),
			array(
				'key'   => 'is_fb_custom_bump',
				'type'  => 'checkbox',
				'label' => __( 'Enable Order Bump Conversion Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Fire "Woofunnels_Bump" custom event when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),

			array(
				'key'          => 'label_section_head_fb',
				'type'         => "label",
				'label'        => __( 'Checkout Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'pixel_is_page_view',
				'type'         => 'checkbox',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end' ],
			),
			array(
				'key'   => 'pixel_initiate_checkout_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable InitiateCheckout Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'   => 'pixel_add_to_cart_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'   => 'pixel_add_payment_info_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable AddPaymentInfo Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'    => 'is_fb_purchase_page_view',
				'type'   => 'checklist',
				'label'  => __( 'Purchase Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( "Enable PageView Event", 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_fb_purchase_event',
				'type'   => 'checklist',
				'label'  => '',
				'hint'   => __( 'Note: FunnelKit will send total order value and store currency based on order. <a target="_blank" href="https://developers.facebook.com/docs/facebook-pixel/pixel-with-ads/conversion-tracking#add-value">Learn More</a>', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable Purchase Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),


			array(
				'key'    => 'enable_general_event',
				'type'   => 'checklist',
				'label'  => '',
				'hint'   => __( 'Use the GeneralEvent for your Custom Audiences and Custom Conversions.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable General Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),

			),
			array(
				'type'        => 'input',
				'key'         => 'general_event_name',
				'label'       => '',
				'placeholder' => __( 'General Event Name', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Customize the name of general event.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'toggler'     => array(
					'key'   => 'enable_general_event',
					'value' => array( 'yes' ),
				),
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Track Steps', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'   => 'is_fb_custom_events',
				'label' => __( 'Enable Custom Funnel Step Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Fire "Woofunnels_Sales", "Woofunnels_Checkout", "Woofunnels_Upsell", "Woofunnels_Downsell", "Woofunnels_Thankyou", "Woofunnels_Optin" & "Woofunnels_OptinConfirmation" respectively when user visits the page.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

				'type'         => "checkbox",
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Advanced', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_fb_enable_content',
				'type'         => 'checklist',
				'label'        => '',
				'hint'         => __( 'Note: Your Product catalog must be synced with Facebook. <a target="_blank" href="https://developers.facebook.com/docs/facebook-pixel/get-started/dynamic-ads">Learn More</a>', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width', 'rem_hint_pad' ],
				'values'       => array(
					array(
						'name'  => __( 'Enable Content Settings for Dynamic Ads', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'          => 'pixel_variable_as_simple',
				'type'         => 'checkbox',
				'label'        => __( 'Enable this to send parent product ID for variable products', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Turn this option ON when your Product Catalog doesn\'t include the variants for variable products.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'bwf_vue_checkbox_label' ],
				'toggler'      => array(
					'key'   => 'is_fb_enable_content',
					'value' => array( 'yes' ),
				),
			),
			array(
				'key'           => 'pixel_content_id_type',
				'styleClasses'  => [ 'group-one-class' ],
				'type'          => 'select',
				'label'         => '',
				'default'       => '0',
				'values'        => [
					[ 'id' => '0', 'name' => __( 'Select content id parameter', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_id', 'name' => __( 'Product ID', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_sku', 'name' => __( 'Product SKU', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				],
				'selectOptions' => [
					'hideNoneSelectedText' => true,
				],
				'toggler'       => array(
					'key'   => 'is_fb_enable_content',
					'value' => array( 'yes' ),
				),
			),
			array(
				'key'         => 'pixel_content_id_prefix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content ID Prefix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add prefix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'toggler'     => array(
					'key'   => 'is_fb_enable_content',
					'value' => array( 'yes' ),
				),

			),
			array(
				'key'         => 'pixel_content_id_suffix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content id suffix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add suffix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'toggler'     => array(
					'key'   => 'is_fb_enable_content',
					'value' => array( 'yes' ),
				),

			),
			array(
				'key'     => 'exclude_from_total',
				'label'   => '',
				'type'    => 'checklist',
				'hint'    => __( 'Check above boxes to exclude shipping/taxes from the total.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values'  => array(
					array(
						'name'  => __( 'Exclude Shipping from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_shipping',
					),
					array(
						'name'  => __( 'Exclude Taxes from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_taxes',
					),

				),
				'toggler' => array(
					'key'   => 'is_fb_purchase_event',
					'value' => array( 'yes' ),
				),
			),

			array(
				'type'   => 'checklist',
				'key'    => 'is_fb_advanced_event',
				'label'  => '',
				'hint'   => __( 'Note: FunnelKit will send customer\'s email, name, phone, address fields whichever available in the order. <a target="_blank" href="https://developers.facebook.com/docs/facebook-pixel/pixel-with-ads/conversion-tracking#advanced_match">Learn More', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable Advanced Matching With the Pixel', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),


		),
		'priority' => 10,
	),
	'google_analytics'      => array(
		'title'    => __( 'Google Analytics', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'Google Analytics', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'google_analytics',
		'fields'   => array(
			array(
				'key'         => 'ga_key',
				'type'        => 'tracking_id',
				'label'       => __( 'Analytics ID', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'placeholder' => __( 'G-9F3K2TGHH4', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'input_hint'        => __( 'Log into your Google Analytics account to find your Analytics ID. <a target="_blank" href="https://funnelkit.com/docs/funnel-builder/how-to/set-up-google-analytics-4-property/">Learn More</a>', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Site Wide Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_ga_page_view_global',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'         => "checkbox",
				'hint'         => __( 'Use this option to fire PageView event on all site pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),

			array(
				'key'   => 'is_ga_add_to_cart_global',
				'label' => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire AddtoCart event when customer add items to the cart', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'   => 'is_ga_view_item_global',
				'label' => __( 'Enable ViewItem Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire ViewItem event on product pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),

			array(
				'key'    => 'is_ga_page_view_lp',
				'type'   => 'checklist',
				'label'  => __( 'Sales Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'type'   => 'checklist',
				'key'    => 'is_ga_page_view_op',
				'label'  => __( 'Optin Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_ga_lead_op',
				'label'  => '',
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable Lead Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Order Bump Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),


			array(
				'key'          => 'is_ga_add_to_cart_bump',
				'type'         => 'checkbox',
				'label'        => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'This event will fire when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),
			array(
				'key'   => 'is_ga_custom_bump',
				'type'  => 'checkbox',
				'label' => __( 'Enable Order Bump Conversion Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Fire "Woofunnels_Bump" custom event when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Checkout Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'google_ua_is_page_view',
				'type'         => 'checkbox',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),
			array(
				'type'  => 'checkbox',
				'key'   => 'google_ua_add_to_cart_event',
				'label' => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'type'  => 'checkbox',
				'key'   => 'google_ua_initiate_checkout_event',
				'label' => __( 'Enable BeginCheckout Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'type'  => 'checkbox',
				'key'   => 'google_ua_add_payment_info_event',
				'label' => __( 'Enable AddPaymentInfo Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'type'  => 'checkbox',
				'key'   => 'google_ua_add_shipping_info_event',
				'label' => __( 'Enable AddShippingInfo Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),


			array(
				'key'    => 'is_ga_purchase_page_view',
				'type'   => 'checklist',
				'label'  => __( 'Purchase Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( "Enable PageView Event", 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_ga_purchase_event',
				'type'   => 'checklist',
				'label'  => '',
				'values' => array(
					array(
						'name'  => __( 'Enable Purchase Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'type'         => 'label',
				'key'          => 'label_section_head_tr',
				'label'        => __( 'Track Steps', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_ga_custom_events',
				'label'        => __( 'Enable Custom Funnel Step Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Fire "Woofunnels_Sales", "Woofunnels_Checkout", "Woofunnels_Upsell", "Woofunnels_Downsell", "Woofunnels_Thankyou", "Woofunnels_Optin" & "Woofunnels_OptinConfirmation" respectively when user visits the page.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'         => "checkbox",
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_adv',
				'label'        => __( 'Advanced', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'google_ua_variable_as_simple',
				'type'         => 'checkbox',
				'label'        => __( 'Enable this to send parent product ID for variable products', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Turn this option ON when your Product Catalog doesn\'t include the variants for variable products.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width', 'rem_hint_pad' ],
			),
			array(
				'key'           => 'google_ua_content_id_type',
				'styleClasses'  => [ 'group-one-class' ],
				'type'          => 'select',
				'label'         => '',
				'default'       => '0',
				'values'        => [
					[ 'id' => '0', 'name' => __( 'Select content id parameter', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_id', 'name' => __( 'Product ID', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_sku', 'name' => __( 'Product SKU', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				],
				'selectOptions' => [
					'hideNoneSelectedText' => true,
				],
			),
			array(
				'key'         => 'google_ua_content_id_prefix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content ID Prefix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add prefix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'         => 'google_ua_content_id_suffix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content ID Suffix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add suffix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'    => 'ga_exclude_from_total',
				'label'  => '',
				'type'   => 'checklist',
				'hint'   => __( 'Check above boxes to exclude shipping/taxes from the total.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Exclude Shipping from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_shipping',
					),
					array(
						'name'  => __( 'Exclude Taxes from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_taxes',
					),

				),

			),

		),
		'priority' => 15,
	),
	'google_ads'            => array(

		'title'    => __( 'Google Ads', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'Google Ads', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'google_ads',
		'fields'   => array(
			array(
				'key'         => 'gad_key',
				'type'        => 'tracking_id',
				'label'       => __( 'Conversion ID', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'placeholder' => __( 'AW-837491263', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'input_hint'        => __( 'Log into your Google Ads account to find your Conversion ID. <a target="_blank" href="https://funnelkit.com/docs/funnel-builder/global-settings/google-ads/#step-1-go-to-your-google-ads-account">Learn More</a>', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Site Wide Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_gad_page_view_global',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'         => "checkbox",
				'hint'         => __( 'Use this option to fire PageView event on all site pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),
			array(
				'key'   => 'is_gad_add_to_cart_global',
				'label' => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire AddtoCart event when customer add items to the cart', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'         => 'gad_addtocart_global_conversion_label',
				'type'        => 'tracking_id_dependent',
				'tracking_id_key' => 'gad_key',
				'label'       => '',
				'placeholder' => __( 'Enter Conversion Label (Optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'   => 'is_gad_view_item_global',
				'label' => __( 'Enable ViewItem Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire ViewItem event on product pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'    => 'is_gad_page_view_lp',
				'label'  => __( 'Sales Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_gad_page_view_op',
				'label'  => __( 'Optin Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_gad_lead_op',
				'label'  => '',
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable Lead Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'         => 'gad_lead_conversion_label',
				'type'        => 'tracking_id_dependent',
				'tracking_id_key' => 'gad_key',
				'label'       => '',
				'placeholder' => __( 'Enter Conversion Label (Optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Order Bump Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),


			array(
				'key'          => 'is_gad_add_to_cart_bump',
				'type'         => 'checkbox',
				'label'        => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'This event will fire when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),
			array(
				'key'         => 'gad_addtocart_bump_conversion_label',
				'type'        => 'tracking_id_dependent',
				'tracking_id_key' => 'gad_key',
				'label'       => '',
				'placeholder' => __( 'Enter Conversion Label (Optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'   => 'is_gad_custom_bump',
				'type'  => 'checkbox',
				'label' => __( 'Enable Order Bump Conversion Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Fire "Woofunnels_Bump" custom event when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_gad',
				'label'        => __( 'Checkout Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),

			array(
				'key'          => 'google_ads_is_page_view',
				'type'         => 'checkbox',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],

			),
			array(
				'key'   => 'google_ads_add_to_cart_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable AddToCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'         => 'gad_addtocart_checkout_conversion_label',
				'type'        => 'tracking_id_dependent',
				'tracking_id_key' => 'gad_key',
				'label'       => '',
				'placeholder' => __( 'Enter Conversion Label (Optional)', 'woofunnels' ),

			),
			array(
				'key'   => 'google_ads_initiate_checkout_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable InitiateCheckout Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'    => 'is_gad_pageview_event',
				'type'   => 'checklist',
				'label'  => __( 'Purchase Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_gad_purchase_event',
				'type'   => 'checklist',
				'label'  => '',
				'values' => array(
					array(
						'name'  => __( 'Enable Conversion Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'         => 'gad_conversion_label',
				'type'        => 'tracking_id_dependent',
				'tracking_id_key' => 'gad_key',
				'label'       => __( 'Conversion Label', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'placeholder' => __( 'Enter Conversion Label (Optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Track Steps', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'   => 'is_gad_custom_events',
				'label' => __( 'Enable Custom Funnel Step Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Fire "Woofunnels_Sales", "Woofunnels_Checkout", "Woofunnels_Upsell", "Woofunnels_Downsell", "Woofunnels_Thankyou", "Woofunnels_Optin" & "Woofunnels_OptinConfirmation" respectively when user visits the page.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

				'type'         => "checkbox",
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Advanced', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'google_ads_variable_as_simple',
				'type'         => 'checkbox',
				'label'        => __( 'Enable this to send parent product ID for variable products', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Turn this option ON when your Product Catalog doesn\'t include the variants for variable products.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width', 'rem_hint_pad' ],
			),
			array(
				'key'           => 'google_ads_content_id_type',
				'styleClasses'  => [ 'group-one-class' ],
				'type'          => 'select',
				'label'         => '',
				'default'       => '0',
				'values'        => [
					[ 'id' => '0', 'name' => __( 'Select content id parameter', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_id', 'name' => __( 'Product ID', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_sku', 'name' => __( 'Product SKU', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				],
				'selectOptions' => [
					'hideNoneSelectedText' => true,
				],
			),
			array(
				'key'         => 'google_ads_content_id_prefix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content ID Prefix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add prefix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'         => 'google_ads_content_id_suffix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content ID Suffix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add suffix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'    => 'gad_exclude_from_total',
				'label'  => '',
				'type'   => 'checklist',
				'hint'   => __( 'Check above boxes to exclude shipping/taxes from the total.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Exclude Shipping from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_shipping',
					),
					array(
						'name'  => __( 'Exclude Taxes from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_taxes',
					),

				),

			),


		),
		'priority' => 20,
	),
	'pinterest'             => array(
		'title'   => __( 'Pinterest', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading' => __( 'Pinterest', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'    => 'pinterest',
		'fields'  => array(
			array(
				'type'        => 'tracking_id',
				'key'         => 'pint_key',
				'label'       => __( 'Tag ID', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'placeholder' => __( '2614535298742', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'input_hint'        => __( 'Log into your Pinterest Ads Manager Account and find Pixel ID. <a target="_blank" href="https://funnelkit.com/docs/funnel-builder/global-settings/pinterest/">Learn More</a>', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Site Wide Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_pint_page_view_global',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'         => "checkbox",
				'hint'         => __( 'Use this option to fire PageView event on all site pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),
			array(
				'key'   => 'is_pint_page_visit_global',
				'label' => __( 'Enable PageVisit Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire PageVisit event on product pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'   => 'is_pint_add_to_cart_global',
				'label' => __( 'Enable AddToCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire AddtoCart event when customer add items to the cart', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),

			array(
				'key'    => 'is_pint_page_view_lp',
				'label'  => __( 'Sales Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),

			array(
				'key'    => 'is_pint_page_view_op',
				'label'  => __( 'Optin Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),

			array(
				'key'    => 'is_pint_lead_op',
				'label'  => '',
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable Lead Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Order Bump Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),


			array(
				'key'          => 'is_pint_add_to_cart_bump',
				'type'         => 'checkbox',
				'label'        => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'This event will fire when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),
			array(
				'key'   => 'is_pint_custom_bump',
				'type'  => 'checkbox',
				'label' => __( 'Enable Order Bump Conversion Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Fire "Woofunnels_Bump" custom event when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_pint',
				'label'        => __( 'Checkout Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),

			array(
				'key'          => 'pint_is_page_view',
				'type'         => 'checkbox',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),

			array(
				'key'   => 'pint_add_to_cart_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable AddToCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'   => 'pint_initiate_checkout_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable InitiateCheckout Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'    => 'is_pint_pageview_event',
				'type'   => 'checklist',
				'label'  => __( 'Purchase Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_pint_purchase_event',
				'type'   => 'checklist',
				'label'  => '',
				'values' => array(
					array(
						'name'  => __( 'Enable Purchase Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Track Steps', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'   => 'is_pint_custom_events',
				'label' => __( 'Enable Custom Funnel Step Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'  => __( 'Fire "Woofunnels_Sales", "Woofunnels_Checkout", "Woofunnels_Upsell", "Woofunnels_Downsell", "Woofunnels_Thankyou", "Woofunnels_Optin", "Woofunnels_OptinConfirmation" respectively when user visits the page.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

				'type'         => "checkbox",
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Advanced', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'pint_variable_as_simple',
				'type'         => 'checkbox',
				'label'        => __( 'Enable this to send parent product ID for variable products', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Turn this option ON when your Product Catalog doesn\'t include the variants for variable products.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width', 'rem_hint_pad' ],
			),
			array(
				'key'           => 'pint_content_id_type',
				'styleClasses'  => [ 'group-one-class' ],
				'type'          => 'select',
				'label'         => '',
				'default'       => '0',
				'values'        => [
					[ 'id' => '0', 'name' => __( 'Select content id parameter', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_id', 'name' => __( 'Product ID', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					[ 'id' => 'product_sku', 'name' => __( 'Product SKU', 'woofunnels' ) ], // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				],
				'selectOptions' => [
					'hideNoneSelectedText' => true,
				],
			),
			array(
				'key'         => 'pint_content_id_prefix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content ID Prefix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add prefix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'         => 'pint_content_id_suffix',
				'type'        => 'input',
				'label'       => '',
				'placeholder' => __( 'Content ID Suffix', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'        => __( 'Add suffix to the content_id parameter (optional)', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'    => 'pint_exclude_from_total',
				'label'  => '',
				'type'   => 'checklist',
				'hint'   => __( 'Check above boxes to exclude shipping/taxes from the total.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Exclude Shipping from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_shipping',
					),
					array(
						'name'  => __( 'Exclude Taxes from Total', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'is_disable_taxes',
					),

				),

			),
		),


		'priority' => 25,
	),
	'tiktok'                => array(

		'title'    => __( 'TikTok', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'TikTok', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'tiktok',
		'fields'   => array(
			array(
				'key'         => 'tiktok_pixel',
				'type'        => 'tracking_id',
				'label'       => __( 'TikTok ID', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'placeholder' => __( 'D4L9N62FLPENAMTU4HG9', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'input_hint'        => __( 'Log into your Tiktok Business Account and find TikTok ID. <a target="_blank" href="https://funnelkit.com/docs/funnel-builder/global-settings/tiktok/">Learn More</a>', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Site Wide Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_tiktok_page_view_global',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'         => "checkbox",
				'hint'         => __( 'Use this option to fire PageView event on all site pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),
			array(
				'key'   => 'is_tiktok_add_to_cart_global',
				'label' => __( 'Enable AddToCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire AddtoCart event when customer add items to the cart', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'   => 'is_tiktok_page_product_content_global',
				'label' => __( 'Enable ViewContent Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire ViewContent event on product pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'    => 'is_tiktok_page_view_lp',
				'label'  => __( 'Sales Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_tiktok_page_view_op',
				'label'  => __( 'Optin Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Order Bump Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),


			array(
				'key'          => 'is_tiktok_add_to_cart_bump',
				'type'         => 'checkbox',
				'label'        => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Fire this event when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_tiktok',
				'label'        => __( 'Checkout Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),

			array(
				'key'          => 'tiktok_is_page_view',
				'type'         => 'checkbox',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),

			array(
				'key'   => 'tiktok_add_to_cart_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable AddToCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				//google_ua_add_to_cart_event
				'key'   => 'tiktok_initiate_checkout_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable InitiateCheckout Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'    => 'is_tiktok_pageview_event',
				'type'   => 'checklist',
				'label'  => __( 'Purchase Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_tiktok_purchase_event',
				'type'   => 'checklist',
				'label'  => '',
				'values' => array(
					array(
						'name'  => __( 'Enable Purchase Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Advanced', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'tiktok_variable_as_simple',
				'type'         => 'checkbox',
				'label'        => __( 'Enable this to send parent product ID for variable products', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Turn this option ON when your Product Catalog doesn\'t include the variants for variable products.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width', 'rem_hint_pad' ],
			),
		),
		'priority' => 30,
	),
	'snapchat'              => array(

		'title'    => __( 'Snapchat', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'Snapchat', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'snapchat',
		'fields'   => array(
			array(
				'key'         => 'snapchat_pixel',
				'type'        => 'tracking_id',
				'label'       => __( 'Pixel ID', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'placeholder' => __( '7fc81b23-8e1c-4fde-b2bf-e3f67a19d2fa', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'input_hint'        => __( 'Log into your Snapchat Business Account and find Pixel ID. <a target="_blank" href="https://funnelkit.com/docs/funnel-builder/global-settings/snapchat/">Learn More</a>', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Site Wide Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'is_snapchat_page_view_global',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'         => "checkbox",
				'hint'         => __( 'Use this option to fire PageView event on all site pages', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad' ],
			),
			array(
				'key'   => 'is_snapchat_add_to_cart_global',
				'label' => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'  => "checkbox",
				'hint'  => __( 'Use this option to fire AddtoCart event when customer add items to the cart', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'    => 'is_snapchat_page_view_lp',
				'label'  => __( 'Sales Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'key'    => 'is_snapchat_page_view_op',
				'label'  => __( 'Optin Page Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'type'   => "checklist",
				'values' => array(
					array(
						'name'  => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),


			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Order Bump Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),


			array(
				'key'          => 'is_snapchat_add_to_cart_bump',
				'type'         => 'checkbox',
				'label'        => __( 'Enable AddtoCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Fire this event when user accepts the order bump', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],
			),

			array(
				'type'         => 'label',
				'key'          => 'label_section_head_snapchat',
				'label'        => __( 'Checkout Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),

			array(
				'key'          => 'snapchat_is_page_view',
				'type'         => 'checkbox',
				'label'        => __( 'Enable PageView Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width' ],

			),
			array(
				'key'   => 'snapchat_add_to_cart_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable AddToCart Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			),
			array(
				'key'   => 'snapchat_initiate_checkout_event',
				'type'  => 'checkbox',
				'label' => __( 'Enable InitiateCheckout Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			),
			array(
				'key'    => 'is_snapchat_purchase_event',
				'type'   => 'checklist',
				'label'  => __( 'Purchase Events', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'values' => array(
					array(
						'name'  => __( 'Enable Purchase Event', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'value' => 'yes',
					),
				),
			),
			array(
				'type'         => 'label',
				'key'          => 'label_section_head_ga',
				'label'        => __( 'Advanced', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'snapchat_variable_as_simple',
				'type'         => 'checkbox',
				'label'        => __( 'Enable this to send parent product ID for variable products', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'hint'         => __( 'Turn this option ON when your Product Catalog doesn\'t include the variants for variable products.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end', 'bwf_remove_lft_pad', 'bwf_clear_rgt_width', 'rem_hint_pad' ],
			),
		),
		'priority' => 30,
	),
	'utm_parameter'         => array(
		'title'    => __( 'First Party Tracking', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'First Party Tracking', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'utm_parameter',
		'fields'   => array(
			array(
				'type'         => 'label',
				'key'          => 'label_section_head_fb',
				'label'        => __( 'Conversion Tracking', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_setting_track_and_events_start', 'bwf_wrap_custom_html_tracking_general' ],
			),
			array(
				'key'          => 'track_utms',
				'type'         => 'upgrade_pro',
				'label'        => __( 'In the lite version, you manage UTM tracking with third-party tools. In the Pro version, we store UTM information and provide real-time analytics.', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'styleClasses' => [ 'wfacp_checkbox_wrap', 'wfacp_setting_track_and_events_end' ],
				'hint'         => '',
			),

		),
		'priority' => 5,
	),
	'funnelkit_advanced'    => array(
		'title'    => __( 'Theme CSS and JavaScript', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'heading'  => __( 'Enabling this setting will apply theme CSS and JavaScript for FunnelKit canvas and FunnelKit Boxed made template', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'slug'     => 'allow_theme_css',
		'fields'   => array(
			array(
				'type'             => 'checklist',
				'key'              => 'allow_theme_css',
				'label'            => __( 'Enable theme CSS and JavaScript', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'value'            => [],
				'options'          => array(
					array(
						'value' => 'wfacp_checkout',
						'label' => __( 'Checkout', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					),
					array(
						'value' => 'wffn_landing',
						'label' => __( 'Sales', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					),
					array(
						'value' => 'wfocu_offer',
						'label' => __( 'One Click Upsell Offer', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						'isPro' => true
					),
					array(
						'value' => 'wffn_ty',
						'label' => __( 'Thank you', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					),

					array(
						'value' => 'wffn_optin',
						'label' => __( 'Optin', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					),
					array(
						'value' => 'wffn_oty',
						'label' => __( 'Optin Confirmation', 'woofunnels' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					),
				),
				'select_all'       => true,
				'select_all_label' => __( 'Select All Steps', 'woofunnels' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'orientation'      => 'horizontal'
			)
		),
		'priority' => 25,
	)

) );
