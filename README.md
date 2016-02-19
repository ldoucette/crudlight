# crudlight
This is a simple php class that creates and update a table using MySQL

A sample file that I used to test my script

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>test for db</title>
	</head>
	<body>
<?php

require_once "CRUDLight.php";

	$dbo = new CRUDLight();
	$tables = $dbo->create('Person');
	echo "<pre>";
	print_r($dbo->getTableList());
	echo "</pre>";
	$dbo->setTable("Person");
	echo "<pre>get column";
	print_r($dbo->getColumns());
	echo "</pre>";
	$dbo->beginAddColumn("Person");
	$dbo->addColumn("fname", array("type"=>"string"));
	$dbo->addColumn("age", array("type"=>"integer"));
	$dbo->commitColumn();
	echo "<br />with added columns<pre>";
	print_r($dbo->getColumns());
	echo "</pre>";
	$dbo->deleteColumn("Person", "age");
	echo "<br />with deleted age column<pre>";
	print_r($dbo->getColumns());
	echo "</pre>";
	$dbo->deleteTable("Person");

/*
  sample script for query
  $dbo->query("select * from person where id = :id", array(id=>33));
	$dbo = null;	
								 */
?>

</body>
</html>
