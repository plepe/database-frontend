<?php
class Page_history extends Page {
  function content($param) {
    if(!base_access('view')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return_to" => array("page" => "history", "table" => $param['table'], "id" => $param['id'])));
      return "Permission denied.";
    }

    global $git;

    if(chdir($git['path']) === false) {
      messages_add("Git: cannot chdir to git directory", MSG_ERROR);
      return;
    }

    $path = ".";
    if(isset($param['table']))
      $path = $param['table'];
    if(isset($param['table']) && isset($param['id']))
      $path = "{$param['table']}/{$param['id']}.json";

    $ret = adv_exec("git log -p " . shell_escape($path), "r");
    $ret = "<pre>" . htmlspecialchars($ret[1]) . "</pre>";

    return array(
      'template' => 'history.html',
      'table' => $param['table'],
      'id' => $param['id'],
      'data' => $ret,
    );
  }
}
