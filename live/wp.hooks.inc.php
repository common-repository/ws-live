<?php
if (get_option("zing_ws_baseurl") && get_option("zing_ws_accname")) {
	if (strstr(get_option('zing_ws_baseurl'),'live.zingiri.com')) update_option('zing_ws_baseurl','http://webshop-us.zingiri.net/');
	add_action("init", "zing_wslive_init");
	add_filter('get_pages', 'zing_wslive_exclude_pages');
	add_filter('the_content', 'zing_wslive_content', 10, 3);
	add_action('wp_head', 'zing_wslive_header');
	add_action('admin_head', 'zing_admin_header');
	add_action('wp_head', 'zing_wslive_header_custom', 100);
	add_filter('wp_title', 'zing_wslive_title');
	add_filter('the_title', 'zing_wslive_page_title', 10, 2);
	add_action('wp_ajax_wslive_ajax_backend', 'wslive_ajax_backend_callback');
	add_action('wp_ajax_aphps_ajax', 'wslive_ajax_backend_callback');
	add_action('wp_ajax_wslive_ajax_frontend', 'wslive_ajax_frontend_callback');
	add_action('wp_ajax_nopriv_wslive_ajax_frontend', 'wslive_ajax_frontend_callback');
	add_action('widgets_init', create_function('', 'return register_widget("zing_ws_widget0");'));
	add_action('widgets_init', create_function('', 'return register_widget("zing_ws_widget1");'));
	add_action('widgets_init', create_function('', 'return register_widget("zing_ws_widget2");'));
	add_action('widgets_init', create_function('', 'return register_widget("zing_ws_widget3");'));
	add_action('widgets_init', create_function('', 'return register_widget("zing_ws_widget4");'));
	add_action('widgets_init', create_function('', 'return register_widget("zing_ws_widget5");'));
}
add_action('admin_notices', 'zing_wslive_admin_notices');
add_action('admin_menu', 'zing_wslive_add_admin');

function zing_wslive_admin_notices() {
	$messages=array();
	$dirs=array();
	
	$upload=wp_upload_dir();
	if ($upload['error']) $messages[]=$upload['error'];
	
	if (count($dirs) > 0) {
		foreach ($dirs as $file) {
			if (!file_exists($file)) $errors[]='Directory ' . $file . ' doesn\'t exist, please create it.';
			elseif (!is_writable($file)) $errors[]='Directory ' . $file . ' is not writable, please chmod to 777';
		}
	}
	
	if (phpversion() < '5') $messages[]="You are running PHP version " . phpversion() . ". You require PHP version 5 or higher to install the Web Shop.";
	if (!class_exists('ZipArchive')) $messages='To use the Zingiri Web Shop migrate functionality you need to have at least PHP 5.2 installed as well as the ZipArchive extension. Please ask your hosting company to upgrade to PHP 5.2 or higher.';
	if (count($messages) > 0) {
		echo "<div id='zing-warning' style='background-color:greenyellow' class='updated fade'><p><strong>";
		foreach ($messages as $message)
			echo $message . '<br />';
		echo "</strong> " . "</p></div>";
	}
}

