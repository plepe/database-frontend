<?
class Page_edit extends Page {
  function content($param) {
    $type = get_object_type($param['type']);
    if(isset($param['id']))
      $ob = get_object($param['type'], $param['id']);
    $form = new form("data", $type->def());

    if($form->is_complete()) {
      $data = $form->get_data();
      if(!isset($param['id']))
	$ob = new Object($param['type'], null);

      $ob->save($data);

      page_reload(page_url(array("page" => "show", "type=" => $param['type'], "id" => $ob->id)));
    }
    else {
      if(isset($ob)) {
	$form->set_data($ob->data);
      }
    }

    $ret .= "<form method='post'>\n";
    $ret .= $form->show();
    $ret .= "<input type='submit' value='Save'>\n";
    $ret .= "</form>\n";

    if(isset($param['id']))
      $ret .= "<a href='" . page_url(array("page" => "show", "type" => $param['type'], "id" => $param['id'])) . "'>Back</a>\n";
    else
      $ret .= "<a href='". page_url(array("page" => "list", "type" => $param['type'])) . "'>Back</a>\n";

    return $ret;
  }
}

