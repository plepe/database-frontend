<?php
class Page_list extends Page {
  function content($param) {
    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    $data = array();
    foreach(get_db_entries($param['table']) as $o) {
      $data[$o->id] = $o->view();
    }

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
      $view = new $view_class($def);
    }
    else {
      $view = new View_Table($def);
    }

    $view->set_data($data);

    return array(
      'template' => 'list.html',
      'table' => $param['table'],
      'view' => $view,
      'param' => $param,
      'views' => $table->views('list'),
    );
  }
}
