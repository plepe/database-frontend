<?php
$object_cache = array();

class Object {
  function __construct($type, $data) {
    $this->type = $type;

    if($data === null) { // new object
      $this->id = null;
      $this->data = array();
    }
    else {
      $this->id = $data['id'];
      $this->data = $data;
    }
  }

  /**
   * save - save new data to database for current object
   * $data: a hash array with key/values to update. if a key does not exist in
   *   $data, it will not be modified in the database.
   */
  function save($data) {
    global $db_conn;
    $set = array();
    $insert_columns = array();
    $insert_values = array();

    foreach($data as $column_id=>$d) {
      $set[] = db_quote_ident($column_id) . "=" . $db_conn->quote($d);
      $insert_columns[] = db_quote_ident($column_id);
      $insert_values[] = $db_conn->quote($d);
    }

    if($this->id === null) {
      $query = "insert into " . db_quote_ident($this->type) . " (" .
        implode(", ", $insert_columns) . ") values (" .
	implode(", ", $insert_values) . ")";
    }
    else {
      $query = "update " . db_quote_ident($this->type) . " set " .
	implode(", ", $set) . " where \"id\"=" .
	$db_conn->quote($this->id);
    }

    if($db_conn->query($query) === false) {
      print_r($db_conn->errorInfo());
    }

    if($this->id === null) {
      $this->id = $db_conn->lastInsertId();
    }
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
