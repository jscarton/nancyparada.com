<?php
/**
 * All functions and hooks for woocommerce plugin
 *
 * @package WordPress
 * @subpackage YIThemes
 */

global $woocommerce;
global $woo_shop_folder;

if ( ! defined( 'YIT_DEBUG' ) || ! YIT_DEBUG ) {
        $message = get_option( 'woocommerce_admin_notices', array() );
        $message = array_diff( $message, array( 'template_files' ));
        update_option( 'woocommerce_admin_notices', $message );
    }

/********** FIX WC 2.1 *************/

if ( version_compare( WC()->version, '2.1', '<' ) ) {
    add_filter( 'woocommerce_template_url', create_function( "", "return 'woocommerce_2.0.x/';" ) );
    add_action( 'wp_enqueue_scripts', 'yit_enqueue_woocommerce_styles', 11 );
    //add_action( 'woocommerce_single_product_summary', 'yit_rating_singleproduct', 10 );

    $woo_shop_folder = 'shop';

}
else {

    /* woocommerce 2.1.x */
    if ( version_compare( $woocommerce->version, '2.2', '<' ) ) {
        add_filter( 'WC_TEMPLATE_PATH', create_function( "", "return 'woocommerce_2.1.x/';" ) );
    } /* woocommerce 2.2.x */
    else if ( version_compare( $woocommerce->version, '2.3', '<' ) ) {
        add_filter( 'woocommerce_template_path', create_function( "", "return 'woocommerce_2.2.x/';" ) );
    }/* woocommerce 2.3.x */
    else if ( version_compare( $woocommerce->version, '2.3', '>=' ) ) {
        add_action( 'wp_enqueue_scripts', 'yit_enqueue_woocommerce_2_3_assets' );
        add_filter( 'the_title', 'yit_remove_wc_2_3_title_filter', 1 );

        if( version_compare( $woocommerce->version, '2.4', '<' ) ) {
            add_filter( 'woocommerce_template_path', create_function( "", "return 'woocommerce_2.3.x/';" ) );
        }
        else if( version_compare( $woocommerce->version, '2.5', '<' ) ) {
            add_filter( 'woocommerce_template_path', create_function( "", "return 'woocommerce_2.4.x/';" ) );
        }
        else if( version_compare( $woocommerce->version, '2.6', '<' ) ) {
            add_filter( 'woocommerce_template_path', create_function( "", "return 'woocommerce_2.5.x/';" ) );
        }else{
            // WC 2.6
            add_filter( 'post_class', 'yit_wc_product_post_class', 30, 3 );
            add_filter( 'product_cat_class', 'yit_wc_product_product_cat_class', 30, 3 );

            // Removed Template
            yit_wc_2_6_removed_unused_template();
        }
    }

    add_filter( 'yit_add_image_size', 'yit_add_featured_image_size' );


    add_filter( 'woocommerce_enqueue_styles', 'yit_enqueue_wc_styles' );
    $woo_shop_folder = 'global';
    add_action( 'admin_init', 'yit_check_version', 8 );
    if ( version_compare( $woocommerce->version, '2.6', '<' ) ) {
        if (!is_active_widget(false, false, 'woocommerce_price_filter', true)) {
            add_filter('loop_shop_post_in', array(WC()->query, 'price_filter'));
        }
    }
}

function yit_enqueue_woocommerce_2_3_assets() {
    wp_enqueue_script( 'yit-woocommerce-2-3', yit_remove_protocol_url(YIT_THEME_ASSETS_URL) . '/js/woocommerce_2.3.js', array( 'jquery', 'jquery-cookie' ), '1.0', true );
}

function yit_check_version() {
    if ( get_option( 'yit_theme_version_1.2.0' ) || get_option( '_wc_needs_update' ) == 1 ) {
        return;
    }
    clear_menu_from_old_woo_pages();
    update_option( 'yit_theme_version_1.2.0', true );
}

function clear_menu_from_old_woo_pages() {
    $locations = get_nav_menu_locations();
    $logout    = get_page_by_path( 'my-account/logout' );
    $parent    = get_page_by_path( 'my-account' );
    $permalink = get_option( 'permalink_structure' );

    $pages_deleted = array(
        get_option( 'woocommerce_pay_page_id' ), get_option( 'woocommerce_thanks_page_id' ), get_option( 'woocommerce_view_order_page_id' ), get_option( 'woocommerce_view_order_page_id' ),
        get_option( 'woocommerce_change_password_page_id' ), get_option( 'woocommerce_edit_address_page_id' ), get_option( 'woocommerce_lost_password_page_id' )
    );


    foreach ( (array) $locations as $name => $menu_ID ) {
        $items = wp_get_nav_menu_items( $menu_ID );
        foreach ( (array) $items as $item ) {

            if ( ! is_null( $logout ) && ! is_null( $parent ) && $item->object_id == $logout->ID ) {
                update_post_meta( $item->ID, '_menu_item_object', 'custom' );
                update_post_meta( $item->ID, '_menu_item_type', 'custom' );
                if ( $permalink == '' ) {
                    $new_url = get_permalink( $parent->ID ) . '&customer-logout';
                }
                else {
                    wp_update_post( array(
                            'ID'        => $logout->ID,
                            'post_name' => 'customer-logout', )
                    );
                    $new_url = get_permalink( $logout->ID );
                }
                update_post_meta( $item->ID, '_menu_item_url', $new_url );
                wp_update_post( array(
                        'ID'         => $item->ID,
                        'post_title' => $logout->post_title, )
                );
            }

            foreach ( $pages_deleted as $page ) {

                if ( $page && $item->object_id == $page && $item->object == 'page' ) {

                    wp_delete_post( $item->ID );

                }

            }
        }

    }
}

/***** END FIX WC 2.1 *******/

/* === GENERAL SETTINGS === */
add_theme_support('woocommerce');
register_sidebar( yit_sidebar_args( 'Shop Sidebar' ) );


/* === HOOKS === */
add_action( 'wp_enqueue_scripts', 'yit_enqueue_woocommerce_assets' );
add_action( 'woocommerce_before_main_content', 'yit_shop_page_meta' );
add_action( 'shop_page_meta'     , 'yit_woocommerce_catalog_ordering' );
add_action( 'shop_page_meta'     , 'yit_woocommerce_list_or_grid' );
add_filter( 'loop_shop_per_page' , 'yit_set_posts_per_page');
add_filter( 'yith_wcwl_button_label', 'yit_change_wishlist_label' );
add_filter( 'yith-wcwl-browse-wishlist-label', 'yit_change_browse_wishlist_label' );
add_action( 'get_footer', 'yit_woocp_footer_script', 20 );
add_action( 'yit_activated', 'yit_woocommerce_default_image_dimensions');
add_action( 'admin_init', 'yit_woocommerce_update' ); //update image names after woocommerce update  
add_action( 'wp_head', 'yit_size_images_style' );
add_action( 'woocommerce_before_main_content', 'yit_woocommerce_primary_start', 5 );
add_action( 'woocommerce_sidebar', 'yit_woocommerce_primary_end', 99 );

add_filter( 'yit_sample_data_tables',  'yit_save_woocommerce_tables' );
add_filter( 'yit_sample_data_options', 'yit_save_woocommerce_options' );
add_filter( 'yit_sample_data_options', 'yit_save_wishlist_options' );
add_filter( 'yit_sample_data_options', 'yit_add_plugins_options' );

/* shop */
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
add_filter( 'woocommerce_breadcrumb_defaults', 'yit_change_breadcrumb_args' );

if( is_shop_enabled() ){
add_action( 'woocommerce_after_shop_loop_item', 'yit_product_other_actions' );
}else{ add_action( 'woocommerce_after_single_product_summary', 'yit_product_other_actions', 5 ); }

