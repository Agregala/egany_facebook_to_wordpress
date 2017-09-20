<?php

require_once dirname( __FILE__ ) . '/class.settings-api.php';

/**
 * Admin options handler class
 *
 * @since 0.4  
 * @author EGANY <support@egany.com>  
 */
class Egany_FB_Group_To_WP_Admin {

    private $settings_api;

    function __construct() {
        $this->settings_api = new Egany_Settings_API();

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }
	
    function get_settings_api() {
		return $this->settings_api; 
	}
	
    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() { 
	
		// phong.nguyen add main menu (+logo)  
		// $main_menu_id = 'edit.php?post_type=egany_fb2wp_post';  // egany_fb2wp_post
		// add_menu_page(__('EGANY Facebook2WP Settings', 'EGANY' ), __('Facebook to WP', 'EGANY' ), 'manage_options', $main_menu_id, null, plugins_url('/assets/images/logo.png', EGANY_PLUGIN_FB2WP_FILE) , 30); 
		
        // add_submenu_page( $main_menu_id, __( 'EGANY Facebook2WP Settings', 'EGANY' ), __( 'Settings', 'EGANY' ), 'manage_options', 'egany_fb2wp-settings', array( $this, 'settings_page' ) ); 
		add_object_page( __( 'EGANY Facebook2WP Settings', 'EGANY' ), __( 'Facebook to WP', 'EGANY' ), 'manage_options', 'egany_fb2wp-settings', array( $this, 'settings_page' ), plugins_url('/assets/images/logo.png', EGANY_PLUGIN_FB2WP_FILE) );
		
		
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'fb2wp_general',
                'title' => __( 'General', 'EGANY' ) 
            ), 
            array(
                'id' => 'fb2wp_page_group',
                'title' => __( 'Page/Group', 'EGANY' )
            ),
            // array(
                // 'id' => 'fb2wp_group',
                // 'title' => __( 'Group', 'EGANY' )
            // ), 
            // array(
                // 'id' => 'fb2wp_scheduler',
                // 'title' => __( 'Scheduler', 'EGANY' )
            // ), 
        );

        return $sections;
    }

	
    /**
     * Return 2 fields: page_group_id, source_type with $intNum (if have) 
     *
     * @author: phong.nguyen 20150918 
     * @param  int $intNum Number for current FB info. (current max. number is 200)  
     * @return array settings fields
     */ 
	function get_sf_fb_id_and_type($intNu) {
		return array( 
			array( 
				'name'    => 'page_group_id'.$intNu,
				'label'   => __( 'Facebook Page/Group ID <span class="page_group_number">'. $intNu.'</span>', 'EGANY' ),  
				'default' => '',
				'size'   => 'regular asd', 
				'desc'    => __( 'Add your Page/Group ID. e.g: 241884142616448. <span class="highlight">Leave blank to remove all relevant page/group info.</span>' ) 
			),
			array( 
                'name'    => 'source_type'. $intNu,
                'label'   => __( 'Source Type '. $intNu, 'EGANY' ),
                'default' => 'publish',
                'type'    => 'select',
                'options' => array(
					'st_group' => 'Facebook Group', // default is Group if there's not matching..
					'st_page' => 'Facebook Page', 
					),
                'desc'    => __( 'Connect to Facebook Page or Group' ) 
            ),  
		);
	}
	
    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */ 
    function get_settings_fields() {
        $settings_fields = array();
		//a:6:{s:6:"app_id";s:16:"1419291691710668";s:10:"app_secret";s:32:"ceca9b3645dbcc815b407a151618797f";s:8:"group_id";s:15:"338026176339978";s:5:"limit";s:2:"30";s:11:"post_status";s:5:"draft";s:14:"comment_status";s:4:"open";}
		// // var_dump(get_post_statuses());  
		
		//tab: Page... 
        $settings_fields['fb2wp_page_group'] = $this->get_sf_fb_id_and_type(''); 
		
		$settings_fields['fb2wp_page_group'] = array_merge($settings_fields['fb2wp_page_group'], $this->get_sf_facebook_info_account() );  // , $this->get_sf_facebook_info_general() 
		
		// //tab: Group 
        // $settings_fields['fb2wp_group'] = array(
			
            // array(
                // 'name'    => 'group_id',
                // 'label'   => __( 'Facebook Group ID', 'EGANY' ),
                // 'default' => '',
                // 'desc'    => __( 'Add your Facebook Group ID. e.g: 241884142616448' )
            // ),
		// );  
		// $settings_fields['fb2wp_group'] = array_merge($settings_fields['fb2wp_group'], $this->get_sf_facebook_info_general() );  
		
		
		// //tab: Scheduler... 
        // $settings_fields['fb2wp_scheduler'] = array( 
           
			// array(
                // 'name'    => 'fb2wp_scheduler_setting',
                // 'label'   => __( 'Scheduler', 'EGANY' ),
                // 'default' => 'egany-5minutes',
                // 'type'    => 'select',
                // 'options' => array(
					// 'egany-1minute' => 'Every 1 Minute', // default is Group if there's not matching..
					// 'egany-5minutes' => 'Every 5 Minutes', 
					// ),
                // 'desc'    => __( 'Scheduler Setting' ) 
            // ),
		// );
		
		
		//tab: General 
        $settings_fields['fb2wp_general'] = array(
			  
        );
		$settings_fields['fb2wp_general'] = array_merge($settings_fields['fb2wp_general'], $this->get_sf_facebook_info_general() ); 

        return $settings_fields;
    }

	
	/*
	 * get_sf_facebook_info_account...
	 * 
	 * @author: phong.nguyen 20150917   
	 * @param: none
	 * @return: array of fields 
	 */ 
    function get_sf_facebook_info_account($number = null) { 
		return array(
			
			array(
                'name'    => 'access_type' . (isset($number) ? $number : ''),
                'label'   => __( 'Access Type', 'EGANY' ) . (isset($number) ? ' ' . $number : ''),
                'default' => 'publish',
                'type'    => 'select',
                'options' => array(
					'' => 'Default', 
					'fb_app' => 'Facebook App.', 
					'fb_access_token' => 'Facebook Access Token', 
					),
                'desc'    => __( 'Use App. or Token to access Facebook. Choose "Default" for using access info. in General tab.' ) 
            ),
			 
            array(
                'name'    => 'app_id' . (isset($number) ? $number : ''),
                'label'   => __( 'Facebook App ID', 'EGANY' ) . (isset($number) ? ' '.$number : ''),
                'default' => '',
                'desc'    => sprintf( __( 'Insert your facebook application ID from <a href="%s" target="blank">here</a>.', 'EGANY' ), 'https://developers.facebook.com/apps/' )
            ),
            array(
                'name'    => 'app_secret' . (isset($number) ? $number : ''),
                'label'   => __( 'Facebook App Secret', 'EGANY' ) . (isset($number) ? ' '.$number : ''),
                'default' => '',
                'desc'    => __( 'Insert your facebook App Secret' )
            ),
			array(
                'name'    => 'access_token' . (isset($number) ? $number : ''),
                'label'   => __( 'Access Token', 'EGANY' ) . (isset($number) ? ' ' .$number : ''),
                'default' => '',
                'class' => 'hidden',
                'desc'    => sprintf( __( 'Insert your facebook access token <a href="%s" target="blank">here</a>.', 'EGANY' ), 'https://developers.facebook.com/tools/explorer/' )
            ), 
			array(
                'name'    => 'hashtag' . (isset($number) ? $number : ''),
                'label'   => __( 'Hashtag', 'EGANY' ) . (isset($number) ? ' ' .$number : ''),
                'default' => '',
                'size' => 'regular asd',
                'desc'    => sprintf( __( 'Insert your facebook hashtag. A sample: <a href="%s" target="blank">#egany</a>.', 'EGANY' ), 'https://facebook.com/hashtag/egany/')  
            ), 
			
		);
	}  
	
	
	/*
	 * get_sf_facebook_info_general...
	 * 
	 * @author: phong.nguyen 20150421 
	 * @param: none
	 * @return: array of fields 
	 */ 
    function get_sf_facebook_info_general() {  
		return array_merge(   
			$this->get_sf_facebook_info_account(),  
			array(  
				  
				array(
					'name'    => 'limit',
					'label'   => __( 'Posts per Query', 'EGANY' ), 
					'default' => '30',
					'desc'    => __( 'Posts fetched from Facebook in a single-query' ),  
					'type'    => 'select',
					'options' => array(
						'15' => '15', 
						'20' => '20',  
						'25' => '25',  
						'30' => '30',  
						),
				),
				array(
					'name'    => 'max_page',
					'label'   => __( 'Max. Queries', 'EGANY' ),
					'default' => '10',
					'desc'    => __( 'How many single-query from Facebook.' ) 
				),
				array(
					'name'    => 'post_status',
					'label'   => __( 'Default Post Status', 'EGANY' ),
					'default' => 'publish',
					'type'    => 'select',
					'options' => array_merge(array('' => ''), get_post_statuses()), // get_post_statuses(), // 
					'desc'    => __( 'What will be the post status when a post is imported/created' )
				),
				array(
					'name'    => 'comment_max',
					'label'   => __( 'Get Comments per Post', 'EGANY' ), 
					'default' => '25',
					'type'    => 'select',
					'options' => array(
						''   => __( '', 'EGANY' ),
						'25'   => __( '25', 'EGANY' ),
						'unlimited' => __( 'Unlimited', 'EGANY' )
					),
				), 
			) // end: array general  
		); 		
		
	}
	
    function settings_page() {
        echo '<div class="wrap">';
        settings_errors();

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }
}