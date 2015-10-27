<?php
register_hook("init", function() {
  global $auth;

  if(isset($_SERVER['REMOTE_USER'])) {
    $user = $auth->get_user($_SERVER['REMOTE_USER']);
    $auth->set_current_user($user);
  }
});

function base_access($access) {
  global $auth;
  global $base_access;

  if(!isset($base_access))
    return true;

  if(!array_key_exists($access, $base_access))
    return true;

  return $auth->access($base_access[$access]);
}

function access($access) {
  global $auth;

  return $auth->access($access);
}
