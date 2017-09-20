<?php
/**
 * egany_facebook_to_wordpress-ajax.php 
 * Author: phong.nguyen
 * Author URI: http://www.facebook.com/
 * @package     WooCommerce/Admin
 * Version: 1.0
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'egany_facebook_to_wordpress_ajax' ) ) :


class egany_facebook_to_wordpress_ajax {

	/**
	 * Hook in methods
	 */
	public static function init() {
		
		// event dictionary format: event => nopriv
		$ajax_events = array(
			'egany_fb2wp_fetch_facebook_data'		=> false, 
			);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) ); 
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
		
		// var_dump($ajax_events);  // => error: The plugin generated ANY characters of unexpected output ..
	}
	

	/**
	 * Get filtered-list of Validation Period
	 */
	public static function egany_fb2wp_fetch_facebook_data() {
		$arrRe = array();  
		global $wp_fb_import;
		
		// //get filter values
		// $term = (string) wc_clean( stripslashes( $_GET['term'] ) );
		// if ( empty( $term ) ) {
			// die();
		// }
		
		wp_reset_postdata();  
		
		//get posting values... 
		$arrFBInfo['source_type'] = $_POST['source_type']; 
		$arrFBInfo['source_id'] = $_POST['source_id']; 
		$arrFBInfo['limit'] = $_POST['limit']; 
		$arrFBInfo['comment_max'] = $_POST['comment_max']; 
		$arrFBInfo['max_page'] = $_POST['max_page']; 
		$arrFBInfo['access_token'] = $_POST['access_token'];  
		$page_num = $_POST['page_num'];  
		$arrFBInfo['page_num'] = $page_num;  // maybe bug
		$arrFBInfo['hashtag'] = $_POST['hashtag']; 
		$paging_until = $_POST['paging_until']; 
		$paging_token = $_POST['paging_token'];  //phong.nguyen 20150502: add __paging_token (...error05-only 3 items, NOT 6) 
		$decoded_fb_posts = null;
		
		// // fetching data     
		//phong.nguyen 20150429 get token, source  
		$decoded_fb_posts = null; 
		$count = $wp_fb_import->fetch_facebook_data($arrFBInfo, $decoded_fb_posts, $paging_until, $paging_token);  
			
		// $count = $wp_fb_import->fetch_facebook_data($source_type, $source_id, $limit, $max_page, $access_token, $page_num, $comment_max, $decoded_fb_posts, $paging_until, $paging_token); 
		
		// // Build the next page URL by doing AJAX... 
		$post_page = null; 
		$paging_until_return = null; // trang den: 1430027095 ???
		$paging_token_return = null;  
		if(isset($decoded_fb_posts) ) // phong.nguyen 20150417 fixed bug != null  
		{
			if ( $page_num && property_exists( $decoded_fb_posts, 'paging' ) ) {

				$paging = $decoded_fb_posts->paging;
				parse_str( $paging->next, $next_page );
				
				$root_page    = add_query_arg( array( 'fb2wp_hist' => '' ), admin_url() );// home_url() ... 
				//prepare posting-variables for ajax    
				$post_page = ($page_num + 1); 
				$paging_until_return = $next_page['until']; // trang den-until: 1430027095; egany-until: 1430223811 ???
				$paging_token_return = $next_page['__paging_token']; 
			}
		}
		//make up return data 
		$arrRe['success'] = 'true';  
		$arrRe['count'] = $count;  
		$arrRe['page_num'] = $page_num;  
		$arrRe['paging_until'] = $paging_until_return;  
		$arrRe['paging_token'] = $paging_token_return;  
			
		// return under json format
		wp_send_json( $arrRe ); 

	}
	
	
	
}

egany_facebook_to_wordpress_ajax::init();


endif;



