<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COD_DZ_Security_Loader {
    private $risk_engine;

    public function __construct() {
        $this->load_dependencies();
        $this->risk_engine = new COD_DZ_Risk_Engine();
    }

    private function load_dependencies() {
        $guards = [
            'class-ip-guard.php',
            'class-honeypot.php',
            'class-rate-limiter.php',
            'class-behavior-guard.php',
            'class-recaptcha.php',
            'class-risk-engine.php'
        ];

        foreach ( $guards as $guard ) {
            require_once plugin_dir_path( __FILE__ ) . $guard;
        }
    }

    public function load_guards() {
        if ( get_option( 'cod_dz_security_ip_guard_enable' ) ) {
            $this->risk_engine->add_guard( new COD_DZ_IP_Guard() );
        }
        if ( get_option( 'cod_dz_security_honeypot_enable' ) ) {
            $this->risk_engine->add_guard( new COD_DZ_Honeypot() );
        }
        if ( get_option( 'cod_dz_security_rate_limiter_enable' ) ) {
            $this->risk_engine->add_guard( new COD_DZ_Rate_Limiter() );
        }
        // $this->risk_engine->add_guard( new COD_DZ_Behavior_Guard() );
        // $this->risk_engine->add_guard( new COD_DZ_Recaptcha() );
    }

    public function get_risk_engine() {
        return $this->risk_engine;
    }
}
