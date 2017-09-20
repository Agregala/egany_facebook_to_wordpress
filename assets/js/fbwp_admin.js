// alert("wc-hooks_for_woo.js is loaded!!!")
// var $ = jQuery;
// window.jQuery = window.$ = jQuery; // from ...\kaikei_MYsql\script\common\common.js, but don't work


// var $ = jQuery;
var $ =jQuery.noConflict();

function show_page_info(){
	//show page_id 
	var $objCurrentRow = false; 
	$objCurrentRow = $('#fb2wp_general\\[page_id\\]').parents('tr');  
	$objCurrentRow.fadeIn("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden'); 
	}); 
	
	//hide group_id  
	$objCurrentRow = $('#fb2wp_general\\[group_id\\]').parents('tr');  
	$objCurrentRow.fadeOut("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden');  ... // $(this)...
	});
}


function show_group_info(){
//show group_id
	$objCurrentRow = $('#fb2wp_general\\[group_id\\]').parents('tr');  
	$objCurrentRow.fadeIn("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden');  ... // $(this)...
	}); 
	
	//hide page_id
	var $objCurrentRow = false; 
	$objCurrentRow = $('#fb2wp_general\\[page_id\\]').parents('tr');  
	$objCurrentRow.fadeOut("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden'); 
	}); 
}

/*
 * hide_all_connection_info
 * 
 * @author: EGANY 20150421 
 * @param: string strTabID (Ex.: fb2wp_general )
 * @param: int $intNum No. of current Page/Group, maximum is 200 for now 20150917 
 * @return: none
 */ 
function hide_all_connection_info(strTabID, $intNum){ 

	//hide Access Token 
	$objCurrentRow = $(strTabID+ '\\[access_token'+ $intNum +'\\]').parents('tr');  
	$objCurrentRow.fadeOut("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden');  ... // $(this)...
	}); 
		
	//hide App. Info. 
	// okok .addClass('hidden'); ... .removeClass('hidden');    
	var $objCurrentRow = false;  
	$objCurrentRow = $(strTabID+ '\\[app_id'+ $intNum +'\\]').parents('tr');   
	$objCurrentRow.fadeOut("fast",function(){  
		// do something... .addClass('hidden'); ... .removeClass('hidden'); 
	}); 
	$objCurrentRow = $(strTabID+ '\\[app_secret'+ $intNum +'\\]').parents('tr');   
	$objCurrentRow.fadeOut("fast",function(){  
		// do something... .addClass('hidden'); ... .removeClass('hidden'); 
	}); 
}


/*
 * show_access_token
 * 
 * @author: EGANY updated 20150421 
 * @param: string $strTabID (Ex.: fb2wp_general )
 * @param: int $intNum No. of current Page/Group, maximum is 200 for now 20150917 
 * @return: none
 */ 
function show_access_token($strTabID, $intNum){

		//show Access Token 
		$objCurrentRow = $( $strTabID+ '\\[access_token'+ $intNum +'\\]').parents('tr');  
		$objCurrentRow.fadeIn("fast",function(){
			// do something... .addClass('hidden'); ... .removeClass('hidden');  ... // $(this)...
		}); 
		
		//hide App. Info. 
		// okok .addClass('hidden'); ... .removeClass('hidden'); 
		var $objCurrentRow = false; 
		$objCurrentRow = $( $strTabID+ '\\[app_id'+ $intNum +'\\]').parents('tr');  
		$objCurrentRow.fadeOut("fast",function(){
			// do something... .addClass('hidden'); ... .removeClass('hidden'); 
		}); 
		$objCurrentRow = $( $strTabID+ '\\[app_secret'+ $intNum +'\\]').parents('tr');  
		$objCurrentRow.fadeOut("fast",function(){
			// do something... .addClass('hidden'); ... .removeClass('hidden'); 
		}); 
}


/*
 * show/hide connection info. 
 * 
 * @author: phong.nguyen 20150917  
 * @param: string access_selection_id (Ex.: '#fb2wp_general\\[access_type\\]' )  
 * @param: string $strTabID (Ex.: fb2wp_general )
 * @param: int $intNum No. of current Page/Group, maximum is 200 for now 20150917 
 * @return: none
 */ 
