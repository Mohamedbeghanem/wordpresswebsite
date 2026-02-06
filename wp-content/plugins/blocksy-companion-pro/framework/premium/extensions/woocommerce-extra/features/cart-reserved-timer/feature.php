<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class CartReservedTimer {
	public function get_dynamic_styles_data($args) {
		return [
			'path' => dirname(__FILE__) . '/dynamic-styles.php'
		];
	}

	public function __construct() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				if (! function_exists('get_plugin_data')) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				$render = new \Blocksy_Header_Builder_Render();
				$has_mini_cart = $render->contains_item('cart');

				if (
					is_admin()
					||
					(
						blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_cart', 'yes') === 'no'
						&&
						blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_mini_cart', 'no') === 'no'
					)
				) {
					return;
				}

				wp_enqueue_style(
					'blocksy-ext-woocommerce-extra-cart-reserved-timer-styles',
					BLOCKSY_URL .
						'framework/premium/extensions/woocommerce-extra/static/bundle/cart-reserved-timer.min.css',
					['blocksy-ext-woocommerce-extra-styles'],
					$data['Version']
				);
			},
			50
		);

		add_filter(
			'blocksy_customizer_options:woocommerce:general:end',
			function ($opts) {
				$opts['has_cart_reserved_timer_panel'] = blocksy_get_options(
					dirname(__FILE__) . '/options.php',
					[],
					false
				);

				return $opts;
			},
			55
		);

		add_filter('blocksy:frontend:dynamic-js-chunks', function ($chunks) {
			if (!class_exists('WC_AJAX')) {
				return $chunks;
			}

			$cache_manager = new \Blocksy\CacheResetManager();

			$trigger = [
				[
					'trigger' => $cache_manager->is_there_any_page_caching() ? 'initial-mount' : 'slight-mousemove',
					'selector' => '[class*="ct-cart-reserved-timer"]',
				],

				[
					'selector' => '[class*="ct-cart-reserved-timer"]',
					'trigger' => 'jquery-event',
					'matchTarget' => false,
					'events' => [
						'added_to_cart',
						'removed_from_cart',
						'wc_fragments_refreshed',
						'updated_cart_totals',
					],
				]
			];

			$chunks[] = [
				'id' => 'blocksy_ext_woo_extra_cart_reserved_timer',
				'selector' => '[class*="ct-cart-reserved-timer"]',
				'mountOnLoad' => true,
				'trigger' => $trigger,
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/woocommerce-extra/static/bundle/cart-reserved-timer.js'
				)
			];

			return $chunks;
		});

		add_action('wp', function () {
			if (
				blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_cart', 'yes') === 'yes'
				||
				blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_mini_cart', 'no') === 'yes'
			) {
				if (! WC()->session) {
					return;
				}

				// empty cart for current session if reservation expired
				$session_id = WC()->session->get_customer_id();
				$storage = new CartReservationStorage();
				$reservation = $storage->get_current_reservation($session_id);

				if ($reservation) {
					$current_time = current_time('timestamp', true);
					$last_modified = strtotime($reservation->last_modified);
					$woo_reserved_timer_time = blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_time', 10);
					$reserved_until = strtotime($reservation->last_modified) + ($woo_reserved_timer_time * 60);

					if ($current_time >= $reserved_until) {
						WC()->cart->empty_cart();
						$storage->remove_cart_reservation($session_id);
					}
				}
			}
		}, 20);

		add_action('wp', function () {
			if (blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_cart', 'yes') === 'yes') {
				add_action('woocommerce_cart_contents', function() {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->cart_page_render('cart');
				});
			}

			if (blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_mini_cart', 'no') === 'yes') {
				add_action('woocommerce_before_mini_cart', function() {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->cart_page_render('mini-cart');
				});
			}
		}, 25);

		add_action('init', function() {
			if (
				blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_cart', 'yes') === 'yes'
				||
				blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_in_mini_cart', 'no') === 'yes'
			) {
				$hooks_to_listen = [
					'woocommerce_add_to_cart',
					'woocommerce_remove_cart_item',
					'woocommerce_cart_item_restored',
					'woocommerce_cart_item_removed',
					'woocommerce_after_cart_item_quantity_update',
				];

				foreach ($hooks_to_listen as $hook) {
					add_action($hook, function() {
						if (!WC()->session || !WC()->cart) {
							return;
						}

						$session_id = WC()->session->get_customer_id();
						$cart_data = [];

						foreach (WC()->cart->get_cart() as $item) {
							$cart_data[$item['product_id']] = $item['quantity'];

							if (isset($item['variation_id']) && $item['variation_id']) {
								$cart_data[$item['variation_id']] = $item['quantity'];
							}
						}

						$minutes = blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_time', 10);
						$last_modified = gmdate('Y-m-d H:i:s', current_time('timestamp', true));

						$storage = new CartReservationStorage();
						$storage->set_cart_reservation($session_id, $cart_data, $last_modified);
					});
				}

				add_action('woocommerce_cart_emptied', function() {
					if (WC()->session) {
						$storage = new CartReservationStorage();
						$storage->remove_cart_reservation(WC()->session->get_customer_id());
					}
				});

				add_filter('woocommerce_cart_item_required_stock_is_not_enough', function($is_not_enough, $product, $values) {
					global $wpdb;
					$storage = new CartReservationStorage();

					$active = $storage->get_all_active_reservations();
					$reserved = 0;

					$current_time = current_time('timestamp', true);
					$woo_reserved_timer_time = blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_time', 10);
					$total_seconds = $woo_reserved_timer_time * 60;

					foreach ($active as $res) {
						if (
							WC()->session
							&&
							$res->session_id === WC()->session->get_customer_id()
						) {
							continue;
						}

						$expires_at = strtotime($res->last_modified) + $total_seconds;

						if ($current_time < $expires_at) {
							$cart = maybe_unserialize($res->cart_data);

							if (isset($cart[$product->get_id()])) {
								$reserved += (int) $cart[$product->get_id()];
							}
						}
					}

					$required_stock = isset($values['quantity']) ? (int) $values['quantity'] : 0;

					$available = max(0, $product->get_stock_quantity() - $reserved);

					if ($available < $required_stock) {
						if (! $is_not_enough) {
							add_filter('woocommerce_format_stock_quantity', [$this, 'woocommerce_format_stock_quantity'], 10, 2);
						}

						return true;
					}

					return $is_not_enough;
				}, 10, 3);

				add_filter('woocommerce_get_stock_html', function($html, $product) {
					if (
						is_cart()
						||
						is_checkout()
						||
						$product->get_type() !== 'variation'
					) {
						return $html;
					}

					$storage = new CartReservationStorage();
					$active = $storage->get_all_active_reservations();

					$reserved = 0;
					foreach ($active as $res) {
						if (
							WC()->session
							&&
							$res->session_id === WC()->session->get_customer_id()
						) {
							continue;
						}

						$cart = maybe_unserialize($res->cart_data);

						if (isset($cart[$product->get_id()])) {
							$reserved += (int) $cart[$product->get_id()];
						}
					}

					$available = max(0, $product->get_stock_quantity() - $reserved);

					$product->set_stock_quantity($available);

					$html = '';
					$availability = $product->get_availability();

					if (! empty($availability['availability'])) {
						ob_start();

						wc_get_template(
							'single-product/stock.php',
							array(
								'product' => $product,
								'class' => $availability['class'],
								'availability' => $availability['availability'],
							)
						);

						$html = ob_get_clean();
					}

					return $html;
				}, 10, 2);

				add_filter('woocommerce_product_get_stock_quantity', function($stock_quantity, $product) {
					if (
						is_cart()
						||
						is_checkout()
					) {
						return $stock_quantity;
					}

					$storage = new CartReservationStorage();
					$active = $storage->get_all_active_reservations();

					$reserved = 0;
					foreach ($active as $res) {
						if (
							WC()->session
							&&
							$res->session_id === WC()->session->get_customer_id()
						) {
							continue;
						}

						$cart = maybe_unserialize($res->cart_data);

						if (isset($cart[$product->get_id()])) {
							$reserved += (int) $cart[$product->get_id()];
						}
					}

					return max(0, $stock_quantity - $reserved);
				}, 10, 2);
			}
		});

		add_action('wp_ajax_blc_ext_cart_reserved_timer_sync', [$this, 'sync_timers']);
		add_action('wp_ajax_nopriv_blc_ext_cart_reserved_timer_sync', [$this, 'sync_timers']);
	}

	public function woocommerce_format_stock_quantity($stock_quantity, $product) {
		remove_filter('woocommerce_format_stock_quantity', [$this, 'woocommerce_format_stock_quantity'], 10, 2);

		$storage = new CartReservationStorage();
		$active = $storage->get_all_active_reservations();

		$reserved = 0;
		foreach ($active as $res) {
			if (
				WC()->session
				&&
				$res->session_id === WC()->session->get_customer_id()
			) {
				continue;
			}

			$cart = maybe_unserialize($res->cart_data);

			if (isset($cart[$product->get_id()])) {
				$reserved += (int) $cart[$product->get_id()];
			}
		}

		$available = max(0, $product->get_stock_quantity() - $reserved);

		return $available;
	}

	public function sync_timers() {
		wp_send_json_success([
			'cart' => $this->cart_page_render('cart'),
			'mini_cart' => $this->cart_page_render('mini-cart'),
		]);
	}

	public function cart_page_render($class_suffix = 'cart') {
		$classes = ['ct-cart-reserved-timer-placeholder-' . $class_suffix];

		if (!WC()->session) {
			return blocksy_html_tag('div', ['class' => join(' ', $classes)], '');
		}

		$session_id = WC()->session->get_customer_id();

		if (!$session_id) {
			return blocksy_html_tag('div', ['class' => join(' ', $classes)], '');
		}

		$storage = new CartReservationStorage();
		$reservation = $storage->get_current_reservation($session_id);

		if (!$reservation) {
			return blocksy_html_tag('div', ['class' => join(' ', $classes)], '');
		}

		$current_time = current_time('timestamp', true);
		$last_modified = strtotime($reservation->last_modified);
		$woo_reserved_timer_time = blc_theme_functions()->blocksy_get_theme_mod('woo_reserved_timer_time', 10);
		$reserved_until = strtotime($reservation->last_modified) + ($woo_reserved_timer_time * 60);

		$remaining = $reserved_until - $current_time;

		if ($remaining <= 0) {
			return blocksy_html_tag('div', ['class' => join(' ', $classes)], '');
		}

		$remain_minutes = intdiv($remaining, 60);
		$remain_seconds = $remaining % 60;

		$date = gmdate('Y-m-d H:i:s', $reserved_until);

		$countdown = blocksy_html_tag(
			'time',
			['datetime' => $date],
			blocksy_html_tag('span', [], str_pad($remain_minutes, 2, '0', STR_PAD_LEFT)) .
				':' .
			blocksy_html_tag('span', [], str_pad($remain_seconds, 2, '0', STR_PAD_LEFT))
		);

		$message = str_replace(
			'{time}',
			$countdown,
			blc_theme_functions()->blocksy_get_theme_mod(
				'woo_reserved_timer_message',
				// phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
				__('<p><strong>🔥 Hurry up! Your selected items are reserved for {time} minutes.</strong></p>', 'blocksy-companion')
			)
		);

		$classes = ['ct-cart-reserved-timer-' . $class_suffix];

		return blocksy_html_tag(
			'div',
			[
				'class' => join(' ', $classes),
				'data-timeout' => $remaining,
			],
			blocksy_html_tag('div', ['class' => 'ct-cart-reserved-timer-message'], $message)
		);
	}
}
