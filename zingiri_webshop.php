<?php
/*
 Plugin Name: Zingiri Web Shop Live
 Plugin URI: http://www.zingiri.com/web-shop
 Description: Zingiri Web Shop is a full featured software package that allows you to set up your own online webshop within minutes.
 Author: Zingiri
 Version: 3.0.0
 Author URI: http://www.zingiri.com/web-shop
 */

if (!defined('ZING_CMS')) define('ZING_CMS','wp');

require(dirname(__FILE__).'/live/bootstrap.php');

register_activation_hook(__FILE__,'zing_wslive_activate');
register_deactivation_hook(__FILE__,'zing_wslive_deactivate');

function zing_wslive_activate() {
	if (is_plugin_active('zingiri-web-shop/zingiri_webshop.php')) die("Zingiri Web Shop Live and Zingiri Web Shop Standard can't be activated at the same time.");
}


