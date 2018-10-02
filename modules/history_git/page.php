<?php
class Page_history extends Page {
  function content() {
    global $app;

    if(!base_access('view')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "history", "table" => $this->param['table'], "id" => $this->param['id'])));
      return "Permission denied.";
    }

    global $git;

    if(chdir($git['path']) === false) {
      messages_add("Git: cannot chdir to git directory", MSG_ERROR);
      return;
    }

    $path = ".";
    if(isset($this->param['table'])) {
      $path = $this->param['table'];
      $table = get_db_table($this->param['table']);
    }
    if(isset($this->param['table']) && isset($this->param['id'])) {
      $path = "{$this->param['table']}/{$this->param['id']}.json";
      $ob = $table->get_entry($this->param['id']);
    }

    $ret = adv_exec("git log -p " . shell_escape($path), "r");
    $ret = "<pre>" . htmlspecialchars($ret[1]) . "</pre>";

    return array(
      'template' => 'history.html',
      'table' => $this->param['table'],
      'table_name' => $table ? $table->name() : null,
      'id' => $this->param['id'],
      'title' => $ob ? $ob->title() : null,
      'data' => $ret,
      'app' => $app,
      'table_list' => get_db_table_names(),
    );
  }
}