function zing_wslive_add_admin() {
	global $zing_ws_name, $zing_ws_shortname, $zing_ws_options, $menus, $txt, $wpdb, $integrator, $remoteMsg;
	global $dbtablesprefix;
	
	zing_ws_live_set_options();
	if (isset($_GET['page']) && strstr($_GET['page'], 'zingiri-web-shop')) {
		
		if (isset($_REQUEST['action']) && ('install' == $_REQUEST['action'])) {
			update_option('zing_ws_installed', 1);
			foreach ($zing_ws_options as $value) {
				if (isset($_REQUEST[$value['id']])) {
					update_option($value['id'], $_REQUEST[$value['id']]);
				} else {
					delete_option($value['id']);
				}
			}
			
			zing_ws_install();
			
			header("Location: " . "admin.php?page=zingiri-web-shop&installed=true");
			die();
		}
	}
	add_menu_page($zing_ws_name, 'Zingiri', 'manage_options', 'zingiri-web-shop', 'zing_wslive_ws_admin', ZING_URL . 'images/menu_webshop.png');
	add_submenu_page('zingiri-web-shop', $zing_ws_name . '- Integration', 'Integration', 'manage_options', 'zingiri-web-shop', 'zing_wslive_ws_admin');
	
	if (isset($_SESSION['wslive']['menus'])) $menus=$_SESSION['wslive']['menus'];
	if (isset($_SESSION['wslive']['txt'])) $txt=$_SESSION['wslive']['txt'];
	if (!isset($menus) || !isset($txt)) {
		wsConnectURL('menus');
		$menus=isset($remoteMsg['menus']) ? $remoteMsg['menus'] : null;
		$txt=$remoteMsg['txt'];
	}
	if (get_option("zing_webshop_version") && $menus) {
		$cap='edit_plugins';
		$groupings=array();
		foreach ($menus as $page => $menu) {
			if (!isset($menu['hide']) || !$menu['hide']) {
				$g=$menu['grouping'];
				$groupLabel=$txt[$menu['group']] ? $txt[$menu['group']] : $menu['group'];
				$menuLabel=$txt[$menu['label']] ? $txt[$menu['label']] : $menu['label'];
				if (!isset($groupings[$g]) && !isset($menu['single'])) {
					add_menu_page($groupLabel, $groupLabel, $cap, $page, 'zing_ws_settings', get_option('zing_ws_baseurl') . 'app/fws/images/menu_' . $g . '.png');
					$groupings[$g]=$page;
				} elseif (isset($menu['single']) && $menu['single']) {
					add_submenu_page('zingiri-web-shop', $menuLabel, $menuLabel, $cap, $page, 'zing_ws_settings');
				} else {
					add_submenu_page($groupings[$g], $menuLabel, $menuLabel, $cap, $page, 'zing_ws_settings');
				}
			}
		}
		if (isset($_GET['page']) && isset($menus[$_GET['page']]) && $menus[$_GET['page']] && isset($menus[$_GET['page']]['hide']) && $menus[$_GET['page']]['hide']) {
			$menu=$menus[$_GET['page']];
			add_submenu_page('zingiri-web-shop', $txt[$menu['label']], $txt[$menu['label']], $cap, $_GET['page'], 'zing_ws_settings');
		}
	}
}

function zing_wslive_content($content) {
	global $remoteMsg, $showPage;
	
	if (isset($remoteMsg['status']) && $remoteMsg['status'] == 'loginfailed') {
		echo '<a href="' . wsDefaultPageUrl() . $remoteMsg['redirect'] . '">' . 'Back' . '</a>';
	} elseif (isset($remoteMsg['status']) && $remoteMsg['status'] == 'loginsuccess') {
		echo '<a href="' . get_option('home') . '/?' . $remoteMsg['redirect'] . '">' . 'Success' . '</a>';
		header('Location: ' . get_option('home') . '/?' . $remoteMsg['redirect']);
		die();
	} elseif (isset($remoteMsg['status']) && $remoteMsg['status'] == 'logoutsuccess') {
		echo '<a href="' . get_option('home') . '/?' . $remoteMsg['redirect'] . '">' . 'Success' . '</a>';
		header('Location:' . get_option('home'));
	} elseif ($showPage) return '<div id="web-shop">' . $remoteMsg['main'] . '</div>';
	else return $content;
}

