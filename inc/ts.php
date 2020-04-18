<?php
function get_timestamps ($after=null) {
  global $db_conn;

  $where = '';
  if ($after) {
    $timestamp = (new DateTime($_REQUEST['ts']))->format('Y-m-d H:i:s');
    $where = "where ts>" . $db_conn->quote($timestamp);
  }

  $res = $db_conn->query("select id, ts from __system__ {$where}");
  while ($elem = $res->fetch()) {
    if ($after) {
      $result['__system__'][] = $elem['id'];
    }
    $timestamp = max($timestamp, $elem['ts']);
  }
  $res->closeCursor();

  if ($after) {
    $result['entries'] = array();
  }
  foreach (get_db_tables() as $table) {
    if ($table->data('ts')) {
      $entries = $table->entries_timestamps($after);
      if (sizeof($entries)) {
        $timestamp = max($timestamp, max($entries));
        if ($after) {
          $result['entries'][$table->id] = array_keys($entries);
        }
      }
    }
  }

  $result['ts'] = (new DateTime($timestamp))->format('Y-m-d\TH:i:s');

  return $result;
}

register_hook('init', function () {
  $ts = get_timestamps();
  if ($ts && array_key_exists('ts', $ts)) {
    html_export_var(array('ts' => $ts['ts']));
  }
});
