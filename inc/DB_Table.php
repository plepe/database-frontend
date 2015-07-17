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

  function column_tables($data=null) {
    if($data === null)
      $data = $this->data;

    $ret = array();

    foreach($data['fields'] as $column_id=>$column_def) {
      if($column_def['count']) {
	$ret[] = $column_id;
      }
    }

    return $ret;
  }

  function update_database_structure($data=null) {
    global $db_conn;
    $columns = array();
    $constraints = array();
    $column_copy = array();

    $old_data = $this->data;

    if($data === null)
      $data = $this->data;

    $old_table_name_quoted = $db_conn->quoteIdent($this->old_id);
    $table_name_quoted = $db_conn->quoteIdent($data['id']);

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
      $columns[] = $db_conn->quoteIdent('id'). " INTEGER PRIMARY KEY";
      $column_copy[] = $db_conn->quoteIdent('id');
      $id_type = "integer";
    }

    $column_cmds = array();

    foreach($data['fields'] as $column=>$column_def) {
      $r = $db_conn->quoteIdent($column) . " text";
      $id_type = "text";

      if($column_def['count']) {
	$column_cmds[] = "create table " . $db_conn->quoteIdent($data['id'] . '_' . $column) . "(\n" .
		  "  id {$id_type} not null,\n" .
		  "  sequence int not null,\n" .
		  "  key text not null,\n" .
		  "  value text null,\n" .
		  "  primary key(id, key),\n" .
		  // foreign key
		  ((array_key_exists('reference', $column_def) && ($column_def['reference'] != null)) ? "foreign key(value) references " . $db_conn->quoteIdent($column_def['reference']) . "(id)" : "") .
		  // /foreign key
		  "  foreign key(id) references " . $db_conn->quoteIdent($data['id']) . "(id)" .
		  ");";

	if((!$new_table) && array_key_exists('old_key', $column_def) && ($column_def['old_key'])) {
	  $old_def = $old_data['fields'][$column_def['old_key']];

	  if($old_def['count']) {
	    $column_cmds[] = "insert into " . $db_conn->quoteIdent($data['id'] . '_' . $column) .
	          "  select * from " . $db_conn->quoteIdent('__tmp___' . $column_def['old_key']) . ";";
	  }
	  else {
	    $column_cmds[] = "insert into " . $db_conn->quoteIdent($data['id'] . '_' . $column) .
	          "  select id, 0, '0', " . $db_conn->quoteIdent($column_def['old_key']) . " from __tmp__;";
	  }
	}
      }
      else {
	if($column == "id")
	  $r .= " primary key";

	if(array_key_exists('reference', $column_def) && ($column_def['reference'] != null)) {
	  $constraints[] = "foreign key(" . $db_conn->quoteIdent($column ). ") references " .
	    $db_conn->quoteIdent($column_def['reference']) . "(id)";
	}

	$columns[] = $r;
	if(array_key_exists('old_key', $column_def) && ($column_def['old_key'])) {
	  $old_def = $old_data['fields'][$column_def['old_key']];

	  if($old_def['count']) {
	    $column_copy[] = "(select group_concat(value, ';') from " . $db_conn->quoteIdent('__tmp___' . $column_def['old_key']) . " __sub__ where __sub__.id=__tmp__.id group by __sub__.id)";
	  }
	  else {
	    $column_copy[] = $db_conn->quoteIdent($column_def['old_key']);
	  }
	}
	else
	  $column_copy[] = "null";
      }
    }

    $cmds = array();
    $cmds[] = "pragma foreign_keys=off;";
    if(!$new_table) {
      $cmds[] = "alter table {$old_table_name_quoted} rename to __tmp__;";

      foreach($old_data['fields'] as $column=>$column_def) {
	if($column_def['count'])
	  $cmds[] = "alter table " . $db_conn->quoteIdent($this->old_id . '_' . $column_def['old_key']) .
	    " rename to " . $db_conn->quoteIdent('__tmp___' . $column_def['old_key']) . ";";
      }
    }

    $cmds[] = "create table {$table_name_quoted} (\n  ".
              implode(",\n  ", $columns) .
              (sizeof($constraints) ?
	        ", " . implode(",\n  ", $constraints) : "") .
              "\n);";

    $cmds = array_merge($cmds, $column_cmds);

    if(!$new_table) {
      $cmds[] = "insert into {$table_name_quoted} select " . implode(", ", $column_copy) . " from __tmp__;";
      $cmds[] = "drop table __tmp__;";

      foreach($old_data['fields'] as $column=>$column_def) {
	if($column_def['count'])
	  $cmds[] = "drop table " . $db_conn->quoteIdent('__tmp___' . $column_def['old_key']) . ";";
      }
    }

    $cmds[] = "pragma foreign_keys=on;";

    messages_debug($cmds);
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

  function views($type) { // type: 'list' or 'show'
    $views = array();

    if(array_key_exists('views', $this->data))
      $views = $this->data['views'];

    $views['default'] = array(
      'title' => 'Default',
      "weight_{$type}" => -1,
    );
    if($type == 'show') {
      $views['json'] = array(
        'title' => 'JSON',
        "weight_{$type}" => 100,
        'class' => 'View_JSON',
      );
    }

    $views = weight_sort($views, "weight_{$type}");

    return $views;
  }

  function view_def($k) {
    if($k == 'default')
      return array(
        'title' => 'Default',
        'weight_show' => -1,
        'weight_list' => -1,
        'fields' => $this->def(),
      );

    if($k == 'json')
      return array(
        'title' => 'JSON',
        'class' => 'View_JSON',
        'weight_show' => 100,
        'fields' => $this->def(),
      );

    if(!array_key_exists($k, $this->data['views'])) {
      messages_add("View does not exist!", MSG_ERROR);
      return array();
    }

    $def = $this->def();
    $ret = $this->data['views'][$k];
    $ret['fields'] = array();
    foreach($this->data['views'][$k]['fields'] as $i=>$d) {
      $key = $d['key'];
      if($key == '__default__')
	$key = "__custom{$i}__";

      $r = array(
        'name' => $d['title'] ? $d['title'] : $def[$d['key']]['name'],
      );

      if($d['format']) {
	$r['format'] = $d['format'];
      }

      $ret['fields'][$key] = $r;
    }

    return $ret;
  }

  function default_view($type) { // type: 'list' or 'show'
    $view = array_keys($this->views($type));
    return $view[0];
  }

  function save($data, $message="") {
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

    git_dump($message);
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
