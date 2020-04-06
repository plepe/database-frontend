<?php
function get_table_fields_form(&$param, $view_def) {
  global $table_fields_form;

  if(isset($table_fields_form))
    return $table_fields_form;

  $table = get_db_table($param['table']);
  if(!$table)
    return null;

  if(!array_key_exists('table_fields', $_SESSION))
    $_SESSION['table_fields'] = array();

  $view_fields = array();
  foreach($table->view_fields() as $field) {
    $view_fields[$field->id] = $field->def['name'];
  }

  $def = array(
    'table_fields' => array(
      'name' => 'Additional table fields',
      'type' => 'select_other',
      'count' => array(
        'index_type' => 'array',
        'default' => 1,
      ),
      'button:other' => 'Custom',
      'other_def' => array(
        'type' => 'form',
        'def' => array(
          'name' => array(
            'name' => 'Title',
            'type' => 'text',
          ),
          'format' => array(
            'name' => 'Format',
            'type' => 'textarea',
	    'desc' => 'Specify a different format for this field (mandatory for custom fields). This field uses the <a href="http://twig.sensiolabs.org/">Twig template engine</a>. You can use replacement patterns.',
          ),
        ),
      ),
      'values' => $view_fields,
      'values_mode' => 'keys',
    ),
  );

  $table_fields_form = new form('table_fields', $def, array(
    'var_name' => '',
  ));

  if(array_key_exists('apply_table_fields', $param)) {
    $table_fields = $table_fields_form->get_data();
    $_SESSION['table_fields'][$param['table']] = $table_fields;
  }

  if($table_fields_form->is_empty()) {
    if(array_key_exists($param['table'], $_SESSION['table_fields'])) {
      $table_fields = $_SESSION['table_fields'][$param['table']];
    }

    $table_fields_form->set_data($table_fields);
  }

  return $table_fields_form;
}

function modify_table_fields(&$param, &$def) {
  $table = get_db_table($param['table']);
  if(!$table)
    return null;

  $table_fields_form = get_table_fields_form($param, $def);
  if (!$table_fields_form)
    return null;

  $data = $table_fields_form->get_data();
  $table_fields_form->set_orig_data($data);

  $fields = $table->view_fields();

  foreach ($data['table_fields'] as $field_id) {
    if (is_array($field_id)) {
      $def['fields'][] = $field_id;
    }
    else if ($field_id) {
      $def['fields'][] = $fields[$field_id]->view_def();
    }
  }
}
