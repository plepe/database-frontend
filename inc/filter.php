<?php
function get_filter_form($param) {
  global $filter_form;

  if(isset($filter_form))
    return $filter_form;

  $table = get_db_table($param['table']);
  if(!$table)
    return null;

  if(!array_key_exists('filter', $_SESSION))
    $_SESSION['filter'] = array();

  $operators = array();
  $filter_form_def = array();
  foreach($table->fields() as $field) {
    foreach($field->filters() as $filter_id=>$filter_def) {
      if(!array_key_exists($filter_id, $operators)) {
        $operators[$filter_id] = array(
          'name' => $filter_def['name'],
          'show_depend' => array('check', 'field', array('or')),
        );
      }

      $operators[$filter_id]['show_depend'][2][] = array('is', $field->id);
    }

    if(array_key_exists('default_filter', $field->def) && $field->def['default_filter']) {
      $filter_form_def["{$field->id}|{$field->def['default_filter']}"] = array(
        'type'      => 'text',
        'name'      => $field->def['name'],
      );
    }
  }

  $filter_form_def = array_merge($filter_form_def, array(
    '__custom__' => array(
      'name' => 'Filter',
      'type' => 'form',
      'count' => array(
        'default' => 0,
        'order' => false,
        'button:add_element' => 'Add custom filter',
        'hide_label' => true,
      ),
      'def'  => array(
        'field'  => array(
          'type' => 'select',
          'name' => 'Field',
          'values' => array_map(function($x) {
            return $x->def['name'];
          }, $table->fields()),
        ),
        'op' => array(
          'type' => 'select',
          'name' => 'Operator',
          'values' => $operators,
        ),
        'value' => array(
          'type' => 'text',
          'name' => 'Value',
        ),
      ),
    ),
  ));

  $filter_form = new form('filter', $filter_form_def);

  if(array_key_exists('apply_filter', $param)) {
    $filter = $filter_form->get_data();
    $_SESSION['filter'][$param['table']] = $filter;
  }

  if($filter_form->is_empty()) {
    if(array_key_exists($param['table'], $_SESSION['filter'])) {
      $filter = $_SESSION['filter'][$param['table']];
      $filter_form->set_data($filter);
    }
  }

  return $filter_form;
}

function get_filter($param) {
  $filter_form = get_filter_form($param);

  $data = $filter_form->get_data();

  $ret = array();
  foreach($data as $k=>$v) {
    if($k == '__custom__') {
      if($v)
        $ret = array_merge($ret, $v);
    }
    else {
      list($field, $op) = explode("|", $k);
      $ret[] = array('field' => $field, 'op' => $op, 'value' => $v);
    }
  }

  return $ret;
}
