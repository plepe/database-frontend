<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php session_start(); ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: text/plain; charset=utf-8'); ?>
<?php
$result = array();

$ts = '';
$timestamp = '';
if (array_key_exists('ts', $_REQUEST)) {
  $ts = "where ts>" . $db_conn->quote((new DateTime($_REQUEST['ts']))->format('Y-m-d H:i:s'));
  $timestamp = $_REQUEST['ts'];
}

$res = $db_conn->query("select id, ts from __system__ {$ts}");
while ($elem = $res->fetch()) {
  $result['__system__'][] = $elem['id'];
  $timestamp = max($timestamp, $elem['ts']);
}
$res->closeCursor();

$result['entries'] = array();
foreach (get_db_tables() as $table) {
  if ($table->data('ts')) {
    $res = $db_conn->query("select id, ts from " . $db_conn->quoteIdent($table->id) . ' ' . $ts);
    $entries = array();
    while ($elem = $res->fetch()) {
      $entries[] = $elem['id'];
      $timestamp = max($timestamp, $elem['ts']);
    }
    $res->closeCursor();

    if (sizeof($entries)) {
      $result['entries'][$table->id] = $entries;
    }
  }
}

$result['ts'] = (new DateTime($timestamp))->format('Y-m-d\TH:i:s');

print json_readable_encode($result);
