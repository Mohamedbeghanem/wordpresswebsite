<?php
/**
 * Admin Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COD_DZ_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            'COD DZ Order Form Settings',
            'COD DZ Form',
            'manage_options',
            'cod_dz_settings',
            array( $this, 'render_settings_page' )
        );
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h2>COD DZ Order Form Settings</h2>
            <h2 class="nav-tab-wrapper">
                <a href="?page=cod_dz_settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?page=cod_dz_settings&tab=security" class="nav-tab <?php echo $active_tab == 'security' ? 'nav-tab-active' : ''; ?>">Security</a>
            </h2>
            <form action="options.php" method="post">
                <?php
                if ( $active_tab == 'general' ) {
                    settings_fields( 'cod_dz_options_group' );
                    do_settings_sections( 'cod_dz_settings' );
                } else {
                    settings_fields( 'cod_dz_security_options_group' );
                    do_settings_sections( 'cod_dz_security_settings' );
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Register settings, sections, and fields
     */
    public function register_settings() {
        // General Settings
        register_setting( 'cod_dz_options_group', 'cod_dz_default_product' );
        register_setting( 'cod_dz_options_group', 'cod_dz_thank_you_page' );
        register_setting( 'cod_dz_options_group', 'cod_dz_home_delivery_cost' );
        register_setting( 'cod_dz_options_group', 'cod_dz_office_delivery_cost' );
        
        add_settings_section(
            'cod_dz_general_section',
            'General Settings',
            null,
            'cod_dz_settings'
        );
        
        // Security Settings
        register_setting( 'cod_dz_security_options_group', 'cod_dz_security_ip_guard_enable' );
        register_setting( 'cod_dz_security_options_group', 'cod_dz_security_ip_whitelist' );
        register_setting( 'cod_dz_security_options_group', 'cod_dz_security_ip_blacklist' );
        register_setting( 'cod_dz_security_options_group', 'cod_dz_security_honeypot_enable' );
        register_setting( 'cod_dz_security_options_group', 'cod_dz_security_rate_limiter_enable' );
        register_setting( 'cod_dz_security_options_group', 'cod_dz_security_rate_limit' );
        register_setting( 'cod_dz_security_options_group', 'cod_dz_security_rate_limit_period' );
        
        add_settings_section(
            'cod_dz_security_section',
            'Security Settings',
            null,
            'cod_dz_security_settings'
        );
        
        add_settings_field(
            'cod_dz_default_product',
            'Default Product ID',
            array( $this, 'render_default_product_field' ),
            'cod_dz_settings',
            'cod_dz_general_section'
        );
        
        add_settings_field(
            'cod_dz_thank_you_page',
            'Thank You Page',
            array( $this, 'render_thank_you_page_field' ),
            'cod_dz_settings',
            'cod_dz_general_section'
        );
        
        add_settings_field(
            'cod_dz_home_delivery_cost',
            'Home Delivery Cost',
            array( $this, 'render_home_delivery_cost_field' ),
            'cod_dz_settings',
            'cod_dz_general_section'
        );
        
        add_settings_field(
            'cod_dz_office_delivery_cost',
            'Office Delivery Cost',
            array( $this, 'render_office_delivery_cost_field' ),
            'cod_dz_settings',
            'cod_dz_general_section'
        );
        add_settings_field(
            'cod_dz_security_ip_guard_enable',
            'Enable IP Guard',
            array( $this, 'render_checkbox_field' ),
            'cod_dz_security_settings',
            'cod_dz_security_section',
            [ 'name' => 'cod_dz_security_ip_guard_enable' ]
        );
        add_settings_field(
            'cod_dz_security_ip_whitelist',
            'IP Whitelist',
            array( $this, 'render_textarea_field' ),
            'cod_dz_security_settings',
            'cod_dz_security_section',
            [ 'name' => 'cod_dz_security_ip_whitelist', 'description' => 'One IP address per line.' ]
        );
        add_settings_field(
            'cod_dz_security_ip_blacklist',
            'IP Blacklist',
            array( $this, 'render_textarea_field' ),
            'cod_dz_security_settings',
            'cod_dz_security_section',
            [ 'name' => 'cod_dz_security_ip_blacklist', 'description' => 'One IP address per line.' ]
        );
        add_settings_field(
            'cod_dz_security_honeypot_enable',
            'Enable Honeypot',
            array( $this, 'render_checkbox_field' ),
            'cod_dz_security_settings',
            'cod_dz_security_section',
            [ 'name' => 'cod_dz_security_honeypot_enable' ]
        );
        add_settings_field(
            'cod_dz_security_rate_limiter_enable',
            'Enable Rate Limiter',
            array( $this, 'render_checkbox_field' ),
            'cod_dz_security_settings',
            'cod_dz_security_section',
            [ 'name' => 'cod_dz_security_rate_limiter_enable' ]
        );
        add_settings_field(
            'cod_dz_security_rate_limit',
            'Rate Limit',
            array( $this, 'render_number_field' ),
            'cod_dz_security_settings',
            'cod_dz_security_section',
            [ 'name' => 'cod_dz_security_rate_limit', 'default' => 5 ]
        );
        add_settings_field(
            'cod_dz_security_rate_limit_period',
            'Rate Limit Period (seconds)',
            array( $this, 'render_number_field' ),
            'cod_dz_security_settings',
            'cod_dz_security_section',
            [ 'name' => 'cod_dz_security_rate_limit_period', 'default' => 60 ]
        );
    }
    
    /**
     * Render fields
     */
    public function render_default_product_field() {
        echo '<input type="number" name="cod_dz_default_product" value="' . esc_attr( get_option('cod_dz_default_product') ) . '" class="regular-text">';
    }
    
    public function render_thank_you_page_field() {
        wp_dropdown_pages( array(
            'name'              => 'cod_dz_thank_you_page',
            'selected'          => get_option('cod_dz_thank_you_page'),
            'show_option_none'  => '— Select a Page —',
        ) );
    }
    
    public function render_home_delivery_cost_field() {
        echo '<input type="number" name="cod_dz_home_delivery_cost" value="' . esc_attr( get_option('cod_dz_home_delivery_cost', 700) ) . '" class="regular-text">';
    }
    
    public function render_office_delivery_cost_field() {
        echo '<input type="number" name="cod_dz_office_delivery_cost" value="' . esc_attr( get_option('cod_dz_office_delivery_cost', 400) ) . '" class="regular-text">';
    }

    public function render_checkbox_field( $args ) {
        $name = $args['name'];
        $checked = get_option( $name ) ? 'checked' : '';
        echo "<input type='checkbox' name='{$name}' value='1' {$checked}>";
    }

    public function render_textarea_field( $args ) {
        $name = $args['name'];
        $value = get_option( $name, '' );
        echo "<textarea name='{$name}' rows='5' cols='50' class='large-text'>" . esc_textarea( $value ) . "</textarea>";
        if ( isset( $args['description'] ) ) {
            echo "<p class='description'>{$args['description']}</p>";
        }
    }

    public function render_number_field( $args ) {
        $name = $args['name'];
        $default = $args['default'] ?? 0;
        $value = get_option( $name, $default );
        echo "<input type='number' name='{$name}' value='" . esc_attr( $value ) . "' class='regular-text'>";
    }
}
