<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('/assets/css/bootstrap-3.3.2-dist/css/bootstrap.min.css', EGANY_PLUGIN_FB2WP_FILE) ?>" /> 
<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('/assets/css/style_frontend.css', EGANY_PLUGIN_FB2WP_FILE) ?>" /> 
<script type="text/javascript" src="<?php echo plugins_url('/assets/js/jquery-1.11.1/jquery-1.11.1.min.js', EGANY_PLUGIN_FB2WP_FILE) ?>"> // must declare like this... </script>  
<script type="text/javascript" src="<?php echo plugins_url('/assets/js/jquery-blockui-2.66/jquery.blockUI.min.js', EGANY_PLUGIN_FB2WP_FILE) ?>"> // must declare like this... </script>  

<?php 


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb; 

// get Facebook info to variables. 
$page_num = 1; // $arrFBInfo['page_num']; 
$limit = $arrFBInfo['limit']; 
$source_id = $arrFBInfo['source_id'];  
$source_type  = $arrFBInfo['source_type']; 
$max_page 	  = $arrFBInfo['max_page']; 
$comment_max  = $arrFBInfo['comment_max']; 
$access_token = $arrFBInfo['access_token']; 
?>
	<!-- <textarea id="no_series_items" name="no_series_items" style="display:none" value="<?php // echo $no_series_items; ?>"> </textarea> -->
	<div id="<?php echo $source_id?>" class="col-md-4 fb2wp_border"> 
		<h2><?php echo __('Fetching Facebook ', 'EGANY' ) . '<span class="fb2wp_uppercase">' .$source_type .'</span>'; ?></h2> 
		<table cellpadding="0" cellspacing="0" class="table">
			<thead>
				<tr>
					<th class="col01"><?php echo ''; ?></th>
					<th class="col02"><?php echo ''; ?></th>
					
				</tr>
			</thead>
			<tbody>
				<!-- data row; no need! cause by using backbone.js -->
				
				<tr>
					<td class="col01"><?php echo 'Posts imported'; ?></td>
					<td class="col02 posts_imported"><?php echo $count; ?></td> 
				</tr>
				<tr>
					<td class="col01"><?php echo 'Showing Page:'; ?></td>
					<td class="col02 page_num"><?php echo $page_num; ?></td> 
				</tr>
				<tr>
					<td class="col01"><?php echo 'Per Page:'; ?></td>
					<td class="col02 limit"><?php echo $limit; ?></td> 
				</tr>
				<tr>
					<td class="col01"><?php echo 'Group/Page ID:'; ?></td>
					<td class="col02 source_id"><?php echo $source_id; ?></td> 
				</tr>
			</tbody>
			<tfoot>
			
			</tfoot>
		</table>
			
		
	
	</div>
	
	
<?php
/*
 * Build the next page URL by doing AJAX...
 * Reload the page automatically after few seconds 
 * NOT loading 1st before, all doing by ajax. 
 * 
 * @author: phong.nguyen 
 *    20150417 fixed bug != null  
 *    20150919 no need check decoded !=null, $decoded_fb_posts have the key 'paging' 
 * 
 */  
