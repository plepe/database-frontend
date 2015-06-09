<?php
$object_cache = array();

class Object {
  function __construct($type, $data) {
    $this->type = $type;
    $this->id = $data['id'];
    $this->data = $data;
  }
}

function get_object($type, $id) {
  global $db_conn;
  global $object_cache;

  if(!array_key_exists($id, $object_cache)) {
    $res = $db_conn->query("select * from " . db_quote_ident($type) . " where id=" . $db_conn->quote($id));
    if($elem = $res->fetch()) {
      $object_cache[$id] = new Object($type, $elem);
    }
    $res->closeCursor();
  }

  return $object_cache[$id];
}

function get_objects($type) {
  global $db_conn;
  global $object_cache;

  $res = $db_conn->query("select * from " . db_quote_ident($type));
  while($elem = $res->fetch()) {
    if(!array_key_exists($elem['id'], $object_cache))
      $object_cache[$elem['id']] = new Object($type, $elem);
  }
  $res->closeCursor();

  return $object_cache;
}
