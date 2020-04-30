<?php require "inc/bootstrap.php"; /* load a local configuration */ ?>
<?php
$user_settings = $auth->current_user()->settings();
$user_settings->save($_REQUEST);
