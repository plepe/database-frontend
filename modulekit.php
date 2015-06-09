<?php
$name = "PDB - A simple but powerful database frontend";

$id = "pdb";

$version = "0.1-dev";

$modules_path = "lib/modulekit";

$depend = array("modulekit-form");

$include = array(
  'php' => array(
    'inc/database.php',
    'inc/Object.php',
    'inc/ObjectType.php',
  ),
);
