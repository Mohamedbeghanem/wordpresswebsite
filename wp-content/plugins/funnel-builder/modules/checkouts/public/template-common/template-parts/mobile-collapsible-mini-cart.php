<?php
if ( ! defined( 'WFACP_TEMPLATE_DIR' ) ) {
	return '';
}
$checkout            = WC()->checkout();
$template            = wfacp_template();
$cart_collapse_title = $template->get_mobile_mini_cart_collapsible_title();
$cart_expanded_title = $template->get_mobile_mini_cart_expand_title();

$class_added = '';
if ( $cart_collapse_title == '' || $cart_expanded_title == '' ) {
	$class_added = 'wfacp_no_title';
}

// Add responsive collapsed classes - INVERTED LOGIC
$collapsed_classes = [];


if ( method_exists( $template, 'enable_order_field_collapsed_by_default' ) ) {

	// Desktop: If enabled, element should be VISIBLE (not collapsed)

	if ( $template->enable_order_field_collapsed_by_default() ) {
		$collapsed_classes[] = 'wfacp_collapsed_active_desktop';
		$collapsed_classes[] = 'wfacp_collapsed_trigger';



	}
	// Tablet: If enabled, element should be VISIBLE (not collapsed)
	if ( $template->enable_order_field_collapsed_by_default( 'tablet' ) ) {
		$collapsed_classes[] = 'wfacp_collapsed_active_tablet wfacp_collapsed_trigger';
		$collapsed_classes[] = 'wfacp_collapsed_trigger';

	}
	// Mobile: If enabled, element should be VISIBLE (not collapsed)
	if ( $template->enable_order_field_collapsed_by_default( 'mobile' ) ) {
		$collapsed_classes[] = 'wfacp_collapsed_active_mobile wfacp_collapsed_trigger';
		$collapsed_classes[] = 'wfacp_collapsed_trigger';

	}
}

// Only add wfacp_display_none if no responsive collapsed classes are active
$display_class = method_exists( $template, 'should_hide_order_summary_by_default' ) && $template->should_hide_order_summary_by_default() ? '' : 'wfacp_display_none';
$css='width: 16px;height: 16px;position: absolute;left: 0;top: 50%;margin-top: -8px;';
if(is_rtl()){
    $css='width: 16px;height: 16px;position: absolute;right: 0;top: 50%;margin-top: -8px;';
}

?>
<div class="wfacp_anim wfacp_order_summary_container wfacp_mb_mini_cart_wrap ">
    <div class="wfacp_mb_cart_accordian clearfix" attr-collaps="<?php echo $cart_collapse_title; ?>" attr-expend="<?php echo $cart_expanded_title; ?>">
        <div class="wfacp_show_icon_wrap <?php echo $class_added; ?>" style="background: none;">
            <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16px" height="16px" viewBox="0 0 446.853 446.853" style="<?php echo $css; ?>"
                 xml:space="preserve">
<g>
    <path d="M444.274,93.36c-2.558-3.666-6.674-5.932-11.145-6.123L155.942,75.289c-7.953-0.348-14.599,5.792-14.939,13.708
		c-0.338,7.913,5.792,14.599,13.707,14.939l258.421,11.14L362.32,273.61H136.205L95.354,51.179
		c-0.898-4.875-4.245-8.942-8.861-10.753L19.586,14.141c-7.374-2.887-15.695,0.735-18.591,8.1c-2.891,7.369,0.73,15.695,8.1,18.591
		l59.491,23.371l41.572,226.335c1.253,6.804,7.183,11.746,14.104,11.746h6.896l-15.747,43.74c-1.318,3.664-0.775,7.733,1.468,10.916
		c2.24,3.184,5.883,5.078,9.772,5.078h11.045c-6.844,7.617-11.045,17.646-11.045,28.675c0,23.718,19.299,43.012,43.012,43.012
		s43.012-19.294,43.012-43.012c0-11.028-4.201-21.058-11.044-28.675h93.777c-6.847,7.617-11.047,17.646-11.047,28.675
		c0,23.718,19.294,43.012,43.012,43.012c23.719,0,43.012-19.294,43.012-43.012c0-11.028-4.2-21.058-11.042-28.675h13.432
		c6.6,0,11.948-5.349,11.948-11.947c0-6.6-5.349-11.948-11.948-11.948H143.651l12.902-35.843h216.221
		c6.235,0,11.752-4.028,13.651-9.96l59.739-186.387C447.536,101.679,446.832,97.028,444.274,93.36z M169.664,409.814
		c-10.543,0-19.117-8.573-19.117-19.116s8.574-19.117,19.117-19.117s19.116,8.574,19.116,19.117S180.207,409.814,169.664,409.814z
		 M327.373,409.814c-10.543,0-19.116-8.573-19.116-19.116s8.573-19.117,19.116-19.117s19.116,8.574,19.116,19.117
		S337.916,409.814,327.373,409.814z"/>
</g>
</svg>
            <a href="#" class="wfacp_summary_link">
                <span><?php echo $cart_collapse_title; ?></span>
                <img src="<?php echo WFACP_PLUGIN_URL . '/assets/img/down-arrow.svg'; ?>" alt="">
            </a>
        </div>
        <div class="wfacp_show_price_wrap">
            <div class="wfacp_cart_mb_fragment_price">
                <span><?php echo wc_price( WC()->cart->total ); ?></span>
            </div>
        </div>
    </div>
    <div class="wfacp_mb_mini_cart_sec_accordion_content wfacp_display_none <?php echo implode( ' ', $collapsed_classes ); ?>">
		<?php
		do_action( 'wfacp_before_sidebar_content' ); ?>
    </div>
</div>
