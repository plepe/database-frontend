<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php call_hooks("init"); ?>
<?php
$type = get_object_type($_REQUEST['type']);
if(isset($_REQUEST['id']))
  $ob = get_object($_REQUEST['type'], $_REQUEST['id']);
$form = new form("data", $type->def);

if($form->is_complete()) {
  $data = $form->get_data();
  $ob->save($data);
}
else {
  if(isset($ob)) {
    $form->set_data($ob->data);
  }
}

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
<form method='post'>
<?php
print $form->show();
?>
<input type='submit' value='Save'>
</form>
  </body>
</html>
