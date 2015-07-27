<?php
register_hook("twig_init", function() {
  global $twig;

  $twig->addFilter(new Twig_SimpleFilter('show', function($data, $options, $opt2) {
    return call_user_func_array(array($data, "show"), array_slice(func_get_args(), 1));
  }, array("is_safe"=>array("html"))));

  $twig->addFilter(new Twig_SimpleFilter('show_list', function($data, $options, $opt2) {
    return call_user_func_array(array($data, "show_list"), array_slice(func_get_args(), 1));
  }, array("is_safe"=>array("html"))));
});

