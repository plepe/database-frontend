<?
class Page_edit extends Page {
  function content($param) {
    $table = get_db_table($param['table']);
    if(isset($param['id']))
      $ob = get_db_entry($param['table'], $param['id']);
    $form = new form("data", $table->def());

    if($form->is_complete()) {
      $data = $form->get_data();
      if(!isset($param['id']))
	$ob = new DB_Entry($param['table'], null);

      $ob->save($data);

      page_reload(page_url(array("page" => "show", "table" => $param['table'], "id" => $ob->id)));
    }

    if($form->is_empty()) {
      if(isset($ob)) {
	$form->set_data($ob->data);
      }
    }

    $ret  = "<form method='post'>\n";
    $ret .= $form->show();
    $ret .= "<input type='submit' value='Save'>\n";
    $ret .= "</form>\n";

    if(isset($param['id']))
      $ret .= "<a href='" . page_url(array("page" => "show", "table" => $param['table'], "id" => $param['id'])) . "'>Back</a>\n";
    else
      $ret .= "<a href='". page_url(array("page" => "list", "table" => $param['table'])) . "'>Back</a>\n";

    return $ret;
  }
}

