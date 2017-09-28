<?php
class Page_admin_general extends Page {
  function content($param) {
    global $system;
    global $app;

    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "admin_general")));
      return "Permission denied.";
    }

    foreach(get_db_tables() as $t)
      $table_names[$t->id] = $t->name();


    $def = array(
      'default_table' => array(
	'type'	=> 'select',
	'name'	=> 'Default table',
	'values' => $table_names,
	'placeholder' => 'Index page',
      ),
    );

    $form = new form("data", $def);

    if($form->is_complete()) {
      $data = $form->get_data();
      
      $result = $system->save($data, $param['message']);

      if($result === true) {
	page_reload(page_url(array("page" => "admin_general")));
      }
      else {
	messages_add("An error occured while saving: {$result}", MSG_ERROR);
      }
    }
    
    if($form->is_empty()) {
      $form->set_data($system->data());
    }

    return array(
      'template' => 'admin_general.html',
      'form' => $form,
      'data' => $system->view(),
      'app' => $app,
    );
  }
}
