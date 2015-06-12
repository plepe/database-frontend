<?php
$object_type_cache = array();
$object_type_is_init = false;

function object_type_init() {
  global $object_type_is_init;
  global $db_conn;

  if($object_type_is_init)
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

class ObjectType {
  function __construct($type, $data) {
    $this->id = $type;
    $this->data = $data;
    $this->def = $data['fields'];
  }
  
  function name() {
    return $this->id;
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

  function def() {
    $ret = $this->def;

    foreach($this->def as $k=>$d) {
      if(array_key_exists('values', $d) && is_string($d['values'])) {
	$values = array();
	foreach(get_objects($d['values']) as $o) {
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
	print "ObjectType::save(): require id for new types\n";
	return;
      }

      $query = "insert into __system__ values (" .
        $db_conn->quote($data['id']) . ", " .
	$db_conn->quote(json_readable_encode($data)) . ")";
    }
    else {
      if(array_key_exists("id", $data) && ($data['id'] != $this->id)) {
	// TODO
	// TODO: check references from other types
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

    if(array_key_exists('id', $data)) {
      $this->id = $data['id'];
    }
  }
}

function get_object_type($type) {
  global $db_conn;
  global $object_type_cache;

  object_type_init();

  if(!array_key_exists($type, $object_type_cache)) {
    $res = $db_conn->query("select * from __system__ where id=" . $db_conn->quote($type));
    if($elem = $res->fetch()) {
      $data = json_decode($elem['data'], true);

      if($data === null) {
	// COMPAT: json_last_error_msg() exists PHP >= 5.5
	if(function_exists("json_last_error_msg"))
	  $error = json_last_error_msg();
	else
	  $error = json_last_error();

	throw new Exception("Can't load object type {$elem['id']}: " . $error);
      }

      $object_type_cache[$elem['id']] = new ObjectType($elem['id'], $data);
    }
    $res->closeCursor();
  }

  return $object_type_cache[$type];
}

function get_object_types() {
  global $db_conn;
  global $object_type_cache;

  object_type_init();

  $res = $db_conn->query("select * from __system__");
  while($elem = $res->fetch()) {
    if(!array_key_exists($elem['id'], $object_type_cache)) {
      $data = json_decode($elem['data'], true);

      if($data === null) {
	// COMPAT: json_last_error_msg() exists PHP >= 5.5
	if(function_exists("json_last_error_msg"))
	  $error = json_last_error_msg();
	else
	  $error = json_last_error();

	throw new Exception("Can't load object type {$elem['id']}: " . $error);
      }

      $object_type_cache[$elem['id']] = new ObjectType($elem['id'], $data);
    }
  }
  $res->closeCursor();

  return $object_type_cache;
}
