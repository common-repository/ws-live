<?php
if (!class_exists('db')) {
	class db {
		var $numrows;
		var $sql="";
		var $row;
		var $table;

		function select($query) {
			global $dbtablesprefix;
			$action="";
			$query=str_replace("##",$dbtablesprefix,$query);
			$this->sql = mysql_query($query) or die($this->error($query));
			$this->numrows=mysql_num_rows($this->sql);
			if ($this->numrows == 0) return false; else return $this->numrows;

		}

		function next() {
			if ($this->row=mysql_fetch_assoc($this->sql)) {
				return $this->row;
			} else { return false; }
		}

		function exists($table) {
			global $dbtablesprefix;
			$query="SELECT * FROM `".$dbtablesprefix.$table."`";
			if (mysql_query($query)) return true;
			else return false;
		}

		function fieldExists($table,$field) {
			global $dbtablesprefix;
			$query = "SHOW COLUMNS FROM `".$dbtablesprefix.$table."` ";
			if ($result=mysql_query($query)) {
				while ($row = mysql_fetch_row($result)) {
					if ($row[0]==$field || $row[0]==strtoupper($field) || $row[0]==strtolower($field)) return true;
				}
			}
			return false;
		}
			
		function allFields($table) {
			global $dbtablesprefix;
			$fields=array();
			$query = "SHOW COLUMNS FROM `".$dbtablesprefix.$table."` ";
			if ($result=mysql_query($query)) {
				while ($row = mysql_fetch_row($result)) {
					$fields[]=strtolower($row[0]);
				}
			}
			return $fields;
		}

		function update($query) {
			global $dbtablesprefix;
			$query=str_replace("##",$dbtablesprefix,$query);
			if ($sql = mysql_query($query)) {
	//			if (function_exists('zfDumpQuery')) zfDumpQuery($table,$query);
			} else {
				die($this->error($query));
			}
			return $sql;
		}

		function error($query='') {
			$msg="Database Error:".$query."/".mysql_error();
			echo $msg;
			if (function_exists("zing_ws_error_handler")) zing_ws_error_handler(0,$msg);
			elseif (function_exists("zing_apps_error_handler")) zing_apps_error_handler(0,$msg);
			die();
		}

		function get($field) {
			if (!empty($this->row[$field])) return $this->row[$field];
			$field=strtolower($field);
			if (!empty($this->row[$field])) return $this->row[$field];
			$field=strtoupper($field);
			if (!empty($this->row[$field])) return $this->row[$field];
			return false;
		}

		function readRecord($table,$keys,$action="")
		{

			Global $dbtablesprefix;

			$query="SELECT * FROM `".$dbtablesprefix.$table."` ";
			$first=TRUE;
			foreach ($keys as $field => $val)
			{

				if ($first)	{ $query.="WHERE "; } else { $query.=" AND "; }
				$first=FALSE;
				$query.="`".$field."`=".qs($val);

			}

			//	echo $query."<br />";
			$sql = mysql_query($query) or die($this->error($query));
			$numrows=mysql_num_rows($sql);

			if ($numrows == 0) return false;

			$row=mysql_fetch_assoc($sql);
			return $row;
		}


		function updateRecord($table,$keys,$row,$action="")
		{

			Global $dbtablesprefix;

			$query="UPDATE `".$dbtablesprefix.$table."` ";
			$first=TRUE;
			foreach ($row as $field => $val)
			{
				$iskey=FALSE;
				foreach ($keys as $keyfield => $keyval)
				{
					if ($field == $keyfield) { $iskey=TRUE; }
				}
				if (!$iskey)
				{
					if ($first)	{ $query.="SET "; } else { $query.=","; }
					$first=FALSE;
					$query.="`".$field."`=".qs($val);
				}
			}
			$first=TRUE;
			foreach ($keys as $keyfield => $keyval)
			{
				if ($first){ $query.=" WHERE "; } else { $query.=" AND "; }
				$first=FALSE;
				$query.= "`".$keyfield."`=".qs($keyval);
			}


			//zing_ws_error_handler(0,$query);
			
			if ($sql_update = mysql_query($query)) {
				if (function_exists('zfDumpQuery')) zfDumpQuery($query,$table);
			} else {
				die($this->error($query));
			}
							
		}

		function insertRecord($table,$keys="",$row,$action="")
		{
			global $dbtablesprefix;

			$query="INSERT INTO `".$dbtablesprefix.$table."` ";
			$first=TRUE;
			foreach ($row as $field => $val)
			{
				$iskey=FALSE;
				if (!empty($keys))
				{
					foreach ($keys as $keyfield => $keyval)
					{
						if ($field == $keyfield) { $iskey=TRUE; }
					}
				}
				if (!$iskey)
				{
					if ($first)	{ $query.="("; } else { $query.=","; }
					$first=FALSE;
					$query.="`".$field."`";
				}
			}
			$query.=") VALUES ";
			$first=TRUE;
			foreach ($row as $field => $val)
			{
				$iskey=FALSE;
				if (!empty($keys))
				{
					foreach ($keys as $keyfield => $keyval)
					{
						if ($field == $keyfield) { $iskey=TRUE; }
					}
				}
				if (!$iskey)
				{
					if ($first)	{ $query.="("; } else { $query.=","; }
					$first=FALSE;
					$query.=qs($val);
				}
			}
			$query.=")";
			//echo $query."<br />";
			$sql_insert = mysql_query($query) or die($this->error($query));
			$id = mysql_insert_id();

			return $id;
		}

		function deleteRecord($table,$keys,$action="")
		{
			Global $dbtablesprefix;

			$query="DELETE FROM `".$dbtablesprefix.$table."` ";
			$first=TRUE;
			foreach ($keys as $field => $val)
			{
				if ($first)	{ $query.="WHERE "; } else { $query.=" AND "; }
				$first=FALSE;
				$query.="`".$field."`=".qs($val);
			}

			//	echo $query."<br />";
			$sql = mysql_query($query) or die($this->dbError(1,$query,"",$action));
		}

		function txbegin()
		{
			Global $txglobal;

			$txglobal=TRUE;
			$query="START TRANSACTION";
			$sql=mysql_query($query) or die($this->error($query));
		}

		function txcommit()
		{
			Global $txglobal;

			if ($txglobal)
			{
				$query="COMMIT";
				$sql=mysql_query($query) or die($this->error($query));
			}
			$txglobal=FALSE;
		}

		function txrollback()
		{
			Global $txglobal;

			if ($txglobal)
			{
				$query="ROLLBACK";
				$sql=mysql_query($query) or die($this->error($query));
			}
			$txglobal=FALSE;
		}

		function dbError($severity, $query, $page, $action)
		{
			Global $gfx_dir;
			Global $txt;
			Global $dbError;
			Global $channel;
			Global $error;
			Global $errormsg;

			$dbError=1;
			$sql=mysql_error();

			$this->txrollback();

			echo "ERROR:".$severity."-".$query."-".$sql;

		}

		function export($tables)
		{
			global $dbtablesprefix;
				
			$this->exported='';
			foreach($tables as $t)
			{
				$this->table = $t['name'];
				$this->fields='';
				$query="SHOW FULL COLUMNS FROM `".$dbtablesprefix.$this->table."`";
				$sql = mysql_query($query) or die($this->error($query));
				while ($row=mysql_fetch_assoc($sql)) {
					$this->fields[$row['Field']]=$row;
				}
				$this->exported .= "--\n";
				if ($t['definition']) {
					$header = $this->create_header($t['auto']);
					$this->exported .= "-- Table structure for table {$this->table}\n--\n\n" . $header . "\n\n";
				}
				if ($t['data']) {
					$data = $this->get_data($t['filter'],$t['fields']);
					$this->exported .= "--\n-- Dumping data for table {$this->table}\n--\n\n" . $data . "\n\n";
				} elseif ($t['data_flat']) {
					$data = $this->get_data_flat($t['filter'],$t['fields']);
					$this->exported .= "--\n-- Dumping data for table {$this->table}\n--\n\n" . $data . "\n\n";
				} 
			}

			return($this->exported);
		}

		function create_header($auto)
		{
			global $dbname,$dbtablesprefix;

			$query="SHOW CREATE TABLE `".$dbtablesprefix.$this->table."`";
			$sql = mysql_query($query) or die($this->error($query));
			$row=mysql_fetch_array($sql);
			$h=str_replace("`".$row[0]."`","`##".$this->table."`",$row[1]);
			
			if ($auto === false) $h=preg_replace('/AUTO_INCREMENT\=[0-9]* / ','',$h);

			$h=$h.';';
			return($h);
		}

		function get_data($filter='',$afields='*')
		{
			global $dbname,$dbtablesprefix;

			if (is_array($afields)) {
				$a=$this->fields;
				foreach ($a as $field => $data) {
					if (!in_array($field,$afields)) {
						unset($this->fields[$field]);
					}
				}
			}

			$fields='';
			foreach ($this->fields as $field) {
				$name = $field['Field'];
				if ($fields) $fields.=',';
				$fields .= "`$name`";
			}
				
			$d = null;
			if ($filter) $sql="SELECT " . $fields . " FROM `" . $dbtablesprefix.$this->table . "` WHERE ".$filter;
			else $sql = "SELECT " . $fields . " FROM `" . $dbtablesprefix.$this->table . "` WHERE 1";
			$data = mysql_query($sql) or $this->error();
			while($cr = mysql_fetch_array($data, MYSQL_NUM))
			{
				$d .= "INSERT INTO `##" . $this->table . "` (".$fields.") VALUES (";

				for($i=0; $i<sizeof($cr); $i++)
				{
					if($cr[$i] == '') {
						$d .= 'NULL,';
					} else {
						$d .= '"'.mysql_real_escape_string($cr[$i]).'",';
					}
				}

				$d = substr($d, 0, strlen($d) - 1);
				$d .= ");\n";
			}

			return($d);
		}

		function get_data_flat($filter='',$afields='*')
		{
			global $dbname,$dbtablesprefix;

			if (is_array($afields)) {
				$a=$this->fields;
				foreach ($a as $field => $data) {
					if (!in_array($field,$afields)) {
						unset($this->fields[$field]);
					}
				}
			}

			$fields='';
			foreach ($this->fields as $field) {
				$name = $field['Field'];
				if ($fields) $fields.=',';
				$fields .= "`$name`";
			}
				
			$d = '';
			if ($filter) $sql="SELECT " . $fields . " FROM `" . $dbtablesprefix.$this->table . "` WHERE ".$filter;
			else $sql = "SELECT " . $fields . " FROM `" . $dbtablesprefix.$this->table . "` WHERE 1";
			$data = mysql_query($sql) or $this->error();
			while($cr = mysql_fetch_array($data, MYSQL_ASSOC))
			{
				$cr['TABLE']=$this->table;
				$d .= '++';
				$d .= serialize($cr);
				$d .= "++\n";
			}

			return($d);
		}
	}
}
?>