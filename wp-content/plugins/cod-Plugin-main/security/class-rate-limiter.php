<?php

class COD_DZ_Rate_Limiter {

    public static function check() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'cod_dz_rate_' . $ip;

        $count = intval(get_transient($key));

        if ($count >= 3) return false;

        set_transient($key, $count + 1, 60);

        return true;
    }
}
