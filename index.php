<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php call_hooks("init"); ?>
<?php Header('Content-Type: text/html; charset=utf-8'); ?>
<?php
$ret  = "The following types exist:\n";
$ret .= "<ul>";
foreach(get_object_types() as $type) {
  $ret .= "<li><a href='list.php?type=" . urlencode($type->id) . "'>" .
    $type->name() . "</a></li>\n";
}
$ret .= "</ul>\n";

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
