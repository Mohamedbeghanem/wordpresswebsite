<?php

class COD_DZ_Risk_Engine {

    public static function evaluate($data) {

        if (!COD_DZ_Honeypot::check($data)) {
            return ['blocked' => true, 'reason' => 'Bot detected (honeypot).'];
        }

        if (!COD_DZ_IP_Guard::allowed()) {
            return ['blocked' => true, 'reason' => 'Access not allowed in your country.'];
        }

        if (!COD_DZ_Behavior_Guard::verify()) {
            return ['blocked' => true, 'reason' => 'Suspicious behavior.'];
        }

        if (!COD_DZ_Rate_Limiter::check()) {
            return ['blocked' => true, 'reason' => 'Rate limit reached.'];
        }

        return ['blocked' => false];
    }
}
