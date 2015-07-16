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
    $def['fields']['__links'] = array(
      "name" => "",
      "format" => 
        "<a href='" .  
	strtr(page_url(array('page' => 'show', 'table' => $param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Show</a> <a href='" .
	strtr(page_url(array('page' => 'edit', 'table' => $param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Edit</a>",
    );

    $view = new table($def['fields'], $data, array("template_engine"=>"twig"));

    return array(
      'template' => 'list.html',
      'table' => $param['table'],
      'view' => $view,
      'param' => $param,
      'views' => $table->views('list'),
    );
  }
}
