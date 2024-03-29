<?php
class Page_admin_table_general extends Page {
  function content() {
    global $app;

    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "admin_table_general", "table" => $this->param['table'])));
      return "Permission denied.";
    }

    $views = array('default');
    if(isset($this->param['table'])) {
      $table = get_db_table($this->param['table']);
      $views = $table->views();
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
        'weight' => array(
          'type'        => 'integer',
          'name'        => 'Sort order in table list',
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
        'title'       => array(
          'type'        => 'textarea',
          'name'        => 'Title format',
          'desc'        => 'Template to format an item (e.g. for headers or references)',
          'default'        => '{{ id }}',
        ),
        'default_view_show'=> array(
          'type'         => 'select',
          'name'         => 'Default view for showing a single object',
          'values'       => $views,
          'default'      => 'default',
        ),
        'default_view_list'=> array(
          'type'         => 'select',
          'name'         => 'Default view for showing a list',
          'values'       => $views,
          'default'      => 'default',
        ),
        'ts' => array(
          'type'         => 'boolean',
          'name'         => 'Add a timestamp field',
          'default'      => true,
        ),
    );

    call_hooks("admin_table_general", $def);

    $form = new form("data", $def);

    if($form->is_complete()) {
      $data = $form->get_data();
      
      if(!isset($table))
	$table = new DB_table(null, null);

      $result = $table->save($data, $this->param['message']);

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
      'table' => $this->param['table'],
      'form' => $form,
      'data' => $table ? $table->view() : null,
      'app' => $app,
    );
  }
}
