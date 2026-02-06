<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Class WFFN_Admin_Dashboard_Widget
 * Handles All the methods about admin notifications
 */
if ( ! class_exists( 'WFFN_Admin_Dashboard_Widget' ) ) {
	#[AllowDynamicProperties]
	class WFFN_Admin_Dashboard_Widget {

		/**
		 * @var WFFN_Admin_Dashboard_Widget|null
		 */

		private static $ins = null;

		public function __construct() {

			$this->widget_register();
		}

		/**
		 * @return WFFN_Admin_Dashboard_Widget|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}


		public function widget_register() {

			$widget_key = 'funnelkit_widget';

			if ( WFFN_Role_Capability::get_instance()->user_access( 'funnel', 'read' ) ) {
				add_meta_box( $widget_key, esc_html__( 'FunnelKit', 'funnel-builder' ), array( $this, 'widget_content' ), 'dashboard', 'normal', 'high' );

			}
		}

		public function widget_content() {

			$license_config     = WFFN_Core()->admin->get_license_config();
			$app_state          = $this->get_current_app_state( $license_config );
			$all_texts_from_pro = apply_filters( 'wffn_localized_text_admin', array() );

			wp_enqueue_script( 'wp-api' );

			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );

			if ( wffn_is_wc_active() ) {
				if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '10.3.0', '>=' ) ) {
					wp_enqueue_script( 'wc-accounting' );
				} else {
					wp_enqueue_script( 'accounting' );
				}
				$price_args = apply_filters(
					'wc_price_args',
					array(
						'ex_tax_label'       => false,
						'currency'           => '',
						'decimal_separator'  => wc_get_price_decimal_separator(),
						'thousand_separator' => wc_get_price_thousand_separator(),
						'decimals'           => wc_get_price_decimals(),
						'price_format'       => get_woocommerce_price_format(),
					)
				);

				$accounting_handle = ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '10.3.0', '>=' ) ) ? 'wc-accounting' : 'accounting';
				wp_localize_script(
					$accounting_handle,
					'wffn_dashboard_params_accounting',
					array(
						'currency_format_num_decimals' => $price_args['decimals'],
						'currency_format_symbol'       => get_woocommerce_currency_symbol(),
						'currency_format_decimal_sep'  => esc_attr( $price_args['decimal_separator'] ),
						'currency_format_thousand_sep' => esc_attr( $price_args['thousand_separator'] ),
						'currency_format'              => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), $price_args['thousand_separator'] ) ),
					)
				);
			}

			$current_user_id = get_current_user_id();
			wp_localize_script(
				'wp-pointer',
				'wffn',
				array(
					'current_user_id' => $current_user_id,
				)
			);
			?>
			<script type="text/html" id="tmpl-wffn-container-template">
				<div class="bwf-tiles">
					<a href="<?php echo esc_url( site_url() ); ?>/wp-admin/admin.php?page=bwf<?php echo $app_state !== 'lite' ? '&path=/analytics' : ''; ?>" class="bwf-tiles-item">
						<div class="bwf-tiles-header">
							<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect width="28" height="28" rx="14" fill="#F1F2F9"/>
								<path d="M15.3541 5.92873C14.4849 5.58105 13.5153 5.58105 12.6461 5.92873L6.64895 8.32757C6.05573 8.56486 5.66675 9.1394 5.66675 9.77832V17.7428C5.66675 18.3817 6.05573 18.9563 6.64895 19.1936L12.6461 21.5924C13.5153 21.9401 14.4849 21.9401 15.3541 21.5924L21.3512 19.1936C21.9444 18.9563 22.3334 18.3817 22.3334 17.7428V9.77832C22.3334 9.1394 21.9444 8.56486 21.3512 8.32757L15.3541 5.92873ZM13.0329 6.89589C13.6538 6.64755 14.3464 6.64755 14.9672 6.89589L20.4103 9.07312L18.0367 10.0226L11.6265 7.45847L13.0329 6.89589ZM10.2241 8.01943L16.6343 10.5835L14.0002 11.6371L7.59001 9.07306L10.2241 8.01943ZM14.5211 12.5507L21.2917 9.84245V17.7428C21.2917 17.9558 21.1621 18.1473 20.9643 18.2264L14.9672 20.6253C14.8218 20.6834 14.6725 20.728 14.5211 20.7589V12.5507ZM13.4794 12.5507V20.7589C13.3279 20.728 13.1784 20.6835 13.0329 20.6253L7.03582 18.2264C6.83808 18.1473 6.70841 17.9558 6.70841 17.7428V9.84233L13.4794 12.5507Z" fill="#353030"/>
							</svg>
							<?php echo esc_html__( 'Total Orders', 'funnel-builder' ); ?>
						</div>
						<div class="bwf-tiles-value-wrap"><span class="bwf-tiles-value">{{data.overall.total_orders}}</span></div>
					</a>
					<a href="<?php echo esc_url( site_url() ); ?>/wp-admin/admin.php?page=bwf<?php echo $app_state !== 'lite' ? '&path=/analytics' : ''; ?>" class="bwf-tiles-item">
						<div class="bwf-tiles-header">
							<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect width="28" height="28" rx="14" fill="#F1F2F9"/>
								<path d="M8.0625 5.66797C7.19956 5.66797 6.5 6.36752 6.5 7.23047V20.7721C6.5 21.6351 7.19955 22.3346 8.0625 22.3346H15.3542C16.2171 22.3346 16.9167 21.6351 16.9167 20.7721V19.2096C16.9167 18.922 16.6833 18.6886 16.3957 18.6886C15.9652 18.6886 15.6902 18.5826 15.5039 18.4428C15.3138 18.3002 15.1688 18.087 15.0624 17.7944C14.8395 17.1815 14.8333 16.3702 14.8332 15.5635C14.8332 15.4254 14.7783 15.293 14.6806 15.1953L14.3819 14.8964C14.2686 14.783 14.1303 14.6446 13.1183 13.6327C12.6319 13.1464 12.4897 12.8199 12.4629 12.6376C12.4415 12.4928 12.4852 12.3935 12.5892 12.294C12.8128 12.08 12.9715 11.9352 13.1512 11.8915C13.2594 11.8652 13.5046 11.8464 13.9442 12.286L17.0692 15.411C17.2726 15.6144 17.6024 15.6144 17.8058 15.411C18.0092 15.2076 18.0092 14.8778 17.8058 14.6744L16.9167 13.7853V10.5712L19.584 13.2386C19.877 13.5316 20.0417 13.929 20.0417 14.3434V21.8138C20.0417 22.1015 20.2749 22.3346 20.5625 22.3346C20.8501 22.3346 21.0833 22.1015 21.0833 21.8138V14.3434C21.0833 13.6527 20.809 12.9904 20.3206 12.502L16.9167 9.09807V7.23047C16.9167 6.36752 16.2171 5.66797 15.3542 5.66797H8.0625ZM15.875 9.31329C15.875 9.31295 15.875 9.31363 15.875 9.31329V12.7436L14.6808 11.5494C14.0787 10.9474 13.4795 10.7397 12.9051 10.8794C12.7887 10.9077 12.6799 10.9496 12.5792 10.9993C12.3028 10.9192 12.0106 10.8763 11.7083 10.8763C9.98244 10.8763 8.58333 12.2754 8.58333 14.0013C8.58333 15.7272 9.98244 17.1263 11.7083 17.1263C12.5122 17.1263 13.2452 16.8228 13.799 16.324C13.8168 16.926 13.8775 17.5841 14.0834 18.1504C14.2374 18.574 14.483 18.9792 14.8788 19.2761L14.8855 19.2811C14.2516 19.4802 13.7917 20.0725 13.7917 20.7721V21.293H9.625V20.7721C9.625 19.9092 8.92545 19.2096 8.0625 19.2096H7.54167V8.79297H8.0625C8.92545 8.79297 9.625 8.09341 9.625 7.23047V6.70964H13.7917V7.23047C13.7917 8.09341 14.4912 8.79297 15.3542 8.79297H15.875V9.31329ZM15.8748 20.7719L15.8749 20.7811C15.8701 21.0646 15.6388 21.293 15.3542 21.293H14.8333V20.7721C14.8333 20.4845 15.0665 20.2513 15.3542 20.2513H15.8748V20.7719ZM9.625 14.0013C9.625 12.8948 10.4876 11.9898 11.5771 11.922C11.4482 12.1626 11.3827 12.4531 11.4323 12.7895C11.5064 13.292 11.8264 13.814 12.3818 14.3693L13.3263 15.3138C12.9443 15.7841 12.3614 16.0846 11.7083 16.0846C10.5577 16.0846 9.625 15.1519 9.625 14.0013ZM8.58333 6.70964V7.23047C8.58333 7.51812 8.35015 7.7513 8.0625 7.7513H7.54167V7.23047C7.54167 6.94282 7.77485 6.70964 8.0625 6.70964H8.58333ZM7.54167 20.2513H8.0625C8.35015 20.2513 8.58333 20.4845 8.58333 20.7721V21.293H8.0625C7.77485 21.293 7.54167 21.0598 7.54167 20.7721V20.2513ZM15.875 7.7513H15.3542C15.0665 7.7513 14.8333 7.51812 14.8333 7.23047V6.70964H15.3542C15.6418 6.70964 15.875 6.94282 15.875 7.23047V7.7513Z" fill="#353030"/>
							</svg>
							<?php echo esc_html__( 'Total Revenue', 'funnel-builder' ); ?>
						</div>

						<div class="bwf-tiles-value-wrap"><span class="bwf-tiles-value">{{data.formatMoney(data.overall.revenue)}}</span></div>
					</a>
					<a href="<?php echo esc_url( site_url() ); ?>/wp-admin/admin.php?page=bwf<?php echo $app_state !== 'lite' ? '&path=/analytics' : ''; ?>" class="bwf-tiles-item">
						<div class="bwf-tiles-header">
							<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect width="28" height="28" rx="14" fill="#F1F2F9"/>
								<path d="M9.08801 7.99521C9.14946 7.8592 9.30872 7.79771 9.44563 7.85713L14.9636 10.252C15.1034 10.3126 15.1665 10.476 15.1037 10.6149C15.0423 10.751 14.883 10.8125 14.7461 10.753L9.22816 8.3582C9.08834 8.29751 9.02525 8.13412 9.08801 7.99521Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M12.4137 20.0368C12.1831 20.1201 11.9299 20.1157 11.7022 20.0245L6.29492 17.8587C5.9155 17.7067 5.66675 17.3391 5.66675 16.9304V9.7357C5.66675 9.32697 5.91549 8.95938 6.29492 8.8074L11.6622 6.65757C11.9137 6.55686 12.1952 6.56245 12.4425 6.67307L17.8529 9.09374C18.2128 9.25476 18.4445 9.61225 18.4445 10.0065V13.3274C18.4445 13.4809 18.3202 13.6052 18.1667 13.6052C18.0133 13.6052 17.889 13.4809 17.889 13.3274V10.3356C17.889 9.94011 17.6559 9.58175 17.2943 9.42144L12.4416 7.27002C12.1948 7.16062 11.9143 7.15562 11.6638 7.25616L6.84984 9.1882C6.47075 9.34034 6.2223 9.70776 6.2223 10.1162V16.5497C6.2223 16.9582 6.47075 17.3256 6.84984 17.4777L11.7061 19.4267C11.9314 19.5171 12.1819 19.5225 12.4109 19.4418L14.2637 18.7889C14.4084 18.7379 14.5667 18.8164 14.6138 18.9625C14.6588 19.1023 14.585 19.2526 14.4469 19.3025L12.4137 20.0368Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M5.94211 9.24447C5.99961 9.10673 6.15708 9.04071 6.29562 9.09625L11.9074 11.346C12.0488 11.4027 12.1164 11.564 12.0578 11.7046C12.0003 11.8423 11.8428 11.9083 11.7043 11.8528L6.09246 9.60303C5.95111 9.54636 5.88345 9.385 5.94211 9.24447Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M12.3334 19.5894C12.3334 19.7428 12.209 19.8672 12.0556 19.8672C11.9022 19.8672 11.7778 19.7428 11.7778 19.5894V12.2165C11.7778 11.7929 12.0448 11.4152 12.4441 11.2738L17.8359 9.36497C17.9721 9.31675 18.1214 9.38913 18.168 9.52591C18.2136 9.65994 18.1433 9.80579 18.01 9.85361L12.9957 11.6531C12.5985 11.7957 12.3335 12.1723 12.3335 12.5943L12.3334 19.5894Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M18.1667 21.499C15.861 21.499 14 19.6751 14 17.4155C14 15.1559 15.861 13.332 18.1667 13.332C20.4723 13.332 22.3333 15.1559 22.3333 17.4155C22.3333 19.6751 20.4723 21.499 18.1667 21.499ZM18.1667 13.8765C16.1667 13.8765 14.5556 15.4555 14.5556 17.4155C14.5556 19.3755 16.1667 20.9545 18.1667 20.9545C20.1666 20.9545 21.7778 19.3755 21.7778 17.4155C21.7778 15.4555 20.1666 13.8765 18.1667 13.8765Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M16.0219 17.4331C16.0234 17.2834 16.1443 17.1625 16.294 17.1609L20.0341 17.1232C20.1881 17.1217 20.3133 17.2469 20.3118 17.4009C20.3102 17.5506 20.1893 17.6715 20.0396 17.6731L16.2995 17.7108C16.1455 17.7123 16.0203 17.5871 16.0219 17.4331Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M17.9107 15.5442C17.9123 15.3945 18.0332 15.2736 18.1829 15.2721C18.3369 15.2705 18.4621 15.3957 18.4606 15.5497L18.4229 19.2898C18.4214 19.4395 18.3004 19.5604 18.1507 19.5619C17.9967 19.5635 17.8715 19.4383 17.873 19.2843L17.9107 15.5442Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
							</svg>

							<?php echo esc_html__( 'Order Bump Revenue', 'funnel-builder' ); ?>
							<?php if ( in_array( $app_state, array( 'lite', 'basic', 'pro_without_license', 'license_expired_on_grace_period', 'license_expired' ), true ) ) { ?>
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="10" cy="10" r="10" fill="#FFC65C"/>
									<path d="M9.46545 6.7153C8.92 8.24666 8.30909 8.99046 7.75273 9.04515C7.13091 9.12172 6.58546 8.84826 6.07273 8.17009C6.0413 8.12899 6.00463 8.09221 5.96364 8.06071C6.11109 7.86358 6.18784 7.62231 6.18145 7.37599C6.17507 7.12967 6.08592 6.89273 5.92846 6.70355C5.77099 6.51437 5.55444 6.38404 5.31388 6.33368C5.07332 6.28332 4.82284 6.31587 4.60304 6.42607C4.38324 6.53626 4.20698 6.71764 4.10282 6.94082C3.99867 7.164 3.97272 7.41591 4.02918 7.65572C4.08565 7.89553 4.22121 8.10921 4.41391 8.26212C4.60661 8.41504 4.84516 8.49824 5.09091 8.49824L5.10182 8.62949L5.99636 12.1735C6.08385 12.5283 6.28697 12.8437 6.57353 13.0696C6.86009 13.2954 7.21367 13.4189 7.57818 13.4204H12.4218C12.7863 13.4189 13.1399 13.2954 13.4265 13.0696C13.713 12.8437 13.9162 12.5283 14.0036 12.1735L14.8982 8.62949C14.9067 8.58629 14.9103 8.54226 14.9091 8.49824C15.1548 8.49824 15.3934 8.41504 15.5861 8.26212C15.7788 8.10921 15.9144 7.89553 15.9708 7.65572C16.0273 7.41591 16.0013 7.164 15.8972 6.94082C15.793 6.71764 15.6168 6.53626 15.397 6.42607C15.1772 6.31587 14.9267 6.28332 14.6861 6.33368C14.4456 6.38404 14.229 6.51437 14.0715 6.70355C13.9141 6.89273 13.8249 7.12967 13.8185 7.37599C13.8122 7.62231 13.8889 7.86358 14.0364 8.06071C13.996 8.08883 13.9594 8.1219 13.9273 8.15915C13.3927 8.83732 12.8364 9.11078 12.2473 9.04515C11.7018 8.99046 11.1127 8.23572 10.5345 6.7153C10.7448 6.59679 10.91 6.41174 11.0042 6.18909C11.0985 5.96644 11.1164 5.71876 11.0553 5.48477C10.9943 5.25079 10.8575 5.04371 10.6666 4.89592C10.4756 4.74813 10.2412 4.66797 10 4.66797C9.75878 4.66797 9.52436 4.74813 9.33341 4.89592C9.14246 5.04371 9.00575 5.25079 8.94466 5.48477C8.88356 5.71876 8.90154 5.96644 8.99577 6.18909C9.09 6.41174 9.25518 6.59679 9.46545 6.7153ZM13.2727 14.2408C13.4174 14.2408 13.5561 14.2984 13.6584 14.401C13.7607 14.5036 13.8182 14.6427 13.8182 14.7877C13.8182 14.9328 13.7607 15.0719 13.6584 15.1744C13.5561 15.277 13.4174 15.3346 13.2727 15.3346H6.72727C6.58261 15.3346 6.44387 15.277 6.34158 15.1744C6.23929 15.0719 6.18182 14.9328 6.18182 14.7877C6.18182 14.6427 6.23929 14.5036 6.34158 14.401C6.44387 14.2984 6.58261 14.2408 6.72727 14.2408H13.2727Z" fill="white"/>
								</svg>
							<?php } ?>

						</div>
						<div class="bwf-tiles-value-wrap"><span class="bwf-tiles-value">{{data.formatMoney(data.overall.bump_revenue)}}</span></div>
					</a>
					<a href="<?php echo esc_url( site_url() ); ?>/wp-admin/admin.php?page=bwf<?php echo $app_state !== 'lite' ? '&path=/analytics' : ''; ?>" class="bwf-tiles-item">
						<div class="bwf-tiles-header">
							<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect width="28" height="28" rx="14" fill="#F1F2F9"/>
								<g clip-path="url(#clip0_283_11169)">
									<path d="M14.0001 5.66797C18.6022 5.66797 22.3334 9.39922 22.3334 14.0013C22.3334 18.6034 18.6022 22.3346 14.0001 22.3346C9.398 22.3346 5.66675 18.6034 5.66675 14.0013C5.66675 9.39922 9.398 5.66797 14.0001 5.66797ZM14.0001 6.82561C10.0431 6.82561 6.82439 10.0444 6.82439 14.0013C6.82439 17.9582 10.0431 21.177 14.0001 21.177C17.957 21.177 21.1758 17.9582 21.1758 14.0013C21.1758 10.0444 17.957 6.82561 14.0001 6.82561Z" fill="#353030"/>
									<path fill-rule="evenodd" clip-rule="evenodd" d="M13.197 10.5C13.197 10.2239 13.4209 10 13.697 10H17.3334C17.6095 10 17.8334 10.2239 17.8334 10.5V14.1364C17.8334 14.4125 17.6095 14.6364 17.3334 14.6364C17.0572 14.6364 16.8334 14.4125 16.8334 14.1364V11H13.697C13.4209 11 13.197 10.7761 13.197 10.5Z" fill="#353030"/>
									<path fill-rule="evenodd" clip-rule="evenodd" d="M17.687 10.1464C17.8822 10.3417 17.8822 10.6583 17.687 10.8536L11.0203 17.5202C10.825 17.7155 10.5085 17.7155 10.3132 17.5202C10.1179 17.325 10.1179 17.0084 10.3132 16.8131L16.9799 10.1464C17.1751 9.95118 17.4917 9.95118 17.687 10.1464Z" fill="#353030"/>
								</g>
								<defs>
									<clipPath id="clip0_283_11169">
										<rect width="20" height="20" fill="white" transform="translate(4 4)"/>
									</clipPath>
								</defs>
							</svg>
							<?php echo esc_html__( 'Upsell Revenue', 'funnel-builder' ); ?>
							<?php if ( in_array( $app_state, array( 'lite', 'basic', 'pro_without_license', 'license_expired_on_grace_period', 'license_expired' ), true ) ) { ?>
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="10" cy="10" r="10" fill="#FFC65C"/>
									<path d="M9.46545 6.7153C8.92 8.24666 8.30909 8.99046 7.75273 9.04515C7.13091 9.12172 6.58546 8.84826 6.07273 8.17009C6.0413 8.12899 6.00463 8.09221 5.96364 8.06071C6.11109 7.86358 6.18784 7.62231 6.18145 7.37599C6.17507 7.12967 6.08592 6.89273 5.92846 6.70355C5.77099 6.51437 5.55444 6.38404 5.31388 6.33368C5.07332 6.28332 4.82284 6.31587 4.60304 6.42607C4.38324 6.53626 4.20698 6.71764 4.10282 6.94082C3.99867 7.164 3.97272 7.41591 4.02918 7.65572C4.08565 7.89553 4.22121 8.10921 4.41391 8.26212C4.60661 8.41504 4.84516 8.49824 5.09091 8.49824L5.10182 8.62949L5.99636 12.1735C6.08385 12.5283 6.28697 12.8437 6.57353 13.0696C6.86009 13.2954 7.21367 13.4189 7.57818 13.4204H12.4218C12.7863 13.4189 13.1399 13.2954 13.4265 13.0696C13.713 12.8437 13.9162 12.5283 14.0036 12.1735L14.8982 8.62949C14.9067 8.58629 14.9103 8.54226 14.9091 8.49824C15.1548 8.49824 15.3934 8.41504 15.5861 8.26212C15.7788 8.10921 15.9144 7.89553 15.9708 7.65572C16.0273 7.41591 16.0013 7.164 15.8972 6.94082C15.793 6.71764 15.6168 6.53626 15.397 6.42607C15.1772 6.31587 14.9267 6.28332 14.6861 6.33368C14.4456 6.38404 14.229 6.51437 14.0715 6.70355C13.9141 6.89273 13.8249 7.12967 13.8185 7.37599C13.8122 7.62231 13.8889 7.86358 14.0364 8.06071C13.996 8.08883 13.9594 8.1219 13.9273 8.15915C13.3927 8.83732 12.8364 9.11078 12.2473 9.04515C11.7018 8.99046 11.1127 8.23572 10.5345 6.7153C10.7448 6.59679 10.91 6.41174 11.0042 6.18909C11.0985 5.96644 11.1164 5.71876 11.0553 5.48477C10.9943 5.25079 10.8575 5.04371 10.6666 4.89592C10.4756 4.74813 10.2412 4.66797 10 4.66797C9.75878 4.66797 9.52436 4.74813 9.33341 4.89592C9.14246 5.04371 9.00575 5.25079 8.94466 5.48477C8.88356 5.71876 8.90154 5.96644 8.99577 6.18909C9.09 6.41174 9.25518 6.59679 9.46545 6.7153ZM13.2727 14.2408C13.4174 14.2408 13.5561 14.2984 13.6584 14.401C13.7607 14.5036 13.8182 14.6427 13.8182 14.7877C13.8182 14.9328 13.7607 15.0719 13.6584 15.1744C13.5561 15.277 13.4174 15.3346 13.2727 15.3346H6.72727C6.58261 15.3346 6.44387 15.277 6.34158 15.1744C6.23929 15.0719 6.18182 14.9328 6.18182 14.7877C6.18182 14.6427 6.23929 14.5036 6.34158 14.401C6.44387 14.2984 6.58261 14.2408 6.72727 14.2408H13.2727Z" fill="white"/>
								</svg>
							<?php } ?>
						</div>
						<div class="bwf-tiles-value-wrap"><span class="bwf-tiles-value">{{data.formatMoney(data.overall.upsell_revenue)}}</span></div>
					</a>
				</div>
			</script>

			<div class="bwf-widget-wrap">

				<?php
				switch ( $app_state ) {
					case 'lite':
						?>
						<div class="bwf-widget-notice is-lite">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M9.33182 5.8912C8.65 7.80539 7.88636 8.73514 7.19091 8.80351C6.41364 8.89921 5.73182 8.55739 5.09091 7.70968C5.05163 7.65831 5.00578 7.61234 4.95455 7.57295C5.13886 7.32655 5.23479 7.02496 5.22681 6.71706C5.21883 6.40916 5.1074 6.11298 4.91057 5.8765C4.71374 5.64003 4.44305 5.47712 4.14235 5.41417C3.84165 5.35122 3.52856 5.39191 3.2538 5.52965C2.97905 5.6674 2.75872 5.89413 2.62853 6.1731C2.49834 6.45208 2.4659 6.76696 2.53648 7.06672C2.60706 7.36649 2.77651 7.63358 3.01739 7.82473C3.25826 8.01587 3.55645 8.11987 3.86364 8.11987L3.87727 8.28394L4.99546 12.7139C5.10481 13.1574 5.35872 13.5516 5.71692 13.834C6.07511 14.1164 6.51709 14.2707 6.97273 14.2726H13.0273C13.4829 14.2707 13.9249 14.1164 14.2831 13.834C14.6413 13.5516 14.8952 13.1574 15.0045 12.7139L16.1227 8.28394C16.1334 8.22993 16.1379 8.1749 16.1364 8.11987C16.4435 8.11987 16.7417 8.01587 16.9826 7.82473C17.2235 7.63358 17.3929 7.36649 17.4635 7.06672C17.5341 6.76696 17.5017 6.45208 17.3715 6.1731C17.2413 5.89413 17.021 5.6674 16.7462 5.52965C16.4714 5.39191 16.1584 5.35122 15.8577 5.41417C15.5569 5.47712 15.2863 5.64003 15.0894 5.8765C14.8926 6.11298 14.7812 6.40916 14.7732 6.71706C14.7652 7.02496 14.8611 7.32655 15.0455 7.57295C14.9951 7.60811 14.9493 7.64945 14.9091 7.69601C14.2409 8.54372 13.5455 8.88554 12.8091 8.80351C12.1273 8.73514 11.3909 7.79172 10.6682 5.8912C10.931 5.74306 11.1375 5.51174 11.2553 5.23343C11.3731 4.95512 11.3955 4.64551 11.3192 4.35304C11.2428 4.06056 11.0719 3.80171 10.8332 3.61697C10.5946 3.43224 10.3015 3.33203 10 3.33203C9.69847 3.33203 9.40545 3.43224 9.16676 3.61697C8.92807 3.80171 8.75718 4.06056 8.68082 4.35304C8.60446 4.64551 8.62693 4.95512 8.74472 5.23343C8.86251 5.51174 9.06897 5.74306 9.33182 5.8912ZM14.0909 15.2981C14.2717 15.2981 14.4452 15.3701 14.573 15.4983C14.7009 15.6265 14.7727 15.8004 14.7727 15.9817C14.7727 16.163 14.7009 16.3369 14.573 16.4651C14.4452 16.5933 14.2717 16.6654 14.0909 16.6654H5.90909C5.72826 16.6654 5.55484 16.5933 5.42697 16.4651C5.29911 16.3369 5.22727 16.163 5.22727 15.9817C5.22727 15.8004 5.29911 15.6265 5.42697 15.4983C5.55484 15.3701 5.72826 15.2981 5.90909 15.2981H14.0909Z" fill="#353030"/>
							</svg>
							<span> <?php echo wp_kses_post( __( 'Get more with FunnelKit PRO—upgrade from Lite for additional features <a href="https://funnelkit.com/exclusive-offer/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Dashboard+Widget+TopBar" target="_blank">Upgrade to PRO</a>' ) ); ?></span>
						</div>
						<?php
						break;
					case 'pro_without_license_on_grace_period':
					case 'pro_without_license':
						?>
						<div class="bwf-widget-notice is-warning">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M21.8012 18.6522L13.336 3.78261C13.0546 3.28702 12.5687 3 12.0061 3C11.4435 3 10.9575 3.28702 10.6763 3.78261L2.21104 18.6522C1.92965 19.1478 1.92965 19.7218 2.21104 20.2174C2.49242 20.713 2.97829 21 3.54089 21H20.4459C21.0085 21 21.4946 20.713 21.7758 20.2174C22.0572 19.7218 22.0827 19.1478 21.8013 18.6522H21.8012ZM20.9317 19.6956C20.8805 19.7739 20.7527 19.9564 20.4969 19.9564L3.56641 19.9566C3.31071 19.9566 3.15726 19.774 3.13157 19.6958C3.08036 19.6175 3.00363 19.4088 3.13157 19.174L11.5968 4.3044C11.7247 4.06962 11.9549 4.04359 12.0316 4.04359C12.1084 4.04359 12.3385 4.06962 12.4665 4.3044L20.9317 19.174C21.0596 19.4088 20.9829 19.6173 20.9317 19.6956V19.6956Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M12.0316 10.5216C11.7502 10.5216 11.52 10.7564 11.52 11.0434V17.0435C11.52 17.3306 11.7502 17.5653 12.0316 17.5653C12.313 17.5653 12.5431 17.3306 12.5431 17.0435V11.0434C12.5431 10.7564 12.313 10.5216 12.0316 10.5216Z" fill="#353030" stroke="#353030" stroke-width="0.3"/>
								<path d="M12.5433 8.95637C12.5433 9.24461 12.3141 9.47817 12.0317 9.47817C11.7493 9.47817 11.5201 9.24461 11.5201 8.95637C11.5201 8.66831 11.7493 8.43475 12.0317 8.43475C12.3141 8.43475 12.5433 8.66832 12.5433 8.95637Z" fill="#353030" stroke="#353030" stroke-width="0.5"/>
							</svg>

							<span>
								<?php
								$license_url = esc_url( admin_url( 'admin.php?page=bwf&path=/settings/woofunnels_general_settings' ) );
								echo wp_kses_post( sprintf( /* translators: %s: URL to license settings page */ __( '<strong>FunnelKit Pro is Not Fully Activated!</strong>  Please activate your license to continue using premium features without interruption. <a href="%s" target="_blank">Activate License</a>', 'funnel-builder' ), $license_url ) );
								?>
							</span>
						</div>
						<?php
						break;
					case 'license_expired':
						?>
						<div class="bwf-widget-notice is-danger">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
								<path d="M21.8012 18.6522L13.336 3.78261C13.0546 3.28702 12.5687 3 12.0061 3C11.4435 3 10.9575 3.28702 10.6763 3.78261L2.21104 18.6522C1.92965 19.1478 1.92965 19.7218 2.21104 20.2174C2.49242 20.713 2.97829 21 3.54089 21H20.4459C21.0085 21 21.4946 20.713 21.7758 20.2174C22.0572 19.7218 22.0827 19.1478 21.8013 18.6522H21.8012ZM20.9317 19.6956C20.8805 19.7739 20.7527 19.9564 20.4969 19.9564L3.56641 19.9566C3.31071 19.9566 3.15726 19.774 3.13157 19.6958C3.08036 19.6175 3.00363 19.4088 3.13157 19.174L11.5968 4.3044C11.7247 4.06962 11.9549 4.04359 12.0316 4.04359C12.1084 4.04359 12.3385 4.06962 12.4665 4.3044L20.9317 19.174C21.0596 19.4088 20.9829 19.6173 20.9317 19.6956V19.6956Z" fill="#ffffff" stroke="#ffffff" stroke-width="0.3"></path>
								<path d="M12.0316 10.5216C11.7502 10.5216 11.52 10.7564 11.52 11.0434V17.0435C11.52 17.3306 11.7502 17.5653 12.0316 17.5653C12.313 17.5653 12.5431 17.3306 12.5431 17.0435V11.0434C12.5431 10.7564 12.313 10.5216 12.0316 10.5216Z" fill="#ffffff" stroke="#ffffff" stroke-width="0.3"></path>
								<path d="M12.5433 8.95637C12.5433 9.24461 12.3141 9.47817 12.0317 9.47817C11.7493 9.47817 11.5201 9.24461 11.5201 8.95637C11.5201 8.66831 11.7493 8.43475 12.0317 8.43475C12.3141 8.43475 12.5433 8.66832 12.5433 8.95637Z" fill="#ffffff" stroke="#ffffff" stroke-width="0.5"></path>
							</svg>

							<span><?php echo wp_kses_post( $all_texts_from_pro['license']['states'][4]['notice']['text'] ); ?> <a href="https://funnelkit.com/exclusive-offer/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Dashboard+Widget+TopBar"><?php echo esc_html( $all_texts_from_pro['license']['states'][4]['notice']['primary_action'] ); ?></a></span>
						</div>
						<?php
						break;
					case 'license_expired_on_grace_period':
						?>
						<div class="bwf-widget-notice is-danger">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
								<path d="M21.8012 18.6522L13.336 3.78261C13.0546 3.28702 12.5687 3 12.0061 3C11.4435 3 10.9575 3.28702 10.6763 3.78261L2.21104 18.6522C1.92965 19.1478 1.92965 19.7218 2.21104 20.2174C2.49242 20.713 2.97829 21 3.54089 21H20.4459C21.0085 21 21.4946 20.713 21.7758 20.2174C22.0572 19.7218 22.0827 19.1478 21.8013 18.6522H21.8012ZM20.9317 19.6956C20.8805 19.7739 20.7527 19.9564 20.4969 19.9564L3.56641 19.9566C3.31071 19.9566 3.15726 19.774 3.13157 19.6958C3.08036 19.6175 3.00363 19.4088 3.13157 19.174L11.5968 4.3044C11.7247 4.06962 11.9549 4.04359 12.0316 4.04359C12.1084 4.04359 12.3385 4.06962 12.4665 4.3044L20.9317 19.174C21.0596 19.4088 20.9829 19.6173 20.9317 19.6956V19.6956Z" fill="#ffffff" stroke="#ffffff" stroke-width="0.3"></path>
								<path d="M12.0316 10.5216C11.7502 10.5216 11.52 10.7564 11.52 11.0434V17.0435C11.52 17.3306 11.7502 17.5653 12.0316 17.5653C12.313 17.5653 12.5431 17.3306 12.5431 17.0435V11.0434C12.5431 10.7564 12.313 10.5216 12.0316 10.5216Z" fill="#ffffff" stroke="#ffffff" stroke-width="0.3"></path>
								<path d="M12.5433 8.95637C12.5433 9.24461 12.3141 9.47817 12.0317 9.47817C11.7493 9.47817 11.5201 9.24461 11.5201 8.95637C11.5201 8.66831 11.7493 8.43475 12.0317 8.43475C12.3141 8.43475 12.5433 8.66832 12.5433 8.95637Z" fill="#ffffff" stroke="#ffffff" stroke-width="0.5"></path>
							</svg>

							<span><?php echo wp_kses_post( str_replace( '{{TIME_GRACE_EXPIRED}}', ( new DateTime( $license_config['f']['ed'] ) )->modify( '+' . $license_config['gp'][0] . ' days' )->format( 'F j, Y' ), $all_texts_from_pro['license']['states'][3]['notice']['text'] ) ); ?> <a href="https://funnelkit.com/exclusive-offer/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Dashboard+Widget+TopBar"><?php echo esc_html( $all_texts_from_pro['license']['states'][3]['notice']['primary_action'] ); ?></a></span>
						</div>
						<?php
						break;
					default:
						echo '';
						break;
				}
				?>

				<div class="bwf-widget-content-wrap">

					<div id="bwf-widget-analytics-container">
						<div class="bwf-tiles">
							<?php foreach ( range( 1, 4 ) as $value ) {  //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable ?>
								<div class="bwf-tiles-item">
									<div class="bwf-tiles-header">
										<div class="bwf-placeholder-item" style="height:28px;width:28px;border-radius:50%"></div>
										<div class="bwf-placeholder-item" style="height:20px;width:100px"></div>
									</div>
									<div class="bwf-tiles-value-wrap bwf-tiles-value bwf-placeholder-item" style="height:20px;width:80px;"></div>
								</div>
							<?php } ?>
						</div>
					</div>

					<?php
					if ( ! defined( 'WFFN_PRO_VERSION' ) ) {
						$year = gmdate( 'Y' );

						// Each campaign has a unique key to allow independent dismissal
						if ( WFFN_Core()->admin_notifications->show_pre_bfcm_header_notification() ) {
							$this->add_bfcm_widget_row( 'promo_bf_pre_' . $year, WFFN_Core()->admin_notifications->promo_pre_bfcm( false ) );
						} elseif ( WFFN_Core()->admin_notifications->show_bf_header_notification() ) {
							$this->add_bfcm_widget_row( 'promo_bf_' . $year, WFFN_Core()->admin_notifications->promo_bfcm( false ) );
						} elseif ( WFFN_Core()->admin_notifications->show_small_business_saturday_header_notification() ) {
							$this->add_bfcm_widget_row( 'promo_bf_sbs_' . $year, WFFN_Core()->admin_notifications->promo_small_business_saturday( false ) );
						} elseif ( WFFN_Core()->admin_notifications->show_bfext_header_notification() ) {
							$this->add_bfcm_widget_row( 'promo_bf_bfext_' . $year, WFFN_Core()->admin_notifications->promo_ext_bfcm( false ) );
						} elseif ( WFFN_Core()->admin_notifications->show_cm_header_notification() ) {
							$this->add_bfcm_widget_row( 'promo_bf_cm_' . $year, WFFN_Core()->admin_notifications->promo_cmonly( false ) );
						} elseif ( WFFN_Core()->admin_notifications->show_cmext_header_notification() ) {
							$this->add_bfcm_widget_row( 'promo_bf_cmext_' . $year, WFFN_Core()->admin_notifications->promo_ext_cmonly( false ) );
						}

						// Show Green Monday notification independently
						if ( WFFN_Core()->admin_notifications->show_green_monday_header_notification() ) {
							$this->add_bfcm_widget_row( 'promo_bf_gm_' . $year, WFFN_Core()->admin_notifications->promo_gm( false ) );
						}
					}

					?>
					<div class="bwf-widget-action-box" id="bwf-d-stripe" data-index="1" style="display:none;">
						<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="bwf-widget-action-box-icon">
							<rect width="44" height="44" rx="8" fill="#6C63FF"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M20.3748 17.5516C20.3748 16.6088 21.1811 16.2462 22.5165 16.2462C24.4315 16.2462 26.8504 16.8022 28.7654 17.7934V12.1121C26.674 11.3143 24.6079 11 22.5165 11C17.4016 11 14 13.5626 14 17.8418C14 24.5143 23.5748 23.4505 23.5748 26.3275C23.5748 27.4396 22.5669 27.8022 21.1559 27.8022C19.0646 27.8022 16.3937 26.9802 14.2772 25.8681V31.622C16.6205 32.589 18.989 33 21.1559 33C26.3969 33 30 30.5099 30 26.1824C29.9748 18.978 20.3748 20.2593 20.3748 17.5516Z" fill="white"/>
						</svg>
						<span><span class="bwf-widget-action-box-title"> <?php echo esc_html__( 'Get FunnelKit Stripe', 'funnel-builder' ); ?></span><br><span class="bwf-widget-action-box-subtitle"><?php echo esc_html__( 'Installs from WordPress.org', 'funnel-builder' ); ?></span></span>
						<span class="bwf-widget-action-box-r"><button class="bwf-button is-stripe is-activate"><span><?php echo esc_html__( 'Install', 'funnel-builder' ); ?></span></button> <span data-type="1" class="bwf-widget-action-box-remove"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_676_13273)"><path d="M2.76782 2.87703L2.8178 2.81914C3.00103 2.6359 3.28777 2.61924 3.48983 2.76917L3.54771 2.81914L7.99996 7.27115L12.4522 2.81914C12.6538 2.61758 12.9806 2.61758 13.1821 2.81914C13.3837 3.0207 13.3837 3.3475 13.1821 3.54906L8.73011 8.0013L13.1821 12.4535C13.3654 12.6368 13.382 12.9235 13.2321 13.1256L13.1821 13.1835C12.9989 13.3667 12.7121 13.3834 12.5101 13.2334L12.4522 13.1835L7.99996 8.73145L3.54771 13.1835C3.34615 13.385 3.01936 13.385 2.8178 13.1835C2.61624 12.9819 2.61624 12.6551 2.8178 12.4535L7.26981 8.0013L2.8178 3.54906C2.63456 3.36582 2.6179 3.07908 2.76782 2.87703L2.8178 2.81914L2.76782 2.87703Z" fill="#82838E"/></g><defs><clipPath id="clip0_676_13273"><rect width="16" height="16" fill="white"/></clipPath></defs></svg></span></span>
					</div>
					<div class="bwf-widget-action-box" id="bwf-d-automations" data-index="2" style="display:none;">
						<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="bwf-widget-action-box-icon">
							<rect x="0.5" y="0.5" width="43" height="43" rx="7.5" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M33.6305 12.5H37.1353L26.1991 31.5H22.7594L33.6305 12.5Z" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M17.8796 24.3696L24.5202 12.5H28.0269L17.5092 30.9932L6.8647 12.5H18.964L17.4083 15.6115H12.9893H12.1394L12.5523 16.3544L17.0062 24.3683L17.4418 25.1521L17.8796 24.3696Z" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M38 12L26.4882 32H21.8972L33.3405 12H38Z" fill="#0073AA"/>
							<path d="M28.8865 12L17.5118 32L6 12H19.773L17.7173 16.1115H17.4433H12.9893L17.4433 24.1254L24.227 12H28.8865Z" fill="#070045"/>
						</svg>
						<span><span class="bwf-widget-action-box-title"><?php echo esc_html__( 'Get FunnelKit Automations', 'funnel-builder' ); ?></span><br><span class="bwf-widget-action-box-subtitle"><?php echo esc_html__( 'Cut down thousands of dollars on your expensive CRM', 'funnel-builder' ); ?> </span></span>
						<span class="bwf-widget-action-box-r"><button class="bwf-button is-primary"><span><?php echo esc_html__( 'Install', 'funnel-builder' ); ?></span></button> <span data-type="2" class="bwf-widget-action-box-remove"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_676_13273)"><path d="M2.76782 2.87703L2.8178 2.81914C3.00103 2.6359 3.28777 2.61924 3.48983 2.76917L3.54771 2.81914L7.99996 7.27115L12.4522 2.81914C12.6538 2.61758 12.9806 2.61758 13.1821 2.81914C13.3837 3.0207 13.3837 3.3475 13.1821 3.54906L8.73011 8.0013L13.1821 12.4535C13.3654 12.6368 13.382 12.9235 13.2321 13.1256L13.1821 13.1835C12.9989 13.3667 12.7121 13.3834 12.5101 13.2334L12.4522 13.1835L7.99996 8.73145L3.54771 13.1835C3.34615 13.385 3.01936 13.385 2.8178 13.1835C2.61624 12.9819 2.61624 12.6551 2.8178 12.4535L7.26981 8.0013L2.8178 3.54906C2.63456 3.36582 2.6179 3.07908 2.76782 2.87703L2.8178 2.81914L2.76782 2.87703Z" fill="#82838E"/></g><defs><clipPath id="clip0_676_13273"><rect width="16" height="16" fill="white"/></clipPath></defs></svg></span></span>
					</div>
					<div class="bwf-widget-action-box" id="bwf-d-automations-pro" data-index="3" style="display:none;">
						<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="bwf-widget-action-box-icon">
							<rect x="0.5" y="0.5" width="43" height="43" rx="7.5" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M33.6305 12.5H37.1353L26.1991 31.5H22.7594L33.6305 12.5Z" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M17.8796 24.3696L24.5202 12.5H28.0269L17.5092 30.9932L6.8647 12.5H18.964L17.4083 15.6115H12.9893H12.1394L12.5523 16.3544L17.0062 24.3683L17.4418 25.1521L17.8796 24.3696Z" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M38 12L26.4882 32H21.8972L33.3405 12H38Z" fill="#0073AA"/>
							<path d="M28.8865 12L17.5118 32L6 12H19.773L17.7173 16.1115H17.4433H12.9893L17.4433 24.1254L24.227 12H28.8865Z" fill="#070045"/>
						</svg>
						<span><span class="bwf-widget-action-box-title"><?php echo esc_html__( 'Get FunnelKit Automations PRO', 'funnel-builder' ); ?> </span><br><span class="bwf-widget-action-box-subtitle"><?php echo wp_kses_post( __( 'Reach your audiences by unlocking features like <strong>automations, broadcast, email builder</strong> and many more', 'funnel-builder' ) ); ?> </span></span>
						<span class="bwf-widget-action-box-r"><button class="bwf-button is-warning"><span><?php echo esc_html__( 'Upgrade Now', 'funnel-builder' ); ?></span></button> <span data-type="3" class="bwf-widget-action-box-remove"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_676_13273)"><path d="M2.76782 2.87703L2.8178 2.81914C3.00103 2.6359 3.28777 2.61924 3.48983 2.76917L3.54771 2.81914L7.99996 7.27115L12.4522 2.81914C12.6538 2.61758 12.9806 2.61758 13.1821 2.81914C13.3837 3.0207 13.3837 3.3475 13.1821 3.54906L8.73011 8.0013L13.1821 12.4535C13.3654 12.6368 13.382 12.9235 13.2321 13.1256L13.1821 13.1835C12.9989 13.3667 12.7121 13.3834 12.5101 13.2334L12.4522 13.1835L7.99996 8.73145L3.54771 13.1835C3.34615 13.385 3.01936 13.385 2.8178 13.1835C2.61624 12.9819 2.61624 12.6551 2.8178 12.4535L7.26981 8.0013L2.8178 3.54906C2.63456 3.36582 2.6179 3.07908 2.76782 2.87703L2.8178 2.81914L2.76782 2.87703Z" fill="#82838E"/></g><defs><clipPath id="clip0_676_13273"><rect width="16" height="16" fill="white"/></clipPath></defs></svg></span></span>
					</div>
					<div class="bwf-widget-action-box" id="bwf-d-cart" data-index="4" style="display:none;">
						<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="bwf-widget-action-box-icon">
							<rect x="0.5" y="0.5" width="43" height="43" rx="7.5" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M33.6305 12.5H37.1353L26.1991 31.5H22.7594L33.6305 12.5Z" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M17.8796 24.3696L24.5202 12.5H28.0269L17.5092 30.9932L6.8647 12.5H18.964L17.4083 15.6115H12.9893H12.1394L12.5523 16.3544L17.0062 24.3683L17.4418 25.1521L17.8796 24.3696Z" fill="#F9F9FF" stroke="#DEDFEA"/>
							<path d="M38 12L26.4882 32H21.8972L33.3405 12H38Z" fill="#0073AA"/>
							<path d="M28.8865 12L17.5118 32L6 12H19.773L17.7173 16.1115H17.4433H12.9893L17.4433 24.1254L24.227 12H28.8865Z" fill="#070045"/>
						</svg>
						<span><span class="bwf-widget-action-box-title"><?php echo esc_html__( ' Get FunnelKit Side Cart', 'funnel-builder' ); ?></span><br><span class="bwf-widget-action-box-subtitle"><?php echo esc_html__( 'Meet dynamic, reward-based side cart for WooCommerce', 'funnel-builder' ); ?></span></span>
						<span class="bwf-widget-action-box-r"><button class="bwf-button is-primary"><span><?php echo esc_html__( 'Install', 'funnel-builder' ); ?></span></button> <span data-type="4" class="bwf-widget-action-box-remove"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_676_13273)"><path d="M2.76782 2.87703L2.8178 2.81914C3.00103 2.6359 3.28777 2.61924 3.48983 2.76917L3.54771 2.81914L7.99996 7.27115L12.4522 2.81914C12.6538 2.61758 12.9806 2.61758 13.1821 2.81914C13.3837 3.0207 13.3837 3.3475 13.1821 3.54906L8.73011 8.0013L13.1821 12.4535C13.3654 12.6368 13.382 12.9235 13.2321 13.1256L13.1821 13.1835C12.9989 13.3667 12.7121 13.3834 12.5101 13.2334L12.4522 13.1835L7.99996 8.73145L3.54771 13.1835C3.34615 13.385 3.01936 13.385 2.8178 13.1835C2.61624 12.9819 2.61624 12.6551 2.8178 12.4535L7.26981 8.0013L2.8178 3.54906C2.63456 3.36582 2.6179 3.07908 2.76782 2.87703L2.8178 2.81914L2.76782 2.87703Z" fill="#82838E"/></g><defs><clipPath id="clip0_676_13273"><rect width="16" height="16" fill="white"/></clipPath></defs></svg></span></span>
					</div>
				</div>
				<div class="bwf-widget-footer">
					<div class="bwf-widget-footer-l">
						<?php if ( $app_state === 'lite' ) { ?>
							<a class="is-success" href="https://funnelkit.com/exclusive-offers"><strong><?php echo esc_html__( 'Upgrade to PRO', 'funnel-builder' ); ?> </strong></a>
						<?php } ?>
						<a href="https://funnelkit.com/blog/" target="_blank"><?php echo esc_html__( 'Blog', 'funnel-builder' ); ?></a>
						<a href="https://funnelkit.com/support/" target="_blank"><?php echo esc_html__( 'Get Help', 'funnel-builder' ); ?> </a>
						<a href="https://www.youtube.com/@BuildWooFunnelsHQ" target="_blank"><?php echo esc_html__( 'Watch Tutorials', 'funnel-builder' ); ?> </a>
					</div>
					<div class="bwf-widget-footer-r">
						<?php if ( $this->is_update_available() ) { ?>
							<a class="is-primary" href="plugins.php?s=funnelkit"><?php echo esc_html__( 'Update Available', 'funnel-builder' ); ?> </a>

						<?php } ?>
					</div>

				</div>
			</div>
			<script type="text/javascript">

				var fkwidget = {};
				fkwidget.basenames = ['funnelkit-stripe-woo-payment-gateway/funnelkit-stripe-woo-payment-gateway.php', 'wp-marketing-automations/wp-marketing-automations.php', 'wp-marketing-automations-pro/wp-marketing-automations-pro.php', 'cart-for-woocommerce/plugin.php'];
				fkwidget.slugs = ['funnelkit-stripe-woo-payment-gateway', 'wp-marketing-automations', 'wp-marketing-automations-pro', 'cart-for-woocommerce'];
				fkwidget.stripe = <?php echo wp_json_encode( WFFN_Common::stripe_state() ); ?>;
				fkwidget.automations = '<?php echo esc_attr( WFFN_Common::get_plugin_status( 'wp-marketing-automations/wp-marketing-automations.php' ) ); ?>';
				fkwidget.cart = '<?php echo esc_attr( WFFN_Common::get_plugin_status( 'cart-for-woocommerce/plugin.php' ) ); ?>';
				fkwidget.automations_pro = '<?php echo esc_attr( WFFN_Common::get_plugin_status( 'wp-marketing-automations-pro/wp-marketing-automations-pro.php' ) ); ?>';
				fkwidget.current_index = 1;
				fkwidget.current_day_before_month = '<?php echo esc_attr( self::get_thirty_days_ago() ); ?>';
				fkwidget.dismissed = <?php echo wp_json_encode( get_user_meta( get_current_user_id(), '_bwf_notifications_close', true ) ); ?>;
				fkwidget.is_wc = '<?php echo esc_attr( wffn_bool_to_string( wffn_is_wc_active() ) ); ?>';
			</script>
			<script>
				(function ($) {

					const apiService = (path = "", method = "GET", data, content_type = "text/plain") => {
						return new Promise((resolve, reject) => {
							jQuery.ajax({
								url: wpApiSettings.root + path,
								type: method,
								data: data,
								beforeSend: function (xhr) {
									xhr.setRequestHeader("X-WP-Nonce", wpApiSettings.nonce);
								},
								dataType: "json",
								contentType: content_type,
								success: resolve,
								error: reject
							});
						});
					};
					$(document).ready(function () {

						function showHideWidget() {
							jQuery('.bwf-widget-action-box').hide();

							if (jQuery('.bwf-widget-action-box.bfcm-widget').length > 0) {
								jQuery('.bwf-widget-action-box.bfcm-widget').show();
								return;
							}
							if (fkwidget.is_wc === 'yes' && jQuery.inArray("wizard_close_1", fkwidget.dismissed) === -1 && fkwidget.stripe.status !== 'connected') {
								jQuery('#bwf-d-stripe').show();
								return;
							}

							if (jQuery.inArray("wizard_close_2", fkwidget.dismissed) === -1 && fkwidget.automations !== 'activated') {
								fkwidget.current_index = 2;
								jQuery('#bwf-d-automations').show();
								return;
							}


							if (jQuery.inArray("wizard_close_3", fkwidget.dismissed) === -1 && fkwidget.automations_pro !== 'activated') {
								fkwidget.current_index = 3;
								jQuery('#bwf-d-automations-pro').show();
								return;
							}

							if (fkwidget.is_wc === 'yes' && jQuery.inArray("wizard_close_4", fkwidget.dismissed) === -1 && fkwidget.cart !== 'activated') {
								fkwidget.current_index = 4;
								jQuery('#bwf-d-cart').show();
							}

						}


						function ShowWidgetState() {
							if (jQuery('.bwf-widget-action-box.bfcm-widget').length > 0) {
								return;
							}
							const statusMapping = {
								1: {
									'not_installed': {text: '<?php echo esc_html__( 'Install', 'funnel-builder' ); ?>', action: 'activate'},
									'not_activated': {text: '<?php echo esc_html__( 'Activate', 'funnel-builder' ); ?>', action: 'activate'},
									'not_connected': {text: '<?php echo esc_html__( 'Connect', 'funnel-builder' ); ?>', action: 'redirect', href: fkwidget.stripe.link}
								},
								2: {
									'install': {text: '<?php echo esc_html__( 'Install', 'funnel-builder' ); ?>', action: 'activate'},
									'activate': {text: '<?php echo esc_html__( 'Activate', 'funnel-builder' ); ?>', action: 'activate'}
								},
								3: {
									'install': {text: '<?php echo esc_html__( 'Upgrade to PRO', 'funnel-builder' ); ?>', action: 'redirect', href: 'https://funnelkit.com/exclusive-offer/'},
									'activate': {text: '<?php echo esc_html__( 'Activate', 'funnel-builder' ); ?>', action: 'activate'}
								},
								4: {
									'install': {text: '<?php echo esc_html__( 'Install', 'funnel-builder' ); ?>', action: 'activate'},
									'activate': {text: '<?php echo esc_html__( 'Activate', 'funnel-builder' ); ?>', action: 'activate'}
								}
							};

							let currentStatus = fkwidget[["stripe", "automations", "automations_pro", "cart"][fkwidget.current_index - 1]];
							if (fkwidget.current_index === 1) {
								currentStatus = currentStatus.status;
							}
							const mapping = statusMapping[fkwidget.current_index][currentStatus];

							if (mapping) {
								$('.bwf-button span').text(mapping.text);
								$('.bwf-button .bwf-loading-ring').remove();
								$('.bwf-button').attr('data-action', mapping.action);
								if (mapping.href) {
									$('.bwf-button').attr('href', mapping.href);
								}
							}


							const redirectMapping = {
								2: {
									'install': 'admin.php?page=autonami',
									'activate': 'admin.php?page=autonami',
								},
								3: {
									'activate': 'admin.php?page=autonami',
								},
								4: {
									'install': 'admin.php?page=fkcart',
									'activate': 'admin.php?page=fkcart',
								}
							}
							const redirect = redirectMapping?.[fkwidget.current_index]?.[currentStatus];
							if (redirect) {
								$('.bwf-button').attr('data-redirect', redirect);
							}
						}

						showHideWidget();
						ShowWidgetState();
						const loadingRing = '<div class="bwf-loading-ring"><div style="border-color: rgb(255, 255, 255) transparent transparent;"></div><div style="border-color: rgb(255, 255, 255) transparent transparent;"></div><div style="border-color: rgb(255, 255, 255) transparent transparent;"></div><div style="border-color: rgb(255, 255, 255) transparent transparent;"></div></div>';
						const addClickEvent = () => jQuery(".bwf-button").click(function () {
							const btn = jQuery(this);
							if (btn.attr('data-action') === 'redirect') {
								window.location.href = btn.attr('href');
								return;
							}
							if (btn.attr('data-action') !== 'activate') {
								return;
							}
							const btnPrevState = btn.clone();
							btn.addClass("is-busy").prop("disabled", true).append(loadingRing);
							apiService("funnelkit-app/activate_plugin", 'POST', JSON.stringify({
								basename: fkwidget.basenames[fkwidget.current_index - 1],
								slug: fkwidget.slugs[fkwidget.current_index - 1],
							}), 'application/json').then((res) => {
								if (res.next_action) {
									apiService(res.next_action, 'GET', {}, 'application/json').then((res) => {
										if (res.link) {
											fkwidget.stripe.status = 'not_connected';
											fkwidget.stripe.link = res.link;
											btn.toggleClass("is-busy").prop("disabled", false);
											showHideWidget();
											ShowWidgetState();
										} else {
											fkwidget.stripe.status = 'connected';
											showHideWidget();
											ShowWidgetState();
										}

									}).catch((e) => {
										btn.replaceWith(btnPrevState);
										addClickEvent();
										console.log(e.responseJSON);
									})
								} else {
									if (btn.attr('data-redirect')) {
										window.location.href = btn.attr('data-redirect');
										return;
									}

									if (fkwidget.current_index === 2) {
										fkwidget.automations = 'activated';
									}
									if (fkwidget.current_index === 3) {
										fkwidget.automations_pro = 'activated';
									}
									if (fkwidget.current_index === 4) {
										fkwidget.cart = 'activated';
									}

									showHideWidget();
									ShowWidgetState();
								}
							}).catch((e) => {
								btn.replaceWith(btnPrevState);
								addClickEvent();
								console.log(e.responseJSON);
							})
						});
						addClickEvent();
						const removeActionBox = (e) => {
							const noticeKey = 'wizard_close_' + jQuery(e.currentTarget).attr('data-type');
							apiService("funnelkit-app/user-preference", 'POST', JSON.stringify({
								action: 'notice_close',
								key: noticeKey,
								user_id: <?php echo esc_attr( get_current_user_id() ); ?>,
							}), 'application/json').then(() => {
								if (fkwidget.dismissed === '') {
									fkwidget.dismissed = [noticeKey];
								} else {
									fkwidget.dismissed.push(noticeKey);
								}
								jQuery(e.target)
									.closest(".bwf-widget-action-box")
									.remove()
									.fadeOut(300);

								showHideWidget();
								ShowWidgetState();

							}).catch((e) => {
								console.log(e.responseJSON);
							})
						};

						jQuery(".bwf-widget-action-box-remove").click((e) => {
							e.preventDefault();
							removeActionBox(e);


						});


						apiService("funnelkit-app/funnel-analytics/dashboard/overview?overall=", 'GET').then((res) => {
							var firstResponse = res;
							var template = wp.template('wffn-container-template');

							// Ensure we have valid data, fallback to defaults if not
							// The API returns data directly in response.data, not response.data.overall
							var overallData = firstResponse && firstResponse.data ? firstResponse.data : {
								total_orders: 0,
								revenue: 0,
								bump_revenue: 0,
								upsell_revenue: 0,
								checkout_revenue: 0
							};

							// Ensure all expected fields exist with default values
							overallData.total_orders = overallData.total_orders || 0;
							overallData.revenue = overallData.revenue || 0;
							overallData.bump_revenue = overallData.bump_revenue || 0;
							overallData.upsell_revenue = overallData.upsell_revenue || 0;
							overallData.checkout_revenue = overallData.checkout_revenue || 0;

							// Define data to be passed to the template
							// The template expects data.overall, so we structure it accordingly
							var data = {
								'overall': overallData,
								formatMoney: function (amt) {
									// Ensure amt is a valid number, default to 0 if not
									var amount = parseFloat(amt) || 0;

									if ('yes' === fkwidget.is_wc && window.accounting && typeof window.accounting !== 'undefined' && window.accounting.hasOwnProperty('formatMoney') && typeof window.accounting.formatMoney === 'function' && window.wffn_dashboard_params_accounting) {
										return window.accounting.formatMoney(
											amount, {
												symbol: window.wffn_dashboard_params_accounting.currency_format_symbol || '$',
												decimal: window.wffn_dashboard_params_accounting.currency_format_decimal_sep || '.',
												thousand: window.wffn_dashboard_params_accounting.currency_format_thousand_sep || ',',
												precision: window.wffn_dashboard_params_accounting.currency_format_num_decimals || 2,
												format: window.wffn_dashboard_params_accounting.currency_format || '%s%v'
											}
										)
									} else {
										// Fallback formatting when WooCommerce is not active
										return '$' + amount.toFixed(2);
									}
								}
							};

							// Append the rendered HTML to a container element
							document.getElementById('bwf-widget-analytics-container').innerHTML = template(data);

						}).catch((e) => {
							console.log('Dashboard widget error:', e);
							// Show fallback content when there's an error
							var template = wp.template('wffn-container-template');
							var fallbackData = {
								'overall': {
									total_orders: 0,
									revenue: 0,
									bump_revenue: 0,
									upsell_revenue: 0,
									checkout_revenue: 0
								},
								formatMoney: function (amt) {
									return '$0.00';
								}
							};
							document.getElementById('bwf-widget-analytics-container').innerHTML = template(fallbackData);
						});
					});
				})(jQuery);
			</script>
			<style>
				.bwf-widget-content-wrap {
					padding: 24px 16px
				}

				.bwf-widget-wrap strong {
					font-weight: 500
				}

				.bwf-widget-wrap a.is-success {
					color: #09b29c
				}

				.bwf-widget-wrap a:not(.bwf-button).is-primary {
					color: #0073aa
				}

				.bwf-tiles {
					border: 1px solid #dedfea;
					display: grid;
					grid-template-columns:repeat(2, 1fr);
					padding: 16px;
					column-gap: 42px;
					row-gap: 16px
				}

				.bwf-tiles-item {
					position: relative;
					text-decoration: none;
					color: #353030;
				}

				.bwf-tiles-item:hover .bwf-tiles-header, .bwf-tiles-item:focus .bwf-tiles-header {
					color: #353030;
				}

				.bwf-tiles-item:hover .bwf-tiles-value-wrap, .bwf-tiles-item:focus .bwf-tiles-value-wrap {
					color: #0073aa;
				}

				.bwf-tiles-item:focus {
					box-shadow: none;
				}

				.bwf-tiles-item:nth-child(odd)::after {
					content: "";
					position: absolute;
					right: -21px;
					height: 56px;
					border-right: 1px solid #dedfea;
					top: 50%;
					transform: translateY(-50%)
				}

				.bwf-tiles-header {
					display: flex;
					align-items: center;
					gap: 8px;
					font-size: 13px;
					line-height: 24px;
					font-weight: 500
				}

				.bwf-tiles-value-wrap {
					margin-top: 12px
				}

				.bwf-tiles-value {
					font-size: 24px;
					line-height: 32px;
					font-weight: 400
				}

				.bwf-tiles-secondary-text {
					margin-top: 4px;
					font-size: 12px;
					line-height: 16px;
					font-weight: 400;
					color: #82838e
				}

				.bwf-widget-action-box {
					border: 1px solid #dedfea;
					margin-top: 16px;
					padding: 16px;
					display: flex;
					align-items: center;
					gap: 16px;
				}

				.bwf-widget-action-box svg.bwf-widget-action-box-icon {
					height: 44px;
					min-width: 44px
				}

				.bwf-widget-action-box-title {
					font-size: 15px;
					line-height: 24px;
					font-weight: 500
				}

				.bwf-widget-action-box-subtitle {
					font-size: 13px;
					line-height: 20px;
					font-weight: 400;
					color: #82838e
				}

				.bwf-widget-action-box-r {
					margin-left: auto;
					display: flex;
					align-items: center;
					gap: 16px
				}

				.bwf-widget-action-box-remove {
					cursor: pointer;
					height: 16px
				}

				.bwf-button {
					font-size: 15px;
					line-height: 16px;
					font-weight: 600;
					min-height: 36px;
					height: 36px;
					border-radius: 8px;
					padding: 0 16px;
					box-shadow: none;
					background: #0073aa;
					color: #fff;
					outline: 0;
					border: 0;
					cursor: pointer;
					white-space: nowrap;
					display: inline-flex;
					align-items: center;
					text-decoration: none;
					position: relative
				}

				.bwf-button.is-primary:hover {
					color: #fff
				}

				.bwf-button:focus {
					box-shadow: none
				}

				.bwf-button.is-secondary {
					border: 1px solid #0073aa;
					color: #0073aa;
					background: #fff
				}

				.bwf-button.is-stripe {
					background: #6c63ff
				}

				.bwf-button.is-warning {
					background-color: #F5C452;
					color: #353030
				}

				.bwf-button.is-busy span {
					visibility: hidden;
				}

				.bwf-widget-footer {
					border-top: 1px solid #dedfea;
					padding: 8px 16px;
					display: flex;
					align-items: center;
					justify-content: space-between;
					font-size: 13px;
					line-height: 20px;
					font-weight: 400;
					color: #82838e
				}

				.bwf-widget-footer a {
					color: #828383;
					text-decoration: none;
					position: relative
				}

				.bwf-widget-footer-l {
					display: flex;
					align-items: center;
					gap: 16px
				}

				.bwf-widget-footer-r {
					display: flex;
					align-items: center;
					gap: 8px
				}

				.bwf-widget-footer-l a:not(:last-child)::after {
					content: "";
					position: absolute;
					right: -8px;
					height: 16px;
					border-right: 1px solid #dedfea;
					top: 50%;
					transform: translateY(-50%)
				}

				.bwf-widget-notice {
					padding: 8px 16px;
					font-size: 13px;
					line-height: 20px;
					font-weight: 400;
					display: flex;
					align-items: center;
					gap: 8px
				}

				.bwf-widget-notice svg {
					min-width: 24px
				}

				.bwf-widget-notice a {
					font-weight: 500
				}

				.bwf-widget-notice.is-lite {
					background: #fef7e8;
					color: #353030
				}

				.bwf-widget-notice.is-lite a {
					color: #0073aa
				}

				.bwf-widget-notice.is-danger {
					background: #e15334;
					color: #fff
				}

				.bwf-widget-notice.is-danger a {
					color: #fff
				}

				.bwf-widget-notice.is-danger svg path {
					fill: #fff
				}

				.bwf-widget-notice.is-warning {
					background: #ffc65c;
					color: #353030
				}

				.bwf-widget-notice.is-warning a {
					color: #353030
				}

				#funnelkit_widget .inside {
					padding: 0;
					margin: 0
				}

				/* Latest Css BF */
				.bwf-widget-action-box.bfcm-widget {
					background-color: #000248;
					border: 1px solid #dedfea;
					border-left: 0;
					border-top: 1px solid #dedfea;
					border-bottom: 1px solid #dedfea;
					border-right: 1px solid #dedfea;
					padding: 16px 48px 24px;
					color: white;
					position: relative;
					display: flex;
					flex-direction: column;
					align-items: center;
					justify-content: center;
					gap: 24px;
					text-align: center;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-bfcm-content {
					display: flex;
					flex-direction: column;
					gap: 24px;
					align-items: center;
					justify-content: center;
					width: 100%;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-bfcm-text-content {
					display: flex;
					flex-direction: column;
					gap: 6px;
					align-items: center;
					justify-content: center;
					width: 100%;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-bfcm-title {
					font-size: 16px;
					line-height: 20px;
					font-weight: 600;
					color: white;
					margin: 0;
					text-align: center;
					width: 100%;
					padding: 0;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-bfcm-subtitle {
					font-size: 14px;
					line-height: 1.5;
					font-weight: 400;
					color: white;
					margin: 0;
					text-align: center;
					width: 100%;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-bfcm-button-wrap {
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-bfcm-button {
					background-color: #22a305;
					color: white;
					padding: 8px 24px;
					border-radius: 8px;
					text-decoration: none;
					font-size: 18px;
					font-weight: 600;
					line-height: 28px;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					gap: 8px;
					border: none;
					cursor: pointer;
					transition: background-color 0.2s ease;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-bfcm-button:hover {
					background-color: #1e8f04;
					color: white;
					text-decoration: none;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-widget-action-box-r {
					position: absolute;
					top: 16px;
					right: 16px;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-widget-action-box-remove {
					cursor: pointer;
					display: flex;
					align-items: center;
					justify-content: center;
					width: 16px;
					height: 16px;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-widget-action-box-remove svg path {
					fill: #82838E;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-widget-action-box-remove:focus {
					outline: none;
				}

				.bwf-widget-action-box.bfcm-widget .bwf-widget-action-box-remove svg {
					outline: none;
				}


				@media (max-width: 1280px) and (min-width: 1025px) {
					.bwf-tiles-value {
						font-size: 18px
					}
				}

				@media (max-width: 1024px) {
					.bwf-tiles {
						grid-template-columns:1fr
					}

					.bwf-tiles-item:nth-child(odd)::after {
						content: unset
					}
				}

				@media (min-width: 1499px) {
					.bwf-tiles-value {
						font-size: 15px;
						line-height: 20px
					}

					.bwf-widget-action-box-title {
						font-size: 13px
					}
				}

				@keyframes placeholder-fade {
					0% {
						opacity: 0.7;
					}

					50% {
						opacity: 1;
					}

					100% {
						opacity: 0.7;
					}
				}

				.bwf-placeholder-item {
					animation: placeholder-fade 1.6s ease-in-out infinite;
					background-color: #f0f0f0;
					color: transparent !important;
				}


				.bwf-loading-ring {
					position: relative;
					width: 24px;
					height: 24px;
					margin: auto;
				}

				.bwf-loading-ring div {
					box-sizing: border-box;
					display: block;
					position: absolute;
					width: calc(24px - 4px);
					height: calc(24px - 4px);
					margin: 2px;
					border: 2px solid #0073aa;
					border-radius: 50%;
					animation: bwf-loading-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
					border-color: #0073aa transparent transparent transparent;
				}

				.bwf-loading-ring div:nth-child(1) {
					animation-delay: -0.45s;
				}

				.bwf-loading-ring div:nth-child(2) {
					animation-delay: -0.3s;
				}

				.bwf-loading-ring div:nth-child(3) {
					animation-delay: -0.15s;
				}

				.bwf-loading-ring div.color-white {
					border: 2px solid #fff;
					border-color: #fff transparent transparent transparent;
				}

				.bwf-button .bwf-loading-ring {
					position: absolute;
					left: 50%;
					top: 50%;
					transform: translate(-50%, -50%);
				}

				@keyframes bwf-loading-ring {
					0% {
						transform: rotate(0deg);
					}
					100% {
						transform: rotate(360deg);
					}
				}

			</style>
			<?php
		}

		function get_current_app_state( $proData, $module = 'f' ) {
			$e  = $proData[ $module ]['e'];
			$la = $proData[ $module ]['la'];
			$ed = $proData[ $module ]['ed'];
			$ad = $proData[ $module ]['ad'];
			$ib = $proData[ $module ]['ib'];

			if ( $ib && $module === 'f' ) {
				return 'basic';
			}
			if ( ! $e ) {
				return 'lite';
			} elseif ( $ed && strtotime( 'now' ) > strtotime( $ed ) ) {
				if ( strtotime( 'now' ) - strtotime( $ed ) < $proData['gp'][0] * 24 * 3600 ) {
					return 'license_expired_on_grace_period';
				}

				return 'license_expired';
			} elseif ( $la === true ) {
				return 'pro';
			} elseif ( strtotime( 'now' ) - strtotime( $ad ) < $proData['gp'][1] * 24 * 3600 ) {
				return 'pro_without_license_on_grace_period';
			}

			return 'pro_without_license';
		}

		public function is_update_available() {

			$plugins     = get_site_transient( 'update_plugins' );
			$all_plugins = get_plugins();

			if ( isset( $plugins->response ) && is_array( $plugins->response ) && isset( $plugins->response[ WFFN_PLUGIN_BASENAME ] ) ) {

				return WFFN_Core()->admin->compare_version( WFFN_VERSION, $plugins->response[ WFFN_PLUGIN_BASENAME ]->new_version );
			} elseif ( defined( 'WFFN_PRO_PLUGIN_BASENAME' ) && isset( $plugins->response ) && is_array( $plugins->response ) && isset( $plugins->response[ WFFN_PRO_PLUGIN_BASENAME ] ) ) {
				return WFFN_Core()->admin->compare_version( WFFN_PRO_VERSION, $plugins->response[ WFFN_PRO_PLUGIN_BASENAME ]->new_version );
			} elseif ( 'install' !== WFFN_Common::get_plugin_status( 'wp-marketing-automations-pro/wp-marketing-automations-pro.php' ) && isset( $plugins->response ) && is_array( $plugins->response ) && isset( $plugins->response['wp-marketing-automations-pro/wp-marketing-automations-pro.php'] ) ) {

				return WFFN_Core()->admin->compare_version( $all_plugins['wp-marketing-automations-pro/wp-marketing-automations-pro.php']['Version'], $plugins->response['wp-marketing-automations-pro/wp-marketing-automations-pro.php']->new_version );
			} elseif ( 'install' !== WFFN_Common::get_plugin_status( 'cart-for-woocommerce/plugin.php' ) && isset( $plugins->response ) && is_array( $plugins->response ) && isset( $plugins->response['cart-for-woocommerce/plugin.php'] ) ) {

				return WFFN_Core()->admin->compare_version( $all_plugins['cart-for-woocommerce/plugin.php']['Version'], $plugins->response['cart-for-woocommerce/plugin.php']->new_version );
			} elseif ( 'install' !== WFFN_Common::get_plugin_status( 'wp-marketing-automations/wp-marketing-automations.php' ) && isset( $plugins->response ) && is_array( $plugins->response ) && isset( $plugins->response['wp-marketing-automations/wp-marketing-automations.php'] ) ) {

				return WFFN_Core()->admin->compare_version( $all_plugins['wp-marketing-automations/wp-marketing-automations.php']['Version'], $plugins->response['wp-marketing-automations/wp-marketing-automations.php']->new_version );
			}

			return false;
		}

		public static function get_thirty_days_ago() {
			$current_time    = current_time( 'mysql' );
			$thirty_days_ago = gmdate( 'Y-m-d', strtotime( '-30 days', strtotime( $current_time ) ) );
			return esc_attr( $thirty_days_ago );
		}

		public function add_bfcm_widget_row( $a, $content ) {

			if ( WFFN_Core()->admin_notifications->is_user_dismissed( get_current_user_id(), "wizard_close_bfcm2024-wd-$a" ) ) {
				return;
			}
			?>
			<div class="bwf-widget-action-box bfcm-widget" id="bwf-d-bfcm" data-index="0">
				<div class="bwf-bfcm-content">
					<div class="bwf-bfcm-text-content">
						<h2 class="bwf-bfcm-title">
							<?php echo wp_kses_post( $content['title'] ); ?>
						</h2>
						<p class="bwf-bfcm-subtitle">
							<?php echo wp_kses_post( sprintf( __( 'Grow your revenue with FunnelKit! Unlock tools like optimized checkouts, upsells, order bumps, and more. Offer ends %s, midnight ET', 'funnel-builder' ), esc_html( $content['date'] ) ) ); ?>
						</p>
					</div>
					<div class="bwf-bfcm-button-wrap">
						<a class="bwf-bfcm-button" target="_blank" href="https://funnelkit.com/exclusive-offer/?utm_source=WordPress&utm_campaign=FB+Lite+Plugin&utm_medium=Dashboard+Widget+TopBar">
							<?php echo esc_html__( 'Claim My Discount', 'funnel-builder' ); ?>
						</a>
					</div>
				</div>
				<span class="bwf-widget-action-box-r">
					<span data-type="bfcm2024-wd-<?php echo esc_attr( $a ); ?>" class="bwf-widget-action-box-remove">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g clip-path="url(#clip0_676_13273)">
								<path d="M2.76782 2.87703L2.8178 2.81914C3.00103 2.6359 3.28777 2.61924 3.48983 2.76917L3.54771 2.81914L7.99996 7.27115L12.4522 2.81914C12.6538 2.61758 12.9806 2.61758 13.1821 2.81914C13.3837 3.0207 13.3837 3.3475 13.1821 3.54906L8.73011 8.0013L13.1821 12.4535C13.3654 12.6368 13.382 12.9235 13.2321 13.1256L13.1821 13.1835C12.9989 13.3667 12.7121 13.3834 12.5101 13.2334L12.4522 13.1835L7.99996 8.73145L3.54771 13.1835C3.34615 13.385 3.01936 13.385 2.8178 13.1835C2.61624 12.9819 2.61624 12.6551 2.8178 12.4535L7.26981 8.0013L2.8178 3.54906C2.63456 3.36582 2.6179 3.07908 2.76782 2.87703L2.8178 2.81914L2.76782 2.87703Z" fill="#82838E"/>
							</g>
							<defs>
								<clipPath id="clip0_676_13273">
									<rect width="16" height="16" fill="white"/>
								</clipPath>
							</defs>
						</svg>
					</span>
				</span>
			</div>
			<?php
		}
	}
}


WFFN_Admin_Dashboard_Widget::get_instance();

