<?php
function zing_ws_live_set_options() {
	global $zing_ws_options,$zing_ws_name,$zing_ws_shortname;
	global $db_prefix,$base_path,$base_url,$db_url;

	$zing_ws_name = "Zingiri Web Shop";
	$zing_ws_shortname = "zing_ws";
	$install_type = array("Yes","No");
	$zing_yn = array("Yes", "No");

	$zing_ws_options=array();

	$zing_ws_options[]=array(  "name" => "Connection settings",
            "type" => "heading",
			"desc" => "This section manages the Web Shop connection settings.");

	$zing_ws_options[]=	array(	"name" => "Base URL",
		"desc" => "URL of the Zingiri Web Shop Live service. Normally this should be left to it's default value.",
		"id" => $zing_ws_shortname."_baseurl",
		"std" => 'http://webshop-us.zingiri.net/',
		//"attr" => 'readonly="readonly"',
		"type" => "text");

	$zing_ws_options[]=	array(	"name" => "API key",
		"desc" => "The API key is automatically generated, record it somewhere in case you need it in the future (e.g. moving your website).",
		"id" => $zing_ws_shortname."_accname",
		"std" => zing_ws_create_api_key('web-shop'),
		"type" => "text");

	if (get_option("zing_webshop_pages")) {
		$zing_ws_options[]=array(  "name" => "General settings",
            "type" => "heading",
			"desc" => "This section manages the Web Shop general settings.");

		global $wpdb;
		if ($ids=get_option("zing_webshop_pages")) {
			$ida=explode(",",$ids);
			foreach ($ida as $i) {
				$p = $wpdb->get_results( "SELECT post_title FROM ".$wpdb->prefix."posts WHERE post_status<>'trash' and id='".$i."'" );
				$zing_ws_options[]=array(	"name" => $p[0]->post_title." page",
			"desc" => "Display ".$p[0]->post_title." page in the menus.",
			"id" => $zing_ws_shortname."_show_menu_".$i,
			"std" => "Yes",
			"type" => "select",
			"options" => $zing_yn);
			}
		}
	}
	
	$zing_ws_options[]=array(  "name" => "Before you install",
            "type" => "heading",
			"desc" => '<div style="text-decoration:underline;display:inline;font-weight:bold">IMPORTANT:</div> Zingiri Web Shop uses web services stored on Zingiri\'s servers. In doing so, data entered via the forms of the application is collected and stored on our servers. Your admin email address, together with the API key listed here above is also recored as as a unique identifier for your account on Zingiri\'s servers. 
					This data remains your property and Zingiri will not use nor make available for use any of this information without your permission. The data is stored securely in a database and is only accessible to persons you have authorized to use Zingiri Web Shop.
					We have a very strict <a href="http://www.zingiri.com/privacy-policy/" target="_blank">privacy policy</a> as well as <a href="http://www.zingiri.com/terms/" target="_blank">terms & conditions</a> governing data stored on our servers.
					<div style="font-weight:bold;display:inline">By installing this plugin you accept these terms & conditions.</div>');
}

function zing_wslive_ws_admin() {

	global $zing_ws_name, $zing_ws_shortname, $zing_ws_options, $integrator;

	zing_ws_live_set_options();

	if (isset($_REQUEST['installed']) && $_REQUEST['installed']) echo '<div id="message" class="updated fade"><p><strong>'.$zing_ws_name.' settings updated.</strong></p></div>';
	?>
<div class="wrap">
	<form method="post">
	<?php if (ZING_CMS=='jl') echo '<input type="hidden" name="option" value="com_zingiriwebshop" />';?>
	<?php zing_options($zing_ws_options);?>
		<center class="submit">
			<input name="install" type="submit" value="Update" />
		</center>
		<input type="hidden" name="action" value="install" />
	</form>
	<?php if (zing_ws_active_install()) {
		echo '<hr />';
		echo '<h3>Migrate</h3>';
		echo '<p>';
		zing_ws_migrate();
		echo 'To migrate your curent local data, first download <a href="'.ZING_WSLIVE_UPLOADS_URL.get_option('zing_ws_dumpfile').'.zip">this file</a> to your computer and then upload it to your <a href="'.get_option('zing_ws_baseurl').get_option('zing_ws_accname').'/wp-admin/admin.php?page=migrate" target="_blank">Zingiri Live Admin Panel</a>.';
		echo '</p><br />';
	}
	?>
	<?php zing_wslive_admin_footer();?>
</div>
<?php
}

function zing_wslive_admin_footer() {
	?>
	<div style="clear:both"></div>
	<hr />
	<div style="margin-bottom:20px;text-align:center;">
		<a href="http://go.zingiri.com/dl.php?type=d&id=30" target="_blank">Documentation</a> | <a href="http://forums.zingiri.com/forumdisplay.php?fid=3" target="_blank">Support Forums</a> | <a href="http://www.zingiri.com/support" target="_blank">Pro Support Options</a>
	</div>
	<div style="text-align:center">
		<image src="<?php echo plugins_url()?>/ws-live/live/images/logo.png" />
	</div>
<?php }