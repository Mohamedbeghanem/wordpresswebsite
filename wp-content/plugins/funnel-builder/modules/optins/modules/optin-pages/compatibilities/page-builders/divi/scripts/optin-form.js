import WFOP_Component from "./abs-component";

class WFOP_Optin_Form extends WFOP_Component {
    static slug = 'et_wfop_optin_form';

    constructor() {
        super();
        this.ajax = true;
        this.c_slug = 'et_wfop_optin_form';
    }

    static css(props) {
        const utils = window.ET_Builder.API.Utils;
        let wfop_divi_style = [];
        if (window.hasOwnProperty(WFOP_Optin_Form.slug + '_fields')) {
            wfop_divi_style = window[WFOP_Optin_Form.slug + '_fields'](utils, props);
        }

        return [wfop_divi_style];
    }

    render() {
        jQuery(document).trigger('wffn_reload_phone_field');
        return super.render();
    }
}

export default WFOP_Optin_Form;