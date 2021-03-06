<?php
class DB_Entry {
  function __construct($type, $id=null, $data=null) {
    $this->type = $type;
    $this->table = get_db_table($type);

    if($id === null) { // new object
      $this->id = null;
      $this->data = array();
    }
    else {
      $this->id = $id;
      $this->data = $data;
    }
  }

  function data($key=null) {
    if($this->data === null)
      $this->load();

    if($key !== null)
      return $this->data[$key];

    return $this->data;
  }

  function title() {
    return twig_render_custom($this->table->title_format(), $this->view());
  }

  /**
   * load - (re-)load data from database
   */
  function load() {
    global $db_conn;

    $this->data = $this->table->load_entries_data(array($this->id));
    $this->data = $this->data[$this->id];
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

      if ($field->db_type() === null) {
        continue;
      }

      // the field has multiple values -> use extra table
      if($field->is_multiple() === true) {
        if($this->id !== null)
          $cmds[] = "delete from " . $db_conn->quoteIdent($this->type . '_' . $column_id) .
            " where " . $db_conn->quoteIdent('id') . "=" . $db_conn->quote($this->id);

        if (isset($field->def['reference']) && $field->def['reference']) {
          foreach ($this->data[$column_id] as $ref_id) {
            $changeset->add(get_db_table($field->def['reference'])->get_entry($ref_id));
          }
          foreach ($data[$column_id] as $ref_id) {
            $changeset->add(get_db_table($field->def['reference'])->get_entry($ref_id));
          }
        }
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
    elseif(sizeof($set)) { // only update when at least one column changes
      $query = "update " . $db_conn->quoteIdent($this->type) . " set " .
	implode(", ", $set) . " where " . $db_conn->quoteIdent('id') . "=" .
	$db_conn->quote($this->id);
    }
    else
      $query = null;

    if(isset($debug) && $debug)
      messages_debug($query);

    if($query && $db_conn->query($query) === false) {
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

          if ($field->db_type() === null) {
            continue;
          }

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
      if (is_array($table)) {
        if ($table['type'] === 'backreference') {
          $query = "delete from " . $db_conn->quoteIdent($table['table']) . " where value=" . $db_conn->quote($this->id);
        } else {
          throw new Exception('unknown table type');
        }
      }
      else {
        $query = "delete from " . $db_conn->quoteIdent($this->type . '_' . $table) . " where id=" . $db_conn->quote($this->id);
      }

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

    $this->view_cache = $this->data();

    foreach($this->table->fields() as $field) {
      $k = $field->id;
      if(array_key_exists('reference', $field->def) && ($field->def['reference'] != null)) {
	if($field->is_multiple() === true) {
	  $this->view_cache[$k] = array();
	  foreach($this->data[$k] as $v) {
              $o = get_db_table($field->def['reference'])->get_entry($v);
              if($o)
                $this->view_cache[$k][] = &$o->view();
          }
        }
	else {
	  if($this->data[$k]) {
	    $o = get_db_table($field->def['reference'])->get_entry($this->data[$k]);
	    if($o)
	      $this->view_cache[$k] = &$o->view();
	  }
	}
      }

      if(array_key_exists('backreference', $field->def) && ($field->def['backreference'] != null)) {
        // backreferences are always multiple
	$this->view_cache[$k] = array();
        list($ref_table, $ref_field) = explode(':', $field->def['backreference']);
	foreach($this->data[$k] as $v) {
          $o = get_db_table($ref_table)->get_entry($v);
          if($o)
            $this->view_cache[$k][] = &$o->view();
	}
      }
    }

    return $this->view_cache;
  }
}
