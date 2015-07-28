<?php
class Page_admin_table_fields extends Page {
  function content($param) {
    if(isset($param['table'])) {
      $table = get_db_table($param['table']);
      if(!$table)
	return null;
    }

    foreach(get_db_tables() as $t)
      $tables_data[$t->id] = $t->view();

    $has_values = array("check", "type", array("or", array("is", "radio"), array("is", "select"), array("is", "checkbox"), array("is", "text"), array("is", "autocomplete"), array("is", "keywords")));
    $field_types = get_field_types();

    $show_depend_count = array("check", "type", array("or"));
    foreach($field_types as $k=>$type) {
      if($type->is_multiple() === null)
	$show_depend_count[2][] = array("is", $k);
    }

    $def = array(
      'fields'	=> array(
	'name'	=>"Fields",
	'type'	=>"hash",
	'default'   =>1,
	'hide_label'=>true,

	'key_def'	=>array(
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
	'def'       =>array(
	  'type'      =>'form',
	  'def'       => array(
	    'name'	=>array(
	      'name'	=>"Name",
	      'type'	=>"text",
	    ),
	    'type'	=>array(
	      'name'	=>"Type",
	      'type'	=>"select",
	      'values_mode' => 'keys',
	      'values'	=>array_map(function($f) {
		return $f->type();
	      }, $field_types),
	      'default'     =>'text',
	    ),
	    'values'	=>array(
	      'name'	=>"Values",
	      'type'	=>"hash",
	      'def'	=>array(
		'name'	=>lang('form:hash_value_field_name'),
		'type'	=>"text",
	      ),
	      'default'=>0,
	      'button:add_element' => "Add value",
	      'show_depend'=>$has_values,
	      // 'include_data'=>array('and', array('not_empty'), $has_values),
	      'include_data'=>$has_values,
	    ),
	    'count' => array(
	      'type' => 'select',
	      'name' => 'Multiple values',
	      'values' => array(
		'no' => 'single value',
		'ordered' => 'multiple values, ordered',
		'unordered' => 'multiple values, unordered',
	      ),
	      'show_depend' => $show_depend_count,
	      'include_data'=>$show_depend_count,
	      'default' => 'no',
	      'req' => true,
	    ),
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
	),
      ),
    );

    $form = new form("data", $def);

    if($form->is_complete()) {
      $data = $form->get_data();
      
      // update multiple value information
      foreach($data['fields'] as $i=>$d) {
	switch($d['count']) {
	  case 'no':
	    $data['fields'][$i]['count'] = null;
	    break;
	  case 'ordered':
	    $data['fields'][$i]['count'] = array('default' => 1);
	    break;
	  case 'unordered':
	    $data['fields'][$i]['count'] = array('default' => 1, 'order' => false);
	    break;
	}
      }

      if(!isset($table))
	$table = new DB_table(null);

      $table->save($data, $param['message']);

      page_reload(page_url(array("page" => "admin_table", "table" => $table->id)));
    }
    
    if($form->is_empty()) {
      if(isset($table)) {
	$data = $table->def;
	foreach($data as $k=>$d) {
	  $data[$k]['old_key'] = $k;
	}

	// update multiple value information
	foreach($data as $i=>$d) {
	  if($d['count'] === null)
	    $data[$i]['count'] = 'no';
	  elseif(array_key_exists('order', $d['count']) && ($d['count']['order'] === false))
	    $data[$i]['count'] = 'unordered';
	  else
	    $data[$i]['count'] = 'ordered';
	}

	if(!sizeof($data)) {
	  $data = array(array());
	}

	$form->set_data(array('fields' => $data));
      }
    }

    return array(
      'template' => 'admin_table_fields.html',
      'table' => $param['table'],
      'form' => $form,
      'data' => $table ? $table->view() : null,
    );
  }
}
