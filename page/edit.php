<?php
class Page_edit extends Page {
  function content() {
    global $app;

    $table = get_db_table($this->param['table']);
    if(!$table)
      return null;

    if(!base_access('view') || !access($table->data('access_edit'))) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "edit", "table" => $this->param['table'], "id" => $this->param['id'])));
      return "Permission denied.";
    }

    if(isset($this->param['id'])) {
      $ob = $table->get_entry($this->param['id']);
      if(!$ob)
	return null;
    }

    if(isset($this->param['clone'])) {
      $ob = $table->get_entry($this->param['clone']);
      if(!$ob)
	return null;
    }

    // if requested, delete entry
    if(isset($this->param['delete']) && $this->param['delete']) {
      if($ob) {
	$ob->remove($this->param['message']);
	messages_add("Entry deleted", MSG_NOTICE);
      }

      page_reload(page_url(array("page" => "list", "table" => $this->param['table'])));

      return array();
    }

    // if requested, cancel
    if(isset($this->param['cancel']) && $this->param['cancel']) {
      if ($this->param['id']) {
        page_reload(page_url(array("page" => "show", "table" => $this->param['table'], "id" => $this->param['id'])));
      } else {
        page_reload(page_url(array("page" => "list", "table" => $this->param['table'])));
      }

      return array();
    }

    $def = $table->def();

    $reference_fields = array();
    $backreference_fields = array();
    foreach ($def as $defk => $defv) {
      if (isset($defv['backreference']) && $defv['backreference']) {
        $def[$defk]['type'] = 'select';
        $def[$defk]['values_mode'] = 'keys';
        $def[$defk]['count'] = array(
          'default' => 1,
          'index_type' => 'array',
          'order' => false,
        );
        $backreference_fields[$defk] = true;
      }

      if (isset($defv['reference']) && $defv['reference'] && !in_array($defv['type'], array('checkbox')) && $defv['reference_create_new']) {
        $reference_fields[$defk] = $defv['count'];

        $ref_table = get_db_table($defv['reference']);

        $sub_def = $ref_table->def();
        foreach ($sub_def as $sub_defk => $sub_defv) {
          if (isset($sub_defv['backreference']) && $sub_defv['backreference']) {
            unset($sub_def[$sub_defk]);
            continue;
          }
        }

        if ($defv['count']) {
          $defv['count'] = array(
            'default' => 0,
            'button:add_element' => lang('edit:add_subelement', 0, $defv['name']),
          );
        }

        $def[$defk] = array(
          'type' => 'form',
          'name' => $defv['name'],
          'count' => $defv['count'],
          'def'  => array(
            'value'    => $defv,
            'new'      => array(
              'type'     => 'form',
              'hide_label' => true,
              'def'      => $sub_def,
              'show_depend' => array('check', 'value', array('not', array('has_value'))),
            )
          ),
        );

        unset($def[$defk]['def']['value']['count']);
        $def[$defk]['def']['value']['hide_label'] = true;
        $def[$defk]['def']['value']['placeholder'] = '-- create new --';
      }
      else if (is_array($defv['count'])) {
        $def[$defk]['count']['button:add_element'] = lang('edit:add_field', 0, $defv['name']);
      }
    }

    $form = new form("data", $def);

    if($form->is_complete()) {
      $changeset = new Changeset($this->param['message']);
      $changeset->open();

      $orig_data = $form->get_orig_data();
      $data = $form->get_data();
      if(!isset($this->param['id'])) {
	$ob = new DB_Entry($this->param['table'], null);
        $orig_data = array();
      }

      $result = true;

      foreach ($reference_fields as $f_id => $f_count) {
        if ($f_count) {
          foreach ($data[$f_id] as $e_id => $e_v) {
            if (!$e_v['value']) {
              $new_object = new DB_Entry($f_id, null);
              $result = $new_object->save($e_v['new'], $changeset);
              if ($result !== true) {
                break;
              }
              $data[$f_id][$e_id] = $new_object->id;
            }
            else {
              $data[$f_id][$e_id] = $e_v['value'];
            }
          }
        } else {
          if (!$data[$f_id]['value']) {
            $new_object = new DB_Entry($f_id, null);
            $result = $new_object->save($data[$f_id]['new'], $changeset);
            if ($result !== true) {
              break;
            }
            $data[$f_id] = $new_object->id;
          }
          else {
            $data[$f_id] = $data[$f_id]['value'];
          }
        }
      }

      if ($result === true) {
        $result = $ob->save($data, $changeset);
      }

      if ($result === true) foreach ($backreference_fields as $f_id => $f_dummy) {
        $field = $def[$f_id];
        $ref_table = explode(':', $field['backreference'])[0];
        $ref_field_id = explode(':', $field['backreference'])[1];
        $ref_table = get_db_table($ref_table);

        if (!array_key_exists($f_id, $orig_data)) {
          $orig_data[$f_id] = array();
        }

        foreach ($orig_data[$f_id] as $old_ref) {
          if (!in_array($old_ref, $data[$f_id])) {
            $other_ob = $ref_table->get_entry($old_ref);
            $old_field_data = $other_ob->data($ref_field_id);
            $p = array_search($ob->id, $old_field_data);
            if ($p !== false) {
              array_splice($old_field_data, $p, 1);
            }
            $result = $other_ob->save(array($ref_field_id => $old_field_data), $changeset);
            if ($result !== true) {
              break;
            }
          }
        }

        foreach ($data[$f_id] as $new_ref) {
          if (!in_array($new_ref, $orig_data[$f_id])) {
            $other_ob = $ref_table->get_entry($new_ref);
            $new_field_data = $other_ob->data($ref_field_id);
            $new_field_data[] = $ob->id;
            $result = $other_ob->save(array($ref_field_id => $new_field_data), $changeset);
            if ($result !== true) {
              break;
            }
          }
        }
      }

      if ($result === true) {
        $changeset->commit();
      }

      if($result === true) {
	page_reload(page_url(array("page" => "show", "table" => $this->param['table'], "id" => $ob->id)));
      }
      else {
	messages_add("An error occured while saving: {$result}", MSG_ERROR);
      }
    }

    if($form->is_empty()) {
      if(isset($ob)) {
        $data = $ob->data();

        if(isset($this->param['clone'])) {
          $data['id'] = null;
        }

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
      'table' => $this->param['table'],
      'table_name' => $table->name(),
      'title' => !isset($this->param['clone']) && $ob ? $ob->title() : null,
      'id' => $this->param['id'],
      'form' => $form,
      'data' => $ob ? $ob->view() : null,
      'app' => $app,
      'table_list' => get_db_table_names(),
    );
  }
}

