<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
$object_types = get_object_types();

$ret = "";

foreach($object_types as $id=>$ob) {
  $ret = $ob->update_database_structure();
}