add_action( 'wp_head', 'yit_woocommerce_javascript_scripts' );
add_action( 'woocommerce_before_shop_loop_item_title', 'yit_woocommerce_out_of_stock_flash' );
add_filter( 'yith-wcan-frontend-args', 'yit_wcan_change_pagination_class' );
if ( yit_get_option( 'shop-view-show-rating' ) ) add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 20 );

/* single */
remove_action( 'yit_after_header', 'yit_page_meta', 20 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash' );

if ( yit_get_option('shop-show-breadcrumb') ) {
    add_action( 'woocommerce_before_shop_loop', 'woocommerce_breadcrumb', 10, 0 );
}

if ( yit_get_option('shop-show-single-product-breadcrumb') ) {
    add_action( 'woocommerce_single_product_summary', 'woocommerce_breadcrumb', 10, 0 );
}

add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 25 );
add_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_sale_flash' );

add_filter( 'woocommerce_product_tabs', 'yit_reorder_product_tabs' );

/* Rich Snippet fix */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
add_action( 'woocommerce_after_single_product', 'woocommerce_upsell_display', 15 );

if ( ! yit_get_option( 'shop-show-related' ) ) {

    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

}
else {

    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
    add_action( 'woocommerce_after_single_product', 'woocommerce_output_related_products', 20 );
    add_action( 'woocommerce_related_products_args', 'yit_related_posts_per_page' );

}

add_filter('woocommerce_breadcrumb_defaults','yit_woocommerce_breadcrumb_defaults');

function yit_related_posts_per_page() {
    global $product;
    $related = $product->get_related(yit_get_option('shop-number-related'));
    return array(
        'posts_per_page' 		=> -1,
        'post_type'				=> 'product',
        'ignore_sticky_posts'	=> 1,
        'no_found_rows' 		=> 1,
        'post__in' 				=> $related
    );
}

if( !yit_get_option( 'shop-show-metas') ) {
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
}

/* tabs */
add_action( 'woocommerce_product_tabs', 'yit_woocommerce_add_tabs' );  // Woo 2
add_action( 'woocommerce_product_tab_panels', 'yit_woocommerce_add_info_panel', 40 );
add_action( 'woocommerce_product_tab_panels', 'yit_woocommerce_add_custom_panel', 50 );

/* admin */
add_action( 'woocommerce_product_options_general_product_data', 'yit_woocommerce_admin_product_ribbon_onsale' );
add_action( 'woocommerce_process_product_meta', 'yit_woocommerce_process_product_meta',2,2 );
add_action( 'yit_after_import', create_function( '', 'update_option("woocommerce_responsive_images", "yes");' ) );


// Redirect to checkout page after add to cart
if ( yit_get_option( 'shop-redirect-to-checkout' ) ) {
    if ( version_compare( WC()->version, '2.3', '<' ) ) {
        add_filter( 'add_to_cart_redirect' , 'yit_redirect_to_checkout' );
    }else{
        add_filter( 'woocommerce_add_to_cart_redirect' , 'yit_redirect_to_checkout' );
    }

    add_filter ( 'wc_add_to_cart_params', 'yit_redirect_cart_to_checkout' );
}



// active the price filter
if( version_compare($woocommerce->version,"2.0.0",'<') ) {
    add_action('init', 'woocommerce_price_filter_init');
}
//add_filter('loop_shop_post_in', 'woocommerce_price_filter');

//add to cart button
add_filter('single_add_to_cart_text', 'yit_add_to_cart_text');
add_filter('add_to_cart_text', 'yit_add_to_cart_text');
function yit_add_to_cart_text( $text ) {
    global $product;

    if($product->product_type == "external" ) return $text;
    return __( yit_get_option( 'add-to-cart-text' ), 'yit' );
}

/* compare button */
global $yith_woocompare;
if ( isset($yith_woocompare) ) {
    remove_action( 'woocommerce_after_shop_loop_item', array( $yith_woocompare->obj, 'add_compare_link' ), 20 );
    remove_action( 'woocommerce_single_product_summary', array( $yith_woocompare->obj, 'add_compare_link' ), 35 );
}

/* magnifier option */
add_filter( 'yith_wcmg_options_theme_plugin', 'yit_remove_items_options_yith_wcmg' );

/* === FUNCTIONS === */
function yit_product_layout() {
    return yit_get_option( 'shop-single-layout', 'layout-2' );
}
function yit_enqueue_woocommerce_assets() {
    //yit_wp_enqueue_style( 1000, 'woocommerce-custom-style', YIT_THEME_URL . '/woocommerce/custom.css' );
    wp_enqueue_script( 'yit-woocommerce', YIT_THEME_ASSETS_URL . '/js/woocommerce.js', array( 'jquery', 'jquery-cookie' ), '1.0', true );
}

function yit_woocommerce_javascript_scripts() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            $('body').bind('added_to_cart', function(){
                $('.add_to_cart_button.added').text('<?php echo apply_filters( 'yit_added_to_cart_text', __( 'ADDED', 'yit' ) ); ?>');
            });
        });
    </script>
<?php
}

function yit_woocommerce_catalog_ordering() {
    if ( ! is_single() ) woocommerce_catalog_ordering();
}

function yit_set_posts_per_page( $cols ) {
    $items = yit_get_option( 'shop-products-per-page', $cols );
    return $items == 0 ? -1 : $items;
}

function yit_product_other_actions() {
    yith_wc_get_template( 'loop/other-actions.php' );
}

function yit_woocommerce_list_or_grid() {
    global $woo_shop_folder;
    yith_wc_get_template( $woo_shop_folder . '/list-or-grid.php' );
}

function yit_woocommerce_added_button() {
    yith_wc_get_template( 'loop/added.php' );
}

function yit_shop_page_meta() {
    global $woo_shop_folder;
    yith_wc_get_template( $woo_shop_folder . '/page-meta.php' );
}


function yit_woocommerce_show_product_thumbnails() {
    yith_wc_get_template( 'single-product/thumbs.php' );
}

function yit_change_breadcrumb_args( $args ) {
    $args['delimiter'] = ' &gt; ';

    return $args;
}

function yit_wcan_change_pagination_class( $args ) {
    $args['pagination'] = '.general-pagination';
    return $args;
}

/* Woo >= 2 */
function yit_woocommerce_add_tabs( $tabs ) {
    global $post;

    if ( yit_get_post_meta( yit_post_id(), '_use_ask_info' ) == 1 ) {
        $tabs['info'] = array(
            'title'    => apply_filters( 'yit_ask_info_label', __('Product Inquiry', 'yit') ),
            'priority' => 30,
            'callback' => 'yit_woocommerce_add_info_panel'
        );
    }

    $custom_tabs = yit_get_post_meta( yit_post_id(), '_custom_tabs');
    if( !empty( $custom_tabs ) ) {
        foreach( $custom_tabs as $tab ) {
            $tabs['custom'.$tab["position"]] = array(
                'title'    => $tab["name"],
                'priority' => 30,
                'callback' => 'yit_woocommerce_add_custom_panel',
                'custom_tab' => $tab
            );
        }
    }

    return $tabs;
}

function yit_woocommerce_prev_next_nav() {
    yith_wc_get_template( 'single-product/nav-links.php' );
}

/* custom and info tabs Woo 2 < */
function yit_woocommerce_add_info_tab() {
    yith_wc_get_template( 'single-product/tabs/tab-info.php' );
}

function yit_woocommerce_add_info_panel() {
    yith_wc_get_template( 'single-product/tabs/info.php' );
}

function yit_woocommerce_add_custom_tab() {
    yith_wc_get_template( 'single-product/tabs/tab-custom.php' );
}

