<?php
class Page_edit extends Page {
  function content($param) {
    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    if(!base_access('view') || !access($table->data('access_edit'))) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "edit", "table" => $param['table'], "id" => $param['id'])));
      return "Permission denied.";
    }

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

    $def = $table->def();

    $reference_fields = array();
    foreach ($def as $defk => $defv) {
      if (isset($defv['reference']) && $defv['reference']) {
        $reference_fields[$defk] = $defv['count'];
        $subdefv = $defv;

        $def[$defk] = array(
          'type' => 'form',
          'name' => $defv['name'],
          'count' => $defv['count'],
          'def'  => array(
            'value'    => $defv,
            'new'      => array(
              'type'     => 'form',
              'hide_label' => true,
              'def'      => get_db_table($defv['reference'])->def(),
              'show_depend' => array('check', 'value', array('is', '__new')),
            )
          ),
        );

        unset($def[$defk]['def']['value']['count']);
        $def[$defk]['def']['value']['hide_label'] = true;
        $def[$defk]['def']['value']['values']['__new'] = 'create new';
      }
    }

    $form = new form("data", $def);

    if($form->is_complete()) {
      $data = $form->get_data();
      if(!isset($param['id']))
	$ob = new DB_Entry($param['table'], null);

      foreach ($reference_fields as $f_id => $f_count) {
        if ($f_count) {
          foreach ($data[$f_id] as $e_id => $e_v) {
            if ($e_v['value'] === '__new') {
              $new_object = new DB_Entry($f_id, null);
              $new_object->save($e_v['new']);
              $data[$f_id][$e_id] = $new_object->id;
            }
            else {
              $data[$f_id][$e_id] = $e_v['value'];
            }
          }
        } else {
          if ($data[$f_id]['value'] === '__new') {
            $new_object = new DB_Entry($f_id, null);
            $new_object->save($data[$f_id]['new']);
            $data[$f_id] = $new_object->id;
          }
          else {
            $data[$f_id] = $data[$f_id]['value'];
          }
        }
      }

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
        $data = $ob->data();

        foreach ($reference_fields as $f_id => $f_count) {
          if ($f_count) {
            foreach ($data[$f_id] as $e_id => $e_v) {
              $data[$f_id][$e_id] = array('value' => $e_v);
            }
          } else {
            $data[$f_id] = array('value' => $data[$f_id]);
          }
        }

	$form->set_data($data);
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

