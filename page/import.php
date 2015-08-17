<?php
class Page_import {
  function content() {
    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "import")));
      return "Permission denied.";
    }

    global $db_conn;

    $form_def = array(
      'table' => array(
	'name' => "Table ID",
	'type' => 'text',
	'req' => true,
      ),
      'file' => array(
	'name' => "File",
	'type' => 'file',
	'path' => 'db/',
	'req' => true,
      ),
      'delimiter' => array(
        'name' => "CSV Delimiter",
	'type' => 'text',
	'default' => ',',
      ),
      'enclosure' => array(
        'name' => "CSV Enclosure Character",
	'type' => 'text',
	'default' => '"',
      ),
      'escape' => array(
        'name' => "CSV Escape Character",
	'type' => 'text',
	'default' => '\\',
      ),
    );

    $form = new form('data', $form_def);

    if($form->is_complete()) {
      $data = $form->save_data();
      $create_data = array(
        'id' => $data['table'],
	'fields' => array(),
      );

      // analyze file
      $f = fopen('db/' . $data['file']['name'], "r");
      $header = fgetcsv($f, 0, $data['delimiter'], $data['enclosure'], $data['escape']);
      $fields = array();

      foreach($header as $col) {
	$create_data['fields'][str_to_id($col)] = array(
	  'type' => 'text',
	  'name' => $col,
	);

	$fields[] = str_to_id($col);
      }

      $db_conn->query("begin");
      db_table_init();
      $table = new DB_Table(null);
      $table->save($create_data, false);

      while($r = fgetcsv($f, 0, $data['delimiter'], $data['enclosure'], $data['escape'])) {
	$d = array();
	foreach($r as $i=>$ri) {
	  $d[$fields[$i]] = $ri;
	}

	$entry = new DB_Entry($data['table'], null);
	$entry->save($d, false);
      }
      $db_conn->query("commit");

      git_dump($param['message']);

      page_reload(page_url(array("page" => "list", "table" => $data['table'])));
    }

    return array(
      'template' => 'import.html',
      'form' => $form,
    );
  }
}
