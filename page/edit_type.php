<?php
class Page_edit_type extends Page {
  function content($param) {
    $type = get_object_type($param['type']);

    $form = new form("data", form_template_editor());

    if($form->is_complete()) {
      $data = $form->get_data();
      
      return "<pre>" . json_readable_encode($data['fields']) . "</pre>\n";
    }
    
    if($form->is_empty()) {
      if(isset($type))
	$form->set_data(array('fields' => $type->def));
    }


    $ret  = "<form method='post'>\n";
    $ret .= $form->show();
    $ret .= "<input type='submit' value='Save'>\n";
    $ret .= "</form>\n";
    
    return $ret;
  }
}
