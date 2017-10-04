<?php
class Page_index extends Page {
  function content($param) {
    global $app;

    if(!base_access('view')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "index")));
      return "Permission denied.";
    }

    $data = array('tables' => array());
    foreach(get_db_tables() as $type) {
      $data['tables'][] = $type->view();
    }

    return array(
      'template' => "index.html",
      'data' => $data,
      'app' => $app,
      'table_list' => get_db_table_names(),
    );
  }
}
