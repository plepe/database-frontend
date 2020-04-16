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
      $table = get_db_table_viewable($_REQUEST['table']);
      if ($table === false) {
        Header('HTTP/1.0 403 Forbidden');
        exit(0);
      }
      elseif (!$table) {
        Header('HTTP/1.0 404 Not Found');
        exit(0);
      }

      if (isset($_REQUEST['id']) && $_REQUEST['id']) {
        if (is_array($_REQUEST['id'])) {
          $data = array_values(array_map(function ($ob) { return $ob ? $ob->view() : null; }, $table->get_entries_by_id($_REQUEST['id'])));
          print json_readable_encode($data);
        }
        else {
          $ob = $table->get_entry($_REQUEST['id']);
          if (!$ob) {
            Header('HTTP/1.0 404 Not Found');
            exit(0);
          }

          print json_readable_encode($ob->view());
        }
      }
      elseif ($_REQUEST['list']) {
        if (isset($_REQUEST['full']) && $_REQUEST['full']) {
          $data = array_values(array_map(function ($ob) { return $ob->view(); }, $table->get_entries()));
          print json_readable_encode($data);
        } else {
          print json_readable_encode($table->get_entry_ids($_REQUEST['filter']));
        }
      }
      else {
        print json_readable_encode($table->view());
      }
    }
    else {
      if (isset($_REQUEST['list']) && $_REQUEST['list']) {
        if (isset($_REQUEST['full']) && $_REQUEST['full']) {
          $data = array_map(function ($table) {
            return $table->view();
          }, get_db_tables_viewable());

          print json_readable_encode(array_values($data));
        }
        else {
          $data = array_keys(get_db_tables_viewable());

          print json_readable_encode($data);
        }
      }
    }

    break;
  case 'PATCH':
    $data = json_decode(file_get_contents("php://input"), true);
    $changeset = new Changeset('');
    $changeset->open();

    if ($_REQUEST['table']) {
      $table = get_db_table_viewable($_REQUEST['table']);
      if ($table === false) {
        Header('HTTP/1.0 403 Forbidden');
        exit(0);
      }
      elseif (!$table) {
        Header('HTTP/1.0 404 Not Found');
        exit(0);
      }
      elseif (!$table->access('edit')) {
        Header('HTTP/1.0 403 Forbidden');
        exit(0);
      }

      if ($_REQUEST['id']) {
        $ob = $table->get_entry($_REQUEST['id']);
        if (!$ob) {
          Header('HTTP/1.0 404 Not Found');
          exit(0);
        }

        $ob->save($data, $changeset);

        print json_readable_encode($ob->view());
      }
      else {
        $table->save($data, $changeset);

        print json_readable_encode($table->view());
      }
    }

    $changeset->commit();

    break;
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $changeset = new Changeset('');
    $changeset->open();

    if (array_key_exists('script', $_REQUEST)) {
      try {
        $result = db_execute($data, $changeset);
      }
      catch (Exception $e) {
        Header('HTTP/1.0 403 ' . $e->getMessage());
        exit(0);
      }
      print json_readable_encode($result);
    }
    else if ($_REQUEST['table']) {
      $table = get_db_table_viewable($_REQUEST['table']);
      if ($table === false) {
        Header('HTTP/1.0 403 Forbidden');
        exit(0);
      }
      elseif (!$table) {
        Header('HTTP/1.0 404 Not Found');
        exit(0);
      }
      elseif (!$table->access('edit')) {
        Header('HTTP/1.0 403 Forbidden');
        exit(0);
      }

      $ob = new DB_Entry($table->id);
      $ob->save($data, $changeset);

      print json_readable_encode($ob->view());
    }

    $changeset->commit();

    break;
  case 'DELETE':
    $data = json_decode(file_get_contents("php://input"), true);
    $changeset = new Changeset('');
    $changeset->open();

    if ($_REQUEST['table']) {
      $table = get_db_table($_REQUEST['table']);
      if ($table === false) {
        Header('HTTP/1.0 403 Forbidden');
        exit(0);
      }
      elseif (!$table) {
        Header('HTTP/1.0 404 Not Found');
        exit(0);
      }
      elseif (!$table->access('edit')) {
        Header('HTTP/1.0 403 Forbidden');
        exit(0);
      }

      if ($_REQUEST['id']) {
        $ob = $table->get_entry($_REQUEST['id']);
        if (!$ob) {
          Header('HTTP/1.0 404 Not Found');
          exit(0);
        }

        $ob->remove($changeset);
      }
      else {
        $table->remove($changeset);
      }
    }

    $changeset->commit();

    break;
  default:
    Header('HTTP/1.0 400 Bad Request');
}
