<?php
/**
 * Custom template for single variation
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product; ?>

<div class="variations_button group">
	<table class="variations" cellspacing="0">
		<tr>
			<td class="label"><label><?php _e( 'Quantity', 'yit' ) ?></label></td>
			<td class="value"><?php woocommerce_quantity_input(); ?></td>
		</tr>
	</table>
</div>

<div class="single_variation"></div>

<div class="variations_button">
	<button type="submit" class="single_add_to_cart_button button alt"><?php echo $product->single_add_to_cart_text(); ?></button>
</div>

<input type="hidden" name="add-to-cart" value="<?php echo $product->id; ?>" />
<input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ); ?>" />
<input type="hidden" name="variation_id" class="variation_id" value="" />
