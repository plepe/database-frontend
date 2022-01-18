<?php
class View_Table extends View {
  function show() {
    $fields = $this->def['fields'];

    foreach ($fields as $key => $def) {
      $fields[$key]['html_attributes'] = 'data-table="' . htmlentities($this->param['table']) . '" data-id="{{ id }}" data-field="' . htmlentities($def['field_id'] ?? $key) . '" data-view="' . htmlentities($this->id) . '"';
    }

    $view = new table($fields, $this->extract, array("template_engine"=>"twig"));

    return $view->show('html-transposed');
  }

  function show_list() {
    $fields = $this->def['fields'];

    $first_field = array_keys($fields)[0];
    $fields[$first_field]['format'] =
      "<a class='TableLink' href='" .
      strtr(page_url(array('page' => 'show', 'table' => $this->param['table'], 'id' => "ID")), array("ID" => "{{ id }}")) .
      "'>" .
      $fields[$first_field]['format'] .
      "</a>" .
      "<a title='edit' class='edit' href='" .
      strtr(page_url(array('page' => 'edit', 'table' => $this->param['table'], 'id' => "ID")), array("ID" => "{{ id }}")) .
      "'><img src='images/edit.png'></a>";

    foreach ($fields as $key => $def) {
      $fields[$key]['html_attributes'] = 'data-table="' . htmlentities($this->param['table']) . '" data-id="{{ id }}" data-field="' . htmlentities($def['field_id'] ?? $key) . '" data-view="' . htmlentities($this->id) . '"';
    }

    $view = new table($fields, $this->extract, array("template_engine"=>"twig"));

    return $view->show('html', $this->param);
  }
}
