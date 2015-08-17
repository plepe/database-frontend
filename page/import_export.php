<?php
class Page_import_export extends Page {
  function content($param) {
    if(!base_access('admin')) {
      page_reload(array("page" => "login", "return_to" => array("page" => "import_export")));
      return "Permission denied.";
    }

    $form = new form("data", array(
      'json' => array(
        'type' => 'json',
	'name' => 'JSON',
      ),
    ));

    if($form->is_complete()) {
      $data = $form->get_data();
      $data = $data['json'];

      foreach($data['__system__'] as $d) {
	$table = new DB_Table();
	$table->save($d);
      }

      foreach($data as $k => $entries) {
	if($k == "__system__")
	  continue;
	
	foreach($entries as $d) {
	  $ob = new DB_Entry($k, null);
	  $ob->save($d);
	}
      }
    }

    if($form->is_empty()) {
      $data = array('__system__' => array());

      foreach(get_db_tables() as $table) {
	$data['__system__'][$table->id] = $table->data();

	$data[$table->id] = array();

	foreach(get_db_entries($table->id) as $entry) {
	  $data[$table->id][] = $entry->data();
	}
      }

      $form->set_data(array("json" => $data));
    }

    return 
      "<form method='post'>\n" .
      $form->show() .
      "<input type='submit' value='Save'/>\n" .
      "</form>\n";
  }
}
