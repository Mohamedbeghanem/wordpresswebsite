<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$product_data = get_query_var( 'product_data' );

if ( ! $product_data ) {
    return;
}

$variations_json = htmlspecialchars( wp_json_encode( $product_data['variations'] ) );
?>
<div class="cod-form-wrapper">
    <div class="form-container">
        <form class="form-content" id="cod-order-form" data-product-id="<?php echo esc_attr( $product_data['product_id'] ); ?>" data-variations="<?php echo $variations_json; ?>">
            <div class="product-info">
                <h3 class="product-name"><?php echo esc_html( $product_data['product_name'] ); ?></h3>
                <div class="product-price"></div>
            </div>

            <div class="swatches-section">
                <?php foreach ( $product_data['attributes'] as $attribute_name => $options ) : ?>
                    <div class="swatch-group">
                        <label class="swatch-label"><?php echo esc_html( wc_attribute_label( $attribute_name ) ); ?></label>
                        <div class="swatch-options">
                            <?php foreach ( $options as $option ) :
                                $term = get_term_by( 'slug', $option, $attribute_name );
                                $label = $term ? $term->name : $option;
                            ?>
                                <button type="button" class="swatch-btn" data-attribute="<?php echo esc_attr( $attribute_name ); ?>" data-value="<?php echo esc_attr( $option ); ?>">
                                    <?php echo esc_html( $label ); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h2 class="form-title">Remplissez le formulaire pour commander</h2>

            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="customer_name" placeholder="Nom et prénom" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="customer_phone" placeholder="Numéro de téléphone" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <select name="customer_state" required>
                        <option value="">votre wilaya</option>
                        </select>
                </div>
                <div class="form-group">
                    <select name="customer_city" required>
                        <option value="">votre commune</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <input type="text" name="customer_address" placeholder="📍 votre adresse" required>
            </div>

            <div class="delivery-section">
                <div class="delivery-title">Lieu de livraison</div>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="home" name="delivery_type" value="home" checked>
                        <label for="home">À domicile</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="office" name="delivery_type" value="office">
                        <label for="office">Au bureau de livraison</label>
                    </div>
                </div>
            </div>

            <div class="price-section">
                <div class="price-row">
                    <span class="price-label">Frais de livraison</span>
                    <span class="price-value" id="delivery-fee">0 DA</span>
                </div>
                <div class="price-row">
                    <span class="price-label">Prix des produits</span>
                    <span class="price-value" id="product-total">0 DA</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Coût total</span>
                    <span class="total-value" id="grand-total">0 DA</span>
                </div>
            </div>

            <div class="quantity-control">
                <button type="button" class="qty-btn" id="qty-decrease">−</button>
                <input type="number" class="qty-input" name="quantity" id="quantity" value="1" min="1" readonly>
                <button type="button" class="qty-btn" id="qty-increase">+</button>
            </div>
            
            <input type="hidden" name="variation_id" id="variation_id" value="">
            <?php COD_DZ_Honeypot::render_field(); ?>
            <button type="submit" class="submit-btn">Commandez Maintenant</button>
        </form>
    </div>
</div>
