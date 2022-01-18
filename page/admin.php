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

    $maintainance_actions = array();
    call_hooks('admin_maintainance_actions', $maintainance_actions);
    $data['maintainance_actions'] = $maintainance_actions;

    if (array_key_exists('action', $_REQUEST)) {
      call_user_func($data['maintainance_actions'][$_REQUEST['action']]['action']);
    }

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
