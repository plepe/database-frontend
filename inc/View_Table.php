<?php
class View_Table extends View {
  function show() {
    $view = new table($this->def['fields'], $this->extract, array("template_engine"=>"twig"));
    return $view->show('html-transposed');
  }

  function show_list() {
    $first_field = array_keys($this->def['fields'])[0];
    $this->def['fields'][$first_field]['format'] =
      "<a class='TableLink' href='" .
      strtr(page_url(array('page' => 'show', 'table' => $this->param['table'], 'id' => "ID")), array("ID" => "{{ id }}")) .
      "'>" .
      $this->def['fields'][$first_field]['format'] .
      "</a>" .
      "<a title='edit' class='edit' href='" .
      strtr(page_url(array('page' => 'edit', 'table' => $this->param['table'], 'id' => "ID")), array("ID" => "{{ id }}")) .
      "'><img src='images/edit.png'></a>";

    $view = new table($this->def['fields'], $this->extract, array("template_engine"=>"twig"));

    return $view->show('html', $this->param);
  }
}