function show_hide_connection_info($access_selection_id, $strTabID, $intNum){ 
	if($( $access_selection_id + ' :selected').val() == 'fb_app')
	{
		//show App. info. 
		show_app_info($strTabID, $intNum); 
	}
	else if($($access_selection_id + ' :selected').val() == 'fb_access_token')
		show_access_token($strTabID, $intNum); 
	else
		hide_all_connection_info($strTabID, $intNum);  
	 
}



/*
 * show_app_info
 * 
 * @author: EGANY updated 20150421 
 * @param: string $strTabID (Ex.: #fb2wp_general )
 * @param: int $intNum No. of current Page/Group, maximum is 200 for now 20150917 
 * @return: none
 */ 
function show_app_info($strTabID, $intNum){ 
	
	// if($intNum == false){
		// alert($strTabID); 
	// }
	// okok .addClass('hidden'); ... .removeClass('hidden'); 
	var $objCurrentRow = false; 
	$objCurrentRow = $($strTabID + '\\[app_id'+ $intNum +'\\]').parents('tr');  
	$objCurrentRow.fadeIn("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden'); 
	}); 
	$objCurrentRow = $($strTabID + '\\[app_secret'+ $intNum +'\\]').parents('tr');  
	$objCurrentRow.fadeIn("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden'); 
	});
	//hide access token 
	$objCurrentRow = $($strTabID + '\\[access_token'+ $intNum +'\\]').parents('tr');  
	$objCurrentRow.fadeOut("fast",function(){
		// do something... .addClass('hidden'); ... .removeClass('hidden');  ... // $(this)...
	});
}

function changeAccessType($intNum){
	
	var $strTabIDDetail = '#fb2wp_page_group'; 
	var $str_access_type_detail = '#fb2wp_page_group\\[access_type'+ $intNum +'\\]';   
	show_hide_connection_info($str_access_type_detail, $strTabIDDetail, $intNum);   
	
	// tab Page: change Access Type  
	$($str_access_type_detail).change(function(){  
		show_hide_connection_info($str_access_type_detail, $strTabIDDetail, $intNum);  
		
		// fb2wp_page[app_id]  &  fb2wp_page[app_secret]
		// fb2wp_page[access_token]  
	}); 
}

/*
 * check current max. number of page_group_idSSS
 * for name with firstChars like 'fb2wp_page...1', 'fb2wp_page...2'... 
 * 
 * @author: phong.nguyen 20150918 
 * return: current max. number 
 */ 
function checkMaxNumber_ForName($firstChars){  
	var $lastInputID = $('[name^="'+ $firstChars +'"]').last() ; // count total: .size();  
	var $strNameRemovedLastBraket = $lastInputID.attr('name').slice(0, -1); 
	return $strNameRemovedLastBraket.substring($firstChars.length); // current max. number... .charAt
	
}



