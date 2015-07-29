<?php
register_hook("init", function() {
  global $auth;

  $auth = new Auth();
});

function auth_user_info() {
  global $auth;

  if($auth->is_logged_in()) {
    return "Logged in as " . $auth->current_user()->name() . ". <a href='" . page_url(array("page" => "logout")) . "'>Logout</a>";
  }
  else {
    return "<a href='" . page_url(array("page" => "login")) . "'>Login</a>";
  }
}
