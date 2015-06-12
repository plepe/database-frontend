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

    $form = new form("data", form_template_editor($template_options));

    if($form->is_complete()) {
      $data = $form->get_data();
      
      return "<pre>" . json_readable_encode($data['fields']) . "</pre>\n";
    }
    
    if($form->is_empty()) {
      if(isset($type)) {
	$data = $type->def;
	foreach($data as $k=>$d) {
	  $data[$k]['old_key'] = $k;
	}

	$form->set_data(array('fields' => $data));
      }
    }


    $ret  = "<form method='post'>\n";
    $ret .= $form->show();
    $ret .= "<input type='submit' value='Save'>\n";
    $ret .= "</form>\n";
    
    return $ret;
  }
}
