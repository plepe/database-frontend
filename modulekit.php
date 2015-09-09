<?php
$name = "PDB - A simple but powerful database frontend";

$id = "pdb";

$version = "0.1-dev";

$depend = array("modulekit-form", "modulekit-table", "modulekit-ajax", "page", "twig", "messages", "json_readable_encode", "shell_escape", "adv_exec", "PDOext", "modulekit-auth");

$include = array(
  'php' => array(
    'inc/database.php',
    'inc/auth.php',
    'inc/DB_Entry.php',
    'inc/DB_Table.php',
    'inc/DB_TableExtract.php',
    'inc/twig_show.php',
    'inc/str_to_id.php',
    'inc/panel.php',
    'inc/View.php',
    'inc/View_Table.php',
    'inc/View_JSON.php',
    'inc/View_PlainText.php',
    'inc/View_PlainHTML.php',
    'inc/FieldTypes.php',
    'inc/Changeset.php',
    'inc/Field.php',
    'inc/filter.php',
    'page/*.php',
  ),
  'js' => array(
    'inc/str_to_id.js',
    'inc/index.js',
    'inc/pager.js',
    'inc/DB_Table.js',
    'inc/DB_Entry.js',
    'inc/DB_TableExtract.js',
    'inc/Field.js',
    'inc/twig_show.js',
    'inc/panel.js',
    'inc/async.js',
    'inc/url_handling.js',
    'page/*.js',
  ),

  'css' => array(
    'style.css',
  ),
);
