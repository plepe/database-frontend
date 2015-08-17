<?php
register_hook("init", function() {
  global $auth;

  $auth = new Auth();

  if(isset($_SERVER['REMOTE_USER'])) {
    $user = $auth->get_user($_SERVER['REMOTE_USER']);
    $auth->set_current_user($user);
  }
});

function auth_user_info() {
  global $auth;
  $ret = "";

  if($auth->is_logged_in()) {
    $ret .= "Logged in as " . $auth->current_user()->name() . ".";
    if(!isset($_SERVER['REMOTE_USER']))
      $ret .= " <a href='" . page_url(array("page" => "logout")) . "'>Logout</a>";
  }
  else {
    if(!isset($_SERVER['REMOTE_USER']))
      $ret .= "<a href='" . page_url(array("page" => "login")) . "'>Login</a>";
  }

  return $ret;
}

function base_access($access) {
  global $auth;
  global $base_access;

  if(!isset($base_access))
    return true;

  if(!array_key_exists($access, $base_access))
    return true;

  return $auth->access($base_access[$access]);
}