function zing_ws_install() {
	if (get_option("zing_webshop_version")) {
		wp_clear_scheduled_hook('zing_ws_cron_hook');
	}
	
	$pages=array();
	$pages[]=array("Shop","main","*",0);
	$pages[]=array("Cart","cart","",0);
	$pages[]=array("Checkout","checkout1","checkout",6);
	$pages[]=array("Admin","admin","",9);
	$pages[]=array("Personal","my","",3);
	$pages[]=array("Login","my","login",1);
	$pages[]=array("Logout","logout","*",3);
	$pages[]=array("Register","customer","add",1);
	
	if (!get_option("zing_webshop_pages")) {
		$ids="";
		foreach ($pages as $i => $p) {
			$my_post=array();
			$my_post['post_title']=$p['0'];
			$my_post['post_content']='Do not delete this page unless you know what you are doing';
			$my_post['post_status']='publish';
			$my_post['post_author']=1;
			$my_post['post_type']='page';
			$my_post['comment_status']='closed';
			$my_post['menu_order']=100 + $i;
			$id=wp_insert_post($my_post);
			if (empty($ids)) {
				$ids.=$id;
			} else {
				$ids.="," . $id;
			}
			if (!empty($p[1])) add_post_meta($id, 'zing_page', $p[1]);
			if (!empty($p[2])) add_post_meta($id, 'zing_action', $p[2]);
			if (!empty($p[3])) add_post_meta($id, 'zing_security', $p[3]);
		}
		update_option("zing_webshop_pages", $ids);
	} else {
		$ids=get_option("zing_webshop_pages");
		$ida=explode(",", $ids);
		$i=0;
		foreach ($ida as $i => $id) {
			$p=$pages[$i];
			if (!empty($p[1])) update_post_meta($id, 'zing_page', $p[1]);
			if (!empty($p[2])) update_post_meta($id, 'zing_action', $p[2]);
			if (!empty($p[3])) update_post_meta($id, 'zing_security', $p[3]);
		}
	}
	update_option("zing_webshop_live_version", ZING_VERSION);
}

function zing_wslive_uninstall_delete_pages() {
	global $wpdb;
	$ids=get_option("zing_webshop_pages");
	$ida=explode(",", $ids);
	foreach ($ida as $id) {
		if (!empty($id)) {
			wp_delete_post($id, true);
			$query="delete from " . $wpdb->prefix . "postmeta where meta_key in ('zing_page','zing_action','zing_security')";
			$wpdb->query($query);
		}
	}
	delete_option("zing_webshop_pages");
}

/**
 * Header hook: loads FWS addons and css files
 *
 * @return unknown_type
 */
function zing_wslive_header() {
	global $remoteMsg;
	
	$ret='';
	
	if (isset($remoteMsg['seo']['description'])) $ret.=sprintf("<meta name=\"description\" content=\"%s\" />", $remoteMsg['seo']['description']);
	if (isset($remoteMsg['seo']['keywords'])) $ret.=sprintf("<meta name=\"keywords\" content=\"%s\" />", $remoteMsg['seo']['keywords']);
	
	$script='';
	if (isset($remoteMsg['vars']) && count($remoteMsg['vars']) > 0) {
		foreach ($remoteMsg['vars'] as $v => $c) {
			if ($v == 'wsURL') $x=get_option('home') . '/index.php?page=ajax&wscr=';
			else $x=$c;
			$script.="var " . $v . "='" . $x . "';";
		}
	}
	$script.="var ajaxurl='" . admin_url('admin-ajax.php') . "';";
	$script.="var wsAjaxURL=ajaxurl+'?action=wslive_ajax_frontend&page=ajax&module=fws&form=';";
	
	if ($script) $ret.='<script type="text/javascript">' . $script . '</script>';
	
	if (isset($remoteMsg['scripts']) && count($remoteMsg['scripts']) > 0) {
		foreach ($remoteMsg['scripts'] as $s) {
			$ret.='<script type="text/javascript" src="' . $s . '"></script>';
		}
	}
	
	if (isset($remoteMsg['css']) && count($remoteMsg['css']) > 0) {
		foreach ($remoteMsg['css'] as $c) {
			$ret.='<link type="text/css" rel="stylesheet" media="all" href="' . $c . '" />';
		}
	}
	echo $ret;
}

function zing_wslive_header_custom() {
	if (file_exists(BLOGUPLOADDIR . 'zingiri-web-shop/custom.css')) echo '<link rel="stylesheet" type="text/css" href="' . BLOGUPLOADURL . 'zingiri-web-shop/custom.css" media="screen" />';
}

/**
 * Initialization of page, action & page_id arrays
 *
 * @return unknown_type
 */
