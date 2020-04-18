<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php session_start(); ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: text/plain; charset=utf-8'); ?>
<?php
session_write_close();

$result = get_timestamps(array_key_exists('ts', $_REQUEST) ? $_REQUEST['ts'] : null);

if (array_key_exists('wait', $_REQUEST) && array_key_exists('ts', $_REQUEST) && $_REQUEST['wait'] && $_REQUEST['ts']) {
  $wait = $_REQUEST['wait'];
  $step = 5;
  while ($result['ts'] === $_REQUEST['ts'] && $wait > 0) {
    sleep($step);
    $result = get_timestamps($_REQUEST['ts']);
    $wait -= $step;
  }
}

print json_readable_encode($result);
