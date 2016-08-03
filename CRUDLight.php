<?php
/**
 *   @author Lolita Doucette
 *   @version 2015.11
 *
 *   CRUDLight Class using MySQL
 *   Create, Read, Update, Delete  
 */

class CRUDLight {
	
	private $host;
	private $dbName;
	private $dobUser;
	private $password;
	
	private $db;
	private $tableList;
	private $table;
	
	private $columns;
	private $rows;
	
	private $fields;
					
	public function __construct() {
		parent::__construct();
		$source = parent::getDbSource();
		try {
			$this->host 	= "dbHost";  // change values here
			$this->user 	= "user";
			$this->password = "password";
			$this->dbName 	= "dbName";
		} catch (Exception $e) {
    		echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	}
	
	private function connect() {
		try {
			$this->db = new PDO("mysql:host=$this->host;dbname=$this->dbName", $this->user, $this->password);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
		return true;
	}

	public function create($table) {
		$table = strtolower($table);
		try {
			$this->connect();
			if (!$this->tableExists($table))  {
				$sql = "CREATE TABLE `$table` (
						`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)";
				$this->db->exec($sql);
			}	else {
				 echo '<br />Table already exists.';
			}
			$this->close();
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
	}
	
	private function tableExists($table) {
		$table = strtolower($table);
		try {
			$result = $this->db->query("SELECT 1 FROM $table LIMIT 1");
		} catch (Exception $e) {
			return FALSE;
		}
		return $result !== FALSE;
	}

	private function setTables() {
		$this->connect();
		try {
			$sql = "SHOW TABLES FROM " . $this->dbName;
			$result = $this->db->query($sql);
			if ($this->isQueryGood($result)) {
				while ($rows = $result->fetch(PDO::FETCH_NUM)) {
					foreach($rows as $row) {
						$this->tableList[] = $row;
					}
				}
				$result=null;
			}
		} catch (PDOException $e) {
			echo 'Show failed: ' . $e->getMessage();
		}
		$this->close();
	}
			
	private function setColumns () {
		try {
			$this->connect();
			$sql = 'DESCRIBE ' . $this->table;
			$q = $this->db->prepare($sql);
			$q->execute();
			$this->columns = $q->fetchAll(PDO::FETCH_COLUMN);
			$this->close();
		} catch (PDOException $e) {
			echo 'Show failed: ' . $e->getMessage();
		}
	}
	
	public function getTableList() {
		if ($this->tableList==null) 
			$this->setTables();
		return $this->tableList;
	}
	
	public function getColumns() {
		if (is_null($this->columns))  {
			$this->setColumns();
		}
		return $this->columns;
	}
	
	private function isTableInDB($table) {
		$table = strtolower($table);
		try {
			if (!isset($this->tableList)) {
				$this->tableList = $this->getTableList();
			}
			if (in_array($table, $this->tableList)) {
				return true;		
			}
		    return false; 
		} catch (Exception $e) {
    		echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	}
	
	private function isValueValid($strCheck) {
		if (is_numeric($strCheck) || is_null($strCheck) || empty($strCheck)) {
			throw new Exception('Invalid value.');
		}
		return $strCheck;
	}
	
	private function isQueryGood($result) {
		if ($result) {
			return true;
		}
		echo "<br />oops! you successfully connected on the db but encountered an error on fetching tables.";
		return false;
	}

	public function setTable($table) {
		$table = strtolower($table);
		try {
			$this->connect();
			if ($this->isTableInDB($table)) {
				$this->isValueValid($table);
				$this->table = $table;
			} else
				echo "<br />" . $table . " is not in Database.";
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		$this->close();
	}
	
	public function beginAddColumn($table) {
		$this->setTable($table);
		$this->fields = null;
	}
		
	public function addColumn($name, $description) {
		if (!in_array($needle, $this->columns)) {
			$field["name"] = $name;
			$field["desc"] = $description;
			$this->fields[] = $field;
		}
	}
		
	private function getType($key, $val) {
		if (strcasecmp(trim($key), "type")==0) {
			$val = strtoupper($val);
			switch ($val) {
				case "STRING":
					$type = " VARCHAR(255) ";
					break;
				case "INTEGER":
					$type = " INT ";
					break;
				case "TEXT":
					$type = " TEXT ";
					break;
				case "DATE":
					$type = " DATE ";
					break;
				case "DECIMAL":
					$type = " DECIMAL ";
					break;
				case "BLOB":
					$type = " BLOB ";
					break;
				default:
					echo "Type not part of CRUDLight.";
			}
			return $type;
		}
	}
	
	public function commitColumn () {
		try {
			$this->connect();
			if ($this->db->inTransaction())
				$this->db->rollBack();
			$this->db->beginTransaction();
			if ($this->db->inTransaction()) {
					$sql = "ALTER TABLE `$this->table` ";
					$count = count($this->fields);
					$counter = 0;
					foreach ($this->fields as $field) {
						$sql .= "ADD COLUMN `" . $field['name'] . "` ";
						foreach ($field['desc'] as $key => $val) {
							$sql .= $this->getType($key, $val);
							if (strcasecmp($key, "nonull")==0) {
								if ($val == 1) 
									$sql .= " NOT NULL ";
							}
							if (strcasecmp($key, "auto")==0) {
								if ($val == 1)
									$sql .= " AUTO_INCREMENT ";
							}
							if (strcasecmp($key, "unsigned")==0) {
								if ($val == 1)
									$sql .= " UNSIGNED ";
							}
							if ($count > 1 && $field != end($this->fields)) 
								$sql .= ", ";
						}
				}
				$result = $this->db->exec($sql);
				if (!$result) {
					$this->db->commit();
				}
				$this->close();
				$this->setColumns();
			}
		} catch (Exception $e) {
    		echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	}
	
	public function deleteColumn($table, $field) {
		$table = strtolower($table);
		try {
			$this->connect();
			if (!$this->isTableInDB($table)) {
				return;
			}
			$sql = "ALTER TABLE `$table` DROP COLUMN `$field`";
			$sql=$this->db->prepare($sql);
			if($sql->execute()){
				echo " Property deleted ";
			} else {
				print_r($sql->errorInfo());
			}
			$this->close();
			$this->setColumns();
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	}
	
	public function deleteTable($table) {
		$table = strtolower($table);
		try {
			$this->connect();
			if (!$this->isTableInDB($table)) {
				return;
			}
			$sql = "DROP TABLE $table";
			$sql=$this->db->prepare($sql);
			if($sql->execute()){
				echo " Table deleted ";
			}else{
				print_r($sql->errorInfo());
			}
			$this->close();
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	}	
		
	private function close() {
		if (!is_null($this->db))
			$this->db = null;
	}
	
}

?>
