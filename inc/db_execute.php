<?php
function db_execute_get ($results, $path) {
  $v = $results[$path[0]];
  if (sizeof($path) > 1) {
    return db_execute_get($v, array_slice($path, 1));
  }
  return $v;
}

function db_execute_set (&$data, $path, $value) {
  if (sizeof($path) > 1) {
    return db_execute_set($data, array_slice($path, 1), $value);
  }
  $data[$path[0]] = $value;
}

function db_execute_references (&$data, $statement, $results) {
  if (!array_key_exists('references', $statement)) {
    return;
  }

  foreach ($statement['references'] as $k => $r) {
    $value_path = explode('.', $k);
    $ref_path = explode('.', $r);

    $value = db_execute_get($results, $ref_path);
    db_execute_set($data, $value_path, $value);
  }
}

function db_execute ($script, $changeset) {
  $results = array();

  foreach ($script as $i => $statement) {
    switch ($statement['action']) {
      case 'create':
        $entry = new DB_Entry($statement['table'], null);
        $data = $statement['data'];
        db_execute_references($data, $statement, $results);
        $entry->save($data, $changeset);
        $results[$i] = $entry->view();
        break;
      case 'update':
        $entry = get_db_table($statement['table'])->get_entry($statement['id']);
        $data = $statement['data'];
        db_execute_references($data, $statement, $results);
        $entry->save($data, $changeset);
        $results[$i] = $entry->view();
        break;
      case 'delete':
        $entry = get_db_table($statement['table'])->get_entry($statement['id']);
        $entry->remove($changeset);
        break;
      case 'select':
        $entry = get_db_table($statement['table'])->get_entry($statement['id']);
        $results[$i] = $entry->view();
        break;
      default:
        throw new Exception("No such action {$statement['action']}");
    }
  }

  return $results;
}
