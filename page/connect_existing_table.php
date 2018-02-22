<?php
class Page_connect_existing_table {
  function content() {
    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "connect_existing_table")));
      return "Permission denied.";
    }

    global $db_conn;
    global $data_path;

    $form_def = array(
      'table' => array(
	'name' => "Table ID",
	'type' => 'select',
        'values' => $db_conn->tables(),
	'req' => true,
      ),
    );

    $form = new form('data', $form_def);

    if($form->is_complete()) {
      $data = $form->save_data();
      $create_data = array(
        'id' => $data['table'],
	'fields' => array(),
      );

      // analyze table
      $res = $db_conn->columns($data['table']);

      foreach ($res as $colid => $meta) {
        $create_data['fields'][$colid] = array(
          'type' => 'text',
          'name' => $colid,
          'coltype' => $meta['type'],
        );
      }

      db_system_init();

      $changeset = new Changeset($param['message']);
      $changeset->open();

      $table = new DB_Table(null);
      $table->save($create_data, $changeset);

      $changeset->commit();

      page_reload(page_url(array("page" => "list", "table" => $data['table'])));
    }

    return array(
      'template' => 'import_csv.html',
      'form' => $form,
    );
  }
}
