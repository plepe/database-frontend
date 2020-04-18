<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php session_start(); ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: text/plain; charset=utf-8'); ?>
<?php
$result = get_timestamps(array_key_exists('ts', $_REQUEST) ? $_REQUEST['ts'] : null);

print json_readable_encode($result);
