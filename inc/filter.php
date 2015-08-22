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
  }

  $filter_form_def = array(
    'filter' => array(
      'name' => 'Filter',
      'type' => 'form',
      'count' => array(
        'default' => 0,
        'order' => false,
        'button:add_element' => 'Add filter'
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
  );

  $filter_form = new form('filter', $filter_form_def);

  if($filter_form->is_complete()) {
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

  return $filter_form->get_data();
}
