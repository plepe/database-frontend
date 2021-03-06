<?php
class Page_admin {
  function content() {
    global $app;

    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "admin")));
      return "Permission denied.";
    }

    $data = array(
      'tables' => array()
    );

    foreach(get_db_tables() as $type) {
      $data['tables'][] = $type->view();
    }

    return array(
      'template' => "admin.html",
      'data' => $data,
      'app' => $app,
    );
  }
}
