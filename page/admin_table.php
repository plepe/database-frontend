<?php
class Page_admin_table extends Page {
  function content($param) {
    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "admin_table", "table" => $param['table'])));
      return "Permission denied.";
    }

    if(array_key_exists('action', $param)) {
      $page = "content_{$param['action']}";
      if(!method_exists($this, $page))
	return false;

      return $this->$page($param);
    }

    if(isset($param['table'])) {
      $table = get_db_table($param['table']);
      if(!$table)
	return null;
    }

    foreach(get_db_tables() as $t)
      $tables_data[$t->id] = $t->view();


    $def = array(
        'id' => array(
	  'type'	=> 'text',
	  'name'	=> 'ID',
	  'req'		=> true,
	),
    );

    $form = new form("data", $def);

    if($form->is_complete()) {
      $data = $form->get_data();
      
      if(!isset($table))
	$table = new DB_table(null);

      $table->save($data, $param['message']);

      page_reload(page_url(array("page" => "admin_table", "table" => $table->id)));
    }
    
    if($form->is_empty()) {
      if(isset($table)) {
	$form->set_data($table->data);
      }
    }

    return array(
      'template' => 'admin_table.html',
      'table' => $param['table'],
      'views' => $table ? $table->views() : null,
      'form' => $form,
      'data' => $table ? $table->view() : null,
    );
  }

  function content_drop($param) {
    $ret = "";
    $table = get_db_table($param['table']);

    if(!$table) {
      $ret .= "Table does not exist.";
    }
    else {
      $result = $table->remove();

      if($result === true) {
        $ret .= "Table dropped.";
        messages_add("Table dropped.");
        page_reload(array("page" => "admin"));
      }
      else
        $ret .= $result;
    }

    $ret .= " <a href='?page=admin'>Back</a>";

    return $ret;
  }
}