function yit_woocommerce_add_custom_panel( $key, $tab ) {
    yith_wc_get_template( 'single-product/tabs/custom.php', array( 'key' => $key, 'tab' => $tab ) );
}

function woocommerce_template_loop_product_thumbnail() {
    global $product;

    echo '<a href="' . apply_filters( 'woocommerce_loop_thumbnail_url', get_permalink() ) . '" class="thumb">' . woocommerce_get_product_thumbnail();

    if ( ! yit_get_option( 'shop-use-second-image' ) ) return;

    // add another image for hover
    $attachments = $product->get_gallery_attachment_ids();
    if ( ! empty( $attachments ) && isset( $attachments[0] ) ) {
        yit_image( "id=$attachments[0]&size=shop_catalog&class=image-hover" );
    }

    echo  '</a>';
}

function yit_product_rate_size() {
    return 13;
}

function yit_woocommerce_sharethis() {
    if ( is_product() ) return;
    do_action('woocommerce_share');
}

function yit_woocommerce_out_of_stock_flash() {
    global $product;

    if ( ! $product->is_in_stock() ) echo '<span class="out-of-stock" style="display: inline;">out of stock</span>';
}

/* share */
function yit_woocommerce_share() {
    if( !yit_get_option( 'shop-share' ) )
    { return; }

    echo do_shortcode('[share class="product-share" title="' . yit_get_option( 'shop-share-title' ) . '" socials="' . yit_get_option( 'shop-share-socials' ) . '"]');
}

function yit_add_sharethis_button_js() {
    global $woocommerce, $product, $post, $yit_sharethis;

    if ( ! isset( $woocommerce->integrations->integrations['sharethis'] ) ||
        empty( $woocommerce->integrations->integrations['sharethis']->publisher_id ) ||
        ( (! yit_get_option('shop-view-show-share') && ! is_product()) || (! yit_get_option('shop-single-show-share') && is_product()) ) ||
        ( yit_get_option( 'shop-layout', 'with-hover' ) == 'classic' && is_product() )
    ) return;

    if ( ! isset( $yit_sharethis ) ) {
        $sharethis = ( is_ssl() ) ? 'https://ws.sharethis.com/button/buttons.js' : 'http://w.sharethis.com/button/buttons.js';
        echo '<script type="text/javascript">var switchTo5x=true;</script><script type="text/javascript" src="' . $sharethis . '"></script>';
        echo '<script type="text/javascript">stLight.options({publisher:"' . $woocommerce->integrations->integrations['sharethis']->publisher_id . '" });</script>';
    }

    printf('<script type="text/javascript">
    stLight.options({
            onhover:false
    });
    stWidget.addEntry({
        	"service" : "sharethis",
        	"element" : document.getElementById("%s"),
        	"url"     : "%s",
        	"title"   : "%s",
        	"type"    : "large",
        	"text"    : "%s",
        	"image"   : "%s",
        	"summary" : "%s"
        }, {button:true});
    </script>', "share_$product->id", get_permalink($product->id), get_the_title(), get_the_title(), yit_image( "output=url", false ), str_replace( array( "\r", "\n" ), ' ', esc_attr( strip_tags( $post->post_content ) ) ) );

    $yit_sharethis = true;
}

/* checkout */
add_filter('wp_redirect', 'yit_woocommerce_checkout_registration_redirect');
function yit_woocommerce_checkout_registration_redirect( $location ) {
    if ( isset($_POST['register']) && $_POST['register'] && isset($_POST['yit_checkout']) && $location == get_permalink(yith_wc_get_page_id('myaccount')) ) {
        $location = get_permalink(yith_wc_get_page_id('checkout'));
    }

    return $location;
}

function yit_change_wishlist_label() {
    return __( 'Wishlist', 'yit' );
}

function yit_change_browse_wishlist_label() {
    return __( 'View Wishlist', 'yit' );
}



/* admin */
function yit_woocommerce_admin_product_ribbon_onsale() {
    yith_wc_get_template( 'admin/custom-onsale.php' );
}
function yit_woocommerce_process_product_meta( $post_id, $post ) {

    $active = (isset($_POST['_active_custom_onsale'])) ? 'yes' : 'no';
    update_post_meta( $post_id, '_active_custom_onsale', esc_attr( $active ) );

    if (isset($_POST['_preset_onsale_icon'])) update_post_meta( $post_id, '_preset_onsale_icon', esc_attr( $_POST['_preset_onsale_icon'] ) );
    if (isset($_POST['_custom_onsale_icon'])) update_post_meta( $post_id, '_custom_onsale_icon', esc_attr( $_POST['_custom_onsale_icon'] ) );
}

// Detect the span to use for the products list
function yit_detect_span_catalog_image( &$classes = null ) {
    global $woocommerce_loop, $yit_is_feature_tab;

    if ( ! isset( $classes ) ) {
        $classes = &$woocommerce_loop['li_class'];
    }
    $content_width = yit_get_sidebar_layout() == 'sidebar-no' ? 1170 : 870;
    if ( isset( $yit_is_feature_tab ) && $yit_is_feature_tab ) $content_width -= 300;
    $product_width = yit_shop_catalog_w() + ( $woocommerce_loop['layout'] == 'classic' ? 6 : 10 ) + 2;  // 10 = padding & 2 = border
    $is_span = false;
    if ( apply_filters( 'yith_woocommerce_responsive_images' , true ) ) {
        $is_span = true;
        if ( yit_get_sidebar_layout() == 'sidebar-no' ) {
            if ( $product_width >= 0   && $product_width < 120 ) { $classes[] = 'span1'; $woocommerce_loop['columns'] = 12; }
            elseif ( $product_width >= 120 && $product_width < 220 ) { $classes[] = 'span2'; $woocommerce_loop['columns'] = 6;  }
            elseif ( $product_width >= 220 && $product_width < 320 ) { $classes[] = 'span3'; $woocommerce_loop['columns'] = 4;  }
            elseif ( $product_width >= 320 && $product_width < 470 ) { $classes[] = 'span4'; $woocommerce_loop['columns'] = 3;  }
            elseif ( $product_width >= 470 && $product_width < 620 ) { $classes[] = 'span6'; $woocommerce_loop['columns'] = 2;  }
            else $is_span = false;

        } else {
            if ( $product_width >= 0   && $product_width < 150 ) { $classes[] = 'span1'; $woocommerce_loop['columns'] = 12; }
            elseif ( $product_width >= 150 && $product_width < 620 ) { $classes[] = 'span3'; $woocommerce_loop['columns'] = 3;  }
            else $is_span = false;

        }

    } else {
        $grid = yit_get_span_from_width( $product_width );
        $classes[] = 'span' . $grid;
        $product_width = yit_width_of_span( $grid );
    }
    if ( $yit_is_feature_tab || ! $is_span ) $woocommerce_loop['columns'] = floor( ( $content_width + 30 ) / ( $product_width + 30 ) );
}


/**
 * SIZES
 */

// shop small
if ( ! function_exists('yit_shop_catalog_w') ) :
    function yit_shop_catalog_w() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_catalog' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_catalog');
        }
        return $size['width'];
    }
endif;

if ( ! function_exists('yit_shop_catalog_h') ) :
    function yit_shop_catalog_h() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_catalog' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_catalog');
        }
        return $size['height'];
   }
endif;

if ( ! function_exists('yit_shop_catalog_c') ) :
    function yit_shop_catalog_c() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_catalog' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_catalog');
        }
        return $size['crop'];
    }
endif;
// shop thumbnail
if ( ! function_exists('yit_shop_thumbnail_w') ) :
    function yit_shop_thumbnail_w() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_thumbnail' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_thumbnail');
        }
        return $size['width'];
    }
