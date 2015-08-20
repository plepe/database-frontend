<?php
$db_table_cache = array();
$db_table_is_init = false;

function db_table_init() {
  global $db_table_is_init;
  global $db_conn;

  if($db_table_is_init)
    return;

  if(!$db_conn->tableExists('__system__')) {
    $db_conn->query(<<<EOT
create table __system__ (
  id		varchar(255) not null,
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

    // set 'old_key' for each field, so that later save() will leave
    // database structure intact. $new_data still has old_key information from
    // before
    foreach($this->data['fields'] as $k=>$d) {
      $this->data['fields'][$k]['old_key'] = $k;
    }

    $this->def = &$this->data['fields'];
  }
  
  function name() {
    return $this->id;
  }

  function data($key=null) {
    if($key !== null)
      return $this->data[$key];

    return $this->data;
  }

  function column_tables($data=null) {
    $field_types = get_field_types();

    if($data === null)
      $data = $this->data;

    $ret = array();

    foreach($data['fields'] as $column_id=>$column_def) {
      if(array_key_exists($column_def['type'], $field_types))
	$field_type = $field_types[$column_def['type']];
      else
	$field_type = new FieldType();

      if(($field_type->is_multiple() === true) || ($column_def['count'])) {
	$ret[] = $column_id;
      }
    }

    return $ret;
  }

  /**
   * updates database structure to specified data
   * first a table __tmp__ and sub-tables __tmp___XXX are created, then these
   * are filled with data from the old table (if it is not new) and then they
   * are renamed to table_id resp table_id_XXX.
   */
  function update_database_structure($data, $changeset) {
    global $db_conn;
    $columns = array();
    $constraints = array();
    $column_copy = array();
    $field_types = get_field_types();

    $old_data = $this->data;

    $tmp_name = "__tmp__";

    // is this a new table?
    if($this->id) {
      $new_table = !$db_conn->tableExists($this->old_id);
    }
    else
      $new_table = true;

    // if there's no 'id' field specified, add a generated one
    if(!array_key_exists('id', $data['fields'])) {
      $columns[] = $db_conn->quoteIdent('id'). " INTEGER auto_increment PRIMARY KEY";
      $column_copy[] = $db_conn->quoteIdent('id');
      $id_type = "integer";
    }
    else {
      $id_type = "varchar(255)";
    }

    $cmds = array();
    $multifield_cmds = array();
    $rename_cmds = array();

    // iterate over all fields and see what to do with them
    foreach($data['fields'] as $column=>$column_def) {
      $column_type = "text";
      if($column == "id") {
	$column_type = "varchar(255)";
      }

      if(array_key_exists('reference', $column_def) && ($column_def['reference'] != null)) {
	$column_type = "varchar(255)";
      }

      $r = $db_conn->quoteIdent($column) . " " . $column_type;

      if(array_key_exists($column_def['type'], $field_types))
	$field_type = $field_types[$column_def['type']];
      else
	$field_type = new FieldType();

      // the field has multiple values -> create a separate table
      // to hold this data.
      if(($field_type->is_multiple() === true) || ($column_def['count'])) {
	$multifield_cmds[] = "create table " . $db_conn->quoteIdent($tmp_name . '_' . $column) . " (\n" .
		  "  " . $db_conn->quoteIdent('id') . " {$id_type} not null,\n" .
		  "  " . $db_conn->quoteIdent('sequence') . " int not null,\n" .
		  "  " . $db_conn->quoteIdent('key') . " varchar(255) not null,\n" .
		  "  " . $db_conn->quoteIdent('value') . " {$column_type} null,\n" .
		  "  primary key(" . $db_conn->quoteIdent('id'). ", " . $db_conn->quoteIdent('key') . "),\n" .
		  // foreign key to referenced table
		  ((array_key_exists('reference', $column_def) && ($column_def['reference'] != null)) ? "foreign key(" . $db_conn->quoteIdent('value') . ") references " . $db_conn->quoteIdent($column_def['reference']) . "(" . $db_conn->quoteIdent('id') . "), " : "") .
		  // /foreign key
		  "  foreign key(" . $db_conn->quoteIdent('id') . ") references " . $db_conn->quoteIdent($tmp_name) . "(" . $db_conn->quoteIdent('id') . ")" .
		  ");";

	// if this is not a new table, copy data from ...
	if((!$new_table) && array_key_exists('old_key', $column_def) && ($column_def['old_key'])) {

	  $old_def = $old_data['fields'][$column_def['old_key']];

	  if(array_key_exists($old_def['type'], $field_types))
	    $old_field_type = $field_types[$old_def['type']];
	  else
	    $old_field_type = new FieldType();

	  // ... it was already a field with multiple values
	  if(($old_field_type->is_multiple() === true) || ($old_def['count'])) {
	    if($db_conn->tableExists($this->old_id . '_' . $column_def['old_key']))
	      $multifield_cmds[] = "insert into " . $db_conn->quoteIdent($tmp_name . '_' . $column) .
		    "  select * from " . $db_conn->quoteIdent($this->old_id . '_' . $column_def['old_key']) . ";";
	  }
	  // ... it was a field with a single value
	  else {
	    $multifield_cmds[] = "insert into " . $db_conn->quoteIdent($tmp_name . '_' . $column) .
	          "  select id, 0, '0', " . $db_conn->quoteIdent($column_def['old_key']) . " from " . $db_conn->quoteIdent($this->old_id);
	  }
	}

        // now finally, we can rename the new table to its final name
        $rename_cmds[] = "alter table " . $db_conn->quoteIdent($tmp_name . '_' . $column) .
          " rename to " . $db_conn->quoteIdent($data['id'] . '_' . $column) . ";";
      }

      // the field only has single value -> add to database table
      else {
	// primary key
	if($column == "id")
	  $r .= " primary key";

	$columns[] = $r;

	// foreign key to referenced table
	if(array_key_exists('reference', $column_def) && ($column_def['reference'] != null)) {
	  $constraints[] = "foreign key(" . $db_conn->quoteIdent($column ). ") references " .
	    $db_conn->quoteIdent($column_def['reference']) . "(id)";
	}

	// if this is not a new table, copy data from ...
	if(array_key_exists('old_key', $column_def) && ($column_def['old_key'])) {
	  $old_def = $old_data['fields'][$column_def['old_key']];

	  if(array_key_exists($old_def['type'], $field_types))
	    $old_field_type = $field_types[$old_def['type']];
	  else
	    $old_field_type = new FieldType();

	  // ... it was a field with multiple values -> aggregate data and concatenate by ';'
	  if(($old_field_type->is_multiple() === true) || ($old_def['count'])) {
	    if($db_conn->tableExists($this->old_id . '_' . $column_def['old_key']))
	      $column_copy[] = "(select group_concat(value, ';') from " . $db_conn->quoteIdent($this->old_id . '_' . $column_def['old_key']) . " __sub__ where __sub__.id=" . $db_conn->quoteIdent($this->old_id) . ".id group by __sub__.id)";
	    else
	      $column_copy[] = "null";
	  }
	  // single value, simple copy
	  else {
	    $column_copy[] = $db_conn->quoteIdent($column_def['old_key']);
	  }
	}

	// it's a new field -> fill with 'null' values
	else
	  $column_copy[] = "null";
      }
    }

    // the new create table statement
    $cmds[] = "create table " . $db_conn->quoteIdent($tmp_name) . " (\n  ".
              implode(",\n  ", $columns) .
              (sizeof($constraints) ?
	        ", " . implode(",\n  ", $constraints) : "") .
              "\n);";

    // now add the cmds for the fields with multiple values
    $cmds = array_merge($cmds, $multifield_cmds);

    // add the commands to copy data from the old table(s)
    if(!$new_table) {
      $cmds[] = "insert into " . $db_conn->quoteIdent($tmp_name) . " select " . implode(", ", $column_copy) . " from " . $db_conn->quoteIdent($this->old_id);
      $drop_cmds[] = "drop table " . $db_conn->quoteIdent($this->old_id);
    }

    // To update the database structure, we now delete the old table(s)
    if(!$new_table) {
      foreach($old_data['fields'] as $old_column_id=>$old_def) {
	if(array_key_exists($old_def['type'], $field_types))
	  $old_field_type = $field_types[$old_def['type']];
	else
	  $old_field_type = new FieldType();

	// ... it was already a field with multiple values
	if(($old_field_type->is_multiple() === true) || ($old_def['count'])) {
	  if($db_conn->tableExists($this->old_id . '_' . $old_column_id)) {
	    $drop_cmds[] = "drop table " . $db_conn->quoteIdent($this->old_id . '_' . $old_column_id) . ";";
	  }
	}
      }

      $rename_cmds[] = "alter table " . $db_conn->quoteIdent($tmp_name) . " rename to " . $db_conn->quoteIdent($data['id']);
    }

    $cmds = array_merge($cmds, $drop_cmds);
    $cmds = array_merge($cmds, $rename_cmds);

    // start
    $changeset->disableForeignKeyChecks();

    global $debug;
    if(isset($debug) && $debug) {
      $debug_msg  = "SQL commands for updating database structure:\n";
      $debug_msg .= "<pre wrap>\n";
      $debug_msg .= implode("\n\n", $cmds);
      $debug_msg .= "</pre>\n";

      messages_debug($debug_msg);
    }

    // finally, execute all commands
    $created = array();
    foreach($cmds as $cmd) {
      $res = $db_conn->query($cmd);
      if($res === false) {
        $error_info = $db_conn->errorInfo();

        // When failing, remove __tmp__ tables
        foreach($created as $c) {
          $db_conn->query("drop table if exists {$c}"); // $c is already quoted
        }

        return "DB_Table::update_database_structure(): Failure executing '{$cmd}', " . $error_info[2];
      }

      // remember all table creations in case we need to rollback
      if(preg_match("/^create table (.*) \(/", $cmd, $m)) {
        $created[] = $m[1];
      }
    }

    $changeset->enableForeignKeyChecks();
    return true;
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
        'class' => 'JSON',
      );
    }

    $views = weight_sort($views, "weight_{$type}");

    return $views;
  }

  function view_def($k) {
    $field_types = get_field_types();

    if($k == 'default') {
      $def = $this->def();

      // special formats for default view
      // * referenced tables
      // * fields with multiple values
      foreach($def as $column_id => $column_def) {
	if(array_key_exists($column_def['type'], $field_types))
	  $field_type = $field_types[$column_def['type']];
	else
	  $field_type = new FieldType();

	if($column_def['reference']) {
	  if(($field_type->is_multiple() === true) || ($column_def['count'])) {
	    $def[$column_id]['format'] =
	      "<ul class='MultipleValues'>\n" .
	      "{% for _ in {$column_id} %}\n" .
	      "<li><a href='{{ page_url({ \"page\": \"show\", \"table\": \"{$column_def['reference']}\", \"id\": _.id }) }}'>" .
	      $field_type->default_format("_.id") .
	      "</a>" .
	      "{% endfor %}\n" .
	      "</ul>\n";
	  }
	  else {
	    $def[$column_id]['format'] = "<a href='{{ page_url({ \"page\": \"show\", \"table\": \"{$column_def['reference']}\", \"id\": {$column_id}.id }) }}'>" .
	    $field_type->default_format("{$column_id}.id") .
	    "</a>";
	  }
	}
	else {
	  if(($field_type->is_multiple() === true) || ($column_def['count'])) {
	    $def[$column_id]['format'] =
	      "<ul class='MultipleValues'>\n" .
	      "{% for _ in {$column_id} %}\n" .
	      "<li>" . $field_type->default_format('_') . "</li>\n" .
	      "{% endfor %}\n" .
	      "</ul>\n";
	  }
	  else {
	    $def[$column_id]['format'] = $field_type->default_format($column_id);
	  }
	}

	if(!array_key_exists('sortable', $column_def)) {
	  $def[$column_id]['sortable'] = array(
	    'type' => 'nat',
	  );
	}
      }

      return array(
        'title' => 'Default',
        'weight_show' => -1,
        'weight_list' => -1,
        'fields' => $def,
      );
    }

    if($k == 'json')
      return array(
        'title' => 'JSON',
        'class' => 'JSON',
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

      $column_def = array_key_exists($key, $def) ? $def[$key] : null;

      if($column_def && array_key_exists($column_def['type'], $field_types))
	$field_type = $field_types[$column_def['type']];
      else
	$field_type = new FieldType();

      if($key == '__custom__')
	$key = "__custom{$i}__";

      $d['name'] = $d['title'] ? $d['title'] : $def[$d['key']]['name'];

      $is_multiple = (($field_type->is_multiple() === true) || ($column_def['count']));

      if(!array_key_exists('format', $d) || ($d['format'] === null)) {
	if($is_multiple)
	  $d['format'] =
	    "<ul>\n" .
	    "{% for _ in {$key} %}\n" .
	    "  <li>" . $field_type->default_format('_') . "</li>\n" .
	    "{% endfor %}\n" .
	    "</ul>\n";
	else
	  $d['format'] = $field_type->default_format($key);
      }

      $ret['fields'][$key] = $d;
    }

    return $ret;
  }

  function default_view($type) { // type: 'list' or 'show'
    $view = array_keys($this->views($type));
    return $view[0];
  }

  /**
   * save - save new data to database for current table
   * $data: a hash array with key/values to update. if a key does not exist in
   *   $data, it will not be modified in the database. if the value is null,
   *   the key will be removed.
   * $changeset: either a message (string) or a Changeset
   * Return:
   *   true: saving successful
   *   <string>: error message
   */
  function save($data, $changeset=null) {
    global $db_conn;

    $new_data = $this->data;
    foreach($data as $k=>$d) {
      if($d === null)
	unset($new_data[$k]);
      else
	$new_data[$k] = $d;
    }

    $data = $new_data;

    if($this->id === null) {
      if(!array_key_exists("id", $data)) {
	return "require id for new types\n";
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

    if(($changeset === null) || is_string($changeset))
      $changeset = new Changeset($changeset);

    $this->old_id = $this->id;
    if(array_key_exists('id', $data)) {
      $this->id = $data['id'];
    }

    $result = $this->update_database_structure($data, $changeset);

    if($result !== true) {
      $changeset->rollBack();
      return $result;
    }

    if($db_conn->query($query) === false) {
      return db_return_error_info($db_conn);
    }

    $this->data = $data;
    $this->def = $data['fields'];

    // update cache
    global $db_table_cache;
    unset($db_table_cache[$old_id]);
    $db_table_cache[$this->id] = $this;

    $changeset->add($this);

    return true;
  }

  function view() {
    return $this->data;
  }

  /**
   * remove the table and all sub tables
   */
  function remove($changeset=null) {
    global $db_conn;
    $field_types = get_field_types();

    if(($changeset === null) || is_string($changeset))
      $changeset = new Changeset($changeset);

    $cmds = array();

    foreach($this->data('fields') as $column=>$column_def) {
      if(array_key_exists($column_def['type'], $field_types))
	$field_type = $field_types[$column_def['type']];
      else
	$field_type = new FieldType();

      if(($field_type->is_multiple() === true) || ($column_def['count']))
	$cmds[] = "drop table if exists " . $db_conn->quoteIdent("{$this->id}_{$column}");
    }

    $cmds[] = "drop table if exists " . $db_conn->quoteIdent($this->id);
    $cmds[] = "delete from __system__ where id=" . $db_conn->quote($this->id);

    global $debug;
    if(isset($debug) && $debug) {
      $debug_msg  = "SQL commands for removing database table:\n";
      $debug_msg .= "<pre wrap>\n";
      $debug_msg .= implode("\n\n", $cmds);
      $debug_msg .= "</pre>\n";

      messages_debug($debug_msg);
    }

    foreach($cmds as $cmd) {
      $res = $db_conn->query($cmd);

      if($res === false) {
        $error_info = $db_conn->errorInfo();

	return "DB_Table::remove(): Failure executing '{$cmd}', " . $error_info[2];
      }
    }

    // remove table from cache
    global $db_table_cache;
    unset($db_table_cache[$this->id]);

    $changeset->add($this);

    return true;
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
