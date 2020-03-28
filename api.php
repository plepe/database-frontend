<?php include "conf.php"; /* load a local configuration */ ?>
<?php
if(!isset($db)) {
  print "Database not configured. Please edit conf.php.";
  exit;
}
if(!isset($data_path) || !is_dir($data_path) || !is_writeable($data_path)) {
  print "<tt>\$data_path</tt> not defined, does not exist or is not writable! Please edit conf.php.";
  exit;
}
?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php session_start(); ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: application/json; charset=utf-8'); ?>

<?php
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    if ($_REQUEST['table']) {
      $table = get_db_table($_REQUEST['table']);
      if (!$table) {
        Header('HTTP/1.0 404 Not Found');
        exit(0);
      }

      if ($_REQUEST['id']) {
        $ob = $table->get_entry($_REQUEST['id']);
        if (!$ob) {
          Header('HTTP/1.0 404 Not Found');
          exit(0);
        }

        print json_readable_encode($ob->data());
      }
      elseif ($_REQUEST['list']) {
        print json_readable_encode($table->get_entry_ids());
      }
      else {
        print json_readable_encode($table->data());
      }
    }

    break;
  case 'PATCH':
    $data = json_decode(file_get_contents("php://input"), true);

    if ($_REQUEST['table']) {
      $table = get_db_table($_REQUEST['table']);
      if (!$table) {
        Header('HTTP/1.0 404 Not Found');
        exit(0);
      }

      if ($_REQUEST['id']) {
        $ob = $table->get_entry($_REQUEST['id']);
        if (!$ob) {
          Header('HTTP/1.0 404 Not Found');
          exit(0);
        }

        $ob->save($data);
        print json_readable_encode($ob->data());
      }
      else {
        $table->save($data);
        print json_readable_encode($table->data());
      }
    }

    break;
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);

    if ($_REQUEST['table']) {
      $table = get_db_table($_REQUEST['table']);
      if (!$table) {
        Header('HTTP/1.0 404 Not Found');
        exit(0);
      }

      $ob = new DB_Entry($table->id);
      $ob->save($data);

      print json_readable_encode($ob->data());
    }

    break;
  default:
    Header('HTTP/1.0 400 Bad Request');
}
