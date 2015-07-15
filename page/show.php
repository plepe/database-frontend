<?php
class Page_show extends Page {
  function content($param) {
    $ret = "";

    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    $object = get_db_entry($param['table'], $param['id']);
    if(!$object)
      return null;

    // if no 'view'-parameter is set, use view with lowest weight
    if(!isset($param['view'])) {
      $view = $table->default_view('show');

      page_reload($this->url() . "&view=" . urlencode($view));
    }
    else {
      $view = $param['view'];
    }

    $def = $table->view_def($view);
    $table = new table($def, array($object->view()), array("template_engine"=>"twig"));

    return array(
      'template' => "show.html",
      'table' => $param['table'],
      'id' => $param['id'],
      'view' => $table,
      'data' => $object->view(),
    );
  }
}
