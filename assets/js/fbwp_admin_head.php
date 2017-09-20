<?php 
// echo '<script>'; 
?>

<script type="text/javascript">

// // var $ = jQuery;
// var $ =jQuery.noConflict(); 
jQuery(document).ready(function($){ 
	// $('#fb2wp_general[access_type]').change(function(){
		// //do something... 
		
	// });
});  



/**
 * elp_show_ajax_loader
 * 
 * @author phong 20150414  
 */
function elp_show_ajax_loader(str_block_html_id) {
	$( str_block_html_id ).block({
		message: null,
		overlayCSS: {
			background: '#fff url("<?php echo plugins_url('/assets/images/ajax-loader.gif', EGANY_PLUGIN_FB2WP_FILE) ?>") no-repeat center',   
			opacity: 0.6,  
		}
	});
}


//more... 

</script>

<?php
// echo '</script>'; 
?>