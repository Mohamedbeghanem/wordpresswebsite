require('./divi.js');
import WFOP_Optin_Form from "./optin-form";

(function ($) {
    $(window).on('et_builder_api_ready', (event, API) => {
        API.registerModules(
            [
                WFOP_Optin_Form
            ]
        );
    });
})(jQuery);