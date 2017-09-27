<?php
/*
Plugin Name: EGANY Facebook to WordPress importer
Plugin URI: http://egany.com/
Description: Import Facebook group/page posts to WordPress
Version: 1.1
Author: EGANY
Author URI: http://egany.com/
License: GNU General Public License v3.0
*/

/**
 * Copyright (c) 2015 EGANY (email: support@egany.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-11  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

// if ( is_admin() ) { // phong.nguyen remove 20150918, frontend also using this
    require_once dirname( __FILE__ ) . '/includes/admin.php';
// }
define( 'EGANY_PLUGIN_FB2WP_FILE', __FILE__ );
define( 'EGANY_PLUGIN_FB2WP_DIR', dirname(__FILE__) );
define( 'MAX_GROUP_PAGE', 200 );
// define( 'EGANY_PLUGIN_FOLDER', dir(__FILE__) );

// Egany_FB_Group_To_WP::init()->trash_all();

/**
 * Egany_FB_Group_To_WP class
 *
 * @class Egany_FB_Group_To_WP The class that holds the entire Egany_FB_Group_To_WP plugin
 */
class Egany_FB_Group_To_WP {

    private $post_type = 'post'; // egany_fb2wp_post
	private $FBWPadmin; // type of... new Egany_FB_Group_To_WP_Admin();
    private $fb_meta_key = 'syndication_permalink'; // egany_fb2wp_post
    /**
     * Constructor for the Egany_FB_Group_To_WP class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {

		//phong.nguyen 20150504: add schedules
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ), 44);

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );
        // add_action( 'init', array( $this, 'register_post_type' ) );

        add_action( 'init', array( $this, 'debug_run' ) );
        add_action( 'init', array( $this, 'historical_import' ) );
        add_action( 'egany_fb2wp_import', array( $this, 'do_import' ) );
		// remove_action( 'egany_fb2wp_import', array( $this, 'do_import' ) );//unknown it works aaa!

        // add_filter( 'cron_schedules', array($this, 'cron_schedules') ); // nono, NOT work!!!

        add_filter( 'get_avatar_comment_types', array( $this, 'avatar_comment_type' ) );
        add_filter( 'get_avatar', array( $this, 'get_avatar' ), 10, 3 );

        add_filter( 'the_content', array( $this, 'the_content' ) );

		//phong.nguyen 20150501: add ajax functions
		include(EGANY_PLUGIN_FB2WP_DIR.'/includes/egany_facebook_to_wordpress-ajax.php');

		$this->FBWPadmin = new Egany_FB_Group_To_WP_Admin();
        if ( is_admin() ) {

			// phong.nguyen 20150416: register js/style such as Admin, Admin-Head
			add_action('admin_enqueue_scripts', array($this,'register_scripts_styles_admin'));
			// add_action('admin_head', array($this,'register_scripts_admin_head'));

        }
		else
		{
			// phong.nguyen 20150501: $this->register_scripts_styles_frontend;
			add_action('wp_enqueue_scripts',array($this,'register_scripts_styles_frontend'));
		}
    }

    /**
     * Registers our custom post type
     *
     * @return void
     */
    public function register_post_type() {
        $labels = array(
            'name'                => _x( 'Facebook Posts', 'Post Type General Name', 'EGANY' ),
            'singular_name'       => _x( 'Facebook Post', 'Post Type Singular Name', 'EGANY' ),
            'menu_name'           => __( 'Facebook Posts', 'EGANY' ),
            'parent_item_colon'   => __( 'Parent Post:', 'EGANY' ),
            'all_items'           => __( 'All Posts', 'EGANY' ),
            'view_item'           => __( 'View Post', 'EGANY' ),
            'add_new_item'        => __( 'Add New Post', 'EGANY' ),
            'add_new'             => __( 'Add New', 'EGANY' ),
            'edit_item'           => __( 'Edit Post', 'EGANY' ),
            'update_item'         => __( 'Update Post', 'EGANY' ),
            'search_items'        => __( 'Search Post', 'EGANY' ),
            'not_found'           => __( 'Not found', 'EGANY' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'EGANY' ),
        );

        $rewrite = array(
            'slug'                => 'fb-post',
            'with_front'          => true,
            'pages'               => true,
            'feeds'               => false,
        );

        $args = array(
            'label'               => __( 'Facebook Post.', 'EGANY' ),
            'description'         => __( 'Facebook Post..', 'EGANY' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'post-formats', 'comments' ),
            'taxonomies'          => array( 'category', 'post_tag' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
			'menu_icon'			  => plugins_url('/assets/images/logo.png', EGANY_PLUGIN_FB2WP_FILE), //phong.nguyen 20150501: add icon ...
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'rewrite'             => $rewrite,
            'capability_type'     => 'post',
        );

        register_post_type( $this->post_type, $args );
    }

    /**
     * Initializes the Egany_FB_Group_To_WP() class
     *
     * Checks for an existing Egany_FB_Group_To_WP() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Egany_FB_Group_To_WP();
        }

        return $instance;
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {
        if ( false == wp_next_scheduled( 'egany_fb2wp_import' ) ){
            wp_schedule_event( time(), 'egany-30minutes', 'egany_fb2wp_import' );
        }
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {
        wp_clear_scheduled_hook( 'egany_fb2wp_import' );
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'EGANY', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    // /**
     // * Add new cron schedule
     // *
     // * @param  array $schedules
     // * @return array
     // */
    // function cron_schedules( $schedules ) {
        // $schedules['half-hour'] = array(
            // 'interval' => MINUTE_IN_SECONDS * 30,
            // 'display' => __( 'In every 30 Minutes', 'EGANY' )
        // );

        // return $schedules;
    // }

    /**
     * Manually trigger the cron
     *
     * @return void
     */
    function debug_run() {
        if ( !isset( $_GET['fb2wp_test'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $this->do_import();

        die();
    }

    /**
     * Get the facebook settings
     *
     * @param string $strPage Ex: fb2wp_general,...
     * @return array
     */
    function get_settings($strPage) {
        $option = get_option( $strPage, array() );
		$source_id = $this->get_source_id($option);

		//phong.nguyen 20150429 removed this
        // // return if no configuration found
        // if ( !isset( $option['app_id'] ) || !isset( $option['app_secret'] ) || !isset( $source_id ) ) {
            // return false;
        // }

		//phong.nguyen 20150429 removed this
        // // no app id or app secret
        // if ( empty( $option['app_id'] ) || empty( $option['app_secret'] ) ) {
            // return false;
        // }

        // // no group id
        // if ( empty( $source_id ) ) {
            // return false;
        // }

        return $option;
    }

    /**
     * check existing tags: Page, Post
     *
     * @author: phong.nguyen 20151010
     * @return void
     */
    function check_existing_tags() {

		//declare info. for tags: Page, Group
		$arr_post_tag = array();
		$arr_post_tag['page'] = array(
			'name' => 'Page',
			'description' => 'Facebook Page',
			'slug' => 'page',
		);
		$arr_post_tag['group'] = array(
			'name' => 'Group',
			'description' => 'Facebook Group',
			'slug' => 'group',
		);
		$str_taxonomy = 'post_tag';   // WP category: Tags
		foreach($arr_post_tag as $key => $value)
		{
			$str_term_id = $this->get_term($str_taxonomy, $value['slug'], $value['name'], $value['description'] ); // nguyễn asd01 => (auto transfer)  nguyen-asd01
		}
	}

    /**
     * get all FB accounts info. inside setting tab Page/Group
     *
     * @author: phong.nguyen 20151010
     * @return array
     */
    function get_all_fb_page_group() {
		$arrOptions = array();
		//get FIRST FB info.
		$arrOptions['page_group'] = $this->get_settings('fb2wp_page_group');

		//get FB info. from the second to MAX_GROUP_PAGE (it's currently 200 )
		$max_allow_ids = MAX_GROUP_PAGE;
		$count_ids = 1;
		$sett_api = $this->FBWPadmin->get_settings_api();
		for($i = 2; $i < 500; $i++){
			$default = '';

			$page_group_id = esc_attr( $sett_api->get_option('page_group_id'.$i, 'fb2wp_page_group', $default ) );
			if(trim($page_group_id) != ''){
				$count_ids++;
				if($count_ids > $max_allow_ids){
					break;
				}
				//do sth...
				$arrOptions['page_group'.$i] = array( // for page/group at number $i...
					'page_group_id' => trim($page_group_id)
					,'source_type' => esc_attr( $sett_api->get_option( 'source_type'.$i, 'fb2wp_page_group', $default ) )
					,'access_type' => esc_attr( $sett_api->get_option( 'access_type'.$i, 'fb2wp_page_group', $default ) )
					,'access_token' => esc_attr( $sett_api->get_option( 'access_token'.$i, 'fb2wp_page_group', $default ) )
					,'app_id' => esc_attr( $sett_api->get_option( 'app_id'.$i, 'fb2wp_page_group', $default ) )
					,'app_secret' => esc_attr( $sett_api->get_option( 'app_secret'.$i, 'fb2wp_page_group', $default ) )
					,'hashtag' => esc_attr( $sett_api->get_option( 'hashtag'.$i, 'fb2wp_page_group', $default ) )
				);
			}
		}

		return $arrOptions;
	}


    /**
     * Do a historical or paginated import
     *
     * This is a clever approach to import all the posts from a group.
     * When you visit the url http://example.com/?fb2wp_type, it'll start it's process.
     *
     * The plugin will start from the recent to next page without any interaction from
     * your end. It'll build the url and reload the page in every 5/10 seconds and impport
     * the next posts.
     *
     * As it doesn't do any blocking in the server, your server will not be overloaded
     * and any timeout wouldn't happen.
     *
     * @return void
     */
    function historical_import() {

		$strImportType = $_GET['fb2wp_type'];
        if ( ! isset( $strImportType ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $page_num     = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;

		// check existing post_tag: Page, Post
		$this->check_existing_tags();

        $option_general       = $this->get_settings('fb2wp_general');
        $arrOptions = array();

		//phong.nguyen 20150503: check "Import Type"
		if($strImportType == 'all')
		{
			//get FB page/group fetching data from it...
			$arrOptions = $this->get_all_fb_page_group();

		}
		else if($strImportType == 'do_import')
		{
			$this->do_import();
		}

		// else if($strImportType == 'page')
			// $arrOptions['page'] = $this->get_settings('fb2wp_page');
		// else if($strImportType == 'group')
			// $arrOptions['group'] = $this->get_settings('fb2wp_group');

		// //do importing by options
		foreach($arrOptions as $key => $option)
		{

			// phong.nguyen 20150415 comment old code
			//$access_token = $option['app_id'] . '|' . $option['app_secret'];

			//phong.nguyen 20150416 get token, source ....
			$arrFBInfo = $this->get_fb_source_info($option_general, $option);
			$arrFBInfo['page_num'] = $page_num; // current page number

			//phong.nguyen 20150429 get token, source
			//add terms if not exist

			//grant terms for current post.
			$this->fetch_facebook_data_history($arrFBInfo); // ($source_type, $source_id, $limit, $max_page, $access_token, $page_num, $comment_max);
			// break; return;
		}


        exit;
    }

    /**
     * get_fb_source_info
     *
     * @author phong.nguyen 20150918
     * @param string $source_type
     * return array: $access_token = $this->get_access_token($option_general, $option);
     */
    function get_fb_source_info($option_general, $option) {

		// // $source_id    = $this->get_source_id($option);  // $option['page_id']; //
		// // $source_type  = $this->get_source_type($option);
		// // $limit        = $this->get_limit($option_general, $option);
		// // $max_page 	  = $this->get_max_page($option_general, $option);
		// // $comment_max  = $this->get_comment_max($option_general, $option);
		// // $access_token = $this->get_access_token($option_general, $option);
		// $arrFBInfo['page_num'] = $page_num; // current page number

		$arrFBInfo = array();

		$arrFBInfo['source_id']    = $this->get_source_id($option);  // $option['page_id']; //
		$arrFBInfo['source_type']  = $this->get_source_type($option);
		$arrFBInfo['limit']        = $this->get_limit($option_general, $option);
		$arrFBInfo['max_page']     = $this->get_max_page($option_general, $option);
		$arrFBInfo['comment_max']  = $this->get_comment_max($option_general, $option);
		$arrFBInfo['access_token'] = $this->get_access_token($option_general, $option);
		$arrFBInfo['hashtag'] 	   = $option['hashtag'];

		return $arrFBInfo;
	}

    /**
     * get_term
     * Insert term by params(slug, name...)  if there's not existing.
     *
     * @author phong.nguyen 20150430
     * @param string $source_type
     * @param string $source_id
     */
    function get_term($str_taxonomy, $str_term_slug, $str_term_name, $str_term_desc=null) {

		// $term = array(7, 8);
		// $str_term_name = 'name...';
		// $str_term_slug = 'cat-name03'; // architecture... decoy-portfolio
		// $str_taxonomy = 'category';
		$objTerm = get_term_by('slug', $str_term_slug, $str_taxonomy);
		$str_term_id = null;
		if(isset($objTerm) & $objTerm!=false)
		{
			//return termID...
			$str_term_id = $objTerm->term_id;
			// var_dump('old: '. $str_term_id);
		}
		else
		{
			// create/insert term
			$objTerm = wp_insert_term(
			  $str_term_name, // the term
			  $str_taxonomy, // the taxonomy
			  array(
				'description'=> $str_term_desc,
				'slug' => $str_term_slug,
				'parent'=> null, // $parent_term_id
			  )
			);

			// maybe ERROR: Cannot use object of type WP_Error as array...???
			// 	+ 'empty_term_name'=>...
			// 	+ "A name is required for this term..."
			if(is_array($objTerm)){ // phong.nguyen 20160118: (an array) is not WP_Error
				$str_term_id = $objTerm['term_id'];
			}

		}


		// //update term
		// $str_term_id = wp_update_term(1, $str_taxonomy, array(
			// 'name' => 'Cat Name...asd01',
			// 'slug' => 'cat-name',
		// ));

		return $str_term_id;

	}
    /**
     * fetch_facebook_data_history
     *
     * @author phong.nguyen 20150429
     * @param string $arrFBInfo Facebook info.
     * @return none
     */
    function fetch_facebook_data_history($arrFBInfo){ // ($source_type, $source_id, $limit, $max_page, $access_token, $page_num, $comment_max) {

		// //fetch Facebook data...
		// $decoded_fb_posts = null;
		// $count = $this->fetch_facebook_data($arrFBInfo, $decoded_fb_posts);
		$count = 0;
        include(EGANY_PLUGIN_FB2WP_DIR.'/includes/html-data-history.php');
		//insert js for admin head.
		include(EGANY_PLUGIN_FB2WP_DIR.'/assets/js/fbwp_admin_head.php');


		return true;

	}

    /**
     * fetch_facebook_data
     *
     * @author phong.nguyen 20150429
     * @param string $arrFBInfo
     * @param string &$decoded_fb_posts Refer to original.
     * @param string $paging_until
     * @param string $paging_token
     * @return int $count number FB posts imported successfully!
     */
    function fetch_facebook_data($arrFBInfo, &$decoded_fb_posts, $paging_until=null, $paging_token=null){
	// ($source_type, $source_id, $limit, $max_page, $access_token, $page_num, $comment_max, &$decoded_fb_posts, $paging_until=null, $paging_token=null) {

		//facebook info...
		$source_id = $arrFBInfo['source_id'];
		$source_type = $arrFBInfo['source_type'];
		$limit = $arrFBInfo['limit'];
		$max_page = $arrFBInfo['max_page'];
		$comment_max = $arrFBInfo['comment_max'];
		$access_token = $arrFBInfo['access_token'];
		$page_num = $arrFBInfo['page_num']; //last info.

        // Hardcode.20150401 choose API Version 2.3
        //$fb_url       = 'https://graph.facebook.com/' . $source_id . '/feed/?limit=' . $limit . '&access_token=' . $access_token;
        // #visualizar2017 cambio /feed/?limit=  por /posts/?limit=
        $fb_url = 'https://graph.facebook.com/v2.10/' . $source_id . '/posts/?limit=' . $limit . '&access_token=' . $access_token ; // . '&page='.$page_num;
		//https://graph.facebook.com/v2.3/$page_id/feed/?limit=250&access_token=$API|$SecretKey

        // build the query URL for next page
        if ( $page_num > 1 ) {
			// // old code
            // // $until        = isset( $_GET['until'] ) ? $_GET['until'] : '';
            // // $paging_token = isset( $_GET['paging_token'] ) ? $_GET['paging_token'] : '';

            // // $fb_url = add_query_arg( array(
                // // 'until'          => $until,
                // // '__paging_token' => $paging_token,
            // // ), $fb_url );

			//phong.nguyen 20150502: add __paging_token (...error05-only 3 items, NOT 6)
            $fb_url = add_query_arg( array(
                'until'          => $paging_until,
                '__paging_token' => $paging_token,
            ), $fb_url );
        }

        // do the import
        $json_posts  = $this->fetch_stream( $fb_url );
        $decoded     = json_decode( $json_posts );
		$decoded_fb_posts = $decoded;
        $group_posts = $decoded->data;

		// phong.nguyen 20150430: get page_name + page_id. Ex: truyen-thong-123456...
		$fb_url = 'https://graph.facebook.com/v2.10/' . $source_id . '/?access_token=' . $access_token;
        $json_posts  = $this->fetch_stream( $fb_url );
        $decoded     = json_decode( $json_posts );
        $page_group_info = $decoded;

		if(isset($page_group_info))
		{
			// add category (by FB name + FB id)
			// phong.nguyen 20160525: remove ID in slug.
			$str_taxonomy = 'category';
			$str_category_slug = $page_group_info->name; //  . '-'. (string)$page_group_info->id;
			$str_category_description = $page_group_info->name . '; id = '. (string) $page_group_info->id;
			$this->get_term($str_taxonomy, $str_category_slug, $page_group_info->name, $str_category_description); // nguyễn asd01 => (auto transfer)  nguyen-asd01

		}



		//http://issues.egany.com/view.php?id=116 (Cho phép chọn get Comments / Post	)  ...
		//paging inside: https://graph.facebook.com/v2.3/{_fb_post_id}/comments?access_token=...
		//next id: $json_comments->paging->cursors->after
		//next link: $json_comments->paging->next
		//exp.: https://graph.facebook.com/v2.3/183711941649962_885739388113877/comments?...
		//Ex. for result:  =>  {"data": [ ]}


		//phong.nguyen 20150419 collect more comments
		//EGANY get comments with param "comment_max", update FB posts

		if(isset($group_posts))
		{
			foreach($group_posts as $post)
			{
				$fb_post_id = $post->id;
				// get comment, attachment; old-link .../comments
				$fb_url_comment = 'https://graph.facebook.com/v2.10/' . $fb_post_id . '/?fields=message,comments,attachments&access_token=' . $access_token;

				$arr_comment_data = array();
				$json_comments = $this->fetch_stream($fb_url_comment);
				$arrDecodedData = json_decode( $json_comments );
				$decoded_comments = null;

				if( isset($arrDecodedData->comments) ){
					$decoded_comments = $arrDecodedData->comments;
					$arr_comment_data = array_merge($arr_comment_data, $decoded_comments->data);
				}

				// phong.nguyen 20160525: loop for collecting more comments...
				if($comment_max=='unlimited'){
					while(isset($decoded_comments->paging->next))  // maybe equal "NULL"..
					{
						$fb_url_comment = $decoded_comments->paging->next;
						$json_comments = $this->fetch_stream($fb_url_comment);
						$arrDecodedData = json_decode( $json_comments );

						if( isset($arrDecodedData->comments) )
						{
							$decoded_comments = $arrDecodedData->comments;
							$arr_comment_data = array_merge($arr_comment_data, $decoded_comments->data);
						}
					}
				}
				//get comments data ...
				if(isset($post->comments->data))
					$post->comments->data = $arr_comment_data;

				// get attachments data
				if( isset($arrDecodedData->attachments->data) ){
					$post->attachments->data = $arrDecodedData->attachments->data;
				}

			}
		}
		// var_dump($group_posts);
		// var_dump('count..group_posts');
		// var_dump(count($group_posts));
        $count       = $this->insert_posts( $group_posts, $arrFBInfo, $page_group_info );

		return $count;
	}


    /**
     * get_comment_max == Get Comments per Post
     *
     * @author phong.nguyen 20150429
     * @param array $option_general
     * @param array $option_page_or_group
     * @return string - always comment_max in General
     */
    function get_comment_max($option_general, $option_page_or_group) {
		$key = 'comment_max';
		$str_max_general =  $option_general[$key];
		// $str_max_page_or_group =  $option_page_or_group[$key];
		// return (trim($str_max_page_or_group)!='' & isset($str_max_page_or_group)) ? $str_max_page_or_group : $str_max_general;
		return $str_max_general;
	}

    /**
     * get_max_page == Max. Queries
     *
     * @author phong.nguyen 20150503
     * @param array $option_general
     * @param array $option_page_or_group
     * @return integer - always max_page in General
     */
    function get_max_page($option_general, $option_page_or_group) {

		$key = 'max_page';
		$default_val = 10;
		$str_max_page_general = isset( $option_general[$key] ) ? intval( $option_general[$key] ) : $default_val;
		// $str_max_page_page_or_group = isset( $option_page_or_group[$key] ) ? intval( $option_page_or_group[$key] ) : $default_val;
		// return ($str_max_page_page_or_group != 0) ? $str_max_page_page_or_group : $str_max_page_general;
		return $str_max_page_general;
	}

    /**
     * get_limit === Posts per Query
     *
     * @author phong.nguyen 20150429
     * @param array $option_general
     * @param array $option_page_or_group
     * @return integer - always limit in General
     */
    function get_limit($option_general, $option_page_or_group) {
		$key = 'limit';
		$default_val = 30;
		$str_limit_general = isset( $option_general[$key] ) ? intval( $option_general[$key] ) : $default_val;
		// $str_limit_page_or_group = isset( $option_page_or_group[$key] ) ? intval( $option_page_or_group[$key] ) : $default_val;
		// return ($str_limit_page_or_group != 0) ? $str_limit_page_or_group : $str_limit_general;
		return $str_limit_general;
	}
    /**
     * get_access_token
     *
     * @param array $option_general
     * @param array $option_page_or_group
     * @return id string
     */
    function get_access_token($option_general, $option_page_or_group) {
		$str_token_general = '';
		$str_token_page_or_group = '';

		// get General token
		if($option_general['access_type'] == 'fb_app')
		{
			$str_token_general =  $option_general['app_id'] . '|' . $option_general['app_secret'];
		}
		else if($option_general['access_type'] == 'fb_access_token')
			$str_token_general = $option_general['access_token'];

		// get group/page token
		if($option_page_or_group['access_type'] == 'fb_app')
		{
			$str_token_page_or_group =  $option_page_or_group['app_id'] . '|' . $option_page_or_group['app_secret'];
		}
		else if($option_page_or_group['access_type'] == 'fb_access_token')
			$str_token_page_or_group = $option_page_or_group['access_token'];

		return (trim($str_token_page_or_group)!='' & isset($str_token_page_or_group)) ? $str_token_page_or_group : $str_token_general;

	}


    /**
     * get_source_id
     *
     * @author phong.nguyen 20150429
     * @param array $option
     * @return id string
     */
    function get_source_id($option) {
		// $page_id     = $option['page_id'];
		// $group_id     = $option['group_id'];

		// if($source_type == 'page') // $option['source_type'] == st_page
		// {
			// return $page_id;
		// }
		// else
			// return $group_id;


		return $option['page_group_id'];
	}

    /**
     * get_source_type
     *
     * @author phong.nguyen 20150918
     * @param array $option
     * @return string $source_type Page, Group, Other
     */
    function get_source_type($option) {

		$source_type     = $option['source_type'];
		if($source_type == 'st_page')
		{
			return 'Page';
		}
		else if($source_type == 'st_group')
			return 'Group';
		else
			return 'Other';

	}

    /**
     * Do the actual import via cron
     *
     * @return boolean
     */
    function do_import() {
        $count = 0;
		$page_num = 1;

		//get FB page/group fetching data from it...
        $option_general       = $this->get_settings('fb2wp_general');
        $arrOptions = array();
		$arrOptions = $this->get_all_fb_page_group();
        var_dump($arrOptions);
		// var_dump($option_general);
		// var_dump($arrOptions);
		$countGroup = 0;
		foreach($arrOptions as $key => $option)
		{

			// phong.nguyen 20150415 comment old code
			//$access_token = $option['app_id'] . '|' . $option['app_secret'];

			//phong.nguyen 20150416 get token, source ....
			$arrFBInfo = $this->get_fb_source_info($option_general, $option);
			$arrFBInfo['page_num'] = $page_num; // current page number
			var_dump($arrFBInfo);
			var_dump('<br/>start>do_import; group #' . $countGroup );

			//phong.nguyen 20150429 get token, source
			$decoded_fb_posts = null;
			$count += $this->fetch_facebook_data($arrFBInfo, $decoded_fb_posts); // no need: $paging_until, $paging_token
			// break; return;
			$countGroup++;
			var_dump('end>do_import; group #' . $countGroup );
		}

        // $count       = $this->insert_posts( $group_posts, $source_id );
		$strMessage = sprintf( '%d posts imported', $count );
		var_dump($strMessage);
		self::log( 'debug: function do_import() ', $strMessage );
    }

    /**
     * Fetch posts from facebook API
     *
     * @param  string $url
     * @return string
     */
    function fetch_stream( $url ) {
        self::log( 'debug', 'Fetching data from facebook' );

        $request = wp_remote_get( $url );
        $json_posts = wp_remote_retrieve_body( $request );

        if ( is_wp_error( $request ) ) {
            self::log( 'error', 'Fetching failed with code. WP_Error' );
            return;
        }

        if ( $request['response']['code'] != 200 ) {
            self::log( 'error', 'Fetching failed with code: ' . $request['response']['code'] );
            return false;
        }

        return $json_posts;
    }

    /**
     * Loop through the facebook feed and insert them
     *
     * @param array $group_posts
     * @param array $$arrFBInfo : $source_id, $source_type  (page/post)...
     * @param string $page_group_info (id, name; founder)
     * @return int
     */
    function insert_posts( $group_posts, $arrFBInfo, $page_group_info ) { //( $group_posts, $source_id, $source_type, $page_group_info ) {
        $count = 0;
        if ( $group_posts ) {
            foreach ($group_posts as $fb_post) {

                $post_id = $this->update_insert_post( $fb_post, $arrFBInfo, $page_group_info );
                if ( $post_id ) {

					//phong.nguyen 20150420: do inserting comments...
					//http://issues.egany.com/view.php?id=118 (Kiểm tra có update mới	... )
					$post = get_post($post_id);
					$_fb_updated_time_OLD = get_post_meta($post_id, '_fb_updated_time', true);

					if( isset($fb_post->comments->data)
					&
					(($_fb_updated_time_OLD != $fb_post->updated_time )
						| ($post->comment_count != count($fb_post->comments->data))) )
					{
						if ( property_exists( $fb_post, 'comments' ) ) {
							$comment_count = $this->insert_comments( $post_id, $fb_post->comments->data);
						}
					}

                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Insert comments for a post
     *
     * @param  int $post_id
     * @param  array $comments
     * @return int
     */
    function insert_comments( $post_id, $comments ) {
        $count = 0;

        if ( $comments ) {
            foreach ($comments as $comment) {
                $comment_id = $this->insert_comment( $post_id, $comment );

                if ( $comment_id ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check if the post already exists
     *
     * Checks via guid. guid = fb post link
     *
     * @global object $wpdb
     * @param string $fb_link_id facebook post link
     * @return boolean
     */
    function is_post_exists( $fb_link_id ) {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s", $fb_link_id ) );

        if ( $row ) {
            return $row->ID;
        }

        return false;
    }

    /**
     * Check if a comment already exists
     *
     * Checks via meta key in comment
     *
     * @global object $wpdb
     * @param string $post_id //phong.nguyen 20150419
     * @param string $fb_comment_id facebook comment id
     * @return boolean
     */
    function is_comment_exists($post_id, $fb_comment_id ) {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_id FROM $wpdb->commentmeta cme  WHERE meta_key = '_fb_comment_id' AND meta_value = %s", $fb_comment_id ) ); // // the old one:


        if ( $row ) {
            return true;
        }

        return false;
    }

    /**
     * set_category_for_post...
     *
     * @author phong.nguyen 20150430
     * @param string $post_id
     * @param string $str_category_slug
     * @param array $arrTags // not-$str_tag_slug
     * @return none
     */
    function set_category_for_post($post_id, $str_category_slug, $arrTags){
		//set TAG group/page for current post...
		//set hashtag
		$str_taxonomy = 'post_tag';
		$count = 0;
		$boAppend = false;
		foreach($arrTags as $str_tag_slug){
			$str_term_id = $this->get_term($str_taxonomy, $str_tag_slug, $str_tag_slug, $str_tag_slug ); // nguyễn asd01 => (auto transfer)  nguyen-asd01
			$count++;
			$boAppend = ($count == 1 ? false : true );
			wp_set_post_terms( $post_id, array($str_term_id), $str_taxonomy, $boAppend); // okok- 'page'
		}

		// set category for current post. (truyen-thong-123456...)
		$str_taxonomy = 'category';
		$str_term_id = $this->get_term($str_taxonomy, $str_category_slug, $str_category_slug, $str_category_slug ); // nguyễn asd01 => (auto transfer)  nguyen-asd01
		wp_set_post_terms( $post_id, array($str_term_id), $str_taxonomy );
	}
    /**
     * Update/Insert a post from facebook
     *
     * @param object $fb_post
     * @param array $arrFBInfo: $source_id, $source_type
     * @param string $page_group_info
     * @return int|WP_Error
     */
    function update_insert_post( $fb_post, $arrFBInfo, $page_group_info) {  //( $fb_post, $source_id, $source_type, $page_group_info ) {

		$source_id = $arrFBInfo['source_id'];
		$source_type = $arrFBInfo['source_type'];

		// phong.nguyen 20160118: fix bug (cause by get_term(...) ),
		// not removed EMPTY-tags -no need!!!  fixed function get_term.
		$arrTags = array($source_type, $arrFBInfo['hashtag']);

		// phong.nguyen 20160525: remove ID in category name.
		$str_category_slug = $page_group_info->name; //  . '-'. (string)$page_group_info->id;

        $option = get_option( 'fb2wp_general', array(
            'post_status'    => 'publish',
            'comment_status' => 'open'
        ) );

        $meta = array(
            '_fb_author_id'   => $fb_post->from->id,
            '_fb_author_name' => $fb_post->from->name,
            '_fb_link'        => $fb_post->actions[0]->link,
            '_fb_group_id'    => $source_id,
            '_fb_post_id'     => $fb_post->id,
            '_fb_updated_time'=> $fb_post->updated_time,
            '_fb_hashtag'	  => $arrFBInfo['hashtag'],
        );

		// phong.nguyen 20150419: find out an existing _fb_post_id
		$args1 = array(
			'post_type'      => $this->post_type,
			'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),

			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'		=>	'post_date',
			'order'			=>	'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_fb_post_id',
					'value'   => $fb_post->id, // like as 147975405395810_358780530981962
					'compare' => '='  //  LIKE
				)
			),
		);

		//unified any results
		$posts = array_unique( array_merge(  get_posts( $args1 ) ) );

		// // var_dump($fb_post->id);
		// if($fb_post->id == '896595423790160_896653017117734')
			// // 896595423790160_896653017117734 (page: Two images02...asd01 by page admin)
			// // 454692878055435_454705114720878(1 img); 454692878055435_454703734721016 (fb info... )
		// {
			// $fb_post->asd = 'asd';
			// var_dump($fb_post->attachments); exit;
		// }

		// check any matching results.
		if(count($posts) > 0)
		{
			// EGNAY 20150420: update post if matching conditions
			// //http://issues.egany.com/view.php?id=118 (Kiểm tra có update mới	... )
			$post_id = $posts[0];
			$_fb_updated_time_OLD = get_post_meta($post_id, '_fb_updated_time', true);
			if($_fb_updated_time_OLD != $fb_post->updated_time )  // can NOT use "post_modified"  and "post_date_gmt"
			{
				$postarr = $this->get_post_data_from_fb_post($fb_post, $option);
				$postarr['ID'] = $post_id;
				wp_update_post( $postarr );
                $this->set_facebook_url($post_id, $postarr[$fb_meta_key]);
				foreach ($meta as $key => $value) {
					update_post_meta( $post_id, $key, $value );
				}
			}

			// phong.nguyen 20150430: before return, set att. for current post: post_tag (Page/Post)  + category(truyen-thong-123456)
			$this->set_category_for_post($post_id, $str_category_slug, $arrTags);

			// phong.nguyen 20160120: update featured-image for post
			$this->set_thumbnail_for_post($post_id, $fb_post, $arrFBInfo);

			return $post_id;
		}

        // // bail out if the post already exists .  phong.nguyen 20150419, removed; it caused some missing
        // if ( $post_id = $this->is_post_exists( $fb_post->actions[0]->link ) ) {
            // return $post_id;
        // }


		$postarr = $this->get_post_data_from_fb_post($fb_post, $option);

        $post_id = wp_insert_post( $postarr, true ); // phong.nguyen 20150918: if fail, return WP_Error

        if ( $post_id && !is_wp_error( $post_id ) ) {

            $this->set_facebook_url($post_id, $postarr[$fb_meta_key]);
            if ( $fb_post->type !== 'status' ) {
                set_post_format( $post_id, $fb_post->type );
            }

            foreach ($meta as $key => $value) {
                update_post_meta( $post_id, $key, $value );
            }
        }


		// if($fb_post->id == '285820618214899_720867924710164' ){  //error: ;
			// var_dump('postarr: ');
			// var_dump($post_id);
			// // var_dump(is_wp_error( $post_id ));
			// var_dump($postarr);  //720867924710164
		// }

		// phong.nguyen 20150430: before return, set att. for current post: post_tag (Page/Post)  + category(truyen-thong-123456)
		$this->set_category_for_post($post_id, $str_category_slug, $arrTags);    //old tags: only $source_type
		// phong.nguyen 20160120: update featured-image for post
		$this->set_thumbnail_for_post($post_id, $fb_post, $arrFBInfo);

        return $post_id;
    }

    /**
     * get_post_data_from_fb_post
     *
     * @author phong.nguyen 20150420
     * @param  stdClass $fb_post Facebook data
     * @param  array $option
     * @return array post-data
     */
    function get_post_data_from_fb_post($fb_post, $option)
	{
		$postarr = array();

        $postarr = array(
            'post_type'      => $this->post_type,
            'post_status'    => $option['post_status'],
            'comment_status' => isset( $option['comment_status'] ) ? $option['comment_status'] : 'open',
            'ping_status'    => isset( $option['comment_status'] ) ? $option['comment_status'] : 'open',
            'post_author'    => 1,
            'post_date'      => gmdate( 'Y-m-d H:i:s', ( strtotime( $fb_post->created_time ) ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
            'post_date_gmt'      => gmdate( 'Y-m-d H:i:s', ( strtotime( $fb_post->updated_time ) ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ), // can NOT use "post_modified" and "post_date_gmt"
            'guid'           => $fb_post->actions[0]->link,
            $fb_meta_key       => "https://www.facebook.com/$fb_post->id"
        );

		switch ($fb_post->type) {
            case 'photo':

                if ( !isset( $fb_post->message ) ) {
                    $postarr['post_title']   = wp_trim_words( strip_tags( $fb_post->story ), 10, '...' );
                    $postarr['post_content'] = sprintf( '<p>%1$s</p> <div class="image-wrap"><img src="%2$s" alt="%1$s" /></div>', $fb_post->story, $fb_post->picture );
                } else {
                    $postarr['post_title']   = wp_trim_words( strip_tags( $fb_post->message ), 10, '...' );
                    $postarr['post_content'] = sprintf( '<p>%1$s</p> <div class="image-wrap"><img src="%2$s" alt="%1$s" /></div>', $fb_post->message, $fb_post->picture );
                }

                break;

            case 'link':
                parse_str( $fb_post->picture, $parsed_link );

                $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->message ), 10, '...' );
                $postarr['post_content'] = '<p>' . $fb_post->message . '</p>';

                if ( !empty( $parsed_link['url']) ) {
                    $postarr['post_content'] .= sprintf( '<a href="%s"><img src="%s"></a>', $fb_post->link, $parsed_link['url'] );
                } else {
                    $postarr['post_content'] .= sprintf( '<a href="%s">%s</a>', $fb_post->link, $fb_post->name );
                }

                break;

            default:  //else types: status, video,...
				//phong.nguyen 20150918: fix bug insert video...
                $postarr['post_title']   = wp_trim_words( strip_tags( $fb_post->message ), 10, '...' );
                $postarr['post_content'] = $fb_post->message;
                break;
        }

		return $postarr;
	}

    /**
     * Insert a comment in a post
     *
     * @param  int $post_id
     * @param  stdClass $fb_comment
     * @return void
     */
    function insert_comment( $post_id, $fb_comment ) {

		// //phong.nguyen 20150419 removed it... no need!!!
        // // bail out if the comment already exists
        if ( $this->is_comment_exists($post_id, $fb_comment->id ) ) {
            return;
        }

        $commentarr = array(
            'comment_post_ID'    => $post_id,
            'comment_author'     => $fb_comment->from->name,
            'comment_author_url' => 'https://facebook.com/' . $fb_comment->from->id,
            'comment_content'    => $fb_comment->message,
            'comment_date'       => gmdate( 'Y-m-d H:i:s', ( strtotime( $fb_comment->created_time ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
            'comment_approved'   => 1,
            'comment_type'       => $this->post_type,
        );

        $meta = array(
            '_fb_author_id'   => $fb_comment->from->id,
            '_fb_comment_id'  => $fb_comment->id
        );

        $comment_id = wp_insert_comment( $commentarr );

        if ( $comment_id && !is_wp_error( $comment_id ) ) {
            foreach ($meta as $key => $value) {
                update_comment_meta( $comment_id, $key, $value );
            }
        }

        self::log( 'debug', 'comment is being inserted with FBID '.$fb_comment->id);

        return $comment_id;
    }

    /**
     * Trash all imported posts
     *
     * @return void
     */
    function trash_all() {
        $query = new WP_Query( array( 'post_type' => $this->post_type, 'posts_per_page' => -1 ) );

        if ( $query->have_posts()) {
            $all_posts = $query->get_posts();

            foreach ($all_posts as $post) {
                wp_delete_post( $post->ID, true );
            }
        }
    }

    /**
     * Adds author, post and group link to the end of the post
     *
     * @global object $post
     * @param string $content
     * @return string
     */
    function the_content( $content ) {
        global $post;

        if ( $post->post_type == $this->post_type ) {
            $author_id   = get_post_meta( $post->ID, '_fb_author_id', true );
            $author_name = get_post_meta( $post->ID, '_fb_author_name', true );
            $link        = get_post_meta( $post->ID, '_fb_link', true );
            $source_id    = get_post_meta( $post->ID, '_fb_group_id', true );
            $hashtag    = get_post_meta( $post->ID, '_fb_hashtag', true );

			//phong.nguyen 20150119: //link template: ...com/groups/[group_id]/?id=[post_id]    &view=permalink
			$strDelimiter = '/';
			$arrLinkExploded = explode($strDelimiter, $link);
			$intLastItem = count($arrLinkExploded) - 1;
			$intPostID = $arrLinkExploded[$intLastItem];
			// remove some last items: (string)posts and (integer)[post_id]
			array_pop($arrLinkExploded); // $arrLinkExploded = unset($arrLinkExploded[$intLastItem]);
			array_pop($arrLinkExploded);
			$strLinkNotFBpostID = implode($strDelimiter, $arrLinkExploded);
			$link = $strLinkNotFBpostID . '?id=' . $intPostID . '&view=permalink';
			// $link = substr($link, 0, strrpos( $link, $strDelimiter) );



			//phong.nguyen 20150416: corrected "id=%d"  =>  "id=%s"
            $author_link = sprintf( '<a href="https://facebook.com/%s" target="_blank">%s</a>', $author_id, $author_name );

			$source_link = 'https://facebook.com';
			$option = get_option( 'fb2wp_general', array() );
			if($option['source_type'] == 'st_page')
			{
				//EGANY removed "/pages"  OKOK!!!
				$source_link = sprintf( '<a href="https://facebook.com/%s" target="_blank">%s</a>', $source_id, __( 'View Page', 'EGANY' ) );
			}
			else // if($option['source_type'] == 'st_group')
			{
				//EGANY removed "/groups"  OKOK!!!
				$source_link = sprintf( '<a href="https://facebook.com/%s" target="_blank">%s</a>', $source_id, __( 'View Group', 'EGANY' ) );
			}

            $custom_data = '<div class="fb-group-meta">';
            $custom_data .= sprintf( __( 'Posted by %s', 'EGANY' ), $author_link );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= sprintf( '<a href="%s" target="_blank">%s</a>', $link, __( 'View Post', 'EGANY' ) );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= $source_link;
			if(trim($hashtag) != ''){
				$custom_data .= '<span class="sep"> | </span>';

				//hastag for Facebook
				// $custom_data .= sprintf( '<a href="https://facebook.com/hashtag/%s" target="_blank">%s</a>', $hashtag, '#' . $hashtag );

				//tag for Wordpress - phong.nguyen 20151002
				$custom_data .= sprintf( '<a href="'. site_url() .'/tag/%s" target="_blank">%s</a>', $hashtag, '#' . $hashtag );


			}
            $custom_data .= '</div>';

            $custom_data = apply_filters( 'egany_fb2wp_content', $custom_data, $post, $author_id, $author_name, $link, $source_id );

			if(trim($intPostID) != '' ){
				$content .= $custom_data;
			}
        }

        return $content;
    }

    /**
     * Add support for avatar in egany_fb2wp_post comment type
     *
     * @param  array $types
     * @return array
     */
    function avatar_comment_type( $types ) {
        $types[] = $this->post_type;

        return $types;
    }

    /**
     * Adds avatar image from facebook in comments
     *
     * @param  string $avatar
     * @param  string $id_or_email
     * @param  int $size
     * @return string
     */
    function get_avatar( $avatar, $id_or_email, $size ) {

        // it's not a comment
        if ( ! is_object( $id_or_email ) ) {
            return $avatar;
        }

        if ( empty( $id_or_email->comment_type ) || $id_or_email->comment_type != $this->post_type ) {
            return $avatar;
        }

		// FB profile id; Ex.: Dien: 761009407251931,  Cuong: 4886988508121
        $profile_id = get_comment_meta( $id_or_email->comment_ID, '_fb_author_id', true );
        if ( ! $profile_id ) {
            return $avatar;
        }

        $image  = sprintf( 'http://graph.facebook.com/%1$s/picture?type=square&height=%2$s&width=%2$s', $profile_id, $size );

        $avatar = sprintf( '<img src="%1$s" class="avatar avatar-44 photo avatar-default" height="%2$s" width="%2$s" />', $image, $size );

        return $avatar;
    }

    /**
     * The main logging function
     *
     * @uses error_log
     * @param string $type type of the error. e.g: debug, error, info
     * @param string $msg
     */
    public static function log( $type = '', $msg = '' ) {
        if ( WP_DEBUG == true ) {
            $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
            error_log( $msg, 3, dirname( __FILE__ ) . '/debug.log' );
        }
    }


	/**
	 * Register any scripts, styles
	 *
	 * phong.nguyen 20150501
	 * @return void
	 */
	public function register_scripts_styles_frontend( ) {

		// //phong.nguyen 20150501: add bootstrap...
		// wp_enqueue_style( 'bootstrap-3.3.2', plugins_url('/assets/bootstrap-3.3.2-dist/cssasd/bootstrap.min.css' , __FILE__ )); // ok
	}

	/**
	 * Register any scripts, styles
	 *
	 * phong.nguyen 20150416
	 * @return void
	 */
	public function register_scripts_styles_admin( ) {
		// // wp_enqueue_script('jquery');

		// //Load ADMIN scripts, styles.
		// // add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		wp_enqueue_style( 'admin-style-fbwp', plugins_url('/assets/css/style_backend.css' , __FILE__ )); // ok
		wp_enqueue_script( 'admin-script-fbwp', plugins_url('/assets/js/fbwp_admin.js' , __FILE__ ), array( 'jquery' )); // ok

		// //add bootstrap...
		// wp_enqueue_style( 'bootstrap-3.3.2', plugins_url('/assets/bootstrap-3.3.2-dist/css/bootstrap.min.css' , __FILE__ )); // ok
	}


	// /**
	 // * Register any scripts, styles at admin section
	 // *
	 // * phong.nguyen 20150416
	 // * @return void
	 // */
	// public function register_scripts_admin_head( ) {

		// //it's OK!!!
		// //register common functions at <head>
		// include_once('assets/js/fbwp_admin_head.php');
	// }


	/**
	 * Add some custom schedule intervals to the default WordPress schedules from
	 * ref. plugin: ...snapshot-2.4.3.2\lib\snapshot_utilities.php: snapshot_utility_add_cron_schedules
	 *
	 * @author phong.nguyen 20150504
	 * @return array $schedules
	 */
	function add_cron_schedules( $schedules ) {

		if (!isset($schedules['egany-1minute'])) {
			$schedules['egany-1minute'] = array(
				'interval' => 60*1,
				'display'  => __( 'Every 1 Minute', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-5minutes'])) {
			$schedules['egany-5minutes'] = array(
				'interval' => 60*5,
				'display'  => __( 'Every 5 Minutes', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-15minutes'])) {
			$schedules['egany-15minutes'] = array(
				'interval' => 60*15,
				'display'  => __( 'Every 15 Minutes', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-30minutes'])) {
			$schedules['egany-30minutes'] = array(
				'interval' => 60*30,
				'display'  => __( 'Every 30 Minutes', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-hourly'])) {
			$schedules['egany-hourly'] = array(
				'interval' => 60*60,
				'display'  => __( 'Once Hourly', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-daily'])) {
			$schedules['egany-daily'] = array(
				'interval' => 1*24*60*60, 				//	86,400
				'display'  => __( 'Once Daily', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-twicedaily'])) {
			$schedules['egany-twicedaily'] = array(
				'interval' => 1*12*60*60, 				// 43,200
				'display'  => __( 'Twice Daily', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-weekly'])) {
			$schedules['egany-weekly'] = array(
				'interval' => 7*24*60*60, 				// 604,800
				'display'  => __( 'Once Weekly', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-twiceweekly'])) {
			$schedules['egany-twiceweekly'] = array(
				'interval' => 7*12*60*60, 				// 302,400
				'display'  => __( 'Twice Weekly', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-monthly'])) {
			$schedules['egany-monthly'] = array(
				'interval' => 30*24*60*60, 				// 2,592,000
				'display'  => __( 'Once Monthly', 'EGANY' ),
			);
		}

		if (!isset($schedules['egany-twicemonthly'])) {
			$schedules['egany-twicemonthly'] = array(
				'interval' => 15*24*60*60, 				// 1,296,000
				'display'  => __( 'Twice Monthly', 'EGANY' ),
			);
		}
		return $schedules;
	}


	/**
	 * set_thumbnail_for_post
	 *
	 * @author phong.nguyen 20160120
	 * @param string $post_id
	 * @param array $fb_post - Facebook post info.
	 * @param array $arrFBInfo - Facebook access info.
	 * @return true | WP_Error
	 */
	function set_thumbnail_for_post( $post_id, $fb_post, $arrFBInfo ) {

		$attach_id = 0;
		$parent_post_id = $post_id; // 0;
		// [removed] phong 20160201: fetch attachments from FB server, NOT use $fb_post->attachments...
		// phong.nguyen 20160525: DON'T call $fb_url again, just get $fb_post->attachments...
		$objAttach01 = null;
		if(isset($fb_post->attachments)){
			$attachments = $fb_post->attachments;

			if(isset($attachments->data[0]->subattachments->data[0]->media->image->src))
			{
                //Sergio Visualizar 2017 esta comparacion comprueba que el type sea photo antes de añadirlo como thumbnail del post
                if($attachments->data[0]->subattachments->data[0]->type==='photo'){
				                $objAttach01 = $attachments->data[0]->subattachments->data[0];
                }
			}
			else if(isset($attachments->data[0]->media->image->src))
			{
                //Sergio Visualizar 2017 esta comparacion comprueba que el type sea photo antes de añadirlo como thumbnail del post
                if($attachments->data[0]->subattachments->data[0]->type==='photo'){
				                $objAttach01 = $attachments->data[0];
                }
			}
		}

		if(isset($objAttach01) )
		{
			$strFBImgSrc = $objAttach01->media->image->src;
			$objContext = stream_context_create( array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			));
			$str_local_filepath = get_home_path() . 'wp-content/uploads/fb_image_tmp.jpg';
			copy($strFBImgSrc, $str_local_filepath, $objContext);

			// upload to RIGHT path
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
            
			$arrFileSubmit = array(
				'name' => 'fb_image_tmp.jpg',
				'tmp_name' => $str_local_filepath,
				'size' => filesize($str_local_filepath) ,
				// 'type' => mime_content_type($str_local_filepath) ,

			);
			$overrides = array(
				'test_form' => false,
				'action' => 'NOT:wp_handle_upload',
				// 'test_upload' => false,
				// 'test_size' => false,
			);
			$objHandleUp = wp_handle_upload( $arrFileSubmit, $overrides, $time=null);
			if ( isset($file['error']) )
			{
				return new WP_Error( 'upload_error', $file['error'] );
			}
			$str_local_filepath = $objHandleUp['file']; // $objHandleUp['url']...['type'];
            if (!filesize($str_local_filepath)){
                 return false;
            }

			$strContent = 'FB post id: ' . $fb_post->id
				. '; Media ID: ' . $objAttach01->target->id
				. '; Media Type: ' . $objAttach01->type
				. '; Media Set URL: ' . $objAttach01->url;
			$wp_upload_dir = wp_upload_dir();
			$filetype = wp_check_filetype( basename( $file_path ), null );
			$strAttachmentTitle = preg_replace( '/\.[^.]+$/', '', basename( $strFBImgSrc ) );
			$post_type = 'attachment';
			$attachment = array(
				// 'guid'           => $wp_upload_dir['url'] . '/' . basename( $str_local_filepath ),
				'post_mime_type' => $objHandleUp['type'],
				'post_title'     => $strAttachmentTitle,
				'post_content'   => $strContent,
				'post_status'    => 'inherit'
			);
			$objAttachment = get_page_by_title( $strAttachmentTitle, null, $post_type);
			if(isset($objAttachment)){
				$attach_id = $objAttachment->ID;
				wp_update_post(
					array(
						'ID' => $attach_id,
						'post_parent' => $parent_post_id,
					)
				);
			}
			else // is null, insert attachment
			{
				$attach_id = wp_insert_attachment( $attachment, $str_local_filepath, $parent_post_id );
			}

			// // If file is an image, process the default thumbnails for previews
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata( $attach_id, $str_local_filepath );
			$update_attach = wp_update_attachment_metadata( $attach_id, $attach_data );

			// update_post_meta($post_id, '_thumbnail_id', $attach_id);
			if($attach_id > 0){
				set_post_thumbnail( $parent_post_id, $attach_id );
			}

		}

		return true;
	}


    /**
     * set meta field facebook id to generate permalynk url
     *
     * https://www.facebook.com/permalink.php?story_fbid=#################&id=#################
     *
     * @author labicco
     * @param string $post_id
     * @param string $fb_meta_permalynk - Facebook access info.
     * @cons  string $fb_meta_key - this is set in global vars of class.
     * @return  N/A
     */
    function set_facebook_url( $post_id, $fb_meta_permalynk ) {
        if ( ! add_post_meta( $post_id, $this->fb_meta_key, $fb_meta_permalynk, true ) ) {
            update_post_meta( $post_id, $this->fb_meta_key, $fb_meta_permalynk );
        }
    }
} // Egany_FB_Group_To_WP

$wp_fb_import = Egany_FB_Group_To_WP::init();
$GLOBAlS['wp_fb_import'] = $wp_fb_import;
