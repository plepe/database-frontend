<?php
class Page_list extends Page {
  function content($param) {
    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    if(array_key_exists('limit', $param)) {
      if($param['limit'] == 0)
        $param['limit'] = null;

      $_SESSION['limit'] = $param['limit'];
    }
    else {
      if(array_key_exists('limit', $_SESSION))
        $param['limit'] = $_SESSION['limit'];
      else
        $param['limit'] = 25;
    }
    if(!array_key_exists('offset', $param))
      $param['offset'] = 0;

    if(array_key_exists('sort', $param)) {
      $_SESSION['sort'] = $param['sort'];
      $_SESSION['sort_dir'] = !array_key_exists('sort_dir', $param) ? $param['sort_dir'] : 'asc';
    }
    else {
      if(array_key_exists('sort', $_SESSION)) {
	$param['sort'] = $_SESSION['sort'];
	$param['sort_dir'] = $_SESSION['sort_dir'];
      }
      else {
        $param['sort'] = $table->data('sort');
      }
    }

    if(!base_access('view') || !access($table->data('access_view'))) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "list", "table" => $param['table'])));
      return "Permission denied.";
    }

    $table_extract = new DB_TableExtract($table);
    $filter_values = get_filter($param);
    $table_extract->set_filter($filter_values);

//    $data = array();
//    foreach($table_extract->get() as $o) {
//      $data[$o->id] = $o->view();
//    }

    // if no 'view'-parameter is set, use session or view with lowest weight
    if(!isset($param['view'])) {
      if(array_key_exists("{$table->id}_view_list", $_SESSION))
        $view = $_SESSION["{$table->id}_view_list"];
      else
        $view = $table->default_view('list');
    }
    else {
      $view = $param['view'];
      $_SESSION["{$table->id}_view_list"] = $view;
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

    $view->set_extract($table_extract);

    html_export_var(array("param" => array(
      "page" => "list",
      "table" => $param['table'],
      "offset" => array_key_exists('offset', $param) ? $param['offset'] : 0,
      "limit" => $param['limit'],
    )));

    return array(
      'template' => 'list.html',
      'table' => $param['table'],
      'result_count' => $table_extract->count(),
      'filter' => get_filter_form($param),
      'filter_values' => $filter_values,
      'view' => $view,
      'param' => $param,
      'views' => $table->views('list'),
    );
  }
}
