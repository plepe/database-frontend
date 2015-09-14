<?php
class Page_import_csv {
  function content() {
    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "import_csv")));
      return "Permission denied.";
    }

    global $db_conn;
    global $data_path;

    $form_def = array(
      'table' => array(
	'name' => "Table ID",
	'type' => 'text',
	'req' => true,
      ),
      'file' => array(
	'name' => "File",
	'type' => 'file',
	'path' => $data_path,
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
      'encoding' => array(
        'name' => "CSV Encoding",
	'type' => 'select',
	'default' => 'UTF-8',
	'values' => array(
	  'UTF-8', 'ISO-8859-1',
	),
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
	if($data['encoding'] == "UTF-8")
	  ; // do nothing
	elseif($data['encoding'] == "ISO-8859-1")
	  $col = utf8_encode($col);
	else
	  $col = iconv($data['encoding'], "UTF-8", $col);

	$create_data['fields'][str_to_id($col)] = array(
	  'type' => 'text',
	  'name' => $col,
	);

	$fields[] = str_to_id($col);
      }

      db_table_init();

      $changeset = new Changeset($param['message']);
      $changeset->open();

      $table = new DB_Table(null);
      $table->save($create_data, $changeset);

      while($r = fgetcsv($f, 0, $data['delimiter'], $data['enclosure'], $data['escape'])) {
	$d = array();
	foreach($r as $i=>$ri) {
	  if($data['encoding'] == "UTF-8")
	    ; // do nothing
	  elseif($data['encoding'] == "ISO-8859-1")
	    $ri = utf8_encode($ri);
	  else
	    $ri = iconv($data['encoding'], "UTF-8", $ri);

	  $d[$fields[$i]] = $ri;
	}

	$entry = new DB_Entry($data['table'], null);
	$entry->save($d, $changeset);
      }

      $changeset->commit();

      page_reload(page_url(array("page" => "list", "table" => $data['table'])));
    }

    return array(
      'template' => 'import_csv.html',
      'form' => $form,
    );
  }
}
