<?php
$db_table_cache = array();

class DB_Table {
  function __construct($type, $data) {
    $this->id = $type;
    $this->data = $data;
    $this->entries_cache = array();

    // set 'old_key' for each field, so that later save() will leave
    // database structure intact. $new_data still has old_key information from
    // before
    foreach($this->data['fields'] as $k=>$d) {
      $this->data['fields'][$k]['old_key'] = $k;
    }

    $this->def = &$this->data['fields'];

    // make sure there is a 'list' and a 'show' view -> create if necessary
    if (!array_key_exists('views', $this->data)) {
      $this->data['views'] = array();
    }
    if (!array_key_exists('list', $this->data['views'])) {
      $r = $this->view_default();
      $r['title'] = "Default 'list' view'";
      $r['auto_add_new_fields_to_views'] = true;
      $this->data['views']['list'] = $r;
    }
    if (!array_key_exists('show', $this->data['views'])) {
      $r = $this->view_default();
      $r['title'] = "Default 'show' view'";
      $r['auto_add_new_fields_to_views'] = true;
      $this->data['views']['show'] = $r;
    }
  }
  
  function name() {
    return $this->data('name') || $this->id;
  }

  function data($key=null) {
    if($key !== null)
      return $this->data[$key];

    return $this->data;
  }

  /**
   * return list of fields
   * @return Field[] all fields of the table
   */
  function fields() {
    if(!isset($this->_fields)) {
      $this->_fields = array();

      foreach($this->def as $column_id=>$column_def) {
        $type = "Field";
        if(isset($column_def['type']) && class_exists("Field_{$column_def['type']}"))
          $type = "Field_{$column_def['type']}";

        $this->_fields[$column_id] = new $type($column_id, $column_def, $this);
      }
    }

    return $this->_fields;
  }

  /**
   * return the specified field or null
   * @param string field_id field id
   * @return Field the specified field
   */
  function field($field_id) {
    $this->fields();

    if(array_key_exists($field_id, $this->_fields))
      return $this->_fields[$field_id];

    return null;
  }

