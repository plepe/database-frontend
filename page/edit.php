<?php
class Page_edit extends Page {
  function content($param) {
    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    if(isset($param['id'])) {
      $ob = get_db_entry($param['table'], $param['id']);
      if(!$ob)
	return null;
    }

    $form = new form("data", $table->def());

    if($form->is_complete()) {
      $data = $form->get_data();
      if(!isset($param['id']))
	$ob = new DB_Entry($param['table'], null);

      $ob->save($data, $param['message']);

      page_reload(page_url(array("page" => "show", "table" => $param['table'], "id" => $ob->id)));
    }

    if($form->is_empty()) {
      if(isset($ob)) {
	$form->set_data($ob->data);
      }
    }

    return array(
      'template' => 'edit.html',
      'table' => $param['table'],
      'id' => $param['id'],
      'form' => $form,
      'data' => $ob ? $ob->view() : null,
    );
  }
}