function zing_wslive_init() {
	global $page_content, $remoteMsg, $wp_version;
	global $zing_page_id_to_page, $zing_page_to_page_id, $wpdb;
	
	ob_start();
	
	if (!session_id()) @session_start();
	
	if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'ajax') return;
	
	if (is_admin()) {
		wp_enqueue_script(array('jquery','jquery-ui-core','jquery-ui-dialog','jquery-ui-datepicker','jquery-ui-sortable','jquery-ui-tabs','jquery-ui-button','jquery-ui-menu'));
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/flick/jquery-ui.css');
		
		if ($wp_version < '3.3') {
			wp_enqueue_script(array('editor','thickbox','media-upload'));
			wp_enqueue_style('thickbox');
		}
	} else {
		
		ob_start();
		
		wp_enqueue_script(array('jquery'));
		wp_enqueue_script(array('jquery-ui-core','jquery-ui-dialog','jquery-ui-datepicker'));
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/flick/jquery-ui.css');
		
		// get pages
		
		$zing_page_id_to_page=array();
		$zing_page_to_page_id=array();
		
		$sql="SELECT post_id,meta_value FROM $wpdb->postmeta,$wpdb->posts WHERE $wpdb->postmeta.post_id=$wpdb->posts.id AND $wpdb->posts.post_type='page' AND meta_key = 'zing_page'";
		$a=$wpdb->get_results($sql);
		
		foreach ($a as $i => $o) {
			if (!isset($zing_page_id_to_page[$o->post_id])) $zing_page_id_to_page[$o->post_id][0]=$o->meta_value;
		}
		$sql="SELECT post_id,meta_value FROM $wpdb->postmeta WHERE meta_key = 'zing_action'";
		$a=$wpdb->get_results($sql);
		foreach ($a as $i => $o) {
			if (!isset($zing_page_id_to_page[$o->post_id][1])) $zing_page_id_to_page[$o->post_id][1]=$o->meta_value;
		}
		
		$zing_page_to_page_id=array();
		foreach ($zing_page_id_to_page as $i => $a) {
			$page=$a[0];
			$action=isset($a[1]) ? $a[1] : null;
			if (isset($a[0]) && isset($a[1])) {
				$zing_page_to_page_id[$page][$action]=$i;
			}
			if (isset($a[0]) && !isset($a[1])) {
				$zing_page_to_page_id[$page]['*']=$i;
			}
		}
		
		// end get pages
		
		$page_content=zing_wslive_main('content');
	}
}

/**
 * Look up FWS page name based on Wordpress page_id
 *
 * @param
 *        	$page_id
 * @return unknown_type
 */
function zing_wslive_page($page_id) {
	global $zing_page_id_to_page;
	if (isset($zing_page_id_to_page[$page_id])) {
		return $zing_page_id_to_page[$page_id][0];
	}
	return "main";
}

/**
 * Look up Wordpress page_id based on FWS page and action
 *
 * @param
 *        	$page
 * @param
 *        	$action
 * @return unknown_type
 */
function zing_wslive_page_id($page, $action="*") {
	global $zing_page_to_page_id;
	
	if (isset($zing_page_to_page_id[$page][$action])) {
		return $zing_page_to_page_id[$page][$action];
	} elseif (isset($zing_page_to_page_id[$page])) {
		return $zing_page_to_page_id[$page];
	}
	return "";
}

/**
 * Exclude certain pages from the menu depending on whether the user is logged in
 * or is an administrator.
 * This depends on the custom field "security":
 * 0 - show if not logged in
 * 1 - show if not logged in but hide if logged in
 * 2 - show if customer logged in
 * 3 - show if customer or user or admin logged in
 * 4 - show if not logged in or if customer logged in
 * 5 - show if user or customer logged in
 * 6 - show if user or admin logged in
 * 9 - show if admin logged in
 *
 * @param
 *        	$pages
 * @return unknown_type
 */
