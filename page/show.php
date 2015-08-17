<?php
class Page_show extends Page {
  function content($param) {
    if(!base_access('view')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "show", "table" => $param['table'], "id" => $param['id'])));
      return "Permission denied.";
    }

    $ret = "";

    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    $object = get_db_entry($param['table'], $param['id']);
    if(!$object)
      return null;

    // if no 'view'-parameter is set, use session or view with lowest weight
    if(!isset($param['view'])) {
      if(array_key_exists("{$table->id}_view_show", $_SESSION))
        $view = $_SESSION["{$table->id}_view_show"];
      else
        $view = $table->default_view('show');
    }
    else {
      $view = $param['view'];
      $_SESSION["{$table->id}_view_show"] = $view;
    }
    $param['view'] = $view;

    $def = $table->view_def($view);

    if(array_key_exists('class', $def)) {
      $view_class = "View_{$def['class']}";
      $view = new $view_class($def, $param);
    }
    else {
      $view = new View_Table($def, $param);
    }

    $view->set_data(array($object->view()));

    return array(
      'template' => "show.html",
      'table' => $param['table'],
      'id' => $param['id'],
      'view' => $view,
      'param' => $param,
      'views' => $table->views('show'),
      'data' => $object->view(),
    );
  }
}
