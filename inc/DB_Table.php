<?php
$db_table_cache = array();
$db_table_is_init = false;

function db_table_init() {
  global $db_table_is_init;
  global $db_conn;

  if($db_table_is_init)
    return;

  if($db_conn->query("select 1 from __system__") === false) {
    $db_conn->query(<<<EOT
create table __system__ (
  id		text	not null,
  data		text	null,
  primary key(id)
);
EOT
    );
  }
}

class DB_Table {
  function __construct($type, $data) {
    $this->id = $type;
    $this->data = $data;
    $this->def = $data['fields'];
  }
  
  function name() {
    return $this->id;
  }

  function update_database_structure($data=null) {
    global $db_conn;
    $columns = array();
    $constraints = array();
    $column_copy = array();

    if($data === null)
      $data = $this->data;

    $old_table_name_quoted = db_quote_ident($this->old_id);
    $table_name_quoted = db_quote_ident($data['id']);

    // is this a new table?
    if($this->id) {
      $new_table = false;
      $res = $db_conn->query("select 1 from {$old_table_name_quoted}");
      if($res === false) {
	$new_table = true;
      }
      else
	$res->closeCursor();
    }
    else
      $new_table = true;

    if(!array_key_exists('id', $data['fields'])) {
      $columns[] = db_quote_ident('id'). " INTEGER PRIMARY KEY";
      $column_copy[] = db_quote_ident('id');
    }

    foreach($data['fields'] as $column=>$column_def) {
      $r = db_quote_ident($column) . " text";

      if($column == "id")
        $r .= " primary key";

      if(array_key_exists('reference', $column_def) && ($column_def['reference'] != null)) {
        $constraints[] = "foreign key(" . db_quote_ident($column ). ") references " .
          db_quote_ident($column_def['reference']) . "(id)";
      }

      $columns[] = $r;
      if(array_key_exists('old_key', $column_def) && ($column_def['old_key']))
	$column_copy[] = db_quote_ident($column_def['old_key']);
      else
	$column_copy[] = "null";
    }

    $cmds = array();
    $cmds[] = "pragma foreign_keys=off;";
    if(!$new_table)
      $cmds[] = "alter table {$old_table_name_quoted} rename to __tmp__;";

    $cmds[] = "create table {$table_name_quoted} (\n  ".
              implode(",\n  ", $columns) .
              (sizeof($constraints) ?
	        ", " . implode(",\n  ", $constraints) : "") .
              "\n);";

    if(!$new_table) {
      $cmds[] = "insert into {$table_name_quoted} select " . implode(", ", $column_copy) . " from __tmp__;";
      $cmds[] = "drop table __tmp__;";
    }

    $cmds[] = "pragma foreign_keys=on;";

    foreach($cmds as $cmd) {
      // print "<pre>" . htmlspecialchars($cmd) . "</pre>\n";
      $res = $db_conn->query($cmd);
      if($res === false) {
	print "Failure executing: {$cmd}";
	print_r($db_conn->errorInfo());
      }
    }
  }

  function def() {
    $ret = $this->def;

    foreach($this->def as $k=>$d) {
      if(array_key_exists('reference', $d) && ($d['reference'] !== null)) {
	$values = array();
	foreach(get_db_entries($d['reference']) as $o) {
	  $values[$o->id] = $o->view();
	}

	$ret[$k]['values'] = $values;
	if(!array_key_exists('format', $ret[$k]))
	  $ret[$k]['format'] = "{{ {$k}.name }}";
      }
    }

    return $ret;
  }

  function save($data) {
    global $db_conn;

    if($this->id === null) {
      if(!array_key_exists("id", $data)) {
	print "DB_Table::save(): require id for new types\n";
	return;
      }

      $query = "insert into __system__ values (" .
        $db_conn->quote($data['id']) . ", " .
	$db_conn->quote(json_readable_encode($data)) . ")";
    }
    else {
      if(array_key_exists("id", $data) && ($data['id'] != $this->id)) {
	$query = "update __system__ set " .
	  "id=" . $db_conn->quote($data['id']) . ", data=" .
	  $db_conn->quote(json_readable_encode($data)) . ' where id=' .
	  $db_conn->quote($this->id);
      }
      else {
	$query = "update __system__ set data=" .
	  $db_conn->quote(json_readable_encode($data)) . ' where id=' .
	  $db_conn->quote($data['id']);
      }
    }

    if($db_conn->query($query) === false) {
      print_r($db_conn->errorInfo());
    }

    $this->old_id = $this->id;
    if(array_key_exists('id', $data)) {
      $this->id = $data['id'];
    }

    $this->update_database_structure($data);

    $this->data = $data;
    $this->def = $data['fields'];
  }

  function view() {
    return $this->data;
  }
}

function get_db_table($type) {
  global $db_conn;
  global $db_table_cache;

  db_table_init();

  if(!array_key_exists($type, $db_table_cache)) {
    $res = $db_conn->query("select * from __system__ where id=" . $db_conn->quote($type));
    if($elem = $res->fetch()) {
      $data = json_decode($elem['data'], true);

      if($data === null) {
	// COMPAT: json_last_error_msg() exists PHP >= 5.5
	if(function_exists("json_last_error_msg"))
	  $error = json_last_error_msg();
	else
	  $error = json_last_error();

	throw new Exception("Can't load db table {$elem['id']}: " . $error);
      }

      $db_table_cache[$elem['id']] = new DB_Table($elem['id'], $data);
    }
    $res->closeCursor();
  }

  if(!array_key_exists($type, $db_table_cache))
    return null;

  return $db_table_cache[$type];
}

function get_db_tables() {
  global $db_conn;
  global $db_table_cache;

  db_table_init();

  $res = $db_conn->query("select * from __system__");
  while($elem = $res->fetch()) {
    if(!array_key_exists($elem['id'], $db_table_cache)) {
      $data = json_decode($elem['data'], true);

      if($data === null) {
	// COMPAT: json_last_error_msg() exists PHP >= 5.5
	if(function_exists("json_last_error_msg"))
	  $error = json_last_error_msg();
	else
	  $error = json_last_error();

	throw new Exception("Can't load db table {$elem['id']}: " . $error);
      }

      $db_table_cache[$elem['id']] = new DB_Table($elem['id'], $data);
    }
  }
  $res->closeCursor();

  return $db_table_cache;
}
