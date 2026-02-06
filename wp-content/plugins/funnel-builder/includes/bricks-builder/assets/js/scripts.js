function triggerPhoneFieldReload() {
    jQuery(document).trigger('wffn_reload_phone_field');
}

function triggerPopupsReload() {
    jQuery(document).trigger('wffn_reload_popups');
}

function triggerJSHooksCheckout() {
    jQuery(document.body).on('wfacp_editor_init', function (e, v) {
        jQuery(document.body).removeClass('wfacp-inside');
        jQuery(document.body).removeClass('wfacp-top');
        jQuery(document.body).removeClass('wfacp-modern-label');
        jQuery(document.body).addClass(v.position_label);
        jQuery(document.body).trigger('wfacp_intl_setup');
        jQuery(document.body).trigger('wfacp_build_preview_fields');


        if (jQuery('#billing_same_as_shipping_field').length > 0) {
            jQuery(document.body).on('click', '#billing_same_as_shipping_field', function (e) {
                setTimeout(function () {
                    if (jQuery('#billing_same_as_shipping').is(':checked')) {
                        jQuery('#billing_same_as_shipping').prop('checked', false).trigger('change');
                    } else {
                        jQuery('#billing_same_as_shipping').prop('checked', true).trigger('change');
                    }
                }, 100);
            });
        }
        if (jQuery('#shipping_same_as_billing_field').length > 0) {

            jQuery(document.body).on('click', '#shipping_same_as_billing_field', function () {


                setTimeout(function () {
                    if (jQuery('#shipping_same_as_billing').is(':checked')) {
                        jQuery('#shipping_same_as_billing').prop('checked', false).trigger('change');

                    } else {
                        jQuery('#shipping_same_as_billing').prop('checked', true).trigger('change');

                    }
                }, 100);
            });

        }
    });
    jQuery(document.body).trigger('wfacp_editor_init', {'position_label': ''});

}