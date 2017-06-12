<?php
function get_filter_form(&$param) {
  global $filter_form;

  if(isset($filter_form))
    return $filter_form;

  $table = get_db_table($param['table']);
  if(!$table)
    return null;

  if(!array_key_exists('filter', $_SESSION))
    $_SESSION['filter'] = array();

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
      'type' => 'filters',
      'def'  => $custom_filters,
      'hide_label' => true,
      'order' => false,
      'button:add_element' => 'Add filter',
    ),
  ));

  $filter_form = new form('filter', $filter_form_def);

  if(array_key_exists('apply_filter', $param)) {
    $filter = $filter_form->get_data();
    $_SESSION['filter'][$param['table']] = $filter;
    $param['offset'] = 0;
  }

  if($filter_form->is_empty()) {
    if(array_key_exists($param['table'], $_SESSION['filter'])) {
      $filter = $_SESSION['filter'][$param['table']];
      $filter_form->set_data($filter);
    }
  }

  return $filter_form;
}

function get_filter(&$param) {
  $filter_form = get_filter_form($param);

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
