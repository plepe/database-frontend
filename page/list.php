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

    // if no 'view'-parameter is set, use view with lowest weight
    if(!isset($param['view'])) {
      $view = $table->default_view('list');

      page_reload($this->url() . "&view=" . urlencode($view));
    }
    else {
      $view = $param['view'];
    }

    $def = $table->view_def($view);
    $def['__links'] = array(
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

    $view = new table($def, $data, array("template_engine"=>"twig"));

    return array(
      'template' => 'list.html',
      'table' => $param['table'],
      'view' => $view,
      'param' => $param,
      'views' => $table->views('show'),
    );
  }
}
