<?php
$object_type_cache = array();

class ObjectType {
  function __construct($type, $def) {
    $this->id = $type;
    $this->def = $def;
  }

  function sql_create_statement() {
    global $db_conn;
    $columns = array();

    if(!array_key_exists('id', $this->def)) {
      $columns[] = db_quote_ident('id'). " INTEGER PRIMARY KEY";
    }

    foreach($this->def as $column=>$column_def) {
      $r = db_quote_ident($column) . " text";

      if($column == "id")
        $r .= " primary key";

      if(array_key_exists('values', $column_def) && is_string($column_def['values'])) {
        $r .= ", foreign key(" . db_quote_ident($column ). ") references " .
          db_quote_ident($column_def['values']) . "(id)";
      }

      $columns[] = $r;
    }

    $ret  = "create table \"{$this->id}\" (\n  ";
    $ret .= implode(",\n  ", $columns);
    $ret .= "\n);\n";

    return $ret;
  }
}

function get_object_type($type) {
  global $object_type_cache;

  if(!array_key_exists($type, $object_type_cache)) {
    $def = file_get_contents("objects/{$type}.json");
    $def = json_decode($def, true);

    if($def === null) {
      // COMPAT: json_last_error_msg() exists PHP >= 5.5
      if(function_exists("json_last_error_msg"))
	$error = json_last_error_msg();
      else
	$error = json_last_error();

      throw new Exception("Can't load object type {$type}: " . $error);
    }

    $object_type_cache[$type] = new ObjectType($type, $def);
  }

  return $object_type_cache[$type];
}

function get_object_types() {
  $ret = array();

  $f = opendir('objects');
  while($r = readdir($f)) {
    if((substr($r, 0, 1) != '.') && (preg_match('/^(.*)\.json$/', $r, $m))) {
      $ret[] = get_object_type($m[1]);
    }
  }
  closedir($f);

  return $ret;
}
