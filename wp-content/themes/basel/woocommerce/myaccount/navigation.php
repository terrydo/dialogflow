<?php
/**
 * My Account navigation
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( class_exists( 'YITH_WCWL' ) ) $wishlist_page_id = yith_wcwl_object_id( get_option( 'yith_wcwl_wishlist_page_id' ) );
$user_info = get_userdata( get_current_user_id() );
$user_roles = $user_info->roles;
do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation">
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
			</li>
		<?php endforeach; ?>
        <?php if ( class_exists( 'YITH_WCWL' ) && basel_get_opt( 'my_account_wishlist' ) && $wishlist_page_id ): ?>
            <li class="wishlist-account-element <?php if( is_page( $wishlist_page_id ) ) echo 'is-active'; ?>">
                <a href="<?php echo YITH_WCWL()->get_wishlist_url(); ?>"><?php echo get_the_title( $wishlist_page_id ); ?></a>
            </li>
        <?php endif; ?>
		<?php if ( class_exists( 'WeDevs_Dokan' ) && apply_filters( 'basel_dokan_link', true ) && ( in_array( 'seller', $user_roles ) || in_array( 'administrator', $user_roles ) ) ): ?>
 			<li class="dokan-account-element">
 				<a href="<?php echo dokan_get_navigation_url(); ?>"><?php echo esc_html__( 'Vendor dashboard', 'basel' ); ?></a>
 			</li>
 		<?php endif; ?>
        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--customer-logout">
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'customer-logout' ) ); ?>"><?php echo esc_html__( 'Logout', 'woocommerce' ); ?></a>
        </li>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
