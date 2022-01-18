<?php require "inc/bootstrap.php"; /* load a local configuration */ ?>
<?php
$content = "module.exports = [";
$first = true;
foreach ($modulekit_load as $module) {
  if (file_exists("modules/{$module}/{$module}.js")) {
    if ($first === true) {
      $content .= "\n";
      $first = false;
    }
    else {
      $content .= ",\n";
    }

    $content .= "  require('../modules/{$module}/{$module}.js')";
  }
}
$content .= "\n]";

file_put_contents("inc/_extensions_.js", $content);
