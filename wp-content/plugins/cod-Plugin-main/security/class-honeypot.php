<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COD_DZ_Honeypot {
    public static function render_field() {
        if ( get_option( 'cod_dz_security_honeypot_enable' ) ) {
            // Hidden via inline style (kept minimal)
            echo '<input type="text" name="honeypot_field" value="" style="display:none!important;" tabindex="-1" autocomplete="off">';
        }
    }
}
