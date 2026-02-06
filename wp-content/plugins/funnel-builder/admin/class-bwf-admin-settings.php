<?php
/**
 * Class to control Settings and its behaviour across the BWF
 * @author buildwoofunnels
 */
if ( ! class_exists( 'BWF_Admin_Settings' ) ) {

	#[AllowDynamicProperties]
	class BWF_Admin_Settings {

		private static $ins = null;

		public function __construct() {
			add_action( 'admin_menu', [ $this, 'maybe_register_admin_menu' ], 900 );
			add_filter( 'woofunnels_global_settings', function ( $menu ) {
				array_push( $menu, array(
					'title'    => __( 'Tools', 'funnel-builder' ),
					'slug'     => 'tools',
					'link'     => apply_filters( 'tools', 'javascript:void(0)' ),
					'priority' => 70,
				) );

				return $menu;
			} );

            if(wffn_is_wc_active()) {
	            add_filter( 'woofunnels_global_settings', function ( $menu ) {
		            array_push( $menu, array(
			            'title'          => __( 'Stripe', 'funnel-builder' ),
			            'slug'           => 'stripe',
			            'link'           => apply_filters( 'stripe', 'javascript:void(0)' ),
			            'is_recommended' => true,
			            'priority'       => 6,
		            ) );

		            return $menu;
	            },4 );
            }
			add_filter( 'woofunnels_global_settings_fields', function ( $settings ) {
				$settings['tools'] = array(
					'tools' => array(
						'title'   => __( 'Tools', 'funnel-builder' ),
						'heading' => __( 'Tools', 'funnel-builder' ),
						'slug'    => 'tools',
					),
					'logs'  => array(
						'title'   => __( 'Logs', 'funnel-builder' ),
						'heading' => __( 'Logs', 'funnel-builder' ),
						'slug'    => 'tools',
					),
				);

				return $settings;
			} );
		}

		public static function get_instance() {

			if ( null === self::$ins ) {
				self::$ins = new self;
			}

			return self::$ins;
		}

		public function maybe_register_admin_menu() {
			global $submenu;
			if ( isset( $submenu['woofunnels'] ) ) {
				foreach ( $submenu['woofunnels'] as $menu ) {
					if ( 'woofunnels_settings' === $menu[2] ) {
						$found = 1;
						break;
					}
				}
			}
			$user = WFFN_Role_Capability::get_instance()->user_access( 'menu', 'read' );
			if ( $user ) {
				if ( empty( $found ) ) {
					add_submenu_page( 'woofunnels', __( 'Settings', 'funnel-builder' ), __( 'Settings', 'funnel-builder' ), $user, 'bwf&path=/settings', array(
						$this,
						'callback',
					) );
				}
			}
		}

		public function render_tab_html( $current ) {
			$get_all_registered_settings = apply_filters( 'woofunnels_global_settings', [] );

			if ( is_array( $get_all_registered_settings ) && count( $get_all_registered_settings ) > 0 ) {
				usort( $get_all_registered_settings, function ( $a, $b ) {
					if ( $a['priority'] === $b['priority'] ) {
						return 0;
					}

					return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
				} );

				?>

                <div class="bwf_menu_list_primary">
                    <ul>

						<?php
						foreach ( $get_all_registered_settings as $menu ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
							$class = '';
							if ( $menu['slug'] === $current ) {
								$class = 'active';
							}
							?>
                            <li class="<?php echo esc_attr( $class ); ?>">
                                <a href="<?php echo esc_url_raw( $menu['link'] ); ?>">
									<?php echo esc_attr( $menu['title'] ); ?>
                                </a>
                            </li>
							<?php

						}
						?>
                    </ul>
                </div>
				<?php
			}
		}


	}
}
BWF_Admin_Settings::get_instance();
