<?php
$db_entry_cache = array();

class DB_Entry {
  function __construct($type, $data) {
    $this->type = $type;
    $this->table = get_db_table($type);

    if($data === null) { // new object
      $this->id = null;
      $this->data = array();
    }
    else {
      $this->id = $data['id'];
      $this->data = $data;
      $this->load();
    }
  }

  function data($key=null) {
    if($this->data === null)
      $this->load();

    if($key !== null)
      return $this->data[$key];

    return $this->data;
  }

  /**
   * load - (re-)load data from database
   */
  function load() {
    global $db_conn;

    $db_conn->beginTransaction();

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

    $db_conn->commit();
  }

  /**
   * save - save new data to database for current object
   * $data: a hash array with key/values to update. if a key does not exist in
   *   $data, it will not be modified in the database.
   * $changeset: either a message (string) or a Changeset
   * Return:
   *   true: saving successful
   *   <string>: error message
   */
  function save($data, $changeset=null) {
    global $db_conn;
    $set = array();
    $cmds = array();
    $insert_columns = array();
    $insert_values = array();

    if(array_key_exists('id', $data))
      $new_id = $data['id'];
    else
      $new_id = $this->id;

    if(($changeset === null) || is_string($changeset))
      $changeset = new Changeset($changeset);

    if($new_id != $this->id) {
      $res = $db_conn->query("select * from " . $db_conn->quoteIdent($this->type) . " where " . $db_conn->quoteIdent('id') . "=" . $db_conn->quote($new_id));
      if($res->fetch()) {
	$res->closeCursor();
	$changeset->rollBack();

	return "Entry already exists.";
      }
      $res->closeCursor();
    }

    foreach($data as $column_id=>$d) {
      $field = $this->table->field($column_id);

      if(!$field) {
	trigger_error("DB_Entry::save(): no such field '" . $column_id . "'", E_USER_ERROR);
	continue;
      }

      // the field has multiple values -> use extra table
      if($field->is_multiple() === true) {
	if($this->id !== null)
	  $cmds[] = "delete from " . $db_conn->quoteIdent($this->type . '_' . $column_id) .
	    " where " . $db_conn->quoteIdent('id') . "=" . $db_conn->quote($this->id);
      }
      else {
	$set[] = $db_conn->quoteIdent($column_id) . "=" . $db_conn->quote($d);
	$insert_columns[] = $db_conn->quoteIdent($column_id);
	$insert_values[] = $db_conn->quote($d);
      }
    }

    global $debug;
    if(isset($debug) && $debug)
      messages_debug(implode("\n\n", $cmds));

    foreach($cmds as $cmd) {
      if($db_conn->query($cmd) === false) {
	return db_return_error_info($db_conn);
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
	implode(", ", $set) . " where " . $db_conn->quoteIdent('id') . "=" .
	$db_conn->quote($this->id);
    }

    if(isset($debug) && $debug)
      messages_debug($query);

    if($db_conn->query($query) === false) {
      return db_return_error_info($db_conn);
    }

    if($this->id === null) {
      $this->id = $db_conn->lastInsertId();
    }

    if(array_key_exists('id', $data)) {
      $this->id = $data['id'];
    }

    foreach($data as $column_id=>$d) {
      $field = $this->table->field($column_id);

      // the field has multiple values -> use extra table
      if($field->is_multiple() === true) {

	$sequence = 0;
	foreach($d as $k=>$v) {
	  // don't save null values
	  if($v === null)
	    continue;

	  $cmds[] = "insert into " . $db_conn->quoteIdent($this->type . '_' . $column_id) .
	    " values (" . $db_conn->quote($this->id) . ", " . $db_conn->quote($sequence) . ", " .
	    $db_conn->quote($k) . ", " . $db_conn->quote($v) . ")";

	  $sequence++;
	}
      }
    }

    if(isset($debug) && $debug)
      messages_debug(implode("\n\n", $cmds));

    foreach($cmds as $cmd) {
      if($db_conn->query($cmd) === false) {
	return db_return_error_info($db_conn);
      }
    }
    $cmds = array();

    $this->data = null;

    $changeset->add($this);

    return true;
  }

  /**
   * remove - remove this entry
   * $changeset: either a message (string) or a Changeset
   */
  function remove($changeset=null) {
    global $db_conn;
    global $debug;

    if(($changeset === null) || is_string($changeset))
      $changeset = new Changeset($changeset);

    foreach($this->table->column_tables() as $table) {
      $query = "delete from " . $db_conn->quoteIdent($this->type . '_' . $table) . " where id=" . $db_conn->quote($this->id);

      if(isset($debug) && $debug)
	messages_debug($query);

      $db_conn->query($query);
    }

    $query = "delete from " . $db_conn->quoteIdent($this->type) . " where id=" . $db_conn->quote($this->id);

    if(isset($debug) && $debug)
      messages_debug($query);

    $res = $db_conn->query($query);

    $changeset->add($this);
  }

  /**
   * view - return data including references to other tables
   */
  function view() {
    if(isset($this->view_cache))
      return $this->view_cache;

    $this->view_cache = $this->data;

    foreach($this->table->fields() as $field) {
      if(array_key_exists('reference', $field->def) && ($column_def['reference'] != null)) {
	if($field->is_multiple() === true) {
	  $this->view_cache[$k] = array();
	  foreach($this->data[$k] as $v) {
	    $o = get_db_entry($field->def['reference'], $v);
	    if($o)
	      $this->view_cache[$k][] = &$o->view();
	  }
	}
	else {
	  if($this->data[$k]) {
	    $o = get_db_entry($field->def['reference'], $this->data[$k]);
	    if($o)
	      $this->view_cache[$k] = &$o->view();
	  }
	}
      }
    }

    return $this->view_cache;
  }
}

function get_db_entry($type, $id) {
  global $db_conn;
  global $db_entry_cache;

  if(!array_key_exists($type, $db_entry_cache))
    $db_entry_cache[$type] = array();

  if(!array_key_exists($id, $db_entry_cache[$type])) {
    $res = $db_conn->query("select * from " . $db_conn->quoteIdent($type) . " where id=" . $db_conn->quote($id));

    if($res === false) {
      messages_debug("get_db_entry('{$type}', '{$id}'): query failed");
      return null;
    }

    if($elem = $res->fetch()) {
      $db_entry_cache[$type][$id] = new DB_Entry($type, $elem);
    }
    $res->closeCursor();
  }

  if(!array_key_exists($id, $db_entry_cache[$type]))
    return null;

  return $db_entry_cache[$type][$id];
}

function get_db_entries($type, $filter=array()) {
  global $db_conn;
  global $db_entry_cache;

  if(!array_key_exists($type, $db_entry_cache))
    $db_entry_cache[$type] = array();

  $table = get_db_table($type);
  $compiled_filter = $table->compile_filter($filter);

  $tables = array();
  $query = array();
  foreach($compiled_filter as $f) {
    if(array_key_exists('table', $f))
      $tables[$f['table']] = true;
    $query[] = $f['query'];
  }

  $joined_tables = "";
  foreach($tables as $t=>$dummy) {
    $joined_tables .= " left join " . $db_conn->quoteIdent($type . '_' . $t) . " on " . $db_conn->quoteIdent($type) . ".id = " . $db_conn->quoteIdent($type . '_' . $t) . ".id";
  }

  if(sizeof($query))
    $query = " where " . implode(" and ", $query);
  else
    $query = "";
  //messages_debug("select * from " . $db_conn->quoteIdent($type) . $joined_tables . $query);

  $res = $db_conn->query("select * from " . $db_conn->quoteIdent($type) . $joined_tables . $query);

  if($res === false) {
    messages_debug("get_db_entries('{$type}'): query failed");
    return array();
  }

  while($elem = $res->fetch()) {
    if(!array_key_exists($elem['id'], $db_entry_cache[$type]))
      $db_entry_cache[$type][$elem['id']] = new DB_Entry($type, $elem);
  }
  $res->closeCursor();

  return $db_entry_cache[$type];
}