function zing_wslive_exclude_pages($pages) {
	$bail_out=((defined('WP_ADMIN') && WP_ADMIN == true) || (strpos($_SERVER['PHP_SELF'], 'wp-admin') !== false));
	if ($bail_out) return $pages;
	
	Global $dbtablesprefix;
	Global $cntry;
	Global $lang;
	Global $lang2;
	Global $lang3;
	
	$loggedin=zing_wslive_loggedin();
	$isadmin=false;
	if (!$isadmin) $iscustomer=true;
	
	$unsetpages=array();
	$l=count($pages);
	for($i=0; $i < $l; $i++) {
		$page=& $pages[$i];
		$security=get_post_meta($page->ID, "zing_security", TRUE);
		$show=false;
		if ($security == 0) {
			$show=true;
		} elseif ($security == "1" && !$loggedin == 1) {
			$show=true;
		} elseif ($security == "2" && $loggedin == 1 && $iscustomer) {
			$show=true;
		} elseif ($security == "3" && $loggedin == 1) {
			$show=true;
		} elseif ($security == "4" && (!$loggedin == 1 || $iscustomer)) {
			$show=true;
		} elseif ($security == "5" && $loggedin == 1 && !$isadmin && ($isuser || $iscustomer)) {
			$show=true;
		} elseif ($security == "6" && ($iscustomer || $isadmin)) { // should really be shown only if something in cart
			$show=true;
		} elseif ($security == "9" && $loggedin == 1 && $isadmin) {
			$show=true;
		}
		if (!$show || get_option("zing_ws_show_menu_" . $page->ID) == "No") {
			unset($pages[$i]);
			$unsetpages[$page->ID]=true;
		}
	}
	
	return $pages;
}

function zing_wslive_loggedin() {
	global $remoteMsg;
	return isset($remoteMsg['loggedin']) ? $remoteMsg['loggedin'] : false;
}

/**
 * Register sidebar widgets
 *
 * @return unknown_type
 */
function zing_wslive_sidebar_init() {
	global $wsWidgets;
	foreach ($wsWidgets as $w) {
		if (isset($w['class'])) {
			$wstemp=new $w['class']();
			register_sidebar_widget(__($w['name']), array($wstemp,'init'));
			if (isset($w['control'])) register_widget_control(__($w['name']), array($wstemp,'control'));
		} elseif (isset($w['function'])) register_sidebar_widget(__($w['name']), $w['function']);
	}
}

function zing_wslive_title($title) {
	global $remoteMsg;
	if (isset($remoteMsg['seo']['title']) && $remoteMsg['seo']['title']) return $remoteMsg['seo']['title'];
	else return $title;
}

function zing_wslive_page_title($pageTitle, $id=0) {
	global $remoteMsg;
	
	if (!in_the_loop()) return $pageTitle;
	
	if (isset($remoteMsg['title']) && $remoteMsg['title']) return ($remoteMsg['title']);
	else return $pageTitle;
}

// Widgets
class zing_ws_widget0 extends WP_Widget {

	function zing_ws_widget0() {
		parent::WP_Widget(false, $name='Zingiri Product Carousel');
	}

	function widget($args, $instance) {
		zing_ws_widget($args, 0);
	}
}
class zing_ws_widget1 extends WP_Widget {

	function zing_ws_widget1() {
		parent::WP_Widget(false, $name='Zingiri Web Shop Cart');
	}

	function widget($args, $instance) {
		zing_ws_widget($args, 1);
	}
}
class zing_ws_widget2 extends WP_Widget {

	function zing_ws_widget2() {
		parent::WP_Widget(false, $name='Zingiri Web Shop General');
	}

	function widget($args, $instance) {
		zing_ws_widget($args, 2);
	}
}
class zing_ws_widget3 extends WP_Widget {

	function zing_ws_widget3() {
		parent::WP_Widget(false, $name='Zingiri Web Shop Random Product');
	}

	function widget($args, $instance) {
		zing_ws_widget($args, 3);
	}
}
class zing_ws_widget4 extends WP_Widget {

	function zing_ws_widget4() {
		parent::WP_Widget(false, $name='Zingiri Web Shop Products');
	}

	function widget($args, $instance) {
		zing_ws_widget($args, 4);
	}
}
class zing_ws_widget5 extends WP_Widget {

	function zing_ws_widget5() {
		parent::WP_Widget(false, $name='Zingiri Web Shop Search');
	}

	function widget($args, $instance) {
		zing_ws_widget($args, 5);
	}
}

