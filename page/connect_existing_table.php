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
	'type' => 'text',
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
      $res = $db_conn->query("select * from " . $db_conn->quoteIdent($data['table']) . " limit 1");

      for ($i = 0; $i < $res->columnCount(); $i++) {
        $meta = $res->getColumnMeta($i);

        $create_data['fields'][str_to_id($meta['name'])] = array(
          'type' => 'text',
          'name' => $meta['name'],
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
