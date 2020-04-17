<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php session_start(); ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: application/json; charset=utf-8'); ?>

<?php
$ret = array();
call_hooks('session_regexp_allowed', $regexp);

$data = json_decode(file_get_contents("php://input"), true);
foreach ($data as $k => $v) {
  foreach ($regexp as $r) {
    if (preg_match($r, $k)) {
      $_SESSION[$k] = $v;
    }
  }
}
