<?php
function get_filter_form_def($table) {
  $operators = array();
  $filter_form_def = array(
  );

  $custom_filters = array();
  foreach($table->fields() as $field) {
    $f = array(
      'name' => $field->def['name'],
      'type' => 'text',
    );

    if (isset($field->def['values']) && sizeof($field->def['values'])) {
      $f['type'] = 'select';
      $f['values'] = $field->def['values'];
      $f['values_mode'] = 'values';
    }

    if (isset($field->def['reference']) && $field->def['reference']) {
      $f['type'] = 'select';
      $f['values'] = array();
      if (strpos($field->def['reference'], ':') === false) {
        foreach(get_db_table($field->def['reference'])->get_entries() as $o) {
          $f['values'][$o->id] = $o->view();
        }
      } else {
        list($ref_table, $ref_field) = explode(':', $field->def['reference']);
        foreach(get_db_table($ref_table)->get_entries() as $o) {
          $f['values'][$o->id] = $o->view();
        }
        // TODO!
      }
      $f['values_mode'] = 'keys';
    }

    $custom_filters[$field->id] = $f;

    if(array_key_exists('default_filter', $field->def) && $field->def['default_filter']) {
      $filter_form_def["{$field->id}|{$field->def['default_filter']}"] = array(
        'type'      => 'text',
        'name'      => $field->def['name'],
      );
    }
  }

  $filter_form_def = array_merge($filter_form_def, array(
    '__custom__' => array(
      'name' => 'Additional filters',
      'type' => 'form_chooser',
      'def'  => $custom_filters,
      'hide_label' => true,
      'include_data' => 'not_null',
      'order' => false,
      'button:add_element' => 'Add filter',
    ),
  ));

  return $filter_form_def;
}

function get_filter_form(&$param, $view=null) {
  global $filter_form;

  if(isset($filter_form))
    return $filter_form;

  $table = get_db_table($param['table']);
  if(!$table)
    return null;

  if (array_key_exists('reset_filter', $param)) {
    unset($_SESSION["{$param['table']}_filter"]);
    unset($_REQUEST["filter"]);
    unset($_REQUEST["form_orig_filter"]);
    unset($param["filter"]);
    unset($param["apply_filter"]);
  }

  $filter_form = new form('filter', get_filter_form_def($table));

  if (array_key_exists('apply_filter', $param)) {
    $filter = $filter_form->get_data();
    $_SESSION["{$param['table']}_filter"] = $filter;
    $param['offset'] = 0;
  }

  if ($filter_form->is_empty() && array_key_exists("{$param['table']}_filter", $_SESSION)) {
    $filter_form->set_data($_SESSION["{$param['table']}_filter"]);
  }
  elseif (!array_key_exists('filter', $param) && $view) {
    if (array_key_exists('filter', $view->def)) {
      $filter_form->set_data($view->def['filter']);
    }
  }

  return $filter_form;
}

function get_filter(&$param, $view=null) {
  $filter_form = get_filter_form($param, $view);

  $data = $filter_form->get_data();
  $filter_form->set_orig_data($data);

  $ret = array();
  foreach($data as $k=>$v) {
    if($k == '__custom__') {
      foreach($v as $vk => $vv) {
	if($vv === null)
	  continue;

	$ret[] = array('field' => $vk, 'op' => 'contains', 'value' => $vv);
      }
    }
    else {
      list($field, $op) = explode("|", $k);
      $ret[] = array('field' => $field, 'op' => $op, 'value' => $v);
    }
  }

  return $ret;
}

register_hook('session_regexp_allowed', function (&$ret) {
  $ret[] = '/^(.*)_filter$/';
});
