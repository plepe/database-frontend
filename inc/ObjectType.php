<?php
$object_type_cache = array();

class ObjectType {
  function __construct($type, $def) {
    $this->id = $type;
    $this->def = $def;
  }
}

function get_object_type($type) {
  global $object_type_cache;

  if(!array_key_exists($type, $object_type_cache)) {
    $def = file_get_contents("objects/{$type}.json");
    $def = json_decode($def, true);

    if($def === null) {
      throw new Exception("Can't load object type {$type}: " . json_last_error_msg());
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
