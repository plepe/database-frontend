<?php
class Page_logout {
  function content($param) {
    global $auth;

    $auth->clear_authentication();
    page_reload(array());

    return "You have been logged out.";
  }
}
