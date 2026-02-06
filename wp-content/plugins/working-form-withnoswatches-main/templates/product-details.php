<?php
global $product;
if (!$product) {
    return;
}
?>

<div class="product-details">
    <div class="product-images">
        <?php echo $product->get_image(); ?>
    </div>
    <div class="product-info">
        <h1><?php echo $product->get_name(); ?></h1>
        <p><?php echo $product->get_price_html(); ?></p>
        <div><?php echo $product->get_description(); ?></div>
    </div>
</div>

<input type="hidden" name="product_id" value="<?php echo $product->get_id(); ?>">

<!-- Variations -->
<?php if ($product->is_type('variable')) : ?>
    <?php foreach ($product->get_variation_attributes() as $attribute_name => $options) : ?>
        <div class="form-field">
            <label for="<?php echo sanitize_title($attribute_name); ?>"><?php echo wc_attribute_label($attribute_name); ?></label>
            <select class="variation-select" name="variation[<?php echo sanitize_title($attribute_name); ?>]">
                <?php foreach ($options as $option) : ?>
                    <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
