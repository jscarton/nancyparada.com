<?php
/**
 * Product loop title
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// title classes
$title_class = array();

if ( yit_get_option('shop-title-uppercase') ) {
	$title_class[] = 'upper';
}

$title_class = empty( $title_class ) ? '' : ' class="' . implode( ' ', $title_class ) . '"';

?>
<h3 class="<?php echo $title_class; ?>"><?php the_title(); ?></h3>
