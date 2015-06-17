<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: text/html; charset=utf-8'); ?>
<?php
if(!array_key_exists('page', $_REQUEST))
  $_REQUEST['page'] = 'index';

$ret = null;
$page = get_page($_REQUEST);
if($page) {
  $ret = $page->content($_REQUEST);

  if(is_array($ret)) {
    $ret = twig_render($ret['template'], $ret);
  }
}

if($ret === null) {
  Header("HTTP/1.0 404 Not Found");

  $ret = file_get_contents("templates/404.html");
}

print twig_render("page.html", array(
  'content' => $ret,
  'messages' => messages_print(),
  'add_headers' =>
    modulekit_to_javascript() . /* pass modulekit configuration to JavaScript */
    modulekit_include_js() . /* prints all js-includes */
    modulekit_include_css() . /* prints all css-includes */
    get_add_html_headers() , /* additional html headers */
  'app' => $app,
));