jQuery(document).ready(function($){ 
	// tab General:  default showing  
	var $intNumGeneral = ''; 
	var $strTabIDGeneral = '#fb2wp_general'; 
	var $str_access_type_general = '#fb2wp_general\\[access_type\\]'; 
	show_hide_connection_info($str_access_type_general, $strTabIDGeneral, $intNumGeneral);  
	
	// if($($str_access_type_general + ' :selected').val() == 'st_page')
	// {
		// show_page_info();  
	// }	
	// else
		// show_group_info();  
	
	// tab General: change Access Type  
	$($str_access_type_general).change(function(){ 
		show_hide_connection_info($str_access_type_general, $strTabIDGeneral, $intNumGeneral); 
		
		// fb2wp_general[app_id]  &  fb2wp_general[app_secret]
		// fb2wp_general[access_token] 
		
	}); 
	 
	// tab Page: default showing  
	changeAccessType('');  
	for($i = 2; $i <= 200; $i++){ 
		changeAccessType($i);  
	} 
	

	// // tab Page/Group: change Access Type   
	$('#add_page_group').click(function(){ 
		var $firstChars = 'fb2wp_page_group[page_group_id'; 
		//check 200 FB accounts 
		if ($('[name^="'+ $firstChars +'"]').size() >= 200) {
			alert('You have reached to 200 FB accounts.'); 
			return; 
		}
		
		// render for next FB info. 
		var $maxNumer = checkMaxNumber_ForName($firstChars);   
		if($maxNumer=='') // default: there's one FB info. existing 
			$maxNumer = 1;   
		var $intNum = parseInt($maxNumer) + 1;  
		$('#fb2wp_page_group_fields').append('<table class="form-table"><tbody> '
			+ '<tr><th scope="row">Facebook Page/Group ID <span class="page_group_number">'+ $intNum +'</span></th> ' + '<td><input name="fb2wp_page_group[page_group_id' +$intNum+']" value=""></input><span class="description"> Add your Page/Group ID. e.g: 241884142616448. <span class="highlight">Leave blank to remove all relevant page/group info.</span></span></td></tr> ' 
			+ '<tr><th scope="row">Source Type '+ $intNum +'</th> ' + '<td><select class="regular" name="fb2wp_page_group[source_type' +$intNum+ ']" id="fb2wp_page_group[source_type'+ $intNum +']"><option value="st_group">Facebook Group</option><option value="st_page" selected="selected">Facebook Page</option></select><span class="description"> Connect to Facebook Page or Group</span></td></tr>' 
			+ '<tr><th scope="row">Access Type '+ $intNum +'</th> ' + '<td><select class="regular" name="fb2wp_page_group[access_type'+ $intNum +']" id="fb2wp_page_group[access_type'+ $intNum +']"><option value="" selected="selected">Default</option><option value="fb_app">Facebook App.</option><option value="fb_access_token">Facebook Access Token</option></select><span class="description"> Use App. or Token to access Facebook</span></td></tr>' 
			+ '<tr><th scope="row">Access Token '+ $intNum +'</th> ' + ' <td><input type="text" class="regular-text" id="fb2wp_page_group[access_token'+ $intNum +']" name="fb2wp_page_group[access_token'+ $intNum +']" value=""><span class="description"> Insert your facebook access token <a href="https://developers.facebook.com/tools/explorer/" target="blank">here</a>.</span></td></tr>'  
			+ '<tr><th scope="row">Facebook App ID '+ $intNum +'</th> ' + ' <td><input type="text" class="regular-text" id="fb2wp_page_group[app_id'+ $intNum +']" name="fb2wp_page_group[app_id'+ $intNum +']" value=""><span class="description"> Insert your facebook application ID from <a href="https://developers.facebook.com/apps/" target="blank">here</a>.</span></td></tr>' 
			+ '<tr><th scope="row">Facebook App Secret '+ $intNum +'</th> ' + ' <td><input type="text" class="regular-text" id="fb2wp_page_group[app_secret'+ $intNum +']" name="fb2wp_page_group[app_secret'+ $intNum +']" value=""><span class="description"> Insert your facebook App Secret</span></td></tr>'
			
			+ '<tr><th scope="row">Facebook App Secret '+ $intNum +'</th> ' + ' <td><input type="text" class="regular-text" id="fb2wp_page_group[app_secret'+ $intNum +']" name="fb2wp_page_group[app_secret'+ $intNum +']" value=""><span class="description"> Insert your facebook App Secret</span></td></tr>'
			
			+'<tr><th scope="row">Hashtag '+ $intNum +'</th><td><input type="text" class="regular" id="fb2wp_page_group[hashtag'+ $intNum +']" name="fb2wp_page_group[hashtag'+ $intNum +']" value=""><span class="description"> Insert your facebook hashtag. A sample: <a href="https://facebook.com/hashtag/egany/" target="blank">#egany</a>.</span></td></tr>'
			  
		+'</tbody></table>');  // end: append (...)  
		
		//fire event for changing Access Type 
		changeAccessType($intNum);  
	});  
	 
});