endif;
if ( ! function_exists('yit_shop_thumbnail_h') ) :
    function yit_shop_thumbnail_h() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_thumbnail' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_thumbnail');
        }
        return $size['height'];
    }
endif;
if ( ! function_exists('yit_shop_thumbnail_c') ) :
    function yit_shop_thumbnail_c() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_thumbnail' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_thumbnail');
        }
        return $size['crop'];
    }
endif;
// shop large
if ( ! function_exists('yit_shop_single_w') ) :
    function yit_shop_single_w() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_single' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_single');
        }
        return $size['width'];
    }
endif;
if ( ! function_exists('yit_shop_single_h') ) :
    function yit_shop_single_h() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size('shop_single');
        }
        else{
            $size = $woocommerce->get_image_size('shop_single');
        }
        return $size['height'];
    }
endif;
if ( ! function_exists('yit_shop_single_c') ) :
    function yit_shop_single_c() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_single' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_single');
        }
        return $size['crop'];
    }
endif;
// shop featured
if ( ! function_exists('yit_shop_featured_w') ) :
    function yit_shop_featured_w() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_featured' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_featured');
        }
        return $size['width'];
    }
endif;
if ( ! function_exists('yit_shop_featured_h') ) :
    function yit_shop_featured_h() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_featured' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_featured');
        }
        return $size['height'];
    }
endif;
if ( ! function_exists('yit_shop_featured_c') ) :
    function yit_shop_featured_c() {
        global $woocommerce;
        if( function_exists( 'wc_get_image_size' ) ){
            $size = wc_get_image_size( 'shop_featured' );
        }
        else{
            $size = $woocommerce->get_image_size('shop_featured');
        }
        return $size['crop'];
    }
endif;

// print style for small thumb size
function yit_size_images_style() {
    $content_width = yit_get_sidebar_layout() == 'sidebar-no' ? 1170 : 870;
    if ( is_single() && yit_product_layout() == 'layout-1' ) $content_width = 1170;
    $margin = 2.5641025641025641025641025641026;    // 30px

    $images_container_w = yit_shop_single_w() / $content_width * 100;
    ?>
    <style type="text/css">
        ul.products li.product.list .product-wrapper { padding-left:<?php echo yit_shop_catalog_w() + 30 + 7 + 2; ?>px; }
        ul.products li.product.list .product-wrapper a.thumb { margin-left:-<?php echo yit_shop_catalog_w() + 30 + 7 + 2; ?>px; }
        .single-product.woocommerce div.product div.images { width:<?php echo $images_container_w ?>%; }
        .single-product.woocommerce div.product div.summary { width:<?php echo 100 - $images_container_w - $margin ?>%; }
    </style>
<?php
}

function yit_add_featured_image_size($images) {

    $element = yit_get_option('shop-featured-image-size');

    if ( ! is_array( $element ) ) {
        $element['width']   = "160";
        $element['height'] = "160";
        $element['crop']    = 1;
    }

    $image_sizes = array(
        'shop_featured_image_size' => array( intval( $element['width'] ), intval( $element['height'] ), ( isset( $element['crop'] ) && $element['crop'] ? true : false ) ),
    );

    return array_merge( $image_sizes , $images );
}

// ADD IMAGE RESPONSIVE OPTION
function yit_add_responsive_image_option( $options ) {
    $tmp = $options[ count($options)-1 ];
    unset( $options[ count($options)-1 ] );

    $options[] = array(
        'name'		=> __( 'Active responsive images', 'yit' ),
        'desc' 		=> __( 'Active this to make the images responsive and adaptable to the layout grid.', 'yit' ),
        'id' 		=> 'woocommerce_responsive_images',
        'std' 		=> 'yes',
        'type' 		=> 'checkbox'
    );

    $options[] = $tmp;

    return $options;
}



/** NAV MENU
-------------------------------------------------------------------- */

add_action('admin_init', array('yitProductsPricesFilter', 'admin_init'));

class yitProductsPricesFilter {
    // We cannot call #add_meta_box yet as it has not been defined,
    // therefore we will call it in the admin_init hook
    static function admin_init() {

        if ( ! is_shop_enabled() || basename($_SERVER['PHP_SELF']) != 'nav-menus.php' )
            return;

        wp_enqueue_script('nav-menu-query', YIT_THEME_ASSETS_URL . '/js/metabox_nav_menu.js', 'nav-menu', false, true);
        add_meta_box('products-by-prices', 'Prices Filter', array(__CLASS__, 'nav_menu_meta_box'), 'nav-menus', 'side', 'low');
    }

    function nav_menu_meta_box() { ?>
        <div class="prices">
            <input type="hidden" name="woocommerce_currency" id="woocommerce_currency" value="<?php echo get_woocommerce_currency_symbol( get_option('woocommerce_currency') ) ?>" />
            <input type="hidden" name="woocommerce_shop_url" id="woocommerce_shop_url" value="<?php echo get_option('permalink_structure') == '' ? YIT_SITE_URL . '/?post_type=product' : get_permalink( get_option('woocommerce_shop_page_id') ) ?>" />
            <input type="hidden" name="menu-item[-1][menu-item-url]" value="" />
            <input type="hidden" name="menu-item[-1][menu-item-title]" value="" />
            <input type="hidden" name="menu-item[-1][menu-item-type]" value="custom" />

            <p>
                <?php _e( sprintf( 'The values are already expressed in %s', get_woocommerce_currency_symbol( get_option('woocommerce_currency') ) ), 'yit' ) ?>
            </p>

            <p>
                <label class="howto" for="prices_filter_from">
                    <span><?php _e('From', 'yit'); ?></span>
                    <input id="prices_filter_from" name="prices_filter_from" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e('From', 'yit'); ?>" />
                </label>
            </p>

            <p style="display: block; margin: 1em 0; clear: both;">
                <label class="howto" for="prices_filter_to">
                    <span><?php _e('To', 'yit'); ?></span>
                    <input id="prices_filter_to" name="prices_filter_to" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e('To'); ?>" />
                </label>
            </p>

            <p class="button-controls">
			<span class="add-to-menu">
				<img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" style="display: none;" />
				<input type="submit" class="button-secondary submit-add-to-menu" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-custom-menu-item" />
			</span>
            </p>

        </div>
    <?php
    }
}

/**
 * Add 'On Sale Filter to Product list in Admin
 */
add_filter( 'parse_query', 'on_sale_filter' );
function on_sale_filter( $query ) {
    global $pagenow, $typenow, $wp_query;

    if ( $typenow=='product' && isset($_GET['onsale_check']) && $_GET['onsale_check'] ) :

        if ( $_GET['onsale_check'] == 'yes' ) :
            $query->query_vars['meta_compare']  =  '>';
            $query->query_vars['meta_value']    =  0;
            $query->query_vars['meta_key']      =  '_sale_price';
        endif;

        if ( $_GET['onsale_check'] == 'no' ) :
            $query->query_vars['meta_value']    = '';
            $query->query_vars['meta_key']      =  '_sale_price';
        endif;

    endif;
}

add_action('restrict_manage_posts','woocommerce_products_by_on_sale');
function woocommerce_products_by_on_sale() {
    global $typenow, $wp_query;
    if ( $typenow=='product' ) :

        $onsale_check_yes = '';
        $onsale_check_no  = '';

        if ( isset( $_GET['onsale_check'] ) && $_GET['onsale_check'] == 'yes' ) :
            $onsale_check_yes = ' selected="selected"';
        endif;

        if ( isset( $_GET['onsale_check'] ) && $_GET['onsale_check'] == 'no' ) :
            $onsale_check_no = ' selected="selected"';
        endif;

        $output  = "<select name='onsale_check' id='dropdown_onsale_check'>";
        $output .= '<option value="">'.__('Show all products (Sale Filter)', 'yit').'</option>';
        $output .= '<option value="yes"'.$onsale_check_yes.'>'.__('Show products on sale', 'yit').'</option>';
        $output .= '<option value="no"'.$onsale_check_no.'>'.__('Show products not on sale', 'yit').'</option>';
        $output .= '</select>';

        echo $output;

    endif;
}


