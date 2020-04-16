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
    return db_execute_set($data[$path[0]], array_slice($path, 1), $value);
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
        $table = get_db_table($statement['table']);
        if (!$table->access('edit')) {
          throw new Exception('Access denied');
        }
        $entry = new DB_Entry($statement['table'], null);
        $data = $statement['data'];
        db_execute_references($data, $statement, $results);
        $entry->save($data, $changeset);
        $results[$i] = $entry->view();
        break;
      case 'update':
        $table = get_db_table($statement['table']);
        if (!$table->access('edit')) {
          throw new Exception('Access denied');
        }
        $entry = get_db_table($statement['table'])->get_entry($statement['id']);
        $data = $statement['data'];
        db_execute_references($data, $statement, $results);
        $entry->save($data, $changeset);
        $results[$i] = $entry->view();
        break;
      case 'delete':
        $table = get_db_table($statement['table']);
        if (!$table->access('edit')) {
          throw new Exception('Access denied');
        }
        $entry = get_db_table($statement['table'])->get_entry($statement['id']);
        $entry->remove($changeset);
        break;
      case 'select':
        $table = get_db_table($statement['table']);
        if (!$table->access('view')) {
          throw new Exception('Access denied');
        }
        $entry = get_db_table($statement['table'])->get_entry($statement['id']);
        $results[$i] = $entry->view();
        break;
      case 'query_ids':
        $table = get_db_table($statement['table']);
        if (!$table->access('view')) {
          throw new Exception('Access denied');
        }
        $results[$i] = get_db_table($statement['table'])->get_entry_ids($statement['filter'] ?? null, $statement['sort'] ?? null);
        break;
      default:
        throw new Exception("No such action {$statement['action']}");
    }
  }

  return $results;
}
