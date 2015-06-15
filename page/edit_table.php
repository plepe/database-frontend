<?php
class Page_edit_table extends Page {
  function content($param) {
    $table = get_db_table($param['table']);

    foreach(get_db_tables() as $t)
      $tables_data[$t->id] = $t->view();

    $template_options = array(
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
      
      $table->save($data);
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


    $ret  = "<form method='post'>\n";
    $ret .= $form->show();
    $ret .= "<input type='submit' value='Save'>\n";
    $ret .= "</form>\n";
    $ret .= "<div><a href='" . page_url(array('page' => 'index')) . "'>Index</a></div>";
    
    return $ret;
  }
}
