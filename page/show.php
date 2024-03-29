<?php
class Page_show extends Page {
  function content() {
    global $app;

    $table = get_db_table($this->param['table']);
    if(!$table)
      return null;

    if(!base_access('view') || !access($table->data('access_view'))) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "show", "table" => $this->param['table'], "id" => $this->param['id'])));
      return "Permission denied.";
    }

    $ret = "";

    $object = $table->get_entry($this->param['id']);
    if(!$object)
      return null;

    $table_extract = new DB_TableExtract($table);

    // if no 'view'-parameter is set, use session or view with lowest weight
    if(!isset($this->param['view'])) {
      if(array_key_exists("{$table->id}_view_show", $_SESSION))
        $view = $_SESSION["{$table->id}_view_show"];
      else
        $view = $table->data('default_view_show');
    }
    else {
      $view = $this->param['view'];
      $_SESSION["{$table->id}_view_show"] = $view;
    }
    $this->param['view'] = $view;

    $def = $table->view_def($view);
    if ($def === false) {
      $def = $table->view_def('default');
    }

    if(array_key_exists('class', $def)) {
      $view_class = "View_{$def['class']}";
      $view = new $view_class($def, $this->param);
    }
    else {
      $view = new View_Table($def, $this->param);
    }

    $filter_values = get_filter($this->param);
    $table_extract->set_filter($filter_values);
    $pager_index = $table_extract->index($object->id);

    $extract = new DB_TableExtract($table);
    $extract->set_ids(array($object->id));
    $view->set_extract($extract);

    // compile pager data
    $pager = array(
      'index' => $pager_index,
      'result_count' => $table_extract->count(),
    );
    $ob = array_values($table_extract->get(0, 1));
    if(sizeof($ob))
      $pager['first'] = $ob[0]->id;

    $ob = array_values($table_extract->get($table_extract->count() - 1, 1));
    if(sizeof($ob))
      $pager['last'] = $ob[0]->id;

    if($pager_index === false) {
    }
    elseif($pager_index === 0) {
      $ob = array_values($table_extract->get(1, 1));
      $pager['next'] = $ob[0]->id;
    }
    else {
      $ob = array_values($table_extract->get($pager_index - 1, 3));
      if(sizeof($ob) > 2) {
        $pager['prev'] = $ob[0]->id;
        $pager['next'] = $ob[2]->id;
      }
      elseif(sizeof($ob) > 1) {
        $pager['prev'] = $ob[0]->id;
      }
    }

    return array(
      'template' => "show.html",
      'table' => $this->param['table'],
      'table_name' => $table->name(),
      'id' => $this->param['id'],
      'title' => $object->title(),
      'view' => $view,
      'param' => $this->param,
      'views' => $table->views('show'),
      'data' => $object->view(),
      'pager' => $pager,
      'filter' => get_filter_form($this->param),
      'filter_values' => $filter_values,
      'app' => $app,
      'table_list' => get_db_table_names(),
    );
  }
}
