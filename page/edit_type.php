<?php
class Page_edit_type extends Page {
  function content($param) {
    $type = get_object_type($param['type']);

    $template_options = array(
      'def_additional' => array(
        'old_key' => array(
	  'type' => 'hidden',
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
      
      $type->save($data);
    }
    
    if($form->is_empty()) {
      if(isset($type)) {
	$data = $type->def;
	foreach($data as $k=>$d) {
	  $data[$k]['old_key'] = $k;
	}

	$form->set_data(array('id' => $type->id, 'fields' => $data));
      }
    }


    $ret  = "<form method='post'>\n";
    $ret .= $form->show();
    $ret .= "<input type='submit' value='Save'>\n";
    $ret .= "</form>\n";
    
    return $ret;
  }
}
