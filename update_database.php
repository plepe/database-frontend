<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
$db_tables = get_db_tables();

$ret = "";

foreach($db_tables as $id=>$ob) {
  $ret = $ob->update_database_structure();
}
