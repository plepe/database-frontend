<?php
class Page_admin_table_general extends Page {
  function content($param) {
    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "admin_table_general", "table" => $param['table'])));
      return "Permission denied.";
    }

    if(isset($param['table'])) {
      $table = get_db_table($param['table']);
    }

    foreach(get_db_tables() as $t)
      $tables_data[$t->id] = $t->view();


    $def = array(
        'id' => array(
	  'type'	=> 'text',
	  'name'	=> 'ID',
	  'req'		=> true,
	),
	'name' => array(
	  'type'	=> 'text',
	  'name'	=> 'Name',
	),
	'access_view' => array(
	  'type'	=> 'text',
	  'name'	=> 'View access',
	  'desc'	=> "see below",
        ),
	'access_edit' => array(
	  'type'	=> 'text',
	  'name'	=> 'Edit access',
	  'desc'	=> "see below",
        ),
    );

    $form = new form("data", $def);

    if($form->is_complete()) {
      $data = $form->get_data();
      
      if(!isset($table))
	$table = new DB_table(null);

      $result = $table->save($data, $param['message']);

      if($result === true) {
	page_reload(page_url(array("page" => "admin_table", "table" => $table->id)));
      }
      else {
	messages_add("An error occured while saving: {$result}", MSG_ERROR);
      }
    }
    
    if($form->is_empty()) {
      if(isset($table)) {
	$form->set_data($table->data);
      }
    }

    return array(
      'template' => 'admin_table_general.html',
      'table' => $param['table'],
      'form' => $form,
      'data' => $table ? $table->view() : null,
    );
  }
}
