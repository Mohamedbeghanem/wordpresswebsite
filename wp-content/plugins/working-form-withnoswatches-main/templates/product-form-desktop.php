<?php
if (!defined('ABSPATH')) exit;
global $product;
?>

<div class="god-form-container">
    <form id="god-order-form-desktop" class="god-order-form">
        <!-- Hidden Fields -->
        <input type="hidden" name="action" value="custom_order">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('custom_order_nonce'); ?>">
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
        <input type="hidden" name="product_price" id="product_price_desktop" value="<?php echo esc_attr($product->get_price()); ?>">
        <input type="hidden" name="quantity" value="1">
        <input type="hidden" name="form_start_time" value="<?php echo time(); ?>">
        
        <!-- Honeypot (Anti-Spam) -->
        <div style="position:absolute;left:-5000px;" aria-hidden="true">
            <input type="text" name="user_nickname" tabindex="-1" autocomplete="off">
        </div>
        
        <h3>معلومات الشحن</h3>

        <!-- Name -->
        <div class="god-field">
            <label for="customer_name_desktop">الاسم الكامل <span class="required">*</span></label>
            <input type="text" name="customer_name" id="customer_name_desktop" required placeholder="أدخل اسمك الكامل">
        </div>

        <!-- Phone -->
        <div class="god-field">
            <label for="customer_phone_desktop">رقم الهاتف <span class="required">*</span></label>
            <input type="tel" name="customer_phone" id="customer_phone_desktop" required placeholder="0555123456">
        </div>

        <!-- Wilaya and Commune Row -->
        <div class="god-row">
            <div class="god-field">
                <label for="customer_wilaya_desktop">الولاية <span class="required">*</span></label>
                <select name="customer_wilaya" id="customer_wilaya_desktop" required>
                    <option value="">اختر الولاية</option>
                </select>
            </div>

            <div class="god-field">
                <label for="customer_commune_desktop">البلدية <span class="required">*</span></label>
                <select name="customer_commune" id="customer_commune_desktop" required disabled>
                    <option value="">اختر البلدية</option>
                </select>
            </div>
        </div>

        <!-- Address -->
        <div class="god-field">
            <label for="customer_address_desktop">العنوان الكامل</label>
            <input type="text" name="customer_address" id="customer_address_desktop" placeholder="الشارع، الحي، رقم المنزل">
        </div>

        <!-- Shipping Method -->
        <div class="god-field">
            <label>طريقة التوصيل <span class="required">*</span></label>
            <div class="god-shipping-options">
                <label class="god-shipping-option">
                    <input type="radio" name="shipping_method" value="home" checked>
                    <span class="god-radio-label">📦 توصيل للمنزل</span>
                </label>
                <label class="god-shipping-option">
                    <input type="radio" name="shipping_method" value="office">
                    <span class="god-radio-label">🏢 مكتب البريد</span>
                </label>
            </div>
        </div>
        <input type="hidden" id="product_price_desktop" value="<?php echo esc_attr($product->get_price()); ?>">
        <!-- Order Summary -->
        <div class="god-summary">
            <div class="god-summary-row">
                <span>السعر:</span>
                <span><?php echo wc_price($product->get_price()); ?></span>
            </div>
            <div class="god-summary-row">
                <span>التوصيل:</span>
                <span id="shipping-cost_desktop">اختر الولاية</span>
            </div>
            <div class="god-summary-row god-summary-total">
                <span>المجموع:</span>
                <span id="total-cost_desktop"><?php echo wc_price($product->get_price()); ?></span>
            </div>
        </div>

        <button type="submit" class="god-submit">اطلب الآن</button>
    </form>
</div>
