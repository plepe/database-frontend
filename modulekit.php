<?php
$name = "PDB - A simple but powerful database frontend";

$id = "pdb";

$version = "0.1-dev";

$depend = array("modulekit-form", "modulekit-table", "page", "twig", "messages", "json_readable_encode", "shell_escape", "adv_exec", "PDOext");

$include = array(
  'php' => array(
    'inc/database.php',
    'inc/DB_Entry.php',
    'inc/DB_Table.php',
    'inc/twig_show.php',
    'inc/str_to_id.php',
    'inc/git.php',
    'inc/View.php',
    'inc/View_Table.php',
    'inc/View_JSON.php',
    'inc/View_PlainText.php',
    'inc/View_PlainHTML.php',
    'inc/FieldTypes.php',
    'page/*',
  ),
  'js' => array(
    'inc/str_to_id.js',
    'inc/index.js',
  ),

  'css' => array(
    'style.css',
  ),
);
