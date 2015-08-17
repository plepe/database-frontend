<?php
class Page_index extends Page {
  function content($param) {
    if(!base_access('view')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "index")));
      return "Permission denied.";
    }

    foreach(get_db_tables() as $type) {
      $data['tables'][] = $type->view();
    }

    return array(
      'template' => "index.html",
      'data' => $data,
    );
  }
}
