<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product, $woocommerce_loop;


// Ensure visibility
if ( ! $product || ! $product->is_visible() ) {
    return;
}


if ( ! is_shop_enabled() || ! yit_get_option( 'shop-view-show-price' ) ){
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
}
if ( ! yit_get_option( 'shop-view-show-rating' ) ) {
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
}

$description_class = '';
if ( yit_get_option('shop-view-show-description') ) {
    $description_class .= ' show-on-list';
}
if ( yit_get_option('shop-classic-show-description') ) {
    $description_class .= ' show-on-classic';
}

?>
<li <?php post_class(); if ( $woocommerce_loop['view'] == 'list' ) : ?> style="min-height: <?php echo $height; ?>px;"<?php endif; ?>>

    <div class="product-wrapper">

        <?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

        <?php
        /**
         * woocommerce_before_shop_loop_item_title hook
         *
         * @hooked woocommerce_show_product_loop_sale_flash - 10
         * @hooked woocommerce_template_loop_product_thumbnail - 10
         */
        do_action( 'woocommerce_before_shop_loop_item_title' );
        ?>

        <?php if ( $woocommerce_loop['layout'] == 'classic' && yit_get_option('shop-view-show-shadow') ) : ?>
            <div class="product-shadow"></div>
        <?php endif; ?>

        <div class="product-meta">
            <div class="product-meta-wrapper">

                <?php
                /**
                 * woocommerce_shop_loop_item_title hook
                 *
                 * @hooked woocommerce_template_loop_product_title - 10
                 */
                do_action( 'woocommerce_shop_loop_item_title' );
                ?>

                <?php if( ! yit_get_option( 'shop-open-hover' )): ?>
                    <div class="grid-add-to-cart"><?php woocommerce_template_loop_add_to_cart() ?></div>
                <?php endif; ?>

                <?php
                /**
                 * woocommerce_after_shop_loop_item_title hook
                 *
                 * @hooked woocommerce_template_loop_rating - 5
                 * @hooked woocommerce_template_loop_price - 10
                 */
                do_action( 'woocommerce_after_shop_loop_item_title' );
                ?>

                <div class="product-description<?php echo $description_class ?>">
                    <?php woocommerce_template_single_excerpt(); ?>
                </div>

                <?php do_action( 'woocommerce_after_shop_loop_item' ); ?>

            </div>
        </div>

    </div>

</li>