// if(isset($decoded_fb_posts) )  
// { 
	// if ( $page_num && property_exists( $decoded_fb_posts, 'paging' ) ) { 
		
		// query next page; => no need!!! , only current page, because NOT loading 1st before, all doing by ajax. 
		$post_page = $page_num; // ===1 ($page_num + 1);   
		$post_paging_until = ''; 
		$post_paging_token = ''; 

		// // get nex Facebook page url => no need!!! 
		// $paging = $decoded_fb_posts->paging;
		// parse_str( $paging->next, $next_page );
		
		// $root_page    = add_query_arg( array( 'fb2wp_hist' => '' ), admin_url() );// home_url() ... 
		// //prepare posting-variables for ajax 
		// // $post_paging_until = $next_page['until']; // trang den: 1430027095 ???
		// // $post_paging_token = $next_page['__paging_token']; 
		
		// $next_page_url = add_query_arg( array(
			// 'page'         => ($page_num + 1),
			// 'until'        => $next_page['until'],
			// 'paging_token' => $next_page['__paging_token']
		// ), $root_page );  
	 
		?>
		<script type="text/javascript">
		
			var posting_data_<?php echo $source_id?> = {
				action: 'egany_fb2wp_fetch_facebook_data',  
				source_type: '<?php echo $source_type ?>',  
				source_id: '<?php echo $source_id ?>',  
				limit: '<?php echo $limit ?>',  
				max_page: '<?php echo $max_page ?>',  
				access_token: '<?php echo $access_token ?>',  
				page_num: '<?php echo $post_page ?>',  
				comment_max: '<?php echo $comment_max ?>',  
				hashtag: '<?php echo $arrFBInfo['hashtag']; ?>',  
				paging_until: '<?php echo $post_paging_until ?>',  
				paging_token: '<?php echo $post_paging_token ?>',  //phong.nguyen 20150502: add __paging_token (...error05-only 3 items, NOT 6)  
				//decoded_fb_posts: '<?php //echo $decoded_fb_posts ?>',  
			};  
			
			fectching_fb_data_ajax_<?php echo $source_id?>(); 
			
			function fectching_fb_data_ajax_<?php echo $source_id?>()
			{
				var boEnableForDoingAjax = true;   
				var page_num = parseInt(posting_data_<?php echo $source_id?>.page_num);   
				
				// while (page_num < 4)  { // nono, can NOT loop by the way... 
					if(boEnableForDoingAjax == true)
					{ 
						setTimeout(function(){  
							var div_id_<?php echo $source_id?> = '<?php echo $source_id?>';  
							var url_id_<?php echo $source_id?> = '<?php echo $next_page_url; ?>';   
							
							elp_show_ajax_loader('div#<?php echo $source_id?>'); 
							$.ajax({
							url: '<?php echo admin_url('admin-ajax.php'); ?>',   
							data: posting_data_<?php echo $source_id?>,
							type: 'POST',
							error: function( jqXHR, exception ) { 
								//break while loop. 
								page_num = posting_data_<?php echo $source_id?>.paging_until; 
								
								//write error string... 
								console.log(jqXHR.status); 
								console.log(exception);  
								$('div#<?php echo $source_id?>').unblock();
							}, 
							success: function( response ) { 
								// alert('success!!!');   
								$('div#<?php echo $source_id?>').unblock();
								var $intCount = parseInt($('#<?php echo $source_id?> td.posts_imported').html()); 
								$intCount = $intCount + response['count']; 
								$('div#<?php echo $source_id?> td.posts_imported').html($intCount); 
								$('div#<?php echo $source_id?> td.page_num').html(response['page_num']); 
								
								//do next timeout loop. 
								// phong.nguyen 20150919: imported successfully at least 1 post => available for doing next FB page. 
								if(page_num < parseInt(posting_data_<?php echo $source_id?>.max_page) && response['count'] > 0) 
								{   
									posting_data_<?php echo $source_id?>.page_num = page_num +1; 
									
									//phong.nguyen 20150920: update paging for next post 
									posting_data_<?php echo $source_id?>.paging_until = response['paging_until']; 
									posting_data_<?php echo $source_id?>.paging_token = response['paging_token']; 
									boEnableForDoingAjax = true;  
									fectching_fb_data_ajax_<?php echo $source_id?>();  
								}
								else
								{ 
									boEnableForDoingAjax = true; 
								}
								
							}, 
							}); // end ajax. 
						}, 1000); // end set timeout 
						
						// //waiting for next timeout loop; 
						// boEnableForDoingAjax = false; 
						// page_num = page_num +1; 
					}// end if: Set timeout 
					
					
					// //nono AAA!!!   
					// setTimeout(function(){}, 1000); // end set timeout 
				// }//end while: page_num 
					
			}
			
				
				// alert($(div_id_<?php echo $source_id?>).find('td.posts_imported').html());   
				// $('#147975405395810').find('td.posts_imported').html()  
				// window.location.href = '<?php echo $next_page_url; ?>';  
				// window.open('<?php echo $next_page_url; ?>', '_blank');  
			
		</script>
		<?php
	// }  
// }  // END: isset($decoded_fb_posts)  
				
				

