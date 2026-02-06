<?php
/**
 * Plugin Name:       Custom Product Demo
 * Plugin URI:        https://example.com/
 * Description:       A plugin to display a single WooCommerce product with a custom order form.
 * Version:           2.5.0
 * Author:            Mohamed Amine B.
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-product-demo
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

function custom_product_demo_init() {
    if (class_exists('WooCommerce')) {
        class Custom_Product_Demo {
            public function __construct() {
                add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);
                add_shortcode('custom_product_form_desktop', array($this, 'shortcode_output'));
                add_shortcode('custom_product_form_phone', array($this, 'shortcode_output_phone'));
                add_action('wp_ajax_nopriv_custom_order', array($this, 'handle_order'));
                add_action('wp_ajax_custom_order', array($this, 'handle_order'));
            }

            public function enqueue_scripts() {
                if (is_admin()) {
                    return;
                }
                
                wp_enqueue_script('jquery');
                
                wp_enqueue_style(
                    'god-custom-product-style', 
                    plugin_dir_url(__FILE__) . 'css/style.css',
                    array(),
                    '2.4.0',
                    'all'
                );
                
                wp_enqueue_script(
                    'god-custom-product-script', 
                    plugin_dir_url(__FILE__) . 'js/main.js', 
                    array('jquery'), 
                    '2.4.0', 
                    true
                );
            }

            private function get_wilayas() {
                $file = plugin_dir_path(__FILE__) . 'data/wilayas.json';
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('GOD Plugin: JSON decode error in wilayas.json - ' . json_last_error_msg());
                        return array();
                    }
                    
                    return $data;
                }
                error_log('GOD Plugin: wilayas.json not found at ' . $file);
                return array();
            }

            private function get_communes() {
                $file = plugin_dir_path(__FILE__) . 'data/communes.json';
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('GOD Plugin: JSON decode error in communes.json - ' . json_last_error_msg());
                        return array();
                    }
                    
                    return $data;
                }
                error_log('GOD Plugin: communes.json not found at ' . $file);
                return array();
            }

            private function get_shipping_costs() {
                $file = plugin_dir_path(__FILE__) . 'data/shipping.json';
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('GOD Plugin: JSON decode error in shipping.json - ' . json_last_error_msg());
                        return array();
                    }
                    
                    return $data;
                }
                error_log('GOD Plugin: shipping.json not found at ' . $file);
                return array();
            }

            public function shortcode_output($atts) {
                $atts = shortcode_atts(array(
                    'product_id' => get_the_ID(),
                ), $atts, 'custom_product_form');

                $product_id = intval($atts['product_id']);
                global $product;
                $product = wc_get_product($product_id);

                if (!$product) {
                    return '<p>المنتج غير موجود.</p>';
                }

                // Load data
                $wilayas = $this->get_wilayas();
                $communes = $this->get_communes();
                $shipping_costs = $this->get_shipping_costs();
                
                // NUCLEAR OPTION: Embed data directly in HTML
                ob_start();
                ?>
                <script type="text/javascript">
                // Inline embedded data - loads immediately, bypasses all optimization
                window.custom_product_ajax = <?php echo wp_json_encode(array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('custom_order_nonce'),
                    'wilayas' => $wilayas,
                    'communes' => $communes,
                    'shipping_costs' => $shipping_costs
                )); ?>;
                </script>
                <?php
                
                include(plugin_dir_path(__FILE__) . 'templates/product-form-desktop.php');
                
                return ob_get_clean();
            }

            public function shortcode_output_phone($atts) {
                $atts = shortcode_atts(array(
                    'product_id' => get_the_ID(),
                ), $atts, 'custom_product_form_phone');

                $product_id = intval($atts['product_id']);
                global $product;
                $product = wc_get_product($product_id);

                if (!$product) {
                    return '<p>المنتج غير موجود.</p>';
                }

                // Load data
                $wilayas = $this->get_wilayas();
                $communes = $this->get_communes();
                $shipping_costs = $this->get_shipping_costs();
                
                // NUCLEAR OPTION: Embed data directly in HTML
                ob_start();
                ?>
                <script type="text/javascript">
                // Inline embedded data - loads immediately, bypasses all optimization
                window.custom_product_ajax_phone = <?php echo wp_json_encode(array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('custom_order_nonce'),
                    'wilayas' => $wilayas,
                    'communes' => $communes,
                    'shipping_costs' => $shipping_costs
                )); ?>;
                </script>
                <?php
                
                include(plugin_dir_path(__FILE__) . 'templates/product-form-phone.php');
                
                return ob_get_clean();
            }

            public function handle_order() {
                // Verify nonce
                if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_order_nonce')) {
                    $this->log_rejection('Invalid nonce');
                    wp_send_json_error(array('message' => 'فشل التحقق الأمني.'));
                }

                // Anti-spam: Honeypot
                if (!empty($_POST['user_nickname'])) {
                    $this->log_rejection('Honeypot field filled');
                    wp_send_json_error(array('message' => 'طلب غير صالح.'));
                }

                // Anti-spam: Form submit time
                $start_time = isset($_POST['form_start_time']) ? (int)$_POST['form_start_time'] : 0;
                if ((time() - $start_time) < 2) {
                    $this->log_rejection('Form submitted too quickly');
                    wp_send_json_error(array('message' => 'طلب غير صالح.'));
                }

                // Anti-spam: User agent check
                if (empty($_SERVER['HTTP_USER_AGENT'])) {
                    $this->log_rejection('Empty user-agent');
                    wp_send_json_error(array('message' => 'طلب غير صالح.'));
                }

                // IP Rate Limiting
                $ip_address = $this->get_ip_address();
                $transient_name = 'order_limit_' . md5($ip_address);
                $order_count = get_transient($transient_name);

                if ($order_count === false) {
                    set_transient($transient_name, 1, 30 * MINUTE_IN_SECONDS);
                } elseif ($order_count >= 3) {
                    $this->log_rejection('IP rate limit exceeded');
                    wp_send_json_error(array('message' => 'لقد تجاوزت الحد المسموح. يرجى المحاولة لاحقاً.'));
                } else {
                    set_transient($transient_name, $order_count + 1, 30 * MINUTE_IN_SECONDS);
                }

                // Validate required fields
                $required_fields = array(
                    'product_id' => 'معرف المنتج',
                    'quantity' => 'الكمية',
                    'customer_name' => 'الاسم',
                    'customer_phone' => 'رقم الهاتف',
                    'customer_wilaya' => 'الولاية',
                    'customer_commune' => 'البلدية',
                    'shipping_method' => 'طريقة التوصيل'
                );

                foreach ($required_fields as $field => $label) {
                    if (empty($_POST[$field])) {
                        wp_send_json_error(array('message' => $label . ' مطلوب.'));
                    }
                }

                // Sanitize inputs
                $product_id = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                $customer_name = sanitize_text_field($_POST['customer_name']);
                $customer_phone = sanitize_text_field($_POST['customer_phone']);
                $customer_wilaya = sanitize_text_field($_POST['customer_wilaya']);
                $customer_commune = sanitize_text_field($_POST['customer_commune']);
                $customer_address = sanitize_text_field($_POST['customer_address'] ?? '');
                $shipping_method = sanitize_text_field($_POST['shipping_method']);

                $customer_email = !empty($_POST['customer_email']) 
                    ? sanitize_email($_POST['customer_email']) 
                    : 'customer_' . time() . '@order.local';

                // Spam detection
                if ($this->is_spam($customer_name)) {
                    $this->log_rejection('Spam detected in name: ' . $customer_name);
                    wp_send_json_error(array('message' => 'البيانات المدخلة غير صالحة.'));
                }

                if ($this->is_disposable_email($customer_email)) {
                    $this->log_rejection('Disposable email: ' . $customer_email);
                    wp_send_json_error(array('message' => 'يرجى استخدام بريد إلكتروني صالح.'));
                }

                // Verify product
                $product = wc_get_product($product_id);
                if (!$product) {
                    wp_send_json_error(array('message' => 'المنتج غير موجود.'));
                }

                // Create order
                try {
                    $order = wc_create_order();
                    $order->add_product($product, $quantity);

                    // Billing
                    $order->set_billing_first_name($customer_name);
                    $order->set_billing_last_name('');
                    $order->set_billing_email($customer_email);
                    $order->set_billing_phone($customer_phone);
                    $order->set_billing_address_1($customer_address);
                    $order->set_billing_city($customer_commune);
                    $order->set_billing_state($customer_wilaya);
                    $order->set_billing_postcode('');
                    $order->set_billing_country('DZ');

                    // Shipping
                    $order->set_shipping_first_name($customer_name);
                    $order->set_shipping_last_name('');
                    $order->set_shipping_address_1($customer_address);
                    $order->set_shipping_city($customer_commune);
                    $order->set_shipping_state($customer_wilaya);
                    $order->set_shipping_postcode('');
                    $order->set_shipping_country('DZ');

                    // Shipping cost
                    $shipping_costs = $this->get_shipping_costs();
                    $shipping_cost = isset($shipping_costs[$customer_wilaya][$shipping_method]) 
                        ? floatval($shipping_costs[$customer_wilaya][$shipping_method]) 
                        : 0;

                    if ($shipping_cost > 0) {
                        $shipping_method_title = ($shipping_method === 'home') 
                            ? 'توصيل للمنزل' 
                            : 'توصيل للمكتب';
                        
                        $item = new WC_Order_Item_Shipping();
                        $item->set_method_title($shipping_method_title);
                        $item->set_method_id('flat_rate');
                        $item->set_total($shipping_cost);
                        $order->add_item($item);
                    }

                    // Meta data
                    $order->add_meta_data('_shipping_method_type', $shipping_method, true);
                    $order->add_meta_data('_customer_wilaya', $customer_wilaya, true);
                    $order->add_meta_data('_customer_commune', $customer_commune, true);

                    $order->calculate_totals();
                    $order->update_status('completed', 'تم إنشاء الطلب من النموذج المخصص');
                    $order->save();

                    $this->log_success($order->get_id(), $ip_address);

                    wp_send_json_success(array(
                        'message' => 'تم إنشاء الطلب بنجاح!',
                        'redirect_url' => $order->get_checkout_order_received_url(),
                        'order_id' => $order->get_id()
                    ));

                } catch (Exception $e) {
                    $this->log_rejection('Order creation failed: ' . $e->getMessage());
                    wp_send_json_error(array('message' => 'حدث خطأ أثناء إنشاء الطلب.'));
                }
            }

            private function log_rejection($reason) {
                $log_dir = plugin_dir_path(__FILE__) . 'logs';
                if (!file_exists($log_dir)) {
                    mkdir($log_dir, 0755, true);
                }
                
                $log_message = sprintf(
                    "[%s] REJECTED - IP: %s | Reason: %s | UA: %s\n",
                    date('Y-m-d H:i:s'),
                    $this->get_ip_address(),
                    $reason,
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                );
                error_log($log_message, 3, $log_dir . '/rejected_orders.log');
            }

            private function log_success($order_id, $ip_address) {
                $log_dir = plugin_dir_path(__FILE__) . 'logs';
                if (!file_exists($log_dir)) {
                    mkdir($log_dir, 0755, true);
                }
                
                $log_message = sprintf(
                    "[%s] SUCCESS - Order ID: %s | IP: %s\n",
                    date('Y-m-d H:i:s'),
                    $order_id,
                    $ip_address
                );
                error_log($log_message, 3, $log_dir . '/successful_orders.log');
            }

            private function get_ip_address() {
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    return $_SERVER['HTTP_CLIENT_IP'];
                } else {
                    return $_SERVER['REMOTE_ADDR'];
                }
            }

            private function is_spam($text) {
                return preg_match('/(.)\1{4,}|(asdasd|test|aaaa|qqqq|xxxx|zzzz)/i', $text);
            }

            private function is_disposable_email($email) {
                $disposable_domains = array(
                    'mailinator.com', 'temp-mail.org', '10minutemail.com',
                    'guerrillamail.com', 'throwaway.email', 'tempmail.com'
                );
                $domain = substr(strrchr($email, "@"), 1);
                return in_array(strtolower($domain), $disposable_domains);
            }
        }
        new Custom_Product_Demo();
    }
}
add_action('plugins_loaded', 'custom_product_demo_init');
