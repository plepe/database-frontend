<?php
$db_entry_cache = array();

class DB_Entry {
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

    $this->load();
  }

  /**
   * load - (re-)load data from database
   */
  function load() {
    global $db_conn;
    $field_types = get_field_types();

    $res = $db_conn->query("select * from " . $db_conn->quoteIdent($this->type) . " where id=" . $db_conn->quote($this->id));
    $this->data = $res->fetch();
    $res->closeCursor();

    foreach(get_db_table($this->type)->column_tables() as $table) {
      $res = $db_conn->query("select * from " . $db_conn->quoteIdent($this->type . '_' . $table) . " where id=" . $db_conn->quote($this->id) . " order by sequence");
      $this->data[$table] = array();
      while($elem = $res->fetch())
	$this->data[$table][$elem['key']] = $elem['value'];
      $res->closeCursor();
    }
  }

  /**
   * save - save new data to database for current object
   * $data: a hash array with key/values to update. if a key does not exist in
   *   $data, it will not be modified in the database.
   */
  function save($data, $message="") {
    global $db_conn;
    $set = array();
    $cmds = array();
    $insert_columns = array();
    $insert_values = array();
    $field_types = get_field_types();

    if(array_key_exists('id', $data))
      $new_id = $data['id'];
    else
      $new_id = $this->id;

    foreach($data as $column_id=>$d) {
      $column_def = get_db_table($this->type)->data['fields'][$column_id];

      if(array_key_exists($column_def['type'], $field_types))
	$field_type = $field_types[$column_def['type']];
      else
	$field_type = FieldType;

      // the field has multiple values -> use extra table
      if(($field_type->is_multiple() === true) || ($column_def['count'])) {
	if($this->id !== null)
	  $cmds[] = "delete from " . $db_conn->quoteIdent($this->type . '_' . $column_id) .
	    " where \"id\"=" . $db_conn->quote($this->id);
      }
      else {
	$set[] = $db_conn->quoteIdent($column_id) . "=" . $db_conn->quote($d);
	$insert_columns[] = $db_conn->quoteIdent($column_id);
	$insert_values[] = $db_conn->quote($d);
      }
    }

    foreach($cmds as $cmd) {
      if($db_conn->query($cmd) === false) {
	print_r($db_conn->errorInfo());
      }
    }
    $cmds = array();

    if($this->id === null) {
      $query = "insert into " . $db_conn->quoteIdent($this->type) . " (" .
        implode(", ", $insert_columns) . ") values (" .
	implode(", ", $insert_values) . ")";
    }
    else {
      $query = "update " . $db_conn->quoteIdent($this->type) . " set " .
	implode(", ", $set) . " where \"id\"=" .
	$db_conn->quote($this->id);
    }

    if($db_conn->query($query) === false) {
      print_r($db_conn->errorInfo());
    }

    if($this->id === null) {
      $this->id = $db_conn->lastInsertId();
    }

    foreach($data as $column_id=>$d) {
      $column_def = get_db_table($this->type)->data['fields'][$column_id];

      if(array_key_exists($column_def['type'], $field_types))
	$field_type = $field_types[$column_def['type']];
      else
	$field_type = FieldType;

      // the field has multiple values -> use extra table
      if(($field_type->is_multiple() === true) || ($column_def['count'])) {

	$sequence = 0;
	foreach($d as $k=>$v) {
	  $cmds[] = "insert into " . $db_conn->quoteIdent($this->type . '_' . $column_id) .
	    " values (" . $db_conn->quote($this->id) . ", " . $db_conn->quote($sequence) . ", " .
	    $db_conn->quote($k) . ", " . $db_conn->quote($v) . ")";

	  $sequence++;
	}
      }
    }

    foreach($cmds as $cmd) {
      if($db_conn->query($cmd) === false) {
	print_r($db_conn->errorInfo());
      }
    }
    $cmds = array();

    if(array_key_exists('id', $data)) {
      $this->id = $data['id'];
    }

    $this->load();

    git_dump($message);
  }

  /**
   * view - return data including references to other tables
   */
  function view() {
    $type = get_db_table($this->type);
    $ret = $this->data;

    foreach($type->def as $k=>$d) {
      if(array_key_exists('reference', $d) && ($d['reference'] != null)) {
	if($this->data[$k]) {
	  $o = get_db_entry($d['reference'], $this->data[$k]);
	  if($o)
	    $ret[$k] = $o->view();
	}
      }
    }

    return $ret;
  }
}

function get_db_entry($type, $id) {
  global $db_conn;
  global $db_entry_cache;

  if(!array_key_exists($type, $db_entry_cache))
    $db_entry_cache[$type] = array();

  if(!array_key_exists($id, $db_entry_cache[$type])) {
    $res = $db_conn->query("select * from " . $db_conn->quoteIdent($type) . " where id=" . $db_conn->quote($id));
    if($elem = $res->fetch()) {
      $db_entry_cache[$type][$id] = new DB_Entry($type, $elem);
    }
    $res->closeCursor();
  }

  if(!array_key_exists($id, $db_entry_cache[$type]))
    return null;

  return $db_entry_cache[$type][$id];
}

function get_db_entries($type) {
  global $db_conn;
  global $db_entry_cache;

  if(!array_key_exists($type, $db_entry_cache))
    $db_entry_cache[$type] = array();

  $res = $db_conn->query("select * from " . $db_conn->quoteIdent($type));
  while($elem = $res->fetch()) {
    if(!array_key_exists($elem['id'], $db_entry_cache[$type]))
      $db_entry_cache[$type][$elem['id']] = new DB_Entry($type, $elem);
  }
  $res->closeCursor();

  return $db_entry_cache[$type];
}
