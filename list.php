<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: text/html; charset=utf-8'); ?>
<?php
$type = $_REQUEST['type'];
$ret  = htmlspecialchars($type) . ":\n";

$data = array();
foreach(get_objects($type) as $o) {
  $data[$o->id] = $o->data;
}

$def = get_object_type($type)->def();
$def['__links'] = array(
  "name" => "",
  "format" => "<a href='show.php?type={$type}&amp;id={{ id }}'>Show</a> <a href='edit.php?type={$type}&amp;id={{ id }}'>Edit</a>",
);

$table = new table($def, $data, array("template_engine"=>"twig"));
$ret .= $table->show();

$ret .= "<div><a href='index.php'>Index</a> | <a href='edit.php?type={$type}'>Create new entry</a></div>\n";

?>
<!DOCTYPE html>
<html>
  <head>
    <title>PDB</title>
    <?php print modulekit_to_javascript(); /* pass modulekit configuration to JavaScript */ ?>
    <?php print modulekit_include_js(); /* prints all js-includes */ ?>
    <?php print modulekit_include_css(); /* prints all css-includes */ ?>
    <?php print print_add_html_headers(); /* additional html headers */ ?>
  </head>
  <body>
<?php
print $ret;
?>
  </body>
</html>
