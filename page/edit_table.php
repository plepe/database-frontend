<?php
class Page_edit_table extends Page {
  function content($param) {
    if(isset($param['table'])) {
      $table = get_db_table($param['table']);
      if(!$table)
	return null;
    }

    foreach(get_db_tables() as $t)
      $tables_data[$t->id] = $t->view();

    $template_options = array(
      'def_form' => array(
	'key_def' => array(
	  'name'  =>lang('form:hash_key_field_name'),
	  'default_func' => array(
	    'js' => <<<EOT
function(value, form_element, form) {
  if(!('name' in form_element.form_parent.elements))
    return null;

  var key = form_element.form_parent.elements.name.get_data();

  if(typeof(key) != 'string')
    return null;

  key = str_to_id(key);

  return key;
}
EOT
	  ),
	  'type'	=>"text",
	  'req'	=>true,
	  'check'	=>array("regexp", "^[a-zA-Z0-9_]+$", "Use only characters, digits and underscores."),
	),
      ),
      'def_additional' => array(
        'old_key' => array(
	  'type' => 'hidden',
	),
	'reference' => array(
	  'type'	=> 'select',
	  'req'		=> false,
	  'name'	=> 'Reference',
	  'desc'	=> 'Use this to reference another table as possibles values for this field. Leave \'Values\' empty.',
	  'values'	=> $tables_data,
	),
      ),
    );

    $def = array_merge(array(
        'id' => array(
	  'type'	=> 'text',
	  'name'	=> 'ID',
	  'req'		=> true,
	),
      ),
      form_template_editor($template_options)
    );

    $form = new form("data", $def);

    if($form->is_complete()) {
      $data = $form->get_data();
      
      if(!isset($table))
	$table = new DB_table(null);

      $table->save($data, $param['message']);
    }
    
    if($form->is_empty()) {
      if(isset($table)) {
	$data = $table->def;
	foreach($data as $k=>$d) {
	  $data[$k]['old_key'] = $k;
	}

	$form->set_data(array('id' => $table->id, 'fields' => $data));
      }
    }

    return array(
      'template' => 'edit_table.html',
      'table' => $param['table'],
      'views' => $table ? $table->views() : null,
      'form' => $form,
      'data' => $table ? $table->view() : null,
    );
  }
}
