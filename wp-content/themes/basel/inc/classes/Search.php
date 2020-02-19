<?php if ( ! defined( 'BASEL_THEME_DIR' ) ) exit( 'No direct script access allowed' );
/**
 * Ajax search
 */


class BASEL_Search {

	public function __construct() {
		add_action( 'wp_ajax_basel_ajax_search', array( $this, 'ajax_suggestions') );
		add_action( 'wp_ajax_nopriv_basel_ajax_search', array( $this, 'ajax_suggestions') );
		add_action( 'init', array( $this, 'sku_init') );
	}

	public function sku_init() {
		if( apply_filters('basel_search_by_sku', basel_get_opt('search_by_sku') ) && basel_woocommerce_installed() ) {
			add_filter('posts_search', array( $this, 'product_search_sku'), 9);
		}
	}

	public function ajax_suggestions() {

		if( apply_filters('basel_search_by_sku', basel_get_opt('search_by_sku') ) && basel_woocommerce_installed() ) {
			add_filter('posts_search', array( $this, 'product_ajax_search_sku'), 10);
		}
		
		$query_args = array(
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'no_found_rows'  => 1,
		);

		$product_visibility_term_ids = wc_get_product_visibility_term_ids();
        $query_args['tax_query'][] = array(
	        'taxonomy' => 'product_visibility',
	        'field'    => 'term_taxonomy_id',
	        'terms'    => $product_visibility_term_ids['exclude-from-search'],
	        'operator' => 'NOT IN',
        );

		if( ! empty( $_REQUEST['query'] ) ) {
			$query_args['s'] = sanitize_text_field( $_REQUEST['query'] );
		}

		if( ! empty( $_REQUEST['number'] ) ) {
			$query_args['posts_per_page'] = (int) $_REQUEST['number'];
		}

		if( ! empty( $_REQUEST['product_cat'] ) ) {
			$query_args['product_cat'] = strip_tags($_REQUEST['product_cat']);
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$query_args['meta_query'][] = array( 'key' => '_stock_status', 'value' => 'outofstock', 'compare' => 'NOT IN' );
		}

		$products = new WP_Query( apply_filters( 'basel_ajax_get_products', $query_args ) );

		$suggestions = array();

		if( $products->have_posts() ) {

			$factory = new WC_Product_Factory();

			$wptexturize = remove_action( 'the_title', 'wptexturize' );
				
			while( $products->have_posts() ) {
				$products->the_post();

				$product = $factory->get_product( get_the_ID() );

				$title       = get_the_title();

				$suggestions[] = array(
					'value' => $title,
					'permalink' => get_the_permalink(),
					'price' => $product->get_price_html(),
					'thumbnail' => $product->get_image(),
				);
			}

			if ( $wptexturize )
			    add_filter( 'the_title', 'wptexturize' );

			wp_reset_postdata();
		} else {
			$suggestions[] = array(
				'value' => esc_html__( 'No products found', 'basel' ),
				'permalink' => ''
			);
		}


		echo json_encode( array(
			'suggestions' => $suggestions
		) );

		die();
	}

	public function product_search_sku($where, $class = false) {
	    global $pagenow, $wpdb, $wp;

	    //VAR_DUMP(http_build_query(array('post_type' => array('product','boobs'))));die();
	    $type = array('product', 'jam');
	    
	    //var_dump(in_array('product', $wp->query_vars['post_type']));
	    if ((is_admin() ) //if ((is_admin() && 'edit.php' != $pagenow) 
	            || !is_search()  
	            || !isset($wp->query_vars['s']) 
	            //post_types can also be arrays..
	            || (isset($wp->query_vars['post_type']) && 'product' != $wp->query_vars['post_type'])
	            || (isset($wp->query_vars['post_type']) && is_array($wp->query_vars['post_type']) && !in_array('product', $wp->query_vars['post_type']) ) 
	            ) {
	        return $where;
	    }

	    $s = $wp->query_vars['s'];

	    return $this->sku_search_query( $where, $s );
	}

	public function product_ajax_search_sku( $where ) {

		if( ! empty( $_REQUEST['query'] ) ) {
			$s = sanitize_text_field( $_REQUEST['query'] );
			return $this->sku_search_query( $where, $s );
		}

		return $where;
	}

	public function sku_search_query( $where, $s ) {
	    global $wpdb;

	    $search_ids = array();
	    $terms = explode(',', $s);

	    foreach ($terms as $term) {
	        //Include the search by id if admin area.
	        if (is_admin() && is_numeric($term)) {
	            $search_ids[] = $term;
	        }
	        // search for variations with a matching sku and return the parent.

	        $sku_to_parent_id = $wpdb->get_col($wpdb->prepare("SELECT p.post_parent as post_id FROM {$wpdb->posts} as p join {$wpdb->postmeta} pm on p.ID = pm.post_id and pm.meta_key='_sku' and pm.meta_value LIKE '%%%s%%' where p.post_parent <> 0 group by p.post_parent", wc_clean($term)));

	        //Search for a regular product that matches the sku.
	        $sku_to_id = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value LIKE '%%%s%%';", wc_clean($term)));

	        $search_ids = array_merge($search_ids, $sku_to_id, $sku_to_parent_id);
	    }

	    $search_ids = array_filter(array_map('absint', $search_ids));

	    if (sizeof($search_ids) > 0) {
	        $where = str_replace(')))', ") OR ({$wpdb->posts}.ID IN (" . implode(',', $search_ids) . "))))", $where);
	    }
	    
	    #remove_actions_for_anonymous_class('posts_search', 'WC_Admin_Post_Types', 'product_search', 10);
	    return $where;

	}
}