if( yit_get_option('shop-customer-vat' ) && yit_get_option('shop-customer-ssn' ) ) {

    add_filter( 'woocommerce_billing_fields' , 'woocommerce_add_billing_fields' );
    function woocommerce_add_billing_fields( $fields ) {
        //$fields['billing_country']['clear'] = true;
        $field = array('billing_ssn' => array(
            'label'       => apply_filters( 'yit_ssn_label', __('SSN', 'yit') ),
            'placeholder' => apply_filters( 'yit_ssn_label_x', _x('SSN', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-first'),
            'clear'       => false
        ));

        yit_array_splice_assoc( $fields, $field, 'billing_address_1');

        $field = array('billing_vat' => array(
            'label'       => apply_filters( 'yit_vatssn_label', __('VAT', 'yit') ),
            'placeholder' => apply_filters( 'yit_vatssn_label_x', _x('VAT', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-last'),
            'clear'       => true
        ));

        yit_array_splice_assoc( $fields, $field, 'billing_address_1');

        return $fields;
    }


    add_filter( 'woocommerce_shipping_fields' , 'woocommerce_add_shipping_fields' );
    function woocommerce_add_shipping_fields( $fields ) {
        $field = array('shipping_ssn' => array(
            'label'       => apply_filters( 'yit_ssn_label', __('SSN', 'yit') ),
            'placeholder' => apply_filters( 'yit_ssn_label_x', _x('SSN', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-first'),
            'clear'       => false
        ));

        yit_array_splice_assoc( $fields, $field, 'shipping_address_1');

        $field = array('shipping_vat' => array(
            'label'       => apply_filters( 'yit_vatssn_label', __('VAT', 'yit') ),
            'placeholder' => apply_filters( 'yit_vatssn_label_x', _x('VAT', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-last'),
            'clear'       => true
        ));

        yit_array_splice_assoc( $fields, $field, 'shipping_address_1');
        return $fields;
    }


    add_filter( 'woocommerce_admin_billing_fields', 'woocommerce_add_billing_shipping_fields_admin' );
    add_filter( 'woocommerce_admin_shipping_fields', 'woocommerce_add_billing_shipping_fields_admin' );
    function woocommerce_add_billing_shipping_fields_admin( $fields ) {
        $fields['vat'] = array(
            'label' => apply_filters( 'yit_vatssn_label', __('VAT', 'yit') )
        );
        $fields['ssn'] = array(
            'label' => apply_filters( 'yit_ssn_label', __('SSN', 'yit') )
        );

        return $fields;
    }

    add_filter( 'woocommerce_load_order_data', 'woocommerce_add_var_load_order_data' );
    function woocommerce_add_var_load_order_data( $fields ) {
        $fields['billing_vat'] = '';
        $fields['shipping_vat'] = '';
        $fields['billing_ssn'] = '';
        $fields['shipping_ssn'] = '';
        return $fields;
    }



} elseif( yit_get_option('shop-customer-vat' ) ) {
    add_filter( 'woocommerce_billing_fields' , 'woocommerce_add_billing_fields' );
    function woocommerce_add_billing_fields( $fields ) {
        $fields['billing_company']['class'] = array('form-row-first');
        $fields['billing_company']['clear'] = false;
        //$fields['billing_country']['clear'] = true;
        $field = array('billing_vat' => array(
            'label'       => apply_filters( 'yit_vatssn_label', __('VAT/SSN', 'yit') ),
            'placeholder' => apply_filters( 'yit_vatssn_label_x', _x('VAT or SSN', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-wide'),
            'clear'       => true
        ));

        yit_array_splice_assoc( $fields, $field, 'billing_address_1');
        return $fields;
    }

    add_filter( 'woocommerce_shipping_fields' , 'woocommerce_add_shipping_fields' );
    function woocommerce_add_shipping_fields( $fields ) {
        $fields['shipping_company']['class'] = array('form-row-first');
        $fields['shipping_company']['clear'] = false;
        //$fields['shipping_country']['clear'] = true;
        $field = array('shipping_vat' => array(
            'label'       => apply_filters( 'yit_vatssn_label', __('VAT/SSN', 'yit') ),
            'placeholder' => apply_filters( 'yit_vatssn_label_x', _x('VAT or SSN', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-wide'),
            'clear'       => true
        ));

        yit_array_splice_assoc( $fields, $field, 'shipping_address_1');
        return $fields;
    }

    add_filter( 'woocommerce_admin_billing_fields', 'woocommerce_add_billing_shipping_fields_admin' );
    add_filter( 'woocommerce_admin_shipping_fields', 'woocommerce_add_billing_shipping_fields_admin' );
    function woocommerce_add_billing_shipping_fields_admin( $fields ) {
        $fields['vat'] = array(
            'label' => apply_filters( 'yit_vatssn_label', __('VAT/SSN', 'yit') )
        );

        return $fields;
    }

    add_filter( 'woocommerce_load_order_data', 'woocommerce_add_var_load_order_data' );
    function woocommerce_add_var_load_order_data( $fields ) {
        $fields['billing_vat'] = '';
        $fields['shipping_vat'] = '';
        return $fields;
    }
}
elseif( yit_get_option('shop-customer-ssn' ) ) {
    add_filter( 'woocommerce_billing_fields' , 'woocommerce_add_billing_ssn_fields' );
    function woocommerce_add_billing_ssn_fields( $fields ) {
        $fields['billing_company']['class'] = array('form-row-first');
        $fields['billing_company']['clear'] = false;
        $field = array('billing_ssn' => array(
            'label'       => apply_filters( 'yit_ssn_label', __('SSN', 'yit') ),
            'placeholder' => apply_filters( 'yit_ssn_label_x', _x('SSN', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-last'),
            'clear'       => true
        ));

        yit_array_splice_assoc( $fields, $field, 'billing_address_1');
        return $fields;
    }

    add_filter( 'woocommerce_shipping_fields' , 'woocommerce_add_shipping_ssn_fields' );
    function woocommerce_add_shipping_ssn_fields( $fields ) {
        $fields['shipping_company']['class'] = array('form-row-first');
        $fields['shipping_company']['clear'] = false;
        $field = array('shipping_ssn' => array(
            'label'       => apply_filters( 'yit_ssn_label', __('SSN', 'yit') ),
            'placeholder' => apply_filters( 'yit_ssn_label_x', _x('SSN', 'placeholder', 'yit') ),
            'required'    => false,
            'class'       => array('form-row-last'),
            'clear'       => true
        ));

        yit_array_splice_assoc( $fields, $field, 'shipping_address_1');
        return $fields;
    }

    add_filter( 'woocommerce_admin_billing_fields', 'woocommerce_add_billing_shipping_ssn_fields_admin' );
    add_filter( 'woocommerce_admin_shipping_fields', 'woocommerce_add_billing_shipping_ssn_fields_admin' );
    function woocommerce_add_billing_shipping_ssn_fields_admin( $fields ) {
        $fields['ssn'] = array(
            'label' => apply_filters( 'yit_ssn_label', __('SSN', 'yit') )
        );

        return $fields;
    }

    add_filter( 'woocommerce_load_order_data', 'woocommerce_add_var_load_order_ssn_data' );
    function woocommerce_add_var_load_order_ssn_data( $fields ) {
        $fields['billing_ssn'] = '';
        $fields['shipping_ssn'] = '';
        return $fields;
    }
}


if( yit_get_option('shop-fields-order') ) {
    add_filter( 'woocommerce_billing_fields' , 'woocommerce_restore_billing_fields_order' );
    function woocommerce_restore_billing_fields_order( $fields ) {
        global $woocommerce;

        if( version_compare( WC()->version, '2.1', '<' ) ){
            $fields['billing_city']['class'][0] = 'form-row-last';
        }
        $fields['billing_country']['class'][0] = 'form-row-first';
        $fields['billing_address_1']['class'][0] = 'form-row-first';
        $fields['billing_address_2']['class'][0] = 'form-row-last';

        /* FIX WOO 2.1.x */
        if ( version_compare( WC()->version, '2.1', '>=' ) ) {
            $fields['billing_country']['class'][0] = 'form-row-wide';
        }

        $country = $fields['billing_country'];
        unset( $fields['billing_country'] );
        yit_array_splice_assoc( $fields, array('billing_country' => $country), 'billing_postcode' );

        return $fields;
    }

    add_filter( 'woocommerce_shipping_fields' , 'woocommerce_restore_shipping_fields_order' );
    function woocommerce_restore_shipping_fields_order( $fields ) {
        global $woocommerce;

        if( version_compare( WC()->version, '2.1', '<' ) ){
            $fields['shipping_city']['class'][0] = 'form-row-last';
        }
        $fields['shipping_country']['class'][0] = 'form-row-first';
        $fields['shipping_address_1']['class'][0] = 'form-row-first';
        $fields['shipping_address_2']['class'][0] = 'form-row-last';

        /* FIX WOO 2.1.x */
        if ( version_compare( WC()->version, '2.1', '>=' ) ) {
            $fields['shipping_country']['class'][0] = 'form-row-wide';
        }

        $country = $fields['shipping_country'];
        unset( $fields['shipping_country'] );
        yit_array_splice_assoc( $fields, array('shipping_country' => $country), 'shipping_postcode' );

        return $fields;
    }
}




/**
 * Return the following cart info:
 * 	- items
 *  - subtotal
 *  - currency
 *
 * @return array
 */
function yit_get_current_cart_info() {
    global $woocommerce;

    $subtotal = str_replace( get_woocommerce_currency_symbol(), '', $woocommerce->cart->get_cart_subtotal() );

    $items = yit_get_option( 'minicart-total-items' ) ? $woocommerce->cart->get_cart_contents_count() : count( $woocommerce->cart->get_cart() );

    return array(
        $items,
        $subtotal,
        get_woocommerce_currency_symbol()
    );
}

function yit_format_cart_subtotal( $price ) {
    $num_decimals = (int) get_option( 'woocommerce_price_num_decimals' );

    $price = apply_filters( 'raw_woocommerce_price', (double) $price );
    $price = number_format( $price, $num_decimals, stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) );

    return explode(get_option( 'woocommerce_price_decimal_sep' ), $price);
}

function yit_add_to_cart_success_ajax( $datas ) {
    global $woocommerce;

    list( $cart_items, $cart_subtotal, $cart_currency ) = yit_get_current_cart_info();

    $label = yit_st_get_cart_label($cart_items);

    $datas['#header-cart .cart-items-number'] = '<span class="cart-items-number">' . $cart_items . '</span>';
    $datas['#header-cart .cart-items-label'] = '<span class="cart-items-label">' . $label . '</span>';

    $datas['#header-cart .cart-subtotal'] = '<span class="cart-subtotal">' . $cart_subtotal . '</span>';
    $datas['#header-cart .cart-subtotal-currency'] = '<span class="cart-subtotal-currency">' . $cart_currency . '</span>';

    return $datas;
}
add_action( 'after_setup_theme', 'yit_woocommerce_hooks' );

if( ! function_exists( 'yit_woocommerce_hooks' ) ) {
    function yit_woocommerce_hooks() {
        global $woocommerce;

        if ( version_compare( WC()->version, '2.4', '>=' ) ) {
            add_filter( 'woocommerce_add_to_cart_fragments', 'yit_add_to_cart_success_ajax' );
        }
        else {
            add_filter( 'add_to_cart_fragments', 'yit_add_to_cart_success_ajax' );
        }
    }
}




/* COMPARE */

function yit_woocp_footer_script() {
    $woocp_compare_events = wp_create_nonce("woocp-compare-events");
// 	$woocp_compare_popup = wp_create_nonce("woocp-compare-popup");
// 	$comparable_settings = get_option('woo_comparable_settings');
// 	if (trim($comparable_settings['popup_width']) != '') $popup_width = $comparable_settings['popup_width'];
// 	else $popup_width = 1000;
// 
// 	if (trim($comparable_settings['popup_height']) != '') $popup_height = $comparable_settings['popup_height'];
// 	else $popup_height = 650;

    $script_add_on = '';
    $script_add_on .= '<script type="text/javascript">
			jQuery(document).ready(function($){';
    $script_add_on .= '
					woo_update_total_compare_list = function(){ 
						var data = {
							action: 		"woocp_update_total_compare",
							security: 		"'.$woocp_compare_events.'"
						};
						$.post( ajax_url, data, function(response) {
							total_compare = $.parseJSON( response );
							$("#total_compare_product").html("("+total_compare+")");
                            $(".woo_compare_button_go").trigger("click");';
// 	if (trim($comparable_settings['popup_type']) == 'lightbox') {
//         $script_add_on .= '
//                             $.lightbox(ajax_url+"?action=woocp_get_popup&security='.$woocp_compare_popup.'", {
//                                 "width"       : '.$popup_width.',
//                                 "height"      : '.$popup_height.'
//                             });';
// 	}else {
//         $script_add_on .= '
//     						$.fancybox({
//     							href: ajax_url+"?action=woocp_get_popup&security='.$woocp_compare_popup.'",
//     							title: "Compare Products",
//     							maxWidth: '.$popup_width.',
//     							maxHeight: '.$popup_height.',
//     							openEffect	: "none",
//     							closeEffect	: "none"
//     						});';
// 	}

    $script_add_on .= '
    					});
					};

				});
			</script>';
    echo $script_add_on;
}


/**
 * Add default images dimensions to woocommerce options
 *
 */
function yit_woocommerce_default_image_dimensions() {
    $field = 'yit_woocommerce_image_dimensions_' . get_template();

    if( get_option($field) == false ) {
        update_option($field, time());

        //woocommerce 1.6.6
        update_option( 'woocommerce_thumbnail_image_width', '65' );
        update_option( 'woocommerce_thumbnail_image_height', '65' );
        update_option( 'woocommerce_single_image_width', '670' );
        update_option( 'woocommerce_single_image_height', '420' );
        update_option( 'woocommerce_catalog_image_width', '254' );
        update_option( 'woocommerce_catalog_image_height', '203' );
        update_option( 'woocommerce_magnifier_image_width', '1340' );
        update_option( 'woocommerce_magnifier_image_height', '840' );
        update_option( 'woocommerce_featured_products_slider_image_width', '160' );
        update_option( 'woocommerce_featured_products_slider_image_height', '160' );

        update_option( 'woocommerce_thumbnail_image_crop', 1 );
        update_option( 'woocommerce_single_image_crop', 1 );
        update_option( 'woocommerce_catalog_image_crop', 1 );
        update_option( 'woocommerce_magnifier_image_crop', 1 );
        update_option( 'woocommerce_featured_products_slider_image_crop', 1 );

        //woocommerce 2.0
        update_option( 'shop_thumbnail_image_size', array( 'width' => 65, 'height' => 65, 'crop' => true ) );
        update_option( 'shop_single_image_size', array( 'width' => 670, 'height' => 420, 'crop' => true ) );
        update_option( 'shop_catalog_image_size', array( 'width' => 254, 'height' => 203, 'crop' => true ) );
        update_option( 'woocommerce_magnifier_image', array( 'width' => 1340, 'height' => 840, 'crop' => true ) );
        update_option( 'shop_featured_image_size', array( 'width' => 160, 'height' => 160, 'crop' => true ) );
    }
}



/**
 * Backup woocoomerce options when create the export gz
 *
 */
function yit_save_woocommerce_tables( $tables ) {
    $tables[] = 'woocommerce_termmeta';
    $tables[] = 'woocommerce_attribute_taxonomies';
    return $tables;
}

/**
 * Backup woocoomerce options when create the export gz
 *
 */
function yit_save_woocommerce_options( $options ) {
    $options[] = 'woocommerce\_%';
    return $options;
}

/**
 * Backup woocoomerce wishlist when create the export gz
 *
 */
function yit_save_wishlist_options( $options ) {
    $options[] = 'yith\_wcwl\_%';
    $options[] = 'yith-wcwl-%';
    return $options;
}

/**
 * Backup options of plugins when create the export gz
 *
 */
function yit_add_plugins_options( $options ) {
    $options[] = 'yith_woocompare_%';
    $options[] = 'yith_wcmg_%';

    return $options;
}

/**
 * Update woocommerce options after update from 1.6 to 2.0
 */
function yit_woocommerce_update() {
    global $woocommerce;

    $field = 'yit_woocommerce_update_' . get_template();

    if( get_option($field) == false && version_compare($woocommerce->version,"2.0.0",'>=') ) {
        update_option($field, time());

        //woocommerce 2.0
        update_option(
            'shop_thumbnail_image_size',
            array(
                'width' => get_option('woocommerce_thumbnail_image_width', 65),
                'height' => get_option('woocommerce_thumbnail_image_height', 65),
                'crop' => get_option('woocommerce_thumbnail_image_crop', 1)
            )
        );

        update_option(
            'shop_single_image_size',
            array(
                'width' => get_option('woocommerce_single_image_width', 462 ),
                'height' => get_option('woocommerce_single_image_height', 392 ),
                'crop' => get_option('woocommerce_single_image_crop', 1)
            )
        );

        update_option(
            'shop_catalog_image_size',
            array(
                'width' => get_option('woocommerce_catalog_image_width', 254 ),
                'height' => get_option('woocommerce_catalog_image_height', 203 ),
                'crop' => get_option('woocommerce_catalog_image_crop', 1)
            )
        );

        update_option(
            'woocommerce_magnifier_image',
            array(
                'width' => get_option('woocommerce_magnifier_image_width', 924 ),
                'height' => get_option('woocommerce_magnifier_image_height', 784 ),
                'crop' => get_option('woocommerce_magnifier_image_crop', 1)
            )
        );

        update_option(
            'shop_featured_image_size',
            array(
                'width' => get_option('woocommerce_featured_products_slider_image_width', 160 ),
                'height' => get_option('woocommerce_featured_products_slider_image_height', 160 ),
                'crop' => get_option('woocommerce_featured_products_slider_image_crop', 1)
            )
        );
    }
}

function woocommerce_taxonomy_archive_description() {
    if ( is_tax( array( 'product_cat', 'product_tag' ) ) && get_query_var( 'paged' ) == 0 && yit_get_option('display-category-image-description') ) {
        global $wp_query;

        $cat = $wp_query->get_queried_object();
        $thumbnail_id = get_woocommerce_term_meta( $cat->term_id, 'thumbnail_id', true );
        $image = wp_get_attachment_image_src( $thumbnail_id, 'full' );

        $description = apply_filters( 'the_content', term_description() );

        if( $image ) {
            echo '<div class="term-header-image"><img src="' . $image[0] . '" width="' . $image[1] . '" height="' . $image[1] . '" alt="' . $cat->name . '" /></div>';
        }

        if ( $description ) {
            echo '<div class="term-description">' . $description . '</div>';
        }
    }
}

function yit_reorder_product_tabs( $tabs ) {
    if( isset( $tabs['reviews'] ) ) {
        $tabs['reviews']['priority'] = 40;
    }

    return $tabs;
}



/** COMPARE DEFAULT OPTIONS */
function yith_change_woocompare_options( $options ) {
    foreach ( $options['general'] as $i => $option ) {
        if ( $option['id'] == 'yith_woocompare_compare_button_in_product_page' ) {
            $options['general'][$i]['default'] = $options['general'][$i]['std'] = 'no';
        }
    }
    return $options;
}
add_filter( 'yith_woocompare_tab_options', 'yith_change_woocompare_options' );

/**
 * Remove Items option from the magnifier
 *
 * @param array $array
 * @return array
 * @since 1.0
 */
function yit_remove_items_options_yith_wcmg( $array ) {

    foreach( $array['slider'] as $key => $option ) {
        if( $option['id'] == 'yith_wcmg_slider_items' ) {
            unset( $array['slider'][$key] );
        }
    }

    return $array;
}

function woocommerce_show_product_outofstock_flash() {
    yith_wc_get_template( 'loop/outofstock-flash.php' );
}
add_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_outofstock_flash' );
add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_outofstock_flash' );

/* Function to add compatibility with WC 2.1 */
function yit_woocommerce_primary_start() {
    global $woo_shop_folder;
    yith_wc_get_template( $woo_shop_folder . '/primary-start.php' );
}

function yit_rating_singleproduct() {
    yith_wc_get_template( 'single-product/rating.php' );
}

function yit_woocommerce_primary_end() {
    global $woo_shop_folder;
    yith_wc_get_template( $woo_shop_folder . '/primary-end.php' );
}


if ( ! function_exists( 'yith_wc_get_page_id' ) ) {

    function yith_wc_get_page_id( $page ) {

        global $woocommerce;

        if ( version_compare( WC()->version, '2.1', '<' ) ) {
            return woocommerce_get_page_id( $page );
        }
        else {

            if ( $page == 'pay' || $page == 'thanks' ) {
                $wc_order = new WC_Order();
                $page     = $wc_order->get_checkout_order_received_url();
            }
            return wc_get_page_id( $page );
        }

    }
}

if ( ! function_exists( 'yith_wc_get_template' ) ) {
    function yith_wc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        if ( function_exists( 'wc_get_template' ) ) {
            wc_get_template( $template_name, $args, $template_path, $default_path );
        }
        else {
            woocommerce_get_template( $template_name, $args, $template_path, $default_path );
        }
    }
}

function yit_enqueue_woocommerce_styles() {
    wp_deregister_style( 'woocommerce_frontend_styles' );
    wp_enqueue_style( 'woocommerce_frontend_styles', get_stylesheet_directory_uri() . '/woocommerce_2.0.x/style.css' );
}

function yit_enqueue_wc_styles( $styles ) {

    global $woocommerce;

    unset( $styles['woocommerce-layout'], $styles['woocommerce-smallscreen'], $styles['woocommerce-general'] );

    $style_version = '';
    if ( version_compare( WC()->version, '2.6', '<' ) ) {
        $style_version = '_' . substr( $woocommerce->version, 0, 3 ) . '.x';
    }

    $styles ['yit-layout'] = array(
        'src'     => get_stylesheet_directory_uri() . '/woocommerce' . $style_version . '/style.css',
        'deps'    => '',
        'version' => '1.0',
        'media'   => ''
    );
    return $styles;
}


if ( ! function_exists( 'yit_woocommerce_default_shiptobilling' ) ) {

    function yit_woocommerce_default_shiptobilling() {
        return ( get_option( 'woocommerce_ship_to_destination' ) == 'billing' || get_option( 'woocommerce_ship_to_destination' ) == 'billing_only' );
    }

}

if ( ! function_exists( 'yit_woocommerce_default_shiptoaddress' ) ) {

    function yit_woocommerce_default_shiptoaddress() {
        return ( get_option( 'woocommerce_ship_to_destination' ) == 'shipping' );
    }
}


if ( ! function_exists( 'yit_woocommerce_breadcrumb_defaults' ) ) {
    function yit_woocommerce_breadcrumb_defaults($arg) {

        if(array_key_exists('wrap_before',$arg)) {
            $arg['wrap_before'] = '<nav class="woocommerce-breadcrumb">' ;
        }

        return $arg;
    }
}

if ( ! function_exists( 'yit_redirect_to_checkout' ) ) {
    /**
     * Redirect to checkout page after add to cart
     *
     * @since 1.6.0
     *
     * @return string
     */
    function yit_redirect_to_checkout() {
        global $woocommerce;
        $checkout_url = $woocommerce->cart->get_checkout_url();
        return $checkout_url;
    }
}

if ( ! function_exists( 'yit_redirect_cart_to_checkout' ) ) {
    /**
     * Redirect to checkout after product is added to cart
     *
     * This function is called only on shop pages and shortcodes.
     *
     * @since 1.6.0
     *
     * @param $params
     * @return null
     */
    function yit_redirect_cart_to_checkout( $params ) {
        return null;
    }
}

if( ! function_exists( 'yit_woocommerce_object' ) ) {

    function yit_woocommerce_object() {

        wp_localize_script( 'yit-woocommerce', 'yit_woocommerce', array(
            'version' => WC()->version,
        ));

    }

}


if( ! function_exists('yit_remove_wc_2_3_title_filter') ) {
    function yit_remove_wc_2_3_title_filter( $title ) {
        if( is_dynamic_sidebar() ) {
            remove_filter( 'the_title', 'wc_page_endpoint_title' );
        }
        return $title;
    }
}

if( defined('YITH_YWAR_PREMIUM') ) {

    add_filter( 'yith_advanced_reviews_loader_gif', 'yit_get_ajax_loader_gif_url' );

}

/* Compatibility with WooCommerce 2.4 */

if( version_compare( WC()->version, '2.4', '>=' ) ) {

    remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
    remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );

    add_action( 'woocommerce_single_variation', 'yit_custom_single_variation', 10 );

    if( ! function_exists( 'yit_custom_single_variation' ) ) {
        /**
         * yit custom template for single variation
         */
        function yit_custom_single_variation() {
            wc_get_template( 'single-product/add-to-cart/yit-single-variation.php' );
        }
    }

    if( ! yit_get_option('shop-view-show-title') ) {
        remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title' );
    }
}

/*************************************************************************************
 *   Compatibility with WooCommerce 2.6
 * **********************************************************************************/
if( !function_exists('yit_wc_product_post_class') ){
    function yit_wc_product_post_class( $classes, $class = '', $post_id = '' ){
        if ( ! $post_id || 'product' !== get_post_type( $post_id ) ) {
            return $classes;
        }
        $product = wc_get_product( $post_id );

        if ( $product ) {

            global $woocommerce_loop;
            // Extra post classes

            if( ! isset( $woocommerce_loop['name'] ) && ! isset( $woocommerce_loop['view'] ) ) {
                return $classes;
            }

            if ( !( isset( $woocommerce_loop['layout'] ) && ! empty( $woocommerce_loop['layout'] ) ) )
                $woocommerce_loop['layout'] = yit_get_option( 'shop-layout', 'with-hover' );

            // view
            if ( !( isset( $woocommerce_loop['view'] ) && ! empty( $woocommerce_loop['view'] ) ) )
                $woocommerce_loop['view'] = yit_get_option( 'shop-view', 'grid' );

            $classes[] = $woocommerce_loop['view'];
            $classes[] = $woocommerce_loop['layout'];

            // force open hover
            if ( $woocommerce_loop['layout'] != 'classic' ) $classes[] = ( ! yit_get_option( 'shop-open-hover' ) ) ? 'add-hover' : 'force-open';

            // open the hover on mobile
            if ( yit_get_option( 'responsive-open-hover' ) && $woocommerce_loop['layout'] != 'classic' ) {
                $classes[] = 'open-on-mobile';
            }
            if ( yit_get_option( 'responsive-force-classic' ) && $woocommerce_loop['layout'] == 'with-hover' ){
                $classes[] = 'force-classic-on-mobile';
            }

            // add border to classic style
            if ( yit_get_option('shop-view-show-border') ) {
                $classes[] = 'with-border';
            }

            yit_detect_span_catalog_image( $classes );  //automatically add the classes
        }
        return $classes;
        
    }
}

if( !function_exists('yit_wc_product_product_cat_class') ){
    function yit_wc_product_product_cat_class( $classes, $class, $category ) {


        global $woocommerce_loop;

        if ( !( isset( $woocommerce_loop['layout'] ) && ! empty( $woocommerce_loop['layout'] ) ) )
            $woocommerce_loop['layout'] = yit_get_option( 'shop-layout', 'with-hover' );

        // view
        if ( !( isset( $woocommerce_loop['view'] ) && ! empty( $woocommerce_loop['view'] ) ) )
            $woocommerce_loop['view'] = yit_get_option( 'shop-view', 'grid' );

        $classes[] = $woocommerce_loop['view'];

        // force open hover
        $classes[] = ( ! yit_get_option( 'shop-open-hover' ) ) ? 'add-hover' : 'force-open';

        $classes[] = 'product-category';
        // open the hover on mobile
        if ( yit_get_option( 'responsive-open-hover' ) ) $classes[] = 'open-on-mobile';

        yit_detect_span_catalog_image( $classes );  //automatically add the classes


        return $classes;

    }
}

/* Product Thumbnails */
if( !function_exists( 'yit_woocommerce_product_thumbnails_columns' ) ){
    add_filter( 'woocommerce_product_thumbnails_columns','yit_woocommerce_product_thumbnails_columns' );
    function yit_woocommerce_product_thumbnails_columns(){
        $columns = floor( yit_shop_single_w() / yit_shop_thumbnail_w() );
        return $columns;
    }
}

/*
 * Mini Cart
 */

if ( ! function_exists( 'yit_st_get_cart_label' ) ) {
    /**
     * Get the string 'items' or 'item' translated
     *
     * @author   Andrea Frascaspata  <andrea.frascaspata@yithemes.com>
     *
     * @param $cart_items
     *
     * @return string | the string translation
     * @since    1.8.0
     */
    function yit_st_get_cart_label( $cart_items ) {
        // wpml fix ----------
        global $sitepress;

        if ( $sitepress == '' || empty( $sitepress ) || ! isset( $sitepress ) ) {

            if ( $cart_items != 1 ) {
                $label = __( 'Items', 'yit' );
            }
            else {
                $label = __( 'Item', 'yit' );
            }

        }
        else {

            if ( $cart_items != 1 ) {

                $label = yit_icl_translate( 'theme', 'yit', 'mini-cart-items-label', 'items' );

            }
            else {

                $label = yit_icl_translate( 'theme', 'yit', 'mini-cart-item-label', 'item' );

            }

        }
        //--End wpml Fix-----------------

        return $label;
    }
}


/***************************************************************************
 * WC 2.6
 ***************************************************************************/
/**
 * @author Andrea Frascaspata
 */
function yit_wc_2_6_removed_unused_template () {

    if( function_exists( 'yit_theme_remove_unused_template' ) ) {

        $option = 'yit_wc_2_6_template_remove';

        $files = array( 'single-product/review.php' );

        yit_theme_remove_unused_template( 'woocommerce' , $option , $files );

    }

}

