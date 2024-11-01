<?php
if (class_exists('ZipArchive')) {
	class Zipper extends ZipArchive {
		var $zipFilesCounter=0;

		function addDir($path) {
			//print '<br />adding ' . $path . ' => '.str_replace(ZING_WSLIVE_UPLOADS_DIR,'',$path);
			$this->addEmptyDir(str_replace(ZING_WSLIVE_UPLOADS_DIR,'',$path));
			$nodes = glob($path . '/*');
			foreach ($nodes as $node) {
				//print '<br>'.$node . ' => '.str_replace(ZING_WSLIVE_UPLOADS_DIR,'',$node);
				if (is_dir($node)) {
					$this->addDir($node);
				} else if (is_file($node))  {
					$this->zipFilesCounter++;
					$this->addFile($node,str_replace(ZING_WSLIVE_UPLOADS_DIR,'',$node));
				}
				if ($this->zipFilesCounter % 500 == 0) {
					$this->close();
					$this->open(ZING_WSLIVE_UPLOADS_DIR.'migrate'.$this->zipFilesCounter.'.zip',ZIPARCHIVE::OVERWRITE);
					//echo '<br />create new dir:'.ZING_WSLIVE_UPLOADS_DIR.'migrate'.$this->zipFiles.'.zip';
					//print '<br />adding ' . $path . ' => '.str_replace(ZING_WSLIVE_UPLOADS_DIR,'',$path);
					$this->addEmptyDir(str_replace(ZING_WSLIVE_UPLOADS_DIR,'',$path));
				}
			}
		}

	} // class Zipper
}

function zing_ws_migrate() {
	$zip = new Zipper;
	$files=array();
	$success=true;

	update_option('zing_ws_dumpfile',md5(time().get_option('home')));

	if (($error=$zip->open(ZING_WSLIVE_UPLOADS_DIR.'migrate.zip',ZIPARCHIVE::OVERWRITE)) === TRUE) {
		$zip->addDir(ZING_WSLIVE_UPLOADS_DIR.'cats');
		$zip->addDir(ZING_WSLIVE_UPLOADS_DIR.'orders');
		$zip->addDir(ZING_WSLIVE_UPLOADS_DIR.'prodgfx');
		$zip->addDir(ZING_WSLIVE_UPLOADS_DIR.'digital-'.get_option('zing_webshop_dig'));

		$zip->addFromString('db.sql',zing_ws_database_dump());

		$params=array();
		$params['digital']=get_option('zing_webshop_dig');
		$params['version']=get_option('zing_webshop_version');
		$params['apps_version']=get_option('zing_apps_player_version');

		$zip->addFromString('config.ini',json_encode($params));

		$zip->close();
	} else {
		$success=false;
		echo '<br />Failed zip file creation of migrate.zip with error '.$error;
	}

	if (($error=$zip->open(ZING_WSLIVE_UPLOADS_DIR.get_option('zing_ws_dumpfile').'.zip',ZIPARCHIVE::OVERWRITE)) === TRUE) {
		if ($handle = opendir(ZING_WSLIVE_UPLOADS_DIR)) {
			while (false !== ($file = readdir($handle))) {
				$dir=ZING_WSLIVE_UPLOADS_DIR.$file;
				if (strstr($file,'migrate')) {
					//echo '<br />zip:'.$dir;
					$zip->addFile($dir,$file);
					$files[]=$dir;
				}
			}
		}
		$zip->close();
	} else {
		$success=false;
	}

	if (count($files) > 0) {
		foreach ($files as $file) {
			unlink($file);
		}
	}

	return $success;
}

function zing_ws_database_dump() {
	$tables=array();

	//definitions only
	$tables[]=array('name' => 'accesslog', 'definition' => true, 'data' => false, 'auto' => false);
	$tables[]=array('name' => 'errorlog', 'definition' => true, 'data' => false, 'auto' => false);

	//definitions and data
	$tables[]=array('name' => 'address', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'bannedip', 'definition' => true, 'data_flat' => false, 'auto' => false);
	$tables[]=array('name' => 'basket', 'definition' => true, 'data_flat' => false, 'auto' => false);
	$tables[]=array('name' => 'category', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'customer', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'discount', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'faccess', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'faces', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'flink', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'frole', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'group', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'order', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'payment', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'paypal_cart_info', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'paypal_payment_info', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'paypal_subscription_info', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'product', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'prompt', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'settings', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'shipping', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'shipping_payment', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'shipping_weight', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'task', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'taxcategory', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'taxes', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'taxrates', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'template', 'definition' => true, 'data_flat' => true);
	$tables[]=array('name' => 'transactions', 'definition' => true, 'data_flat' => true);

	//definitions and selected data
	//$tables[]=array('name' => 'user', 'definition' => true, 'data' => true, 'filter' => 'id=1', 'fields' => array('ID','LOGINNAME','PASSWORD','LASTNAME','EMAIL','GROUP'));

	$db=new db();
	$dump=$db->export($tables);

	return $dump;
}

function zing_ws_active_install() {
	global $dbtablesprefix;
	if ($dbtablesprefix) {
		$sql = mysql_query("show tables like '".$dbtablesprefix."%'");
		if (mysql_num_rows($sql)) return true;
	}
	return false;
}