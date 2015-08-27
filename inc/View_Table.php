<?php
class View_Table extends View {
  function show() {
    $view = new table($this->def['fields'], array($this->data[0]), array("template_engine"=>"twig"));
    return $view->show('html-transposed');
  }

  function show_list() {
    $this->def['fields']['__links'] = array(
      "name" => "",
      "format" => 
        "<a class='TableLink' href='" .  
	strtr(page_url(array('page' => 'show', 'table' => $this->param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Show</a> <a class='TableLink' href='" .
	strtr(page_url(array('page' => 'edit', 'table' => $this->param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Edit</a>",
    );

    $view = new table($this->def['fields'], $this->data, array("template_engine"=>"twig"));

    return $view->show();
  }
}
