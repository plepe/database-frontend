<?php
$db_table_cache = array();
$db_table_cache_complete = false;

class DB_Table {
  function __construct($type, $data) {
    $this->id = $type;
    $this->entries_cache = array();

    $this->_load($data);
  }

  function clear_cache () {
    unset($this->data);
    unset($this->_fields);
    $this->entries_cache = array();
  }

  function _load($data=null) {
    if (!$data) {
      global $db_conn;
      $res = $db_conn->query("select * from __system__ where id=" . $db_conn->quote($this->id));

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
      }
    }

    $this->data = $data;

    if (!$this->data) {
      return;
    }

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
    if (!array_key_exists('default', $this->data['views'])) {
      $r = $this->view_default();
      $r['title'] = "default";
      $r['auto_add_new_fields'] = true;
      $this->data['views']['default'] = $r;
    }
    if (!isset($this->data['default_view_show'])) {
      $this->data['default_view_show'] = 'default';
    }
    if (!isset($this->data['default_view_list'])) {
      $this->data['default_view_list'] = 'default';
    }
  }
  
  function name() {
    $name = $this->data('name');
    return $name ? $name : $this->id;
  }

  function data($key=null) {
    if (!isset($this->data)) {
      $this->_load();
    }

    if($key !== null) {
      if (array_key_exists($key, $this->data)) {
        return $this->data[$key];
      }
      else {
        return null;
      }
    }

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

      if ($this->data('ts')) {
        $this->_fields['ts'] = new Field_datetime('ts', array(
          'name' => 'Timestamp',
          'count' => null,
          'sortable' => true,
        ), $this);
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

  function view_fields() {
    $ret = $this->fields();

    foreach (get_db_tables() as $table_id => $table) {
      foreach ($table->data('fields') as $field_id => $field) {
        if (isset($field['reference']) && $field['reference'] == $this->id) {
          $ref_field = array(
            'id' => "__reference:{$table_id}:{$field_id}__",
            'src_table' => $this->id,
            'dest_table' => $table_id,
            'dest_field' => $field_id,
          );
          $ret[$ref_field['id']] = new ViewBackreferenceField($ref_field, $field);
        }
      }
    }

    foreach ($this->views() as $view_id => $view) {
      if ($view['class'] !== 'Table') {
        continue;
      }

      foreach ($view['fields'] as $field_num => $field) {
        if ($field['key'] === '__custom__' || $field['format']) {
          $field['name'] =
            $field['key'] === '__custom__'
              ? $field['title'] . " (View: {$view['title']})"
              : $this->field($field['key'])->name() . " (View: {$view['title']})";
          $field['id'] = "__custom:{$view_id}:{$field_num}__";
          $ret[$field['id']] = new ViewField($field);
        }
      }
    }

    return $ret;
  }

  /**
   * return list of field ids whose values are stored in sub tables
   */
  function column_tables() {
    $ret = array();

    foreach($this->fields() as $fid => $field) {
      if (isset($field->def['backreference'])) {
        list($ref_table, $ref_field) = explode(':', $field->def['backreference']);
        $ret[] = array(
          'type' => 'backreference',
          'table' => $ref_table . '_' . $ref_field,
          'field_id' => $fid,
          'id' => 'value',
          'value' => 'id',
        );
      }
      elseif($field->is_multiple() === true) {
	$ret[] = $field->id;
      }
    }

    return $ret;
  }

  /**
   * Return definition of ID field
   */
  function id_field ($data=null) {
    global $db_conn;

    if ($data === null) {
      $data = $this->data;
    }

    if(!array_key_exists('id', $data['fields'])) {
      return array(
        'key' => 'id',
        'type' => 'integer',
        'options' => 'auto_increment primary key',
      );
    }
    else {
      switch($db_conn->getAttribute(PDO::ATTR_DRIVER_NAME)) {
        case 'sqlite':
          $id_collate = "binary";
          break;
        case 'mysql':
          $id_collate = "utf8_bin";
          break;
      }

      return array(
        'key' => 'id',
        'type' => "varchar(255) collate {$id_collate}",
        'options' => '',
      );
    }
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
    if($this->id && isset($this->old_id)) {
      $new_table = !$db_conn->tableExists($this->old_id);
    }
    else
      $new_table = true;

    // if there's no 'id' field specified, add a generated one
    $id_field = $this->id_field($data);
    $id_type = $id_field['type'];
    if(!array_key_exists('id', $data['fields'])) {
      $columns[] = $db_conn->quoteident('id') . " {$id_field['type']} {$id_field['options']}";
      $column_copy[] = $db_conn->quoteident('id');
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
        $foreign_id_field = get_db_table($column_def['reference'])->id_field();
        $column_type = $foreign_id_field['type'];
      }

      if(array_key_exists('backreference', $column_def) && ($column_def['backreference'] != null)) {
        // Backreference -> don't need to create a field/table!
        continue;
      }

      $r = $db_conn->quoteIdent($column) . " " . $column_type;

      if(array_key_exists($column_def['type'], $field_types))
	$field_type = $field_types[$column_def['type']];
      else
	$field_type = Field;

      $field = new $field_type($column, $column_def, $this);

      // the field has multiple values -> create a separate table
      // to hold this data.
      if(($field->is_multiple() === true) || ($column_def['count'])) {
	$multifield_cmds[] = "create table " . $db_conn->quoteIdent($tmp_name . '_' . $column) . " (\n" .
		  "  " . $db_conn->quoteIdent('id') . " {$id_type} not null,\n" .
		  "  " . $db_conn->quoteIdent('sequence') . " int not null,\n" .
		  "  " . $db_conn->quoteIdent('key') . " varchar(255) not null,\n" .
		  "  " . $db_conn->quoteIdent('value') . " {$column_type} null,\n" .
                  "  " . (array_key_exists('ts', $data) && $data['ts'] ? $db_conn->quoteIdent('ts') . " TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" : '') .

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
	    $old_field_type = Field;

          $old_field = new $old_field_type($column_def['old_key'], $old_def, $this);

	  // ... it was already a field with multiple values
	  if(($old_field->is_multiple() === true) || ($old_def['count'])) {
	    if($db_conn->tableExists($this->old_id . '_' . $column_def['old_key']))
	      $multifield_cmds[] = "insert into " . $db_conn->quoteIdent($tmp_name . '_' . $column) .
		    "  select " .
                    $db_conn->quoteIdent('id') . ', ' .  $db_conn->quoteIdent('sequence') . ', ' . $db_conn->quoteIdent('key') . ', ' . $db_conn->quoteIdent('value') .
                    ($data['ts'] ?
                      (array_key_exists('ts', $old_data) && $old_data['ts'] ? ", " . $db_conn->quoteIdent('ts') : ", now()") :
                      ""
                    ) .
                    " from " . $db_conn->quoteIdent($this->old_id . '_' . $column_def['old_key']) . ";";
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
	    $old_field_type = Field;

          $old_field = new $old_field_type($column_def['old_key'], $old_def, $this);

	  // ... it was a field with multiple values -> aggregate data and concatenate by ';'
	  if(($old_field->is_multiple() === true) || ($old_def['count'])) {
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

    if (array_key_exists('ts', $data) && $data['ts']) {
      $columns[] = $db_conn->quoteIdent('ts') . " TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
      $column_copy[] = $old_data['ts'] ? $db_conn->quoteIdent('ts') : 'now()';
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
	  $old_field_type = Field;

        $old_field = new $old_field_type($column_def['old_key'], $old_def, $this);
	// ... it was already a field with multiple values
	if(($old_field->is_multiple() === true) || ($old_def['count'])) {
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
          // Todo: title() may remove HTML
          $values[$o->id] = $o->title();
        }
	$ret[$k]['values'] = $values;
	if(!array_key_exists('format', $ret[$k]))
	  $ret[$k]['format'] = "{{ {$k}.name }}";
      }

      if(array_key_exists('backreference', $d) && ($d['backreference'] !== null)) {
        list($ref_table, $ref_field) = explode(':', $d['backreference']);
        foreach(get_db_table($ref_table)->get_entries() as $o) {
          // Todo: title() may remove HTML
          $values[$o->id] = $o->title();
        }
	$ret[$k]['values'] = $values;
	if(!array_key_exists('format', $ret[$k]))
	  $ret[$k]['format'] = "{{ {$k}.name }}";
      }

      $ret[$k]['type'] = $field->form_type();

      foreach ($field->additional_form_def() as $dk => $dv) {
        $ret[$k][$dk] = $dv;
      }
    }

    return $ret;
  }

  function views($type='list') { // type: 'list' or 'show'
    $views = array();

    if(array_key_exists('views', $this->data))
      $views = $this->data['views'];

    if($type == 'show') {
      $views['json'] = array(
        'title' => 'JSON',
        "weight" => 100,
        'class' => 'JSON',
      );
    }

    $views = weight_sort($views, "weight");

    return $views;
  }

  function view_default() {
    $def = array();

    // special formats for default view
    // * referenced tables
    // * fields with multiple values
    foreach($this->fields() as $column_id=>$field) {
      $def[$column_id] = array(
        'key' => $column_id,
      );
    }

    return array(
      'title' => 'Default',
      'weight' => -1,
      'fields' => $def,
      'class' => 'Table',
    );
  }

  function view_def($k) {
    if($k == 'json')
      return array(
        'title' => 'JSON',
        'class' => 'JSON',
        'weight' => 100,
        'fields' => $this->def(),
      );

    if(!array_key_exists($k, $this->data['views'])) {
      messages_add("View does not exist!", MSG_ERROR);
      return false;
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

  function title_format($prefix='') {
    if ($template = $this->data('title')) {
      return $template;
    }

    return '{{ id }}';
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

      $query = "insert into __system__ (id, data) values (" .
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
    if (isset($old_id)) {
      unset($db_table_cache[$old_id]);
    }
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
	$field_type = Field;

      $field = new $field_type($column, $column_def, $this);

      if(($field->is_multiple() === true) || ($column_def['count']))
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

        $ret[] = $r;
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
	  $ret[] = $r;
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

  function _timestamp_query () {
    global $db_conn;

    if (!$this->data('ts')) {
      return '';
    }

    $ts = array();
    foreach($this->column_tables() as $table) {
      if (is_array($table)) {
      }
      else {
        $ts[] = 'coalesce((select max(ts) from ' . $db_conn->quoteIdent($this->id . '_' . $table) . " where " . $db_conn->quoteIdent($this->id) . '.id=' . $db_conn->quoteIdent($this->id . '_' . $table) . '.id), ' . $db_conn->quote('') . ')';
      }
    }
    if (sizeof($ts)) {
      return ', greatest(ts, ' . implode(', ', $ts) . ') as ts';
    }
    else {
      return ', ts';
    }
  }

  function entries_timestamps ($after=null) {
    global $db_conn;

    if (!$this->data('ts')) {
      return null;
    }

    $ts = $this->_timestamp_query();
    $data = array();
    $res = $db_conn->query("select * from (select id {$ts} from " . $db_conn->quoteIdent($this->id) . ') t' . ($after ? ' where ts>' . $db_conn->quote((new DateTime($after))->format('Y-m-d H:i:s')) : ''));

    while($elem = $res->fetch()) {
      $data[$elem['id']] = $elem['ts'];
    }
    $res->closeCursor();

    return $data;
  }

  function load_entries_data($ids) {
    global $db_conn;
    $data = array();

    $where_quoted = implode(" or ", array_map(function($x) {
      global $db_conn;
      return "id=" . $db_conn->quote($x);
    }, $ids));

    $where_quoted_backreference = implode(" or ", array_map(function($x) {
      global $db_conn;
      return "value=" . $db_conn->quote($x);
    }, $ids));

    $ts = $this->_timestamp_query();

    $res = $db_conn->query("select * {$ts} from " . $db_conn->quoteIdent($this->id) . " where " . $where_quoted);
    while($elem = $res->fetch()) {
      $data[$elem['id']] = $elem;
    }
    $res->closeCursor();

    foreach($this->column_tables() as $table) {
      if (is_array($table)) {
        if ($table['type'] === 'backreference') {
          $res = $db_conn->query("select * from " . $db_conn->quoteIdent($table['table']) . " where " . $where_quoted_backreference);

          // initialize property with empty array for all to-be-loaded entries
          foreach($data as $id=>$d)
            $data[$id][$table['field_id']] = array();

          while($elem = $res->fetch())
            $data[$elem[$table['id']]][$table['field_id']][] = $elem[$table['value']];
          $res->closeCursor();
        }
      }
      else {
        $res = $db_conn->query("select * from " . $db_conn->quoteIdent($this->id . '_' . $table) . " where " . $where_quoted);

        // initialize property with empty array for all to-be-loaded entries
        foreach($data as $id=>$d)
          $data[$id][$table] = array();

        while($elem = $res->fetch()) {
          $data[$elem['id']][$table][$elem['key']] = $elem['value'];

          if ($this->data('ts') && $elem['ts'] > $data[$elem['id']]['ts']) {
            $data[$elem['id']]['ts'] = $elem['ts'];
          }
        }

        $res->closeCursor();
      }
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

  function create_entry($id) {
    return new DB_Entry($id);
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
    $select = array();
    if($compiled_filter !== null) foreach($compiled_filter as $f) {
      if(array_key_exists('table', $f))
        $tables[$f['table']] = $f['id_field'];
      if (array_key_exists('select', $f))
        $select[] = $f['select'];

      $query[] = $f['query'];
    }

    $order = array();
    if($compiled_sort !== null) foreach($compiled_sort as $f) {
      if(array_key_exists('table', $f))
        $tables[$f['table']] = $f['id_field'];
      if (array_key_exists('select', $f))
        $select[] = $f['select'];

      $order[] = $f['sort'];
    }

    $main_table_quoted = $db_conn->quoteIdent($this->id);
    unset($tables[$main_table_quoted]);

    $joined_tables = "";
    foreach($tables as $t=>$column) { // $t is always quoted
      $joined_tables .= " left join {$t} on {$main_table_quoted}.id = {$t}.{$column}";
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

    if (sizeof($select)) {
      $select = ', ' . implode(', ', $select) . ' ';
    }
    else {
      $select = ' ';
    }

    $query =
      "select distinct " . $db_conn->quoteIdent($this->id) . ".id " .
      $select .
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

  function access ($type='view') {
    return base_access($type) && access($this->data("access_{$type}"));
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

function get_db_table_viewable ($table_id) {
  $table = get_db_table($table_id);

  if (!$table->access('view')) {
    return false;
  }

  return $table;
}

function get_db_tables() {
  global $db_conn;
  global $db_table_cache;
  global $db_table_cache_complete;
  $ret = array();

  if ($db_table_cache_complete) {
    return $db_table_cache;
  }

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

    $ret[$elem['id']] = $db_table_cache[$elem['id']];
  }
  $res->closeCursor();

  $db_table_cache_complete = true;
  $db_table_cache = $ret;
  return $db_table_cache;
}

function get_db_tables_viewable () {
  $tables = get_db_tables();

  $tables = array_filter($tables, function ($table) {
    return $table->access('view');
  });

  return $tables;
}

function get_db_table_names () {
  $ret = array();

  $tables = get_db_tables();

  usort($tables, function ($a, $b) {
    $weight_a = $a->data('weight') ?? 0;
    $weight_b = $b->data('weight') ?? 0;

    if ($weight_a === $weight_b) {
      return $a->name() <=> $b->name();
    }
    else {
      return $weight_a - $weight_b;
    }
  });

  foreach($tables as $type) {
    $ret[$type->id] = $type->name();
  }

  return $ret;
}
