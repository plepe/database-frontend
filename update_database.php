<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
$object_types = get_object_types();

$ret = "";

foreach($object_types as $id=>$ob) {
  $ret = $ob->sql_create_statement();
  // print $ret;
  $db_conn->query($ret);
}
