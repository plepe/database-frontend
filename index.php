<?php require 'inc/bootstrap.php'; ?>
<?php Header('Content-Type: text/html; charset=utf-8'); ?>
<?php
if(!isset($default_settings))
  $default_settings = array();

$system = new DB_System($db);
if(!array_key_exists('page', $_REQUEST)) {
  if($system->data('default_table')) {
    $_REQUEST['page'] = 'list';
    $_REQUEST['table'] = $system->data('default_table');
  }
  else
    $_REQUEST['page'] = 'index';
}

$ret = null;
$page = get_page($_REQUEST);
if($page) {
  $ret = $page->content();

  if(is_array($ret)) {
    $ret = twig_render($ret['template'], $ret);
  }
}

if($ret === null) {
  Header("HTTP/1.0 404 Not Found");

  $ret = file_get_contents("templates/404.html");
}

call_hooks("page_ready");
$user_settings = $default_settings;
foreach ($auth->current_user()->settings()->data as $k => $v) {
  $user_settings[$k] = $v;
}

html_export_var(array('app' => $app, 'user_settings' => $user_settings));

print twig_render("page.html", array(
  'content' => $ret,
  'messages' => messages_print(),
  'add_headers' =>
    modulekit_to_javascript() . /* pass modulekit configuration to JavaScript */
    modulekit_include_js() . /* prints all js-includes */
    modulekit_include_css() . /* prints all css-includes */
    get_add_html_headers() , /* additional html headers */
  'app' => $app,
  'user_info' => auth_user_menu(),
  'page' => $page->param,
));
