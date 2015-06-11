<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php Header('Content-Type: text/html; charset=utf-8'); ?>
<?php
$object = get_object($_REQUEST['type'], $_REQUEST['id']);
?>
<html>
  <head>
    <title>PDB</title>
    <?php print modulekit_to_javascript(); /* pass modulekit configuration to JavaScript */ ?>
    <?php print modulekit_include_js(); /* prints all js-includes */ ?>
    <?php print modulekit_include_css(); /* prints all css-includes */ ?>
  </head>
  <body>
<pre>
<?php
print_r($object);
?>
</pre>
<?php
print "<a href='list.php?type=" . urlencode($_REQUEST['type']) . "'>Back</a>\n";
?>
  </body>
</html>
