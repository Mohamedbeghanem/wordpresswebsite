<?php

namespace WPNotif_Compatibility\ContactForm7;


use WPNotif;

if (!defined('ABSPATH')) {
    exit;
}


final class Field_Phone
{
    public static $field_type = 'wpnotif_phone';
    protected static $_instance = null;
    public $type = 'contactform7';
    public $countrycode_prefix = 'countrycode_';

    /**
     *  Constructor.
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('wpcf7_admin_init', array($this, 'add_tag_generator'), 50);
        add_action('wpcf7_init', array($this, 'add_field'), 10, 0);

        add_filter('wpcf7_validate_' . self::$field_type, array($this, 'validate'), 10, 2);
        add_filter('wpcf7_validate_' . self::$field_type . '*', array($this, 'validate'), 10, 2);

        add_filter('wpcf7_posted_data_' . self::$field_type, array($this, 'posted_value'), 10, 3);

    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function posted_value($value, $value_orig, $tag)
    {
        $field_name = $tag['name'];

        $countrycode = sanitize_text_field($_POST[$this->countrycode_prefix . $field_name]);
        $phone = $countrycode . $value;

        return $phone;
    }

    public function validate($result, $tag)
    {
        $name = $tag->name;
        $is_required = $tag->is_required();
        $nationalNumber = isset($_POST[$name]) ? $_POST[$name] : '';

        $countrycode = isset($_POST[$this->countrycode_prefix . $name]) ? $_POST[$this->countrycode_prefix . $name] : '';

        $phone = $countrycode . $nationalNumber;

        $parse = \WPNotif_Handler::parseMobile($phone);
        if (!$parse && !empty($phone)) {
            $result->invalidate($tag, esc_attr__("Please enter a valid number", 'wpnotif'));
        } else if ($is_required && empty($phone) && empty($parse)) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        }

        return $result;
    }

    public function add_field()
    {
        wpcf7_add_form_tag(array(self::$field_type, self::$field_type . '*'),
                array($this, 'form_tag_handler'), array('name-attr' => true));
    }


    public function add_tag_generator()
    {
        $tag_generator = \WPCF7_TagGenerator::get_instance();
        $tag_generator->add(
                self::$field_type,
                __('Phone Number (WPNotif)', 'wpnotif'),
                array($this, 'tag_generator'),
                ['version' => 2]
        );
    }


    public function form_tag_handler($tag)
    {

        if (empty($tag->name)) {
            return '';
        }

        $field = '';
        $validation_error = wpcf7_get_validation_error($tag->name);

        $class = wpcf7_form_controls_class($tag->type);

        $class .= ' wpcf7-validates-as-' . self::$field_type;

        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }

        $atts = array();

        $atts['class'] = $tag->get_class_option($class);
        $atts['id'] = $tag->get_id_option();
        $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

        if ($tag->has_option('readonly')) {
            $atts['readonly'] = 'readonly';
        }

        if ($tag->is_required()) {
            $atts['aria-required'] = 'true';
        }

        $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

        $countrycode = WPNotif::getDefaultCountryCode();


        $atts['value'] = '';

        $atts['type'] = 'text';

        $atts['name'] = $tag->name;

        $atts = wpcf7_format_atts($atts);

        $countrycode_atts = array();
        $countrycode_atts['name'] = $this->countrycode_prefix . $tag->name;
        $countrycode_atts = wpcf7_format_atts($countrycode_atts);


        $field .= sprintf(
                '<span class="wpcf7-form-control-wrap %1$s">', sanitize_html_class($tag->name));


        $field .= "<span class='contactform7_container_mobile wpnotif_phone_field_container'>";
        $field .= '<span class="wpnotif_phonefield">';
        $field .= '<span class="wpnotif_countrycodecontainer">';
        $field .= sprintf('<input type="text" %s
                                   class="wpnotif_countrycode"
                                   value="%s" maxlength="6" size="3"
                                   placeholder="%s" />', $countrycode_atts, $countrycode, $countrycode);
        $field .= '</span>';

        $field .= sprintf('<input %1$s />', $atts);

        $field .= '</span>';

        $field .= '</span>' . $validation_error;

        $field .= '</span>';
        do_action('wpnotif_load_frontend_scripts');

        return $field;
    }

    public function tag_generator($contact_form, $args = '')
    {


        $options = $args;
        $field_types = array(
                self::$field_type => array(
                        'display_name' => __('WPNotif Phone Number field', 'contact-form-7'),
                        'heading' => __('WPNotif Phone Number field form-tag generator', 'contact-form-7'),
                        'description' => __('Generates a form-tag for a <a href="https://wpnotif.unitedover.com">WPNotif Phone field</a>.', 'contact-form-7'),
                ),
        );

        $tgg = new \WPCF7_TagGeneratorGenerator($options['content']);

        $formatter = new \WPCF7_HTMLFormatter();

        $formatter->append_start_tag('header', array(
                'class' => 'description-box',
        ));

        $formatter->append_start_tag('h3');

        $formatter->append_preformatted(
                esc_html($field_types[self::$field_type]['heading'])
        );

        $formatter->end_tag('h3');

        $formatter->append_start_tag('p');

        $formatter->append_preformatted(
                wp_kses_data($field_types[self::$field_type]['description'])
        );

        $formatter->end_tag('header');

        $formatter->append_start_tag('div', array(
                'class' => 'control-box',
        ));

        $formatter->call_user_func(static function () use ($tgg, $field_types) {
            $tgg->print('field_type', array(
                    'with_required' => true,
                    'select_options' => array(
                            self::$field_type => 'WPNotif Phone Number field',
                    ),
            ));

            $tgg->print('field_name');

            $tgg->print('class_attr');

        });

        $formatter->end_tag('div');

        $formatter->append_start_tag('footer', array(
                'class' => 'insert-box',
        ));

        $formatter->call_user_func(static function () use ($tgg, $field_types) {
            $tgg->print('insert_box_content');
        });

        $formatter->print();

    }

}