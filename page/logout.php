<?php
class Page_logout {
  function content($param) {
    global $auth;

    $auth->clear_authentication();

    if(array_key_exists('return_to', $param)) {
      page_reload($param['return_to']);
    }
    elseif(array_key_exists('HTTP_REFERER', $_SERVER)) {
      page_reload($_SERVER['HTTP_REFERER']);
    }
    else {
      page_reload(array());
    }

    return "You have been logged out.";
  }
}
