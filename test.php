<?php
require "json_database.php";


$db = new JsonDatabase();
try {
	$db->create_database("db");
	$db->create_table("table");
}
catch (Exception $e) {
	echo "There is no need to create these directories an folders again\n";
}

$db->connect_database("db");
// clear the table for testing
$db->remove("table",(object)[]);
// create some data
$db->insert("table",
	(object)['a'=>"a value for a",'id'=>0, 'flag'=>true]);
$db->insert("table",
	[
	(object)['id'=>1,'b'=>'a value for b'],
	(object)['id'=>2,'a'=>'another value a','b'=>'another value b','flag'=>true],
	(object)['id'=>3,'a'=>'another value a','b'=>'another value b','flag'=>false],
	(object)['a'=>'another value a','b'=>'another value b','flag'=>false]
	]);
$db->remove("table",(object)['id'=>1]);
$db->update("table", (object)['a'=>'flagged','f'=>'new column'] , (object)['flag'=>true]);
echo "Read a single line from a table:\n";
print_r($db->get_row("table", (object)['id'=>3]));

echo "Read a sub table according to a condition:\n";
$db_copy = new JsonDatabaseCopy();
$db_copy->connect_database("db");
$db_copy->printPartialTable("table",(object)['a'=>'flagged'])

?>
