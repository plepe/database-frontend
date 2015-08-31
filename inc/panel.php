<?php
register_hook("twig_init", function() {
  global $twig;

  $twig->addFunction(new Twig_SimpleFunction('panel_items', function($param) {
    $items = array();

    call_hooks("panel_items", $items, $param);
    weight_sort($items);

    foreach($items as $i=>$item) {
      if(is_array($item)) {
        $items[$i] = "<a class='LinkButton' href='" . page_url($item['url']) . "'>{$item['title']}</a>";
      }
    }

    return implode("\n", $items);
  }, array("is_safe"=>array("html"))));
});
