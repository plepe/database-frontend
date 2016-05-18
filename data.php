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
<?php
$system = new DB_System($db);
$data = array();

if(!array_key_exists('table', $_REQUEST)) {
  $data['data'] = array();

  foreach(get_db_tables() as $table) {
    $d = $table->view();

    $data['data'][$table->id] = array(
      'id' => $d['id'],
      'name' => $d['name'],
      'data' => $d,
    );
  }

  $data['count'] = count($data['data']);
}
else {
  $table = get_db_table($_REQUEST['table']);

  if(array_key_exists('id', $_REQUEST)) {
    $entry = $table->get_entry($_REQUEST['id']);
    $data = $entry->view();
  }
  elseif(array_key_exists('ids', $_REQUEST)) {
    if(is_string($_REQUEST['ids']))
      $ids = explode(",", $_REQUEST['ids']);
    else
      $ids = $_REQUEST['ids'];

    $data['data'] = array();
    foreach($table->get_entries_by_id($ids) as $entry) {
      $data['data'][$entry->id] = $entry->view();
    }

    $data['count'] = sizeof($data['data']);
  }
  elseif(array_key_exists('filter', $_REQUEST)) {
    $filter = array();
    if(is_array($_REQUEST['filter'])) {
      foreach($_REQUEST['filter'] as $f) {
        if(is_string($f)) {
          $f = explode(",", $f);
          $filter[] = array(
            'key' => $f[0],
            'op' => $f[1],
            'value' => $f[2],
          );
        }
        else {
          $filter[] = $f;
        }
      }
    }

    $sort = null;
    if(array_key_exists('sort', $_REQUEST))
      $sort[] = array('key' => $_REQUEST['sort'], 'dir' => 'asc');

    $offset = 0;
    if(array_key_exists('offset', $_REQUEST))
      $offset = $_REQUEST['offset'];

    $limit = 25;
    if(array_key_exists('limit', $_REQUEST))
      $limit = $_REQUEST['limit'];

    $data['count'] = $table->get_entry_count();
    $data['data'] = array();
    foreach($table->get_entries($filter, $sort, $offset, $limit) as $entry) {
      $data['data'][] = $entry->id;
    }
  }
  else {
    $data = $table->view();
  }
}

Header('Content-Type: application/json; charset=utf-8');
print json_readable_encode($data);
