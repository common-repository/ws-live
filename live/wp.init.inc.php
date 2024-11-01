<?php
// Pre-2.6 compatibility for wp-content folder location
if (!defined("WP_CONTENT_URL")) {
	define("WP_CONTENT_URL", get_option("siteurl") . "/wp-content");
}
if (!defined("WP_CONTENT_DIR")) {
	define("WP_CONTENT_DIR", ABSPATH . "wp-content");
}
if (!defined("WP_PLUGIN_URL")) {
	define("WP_PLUGIN_URL", get_option("siteurl") . "/wp-content/plugins");
}
if (!defined("WP_PLUGIN_DIR")) {
	define("WP_PLUGIN_DIR", ABSPATH . "wp-content/plugins");
}

$upload=wp_upload_dir();
if (!defined("BLOGUPLOADDIR")) {
	define("BLOGUPLOADDIR",$upload['path'].'/');
}
if (!defined("BLOGUPLOADURL")) {
	define("BLOGUPLOADURL",$upload['url'].'/');
}

if (!defined("ZING_WSLIVE_PLUGINSDIR")) {
	define("ZING_WSLIVE_PLUGINSDIR",realpath(dirname(__FILE__).'/..').'/');
}

if (!defined("ZING_WSLIVE_PLUGIN")) {
	$zing_plugin=str_replace(realpath(dirname(__FILE__).'/../..'),"",dirname(__FILE__));
	$zing_plugin=substr($zing_plugin,1);
	define("ZING_WSLIVE_PLUGIN", $zing_plugin);
}
if (!defined("ZING")) {
	define("ZING", true);
}
if (!defined("ZING_WSLIVE_SUB")) {
	if (get_option("siteurl") == get_option("home"))
	{
		define("ZING_WSLIVE_SUB", "wp-content/plugins/".ZING_WSLIVE_PLUGIN."/fws/");
	}
	else {
		define("ZING_WSLIVE_SUB", str_replace(get_option("home")."/","",get_option("siteurl"))."/wp-content/plugins/".ZING_WSLIVE_PLUGIN."/fws/");
	}
}

if (!defined("ZING_WSLIVE_DIR")) {
	define("ZING_WSLIVE_DIR", dirname(__FILE__)."/fws/");
}
if (!defined("ZING_WSLIVE_LOC")) {
	define("ZING_WSLIVE_LOC",dirname(__FILE__)."/");
}
if (!defined("ZING_URL")) {
	define("ZING_URL", WP_PLUGIN_URL . "/".ZING_WSLIVE_PLUGIN."/");
}

if (!defined("ZING_HOME")) {
	define("ZING_HOME", get_option("home"));
}

if (!defined("ZING_WSLIVE_UPLOADS_URL")) {
	define("ZING_WSLIVE_UPLOADS_URL", BLOGUPLOADURL);
}
if (!defined("ZING_WSLIVE_UPLOADS_DIR")) {
	define("ZING_WSLIVE_UPLOADS_DIR", BLOGUPLOADDIR);
}

if (function_exists("qtrans_getLanguage")) {
	if (!session_id()) @session_start();
	if (isset($_GET['lang'])) $_SESSION['lang']=$_GET['lang'];
	elseif (isset($_SESSION['lang'])) $_GET['lang']= $_SESSION['lang'];
}
