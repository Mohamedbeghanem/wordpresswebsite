import React from "react";

class WFOP_Component extends React.Component {
    static style_data=[];
    constructor() {
        super();
        this.timeout = null;
        this.ajax = false;
        this.c_slug = '';
        this.state = {formData: 'Loading ....'};
    }

    static css(props) {
        const utils = window.ET_Builder.API.Utils;
        let wfop_divi_style = [];
        if (window.hasOwnProperty(this.c_slug + '_fields')) {
            wfop_divi_style = window[this.c_slug + '_fields'](utils, props);
        }

        return wfop_divi_style;
    }

    componentDidMount() {
        if (true != this.ajax) {
            return;
        }
        this.send_json();

    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if (true != this.ajax) {
            return;
        }
        if (JSON.stringify(this.props) === JSON.stringify(prevProps)) {
            return;
        }
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.send_json();
        }, 600);
    }

    send_json() {
        let settings = JSON.stringify(this.props);
        settings = JSON.parse(settings);
        settings.action = this.c_slug;
        settings.post_id = et_pb_custom.page_id;
        settings.et_load_builder_modules = '1';
        let request = {
            url: et_pb_custom.ajaxurl,
            method: 'POST',
            data: settings,
            success: (rsp, jqxhr, status) => {
                rsp = rsp.replace(/\\/g, "");
                this.setState({formData: rsp});
                this.ajaxSuccess(rsp, jqxhr, status);
            },
            complete: (rsp, jqxhr, status) => {


            },
            error: (rsp, jqxhr, status) => {
            }
        };

        jQuery.ajax(request);

    }

    ajaxSuccess(rsp, jqxhr, status) {

    }

    render() {
        return React.createElement("div", {
            className: this.c_slug + " wfop_divi_style",
            dangerouslySetInnerHTML: {
                __html: this.state.formData
            }
        });
    }

}

export default WFOP_Component;