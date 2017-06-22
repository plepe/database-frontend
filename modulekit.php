<?php
$name = "PDB - A simple but powerful database frontend";

$id = "pdb";

$version = "0.1-dev";

$depend = array("modulekit-form", "modulekit-table", "page", "twig", "messages", "json_readable_encode", "shell_escape", "adv_exec", "PDOext", "modulekit-auth", "str_to_id", "opt_sort", "modulekit-auth-pages", "modulekit-auth-user-menu", "modulekit-auth-js", "modulekit-auth-user-settings-js", "modulekit-auth-user-settings-page", "ajax");

$include = array(
  'php' => array(
    'inc/database.php',
    'inc/auth.php',
    'inc/DB_Entry.php',
    'inc/DB_Table.php',
    'inc/DB_TableExtract.php',
    'inc/DB_System.php',
    'inc/twig_show.php',
    'inc/panel.php',
    'inc/View.php',
    'inc/View_Table.php',
    'inc/View_JSON.php',
    'inc/View_PlainText.php',
    'inc/View_PlainHTML.php',
    'inc/ViewField.php',
    'inc/ViewBackreferenceField.php',
    'inc/FieldTypes.php',
    'inc/Changeset.php',
    'inc/Field.php',
    'inc/filter.php',
    'inc/table_fields.php',
    'inc/auto_add_new_fields_to_views.php',
    'inc/array_insert_before.php',
    'inc/random_ids.php',
    'page/*',
  ),
  'js' => array(
    'inc/index.js',
    'inc/pager.js',
    'inc/mousetrap.min.js',
    'inc/mousetrap-auto.js',
    'inc/filter.js',
    'inc/table_fields.js',
    'inc/random_ids.js',
    'inc/page_list.js',
  ),

  'css' => array(
    'style.css',
  ),
);
