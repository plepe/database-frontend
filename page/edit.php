<?php
class Page_edit extends Page {
  function content($param) {
    if(!base_access('view')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "edit", "table" => $param['table'], "id" => $param['id'])));
      return "Permission denied.";
    }

    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    if(isset($param['id'])) {
      $ob = $table->get_entry($param['id']);
      if(!$ob)
	return null;
    }

    // if requested, delete entry
    if(isset($param['delete']) && $param['delete']) {
      if($ob) {
	$ob->remove($param['message']);
	messages_add("Entry deleted", MSG_NOTICE);
      }

      page_reload(page_url(array("page" => "list", "table" => $param['table'])));

      return array();
    }

    $form = new form("data", $table->def());

    if($form->is_complete()) {
      $data = $form->get_data();
      if(!isset($param['id']))
	$ob = new DB_Entry($param['table'], null);

      $result = $ob->save($data, $param['message']);

      if($result === true) {
	page_reload(page_url(array("page" => "show", "table" => $param['table'], "id" => $ob->id)));
      }
      else {
	messages_add("An error occured while saving: {$result}", MSG_ERROR);
      }
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

