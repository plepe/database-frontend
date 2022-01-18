<?php require "inc/bootstrap.php"; ?>
<?php Header('Content-Type: application/json; charset=utf-8'); ?>

<?php
$field = get_db_table($_REQUEST['table'])->field($_REQUEST['field']);
if (isset($field->generator)) {
  $list = array();
  for ($i = 0; $i < 32; $i++) {
    $list[] = $field->generator->get();
  }
  print json_readable_encode($list);
}
