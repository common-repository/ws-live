<?php
function wsDefaultPageUrl() {
	$ids=get_option("zing_webshop_pages");
	$ida=explode(",",$ids);
	$pageID=$ida[0];
		
	if (get_option('permalink_structure')){
		$homePage = get_option('home');
		$wordpressPageName = get_permalink($pageID);
		$wordpressPageName = str_replace($homePage,"",$wordpressPageName);
		$pid="";
		return $homePage.$wordpressPageName.'?';
	}else{
		return get_option('home').'/?page_id='.$pageID.'&';
	}
}

function wsConnectURL($page='') {
	global $user,$remoteMsg,$current_user;

	if (empty($page)) {
		if (isset($_REQUEST['page'])) $page=$_REQUEST['page'];
		else $page='main';
	}

	if ($page=='main') $page='wsmain';
	elseif ($page=='logout') $page='wslogout';
	$remoteMsg==array();
	$url=get_option('zing_ws_baseurl').'api.php?page='.$page;
	
	$wp=array();
	if (is_user_logged_in()) {
		$wp['login']=$current_user->data->user_login;
		$wp['email']=$current_user->data->user_email;
		$wp['first_name']=get_user_meta($current_user->data->ID,'first_name',true) ? get_user_meta($current_user->data->ID,'first_name',true) : $current_user->data->display_name;
		$wp['last_name']=get_user_meta($current_user->data->ID,'last_name',true) ? get_user_meta($current_user->data->ID,'last_name',true) : $current_user->data->display_name;
		$wp['roles']=$current_user->roles;
	}
	$wp['lic']=get_option('bookings_lic');
	$wp['gmt_offset']=get_option('gmt_offset');
	$wp['siteurl']=get_option('bookings_siteurl') ? get_option('bookings_siteurl') : home_url();
	$wp['sitename']=get_bloginfo('name');
	$wp['client']='wordpress';
	if (defined('WSLIVE_AJAX_ORIGIN') && (WSLIVE_AJAX_ORIGIN=='f')) {
		$wp['mode']='f';
		$wp['pageurl']=wsDefaultPageUrl();
	} elseif (!is_admin()) {
		$wp['mode']='f';
		$wp['pageurl']=wsDefaultPageUrl();
	} else {
		$wp['mode']='b';
		$wp['pageurl']=get_admin_url().'admin.php?';
	}
	$wp['time_format']=get_option('time_format');
	$wp['admin_email']=get_option('admin_email');
	$wp['lang']='en_US';
	
	$wp['isadmin']=is_admin();
	$wp['key']=get_option('zing_ws_accname');
	$wp['_txt']=(isset($_SESSION['wslive']['txt'])) ? 0 : 1;
	$wp['url']=get_option('home');
	$wp['cms']=ZING_CMS;
	
	$wspar=urlencode(base64_encode(json_encode($wp)));
	$url.='&saaspar='.$wspar;
	
	$and='&';
	$vars='';
	if (count($_GET) > 0) {
		foreach ($_GET as $n => $v) {
			if ($n!="page" && $n!='page_id')
			{
				if (!is_array($v)) {
					$vars.= $and.$n.'='.urlencode($v);
					$and="&";
				}
			}
		}
	}

	$url.=$vars;
	$news = new wsLiveHttpRequest($url,'wslive');
	$news->follow=false;
	if ($news->live()) {
		
		$news->forceWithRedir=array('saaspar' => $wspar);
		$msg=$news->DownloadToString();
		if ($news->error) {
			$remoteMsg['main']='<div style="text-align:center;font-size:24px;padding:10px;color:red;">Could not connect to Web Shop</div>';
		} elseif (isset($news->headers['location'])) {
			header('Location: '.$news->headers['location']);die();
		} else {
			
			if ($page=='ajax') {
				$remoteMsg['main']=$msg;
				return true;
			}
			$remoteMsg=$ret=json_decode($msg,true);
			if (!$remoteMsg) echo 'ERROR:'.$msg;
			if (!is_array($ret)) return array('main' => 'Problem connecting to server');
			if (isset($remoteMsg['menus'])) $_SESSION['wslive']['menus']=$remoteMsg['menus'];
			if (!isset($_SESSION['wslive']['txt'])) $_SESSION['wslive']['txt']=$remoteMsg['txt'];
			else $remoteMsg['txt']=$_SESSION['wslive']['txt'];
			
			if (isset($remoteMsg['main'])) $remoteMsg['main']=zing_wslive_parser($remoteMsg['main']);
			if (isset($remoteMsg['print']) && $remoteMsg['print']) {
				zing_ws_print($remoteMsg['print_title'],$remoteMsg['main']);
				die();
			} elseif (isset($remoteMsg['download']) && $remoteMsg['download']) {
				send_file($remoteMsg['download_path'], $remoteMsg['download_file'], $remoteMsg['download_name']);
				die();
			} else return $ret;
		}
	} else return false;
}

# Send (download) file via pass thru
#-------------------------------------
function send_file($path, $file, $filename){

	$mainpath = "$path/$file";
	$filesize2 = sprintf("%u", filesize($mainpath));
	set_time_limit(0);

	$handle = @fopen($mainpath,"rb");
	if ($handle) {
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=\"".$filename."\"");
		header("Content-Type: application/force-download");
		header("Content-length:".(string)($filesize2));
		while(!feof($handle)) {
			print(fread($handle, 1024*8));
			flush_now();
			if (connection_status()!=0) {
				@fclose($handle);
				die();
			}
		}
		@fclose($handle);
	} else {
		print "<p><center><font class=\"changed\">ERROR - Invalid Request (Downloadable file Missing or Unreadable)</font></center><br><br>";
	}
	return;
}


function flush_now() {
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	return true;
}