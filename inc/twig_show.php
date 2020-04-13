<?php
register_hook("twig_init", function() {
  global $twig;

  $twig->addFilter(new Twig_SimpleFilter('show', function($data) {
    return call_user_func_array(array($data, "show"), array_slice(func_get_args(), 1));
  }, array("is_safe"=>array("html"))));

  $twig->addFilter(new Twig_SimpleFilter('show_list', function($data) {
    return call_user_func_array(array($data, "show_list"), array_slice(func_get_args(), 1));
  }, array("is_safe"=>array("html"))));

  $twig->addFunction(new Twig_SimpleFunction('entry_title', function($table, $id) {
    if ($entry = get_db_table($table)->get_entry($id)) {
      return $entry->title();
    }

    return null;
  }, array("is_safe"=>array("html"))));

  $twig->addFunction(new Twig_SimpleFunction('get_entry', function($table, $id) {
    $entry = get_db_table($table)->get_entry($id);

    if($entry)
      return $entry->view();
  }));

  $twig->addFunction(new Twig_SimpleFunction('get_entries', function($table, $filter=array(), $sort=array(), $offset=0, $limit=null) {
    return array_map(function($ob) {
      return $ob->view();
    }, get_db_table($table)->get_entries($filter));
  }));

  $twig->addFilter(new Twig_SimpleFilter('age', function($date) {
    $now = new DateTime();
    $date1 = new DateTime($date);
    $diff = $now->getTimestamp() - $date1->getTimestamp();

    if ($diff < 0) {
      $text = "not yet";
    }
    else if ($diff < 2 * 60) {
      $text = "just now";
    }
    elseif ($diff < 45 * 60) {
      $text = round($diff / 60) . " minutes ago";
    }
    elseif ($diff < 90 * 60) {
      $text = "an hour ago";
    }
    elseif ($diff < 24 * 3600) {
      $text = round($diff / 3600) . " hours ago";
    }
    elseif ($diff < 48 * 3600) {
      $text = "yesterday";
    }
    elseif ($diff < 61 * 86400) {
      $text = round($diff / 86400) . " days ago";
    }
    elseif ($diff < 380 * 86400) {
      $text = round($diff / 30.4 / 86400) . " months ago";
    }
    else {
      $text = round($diff / 365.25 / 86400) . " years ago";
    }

    return "<span title=\"" . htmlspecialchars($date) . "\">{$text}</span>";
  }, array("is_safe"=>array("html"))));
});
