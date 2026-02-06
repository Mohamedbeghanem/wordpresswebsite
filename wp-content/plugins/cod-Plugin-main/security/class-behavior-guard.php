<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COD_DZ_Behavior_Guard {
    public static function verify() {
        // If timer field present, ensure submit took >= 3 seconds
        if ( isset( $_POST['timer'] ) ) {
            $t = intval( $_POST['timer'] );
            if ( $t < 3 ) {
                return false;
            }
        }
        return true;
    }
}