  /**
   * return list of field ids whose values are stored in sub tables
   */
  function column_tables() {
    $ret = array();

    foreach($this->fields() as $field) {
      if($field->is_multiple() === true) {
	$ret[] = $field->id;
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
    $table_append = "";

    switch($db_conn->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'sqlite':
        $id_collate = "binary";
        break;
      case 'mysql':
        $id_collate = "utf8_bin";
        $table_append .= "DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        break;
    }

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
      $id_type = "varchar(255) collate {$id_collate}";
    }

    $cmds = array();
    $multifield_cmds = array();
    $rename_cmds = array();
    $drop_cmds = array();

    // iterate over all fields and see what to do with them
    foreach($data['fields'] as $column=>$column_def) {
      $column_type = "text";
      if($column == "id") {
	$column_type = "varchar(255) collate {$id_collate}";
      }

      if(array_key_exists('reference', $column_def) && ($column_def['reference'] != null)) {
	$column_type = "varchar(255) collate {$id_collate}";
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
		  ") {$table_append};";

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
              "\n) {$table_append};";

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
    }

    $rename_cmds[] = "alter table " . $db_conn->quoteIdent($tmp_name) . " rename to " . $db_conn->quoteIdent($data['id']);

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
      $field = $this->field($k);

      if(array_key_exists('reference', $d) && ($d['reference'] !== null)) {
	$values = array();
	foreach(get_db_table($d['reference'])->get_entries() as $o) {
	  $values[$o->id] = $o->view();
	}

	$ret[$k]['values'] = $values;
	if(!array_key_exists('format', $ret[$k]))
	  $ret[$k]['format'] = "{{ {$k}.name }}";
      }

      $ret[$k]['type'] = $field->form_type();
    }

    return $ret;
  }

  function views($type) { // type: 'list' or 'show'
    $views = array();

    if(array_key_exists('views', $this->data))
      $views = $this->data['views'];

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

  function view_default() {
    $def = $this->def();

    // special formats for default view
    // * referenced tables
    // * fields with multiple values
    foreach($this->fields() as $column_id=>$field) {
      $column_def = $field->def;

      $def[$column_id] = $field->view_def();
      $def[$column_id]['key'] = $column_id;
    }

    return array(
      'title' => 'Default',
      'weight_show' => -1,
      'weight_list' => -1,
      'fields' => $def,
    );
  }

  function view_def($k) {
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

      if($key == '__custom__') {
	$key = "__custom{$i}__";
	$field = new Field(null, array(), $this);
      }
      else {
	$field = $this->field($d['key']);
      }

      $d['name'] = $d['title'] ? $d['title'] : $field->def['name'];

      if(!array_key_exists('format', $d) || ($d['format'] === null)) {
	$x = $field->view_def();
	$d['format'] = $x['format'];
      }

      if((!array_key_exists('sortable', $d)) || ($d['sortable'] === null))
	$d['sortable'] = $this->def[$key]['sortable'];

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

    call_hooks('table_pre_save', $data, $this);

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

  function compile_filter($filter) {
    global $db_conn;
    $ret = array();

    foreach($filter as $f) {
      if(!array_key_exists('field', $f) && array_key_exists(0, $f) && (sizeof($f) == 3)) {
	$f['field'] = $f[0];
	$f['op'] = $f[1];
	$f['value'] = $f[2];
      }

      if($f['field'] && $f['op'] && $f['value']) {
        $field = $this->field($f['field']);
        if($field == null)
          continue;

        $r = $field->compile_filter($f);
        if($r === null) {
          messages_add("Can't compile filter " . print_r($f, 1), MSG_ERROR);
          continue;
        }

        $ret[] = array(
          'table' => $field->sql_table_quoted(),
          'query' => $r,
        );
      }
    }

    if(sizeof($ret))
      return $ret;

    return null;
  }

  /**
   * compile sort descriptions to sql statements
   * @param mixed[] list of sort options. If all sort options can be compiled, will
   *   modify the value to null.
   * @return string[] list of sql order statements.
   */
  function compile_sort(&$sort) {
    global $db_conn;
    $sort_success = true;
    $ret = array();

    if(!$sort)
      return null;

    foreach($sort as $i => $f) {
      if($f['key']) { //TODO: 'field' instead of 'key'
        $field = $this->field($f['key']);
        if($field == null)
          continue;

        $r = $field->compile_sort($f);
        if($r !== null) {
	  $ret[] = array(
	    'table' => $field->sql_table_quoted(),
	    'sort' => $r,
	  );
	}
	else
	  $sort_success = false;
      }
    }

    if($sort_success)
      $sort = null;

    if(sizeof($ret))
      return $ret;

    return null;
  }

  function load_entries_data($ids) {
    global $db_conn;
    $data = array();

    $where_quoted = implode(" or ", array_map(function($x) {
      global $db_conn;
      return "id=" . $db_conn->quote($x);
    }, $ids));

    $res = $db_conn->query("select * from " . $db_conn->quoteIdent($this->id) . " where " . $where_quoted);
    while($elem = $res->fetch()) {
      $data[$elem['id']] = $elem;
    }
    $res->closeCursor();

    foreach($this->column_tables() as $table) {
      $res = $db_conn->query("select * from " . $db_conn->quoteIdent($this->id . '_' . $table) . " where " . $where_quoted);

      // initialize property with empty array for all to-be-loaded entries
      foreach($data as $id=>$d)
	$data[$id][$table] = array();

      while($elem = $res->fetch())
	$data[$elem['id']][$table][$elem['key']] = $elem['value'];
      $res->closeCursor();
    }

    return $data;
  }

  function sort_custom($ids, $sorts) {
    $entries = $this->get_entries_by_id($ids);
    $data = array();

    foreach($entries as $entry) {
      $data[$entry->id] = array(
        '__id' => $entry->id
      );

      foreach($sorts as $sort) {
	$data[$entry->id][$sort['key']] = $entry->data($sort['key']);
      }
    }

    $data = opt_sort($data, $sorts);

    return array_map(function($x) {
      return $x['__id'];
    }, $data);
  }

  function get_entry($id, $data=null) {
    global $db_conn;
    global $db_entry_cache;

    if(!array_key_exists($id, $this->entries_cache)) {
      if($data === null) {
	$res = $db_conn->query("select id from " . $db_conn->quoteIdent($this->id) . " where id=" . $db_conn->quote($id));

	if($res === false) {
	  messages_debug("Table '{$this->id}'->get_entry('{$id}'): query failed");
	  return null;
	}

	if($elem = $res->fetch()) {
	  $this->entries_cache[$id] = new DB_Entry($this->id, $id, null);
	}
	$res->closeCursor();
      }
      else {
	$this->entries_cache[$id] = new DB_Entry($this->id, $id, $data);
      }
    }

    if(!array_key_exists($id, $this->entries_cache))
      return null;

    return $this->entries_cache[$id];
  }

  function get_entries_by_id($ids) {
    global $db_entry_cache;
    $data = array();

    $to_load_ids = array_diff($ids, array_keys($this->entries_cache));

    if(sizeof($to_load_ids)) {
      $data = $this->load_entries_data($to_load_ids);
    }

    // create all entries
    $ret = array();
    foreach($ids as $id) {
      $ret[$id] = $this->get_entry($id, array_key_exists($id, $data) ? $data[$id] : null);
    }

    return $ret;
  }

  function get_entry_ids($filter=array(), $sort=array(), $offset=0, $limit=null) {
    global $db_conn;
    global $db_entry_cache;

    if($default_sort = $this->data('sort')) {
      $fields = $this->data('fields');
      $default_sort = array(array(
        'key' => $default_sort,
        'type' => $fields[$default_sort]['sortable']['type'],
        'dir' => $fields[$default_sort]['sortable']['dir'],
        'null' => $fields[$default_sort]['sortable']['null'],
      ));
    }
    else
      $default_sort = array();

    if($sort === null)
      $sort = $default_sort;
    else
      $sort = array_merge($default_sort, $sort);

    $sort = weight_sort($sort);

    $compiled_filter = $this->compile_filter($filter);
    $compiled_sort = $this->compile_sort($sort);

    $tables = array();
    $query = array();
    if($compiled_filter !== null) foreach($compiled_filter as $f) {
      if(array_key_exists('table', $f))
        $tables[$f['table']] = true;

      $query[] = $f['query'];
    }

    $order = array();
    if($compiled_sort !== null) foreach($compiled_sort as $f) {
      if(array_key_exists('table', $f))
        $tables[$f['table']] = true;

      $order[] = $f['sort'];
    }

    $main_table_quoted = $db_conn->quoteIdent($this->id);
    unset($tables[$main_table_quoted]);

    $joined_tables = "";
    foreach($tables as $t=>$dummy) { // $t is always quoted
      $joined_tables .= " left join {$t} on {$main_table_quoted}.id = {$t}.id";
    }

    if(sizeof($query))
      $query = " where " . implode(" and ", $query);
    else
      $query = "";

    if(sizeof($order))
      $order = " order by " . implode(", ", $order);
    else
      $order = "";

    // check validity of limit and offset
    if($limit && (!preg_match("/^[0-9]+$/", $limit)))
      unset($limit);
    if($offset && (!preg_match("/^[0-9]+$/", $offset)))
      unset($offset);

    $query =
      "select distinct " . $db_conn->quoteIdent($this->id) . ".id " .
      "from " . $db_conn->quoteIdent($this->id) . $joined_tables .
      $query . $order .
      // if not all sort options could be compiled, we need to select all
      // values and sort them later
      (($sort === null) && $limit ? " limit {$limit}" .
      ($offset ? " offset {$offset}" : "") : "");
    // messages_debug($query);
    $res = $db_conn->query($query);

    if($res === false) {
      messages_debug("Table '{$this->id}'->get_entries(): query failed");
      return array();
    }

    $ret = array();
    while($elem = $res->fetch()) {
      $ret[] = $elem['id'];
    }
    $res->closeCursor();

    if($sort)
      $ret = $this->sort_custom($ret, $sort);

    if($limit)
      $ret = array_slice($ret, $offset, $limit);

    return $ret;
  }

  function get_entries($filter=array(), $sort=array(), $offset=0, $limit=null) {
    $ids = $this->get_entry_ids($filter, $sort, $offset, $limit);
    return $this->get_entries_by_id($ids);
  }

  function get_entry_count($filter=array()) {
    return sizeof($this->get_entry_ids($filter));
  }
}

function get_db_table($type) {
  global $db_conn;
  global $db_table_cache;

  db_system_init();

  if($type == '__system__')
    return null;

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

  db_system_init();

  $res = $db_conn->query("select * from __system__ where id != '__system__'");
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