function zing_ws_widget($args, $i) {
	global $remoteMsg;
	extract($args);
	echo $before_widget;
	if (isset($remoteMsg['status']) && $remoteMsg['status'] == 'maintenance') echo $remoteMsg['main'];
	elseif (isset($remoteMsg['widgets'][$i])) echo $remoteMsg['widgets'][$i]['content'];
	else echo 'No content for widget ' . $args['widget_name'];
	echo $after_widget;
}

/**
 * Deactivation of web shop
 *
 * @return void
 */
function zing_wslive_deactivate() {
	global $zing_ws_options;
	zing_ws_live_set_options();
	zing_wslive_uninstall_delete_pages();
	delete_option('zing_ws_installed');
	foreach ($zing_ws_options as $value) {
		delete_option($value['id']);
	}
	delete_option("zing_ws_cache");
	delete_option("zing_ws_cron");
	delete_option("zing_ws_acckey");
	delete_option("widget_zing_ws_widget0");
	delete_option("widget_zing_ws_widget1");
	delete_option("widget_zing_ws_widget2");
	delete_option("widget_zing_ws_widget3");
	delete_option("widget_zing_ws_widget4");
	delete_option("widget_zing_ws_widget5");
	delete_option("zing_webshop_live_version");
}

function zing_ws_settings() {
	global $menus, $remoteMsg;
	
	// main window
	echo '<div id="wslive-wrapper">';
	echo '<div id="wslive-main">';
	$_GET['page']=str_replace('zingiri-web-shop-', '', $_GET['page']);
	$pg=$_GET['page'];
	$params=array();
	$pairs=explode('&', $menus[$pg]['href']);
	foreach ($pairs as $pair) {
		list($n, $v)=explode('=', $pair);
		if ($n != 'page') {
			if (($n == 'form' || $n == 'formid') && (isset($_GET['form']) || isset($_GET['formid']))) break;
			elseif (!isset($_GET[$n])) $_GET[$n]=$v;
			$params[$n]=$v;
		}
	}
	if (isset($menus[$pg]['page'])) {
		$_GET['page']=$menus[$pg]['page'];
		$callPage=$_GET['page'];
	} else {
		$callPage=$pg;
	}
	if ($callPage=='zingiri-web-shop') return;
	wsConnectURL($callPage);
	echo $remoteMsg['main'];
	echo '</div>';
	zing_wslive_admin_footer();
	echo '</div>';
}

function zing_admin_header() {
	global $remoteMsg, $wp_version;
	
	if (!isset($remoteMsg['adminheader'])) wsConnectURL();
	if (isset($remoteMsg['adminheader'])) echo $remoteMsg['adminheader'];
	
	echo '<script type="text/javascript">';
	echo "var aphpsAjaxURL=ajaxurl+'?action=wslive_ajax_backend&page=ajax&zfaces=ajax&form=';";
	echo "var zfajax=ajaxurl+'?action=wslive_ajax_backend&page=ajax&zfaces=ajax&form=';";
	echo '</script>';
	if ($wp_version < '3.3') wp_tiny_mce(false, array('editor_selector' => 'theEditor'));
}

function zing_wslive_parser($buffer) {
	global $wp_version;
	if (is_admin() && ($wp_version >= '3.3')) {
		if (!class_exists('simple_html_dom')) require (dirname(__FILE__) . '/includes/simple_html_dom.php');
		$html=new simple_html_dom();
		$html->load($buffer);
		if ($textareas=$html->find('textarea[class=theEditor]')) {
			foreach ($textareas as $textarea) {
				ob_start();
				wp_editor($textarea->innertext, $textarea->id);
				$editor=ob_get_clean();
				$textarea->outertext=$editor;
			}
		}
		return $html->__toString();
	}
	$buffer=str_replace('index.php?page=', '?zpage=', $buffer);
	return $buffer;
}

/*
 * Ajax calls
 */
function wslive_ajax_backend_callback() {
	global $remoteMsg;
	define('WSLIVE_AJAX_ORIGIN', "b");
	$pg=isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
	wsConnectURL($pg);
	echo $remoteMsg['main'];
	die();
}

function wslive_ajax_frontend_callback() {
	global $remoteMsg;
	define('WSLIVE_AJAX_ORIGIN', "f");
	$pg=isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
	wsConnectURL($pg);
	echo $remoteMsg['main'];
	die('');
}
