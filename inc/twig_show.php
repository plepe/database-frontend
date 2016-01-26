<?php
register_hook("twig_init", function() {
  global $twig;

  $twig->addFilter(new Twig_SimpleFilter('show', function($data) {
    return call_user_func_array(array($data, "show"), array_slice(func_get_args(), 1));
  }, array("is_safe"=>array("html"))));

  $twig->addFilter(new Twig_SimpleFilter('show_list', function($data) {
    return call_user_func_array(array($data, "show_list"), array_slice(func_get_args(), 1));
  }, array("is_safe"=>array("html"))));

  $twig->addFunction(new Twig_SimpleFunction('get_entry', function($table, $id) {
    $entry = get_db_table($table)->get_entry($id);

    if($entry)
      return $entry->view();
  }));

  $twig->addFunction(new Twig_SimpleFunction('get_entries', function($table, $filter) {
    return array_map(function($ob) {
      return $ob->view();
    }, get_db_table($table)->get_entries($filter));
  }));
});
