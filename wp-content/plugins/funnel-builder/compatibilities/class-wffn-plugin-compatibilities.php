<?php

/**
 * Class WFFN_Plugin_Compatibilities
 * Loads all the compatibilities files we have to provide compatibility with each plugin
 */
if ( ! class_exists( 'WFFN_Plugin_Compatibilities' ) ) {
	class WFFN_Plugin_Compatibilities {

		public static $plugin_compatibilities = array();

		public static function load_all_compatibilities() {
			$compatibilities = array(
				'class-wffn-beaver-builder-compatibility.php'            => class_exists( 'FLBuilderLoader' ),
				'class-wffn-cartflows-compatibility.php'                 => class_exists( 'Cartflows_Checkout_Markup' ),
				'class-wffn-nextmove-compatibility.php'                  => class_exists( 'xlwcty' ),
				'class-wffn-oxygen-builder-compatibility.php'            => true, //force load for now
				'class-wffn-pixel-cog.php'                               => defined( 'PIXEL_COG_VERSION' ),
				'class-wffn-thrive-theme-compatibility.php'              => defined( 'THRIVE_TEMPLATE' ),
				'class-wffn-ux-builder-compatibility.php'                => function_exists( 'add_ux_builder_post_type' ),
				'class-wffn-wc-cashfree.php'                             => class_exists( 'WC_Gateway_cashfree' ),
				'class-wffn-wc-deposite.php'                             => function_exists( 'wc_deposits_woocommerce_is_active' ) && wc_deposits_woocommerce_is_active(),
				'class-wffn-weglot-compatibility.php'                    => ( defined( 'WEGLOT_VERSION' ) || class_exists( 'WeglotWP\Third\Woocommerce\WC_Filter_Urls_Weglot' ) ),
				'class-wffn-breakdance-builder-compatibility.php'        => defined( 'BREAKDANCE_WOO_DIR' ),
				'class-wffn-pys-compatibility.php'                       => class_exists( 'PixelYourSite\EventsManager' ),
				'class-wffn-paghiper-compatibility.php'                 => class_exists( 'WC_Paghiper' ),
				'rest/class-bwfan-compatibility-with-sg-cache.php'       => function_exists( 'sg_cachepress_purge_cache' ),
				'rest/class-wffn-clearfy-compatibility.php'              => class_exists( 'Clearfy_Plugin' ),
				'rest/class-wffn-force-login-compability.php'            => function_exists( 'v_forcelogin_rest_access' ),
				'rest/class-wffn-password-protected-compability.php'     => class_exists( 'Password_Protected' ),
				'rest/class-wffn-permatters-compability.php'             => defined( 'PERFMATTERS_VERSION' ),
				'rest/class-wffn-wp-rest-authenticate-compatibility.php' => function_exists( 'mo_api_auth_activate_miniorange_api_authentication' ),
				'class-wffn-wpml-plugin-compatibility.php'               => class_exists( 'SitePress' ) && defined( 'ICL_SITEPRESS_VERSION' ),
				'class-wffn-polylang-plugin-compatibility.php'           => function_exists( 'pll_default_language' ) && function_exists( 'pll_home_url' ),
			);

			add_action( 'after_setup_theme', [ __CLASS__, 'themes' ] );
			self::add_files( $compatibilities );
		}

		public static function add_files( $paths ) {
			try {
				foreach ( $paths as $file => $condition ) {
					if ( false === $condition ) {
						continue;
					}
					include_once __DIR__ . '/' . $file;
				}
			} catch ( Exception|Error $e ) {
				BWF_Logger::get_instance()->log( 'Error while loading compatibility files: ' . $e->getMessage(), 'wffn-compatibilities' );
			}
		}

		public static function themes() {
			$themes_compatibilities = array(
				'class-wffn-bricks-theme-compatibility.php'  => function_exists( 'bricks_is_builder' ),
				'class-wffn-divi-theme-compatibility.php'    => defined( 'ET_CORE_VERSION' ) || function_exists( 'et_setup_theme' ),
				'class-wffn-enfold-theme-compatibility.php'  => class_exists( 'AviaBuilder' ),
				'class-wffn-rehub-theme-compatibility.php'   => defined( 'RH_MAIN_THEME_VERSION' ),
				'class-wffn-woodmart-theme-compatibilty.php' => defined( 'WOODMART_THEME_DIR' ),
				'class-wffn-thrive-theme-compatibility.php'  => defined( 'THRIVE_TEMPLATE' ),
				'class-wffn-ux-builder-compatibility.php'    => function_exists( 'add_ux_builder_post_type' ),
				'class-wffn-avada-theme-compatibility.php'   => class_exists( 'FusionBuilder' ),
			);
			self::add_files( $themes_compatibilities );

		}

		public static function register( $object, $slug ) {
			self::$plugin_compatibilities[ $slug ] = $object;
		}

		public static function get_compatibility_class( $slug ) {
			return ( isset( self::$plugin_compatibilities[ $slug ] ) ) ? self::$plugin_compatibilities[ $slug ] : false;
		}



		public static function get_language_compatible_plugin() {
			if ( empty( self::$plugin_compatibilities ) ) {
				return '';
			}

			foreach ( self::$plugin_compatibilities as $plugins_class ) {
				if ( property_exists( $plugins_class, 'is_language_support' ) && true === $plugins_class->is_language_support ) {
					return call_user_func( array( $plugins_class, 'get_plugin_nicename' ) );
				}
			}

			return '';
		}


	}

	WFFN_Plugin_Compatibilities::load_all_compatibilities();
}

