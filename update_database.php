<?php require "inc/bootstrap.php";
<?php
$db_tables = get_db_tables();

$ret = "";

foreach($db_tables as $id=>$ob) {
  $ret = $ob->update_database_structure();
}
