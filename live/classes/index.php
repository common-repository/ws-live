<?php
if ($handlex = opendir(dirname(__FILE__))) {
	while (false !== ($filex = readdir($handlex))) {
		if (strstr($filex,"class.php")) {
			require_once(dirname(__FILE__)."/".$filex);
		}
	}
	closedir($handlex);
}
?>