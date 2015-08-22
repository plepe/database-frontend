<?php
class Field {
  function __construct($column_id, $column_def, $table) {
    $this->id = $column_id;
    $this->def = $column_def;
    $this->table = $table;
  }

  function type() {
    return substr(get_called_class(), 10);
  }

  function db_type() {
    return 'json';
  }

  function is_multiple() {
    if(array_key_exists('count', $this->def) && $this->def['count'])
      return true;

    return false;
  }

  /**
   * returns the quoted name of the table which holds this field's value
   * @return string quoted table name
   */
  function sql_table_quoted() {
    global $db_conn;

    if($this->is_multiple())
      return $db_conn->quoteIdent($this->table->id . '_' . $this->id);

    return $db_conn->quoteIdent($this->table->id);
  }

  /**
   * returns the quoted name of the column which holds this field's value
   * in the table (see sql_table_quoted())
   * @return string quoted column name
   */
  function sql_column_quoted() {
    global $db_conn;

    if($this->is_multiple())
      return $db_conn->quoteIdent('value');
    else
      return $db_conn->quoteIdent($this->id);
  }

  function default_format($key) {
    if($key === null)
      $key = $this->id;

    return "{{ $key }}";
  }

  function view_def() {
    $ret = $this->def;

    if($this->def['reference']) {
      if($this->is_multiple() === true) {
	$ret['format'] =
	  "<ul class='MultipleValues'>\n" .
	  "{% for _ in {$this->id} %}\n" .
	  "<li><a href='{{ page_url({ \"page\": \"show\", \"table\": \"{$this->def['reference']}\", \"id\": _.id }) }}'>" .
	  $this->default_format("_.id") .
	  "</a>" .
	  "{% endfor %}\n" .
	  "</ul>\n";
      }
      else {
	$ret['format'] =
	  "<a href='{{ page_url({ \"page\": \"show\", \"table\": \"{$this->def['reference']}\", \"id\": {$this->id}.id }) }}'>" .
	  $this->default_format("{$this->id}.id") .
	  "</a>";
      }
    }
    else {
      if($this->is_multiple() === true) {
	$ret['format'] =
	  "<ul class='MultipleValues'>\n" .
	  "{% for _ in {$this->id} %}\n" .
	  "<li>" . $this->default_format("_") . "</li>\n" .
	  "{% endfor %}\n" .
	  "</ul>\n";
      }
      else {
	$ret['format'] = $this->default_format();
      }
    }

    if(!array_key_exists('sortable', $this->def)) {
      $ret['sortable'] = array(
	'type' => 'nat',
      );
    }

    return $ret;
  }

  function compile_filter($def) {
    global $db_conn;

    $column = $this->sql_table_quoted() . '.' . $this->sql_column_quoted();

    switch($def['op']) {
      case 'contains':
        return "{$column} like " . $db_conn->quote('%' . $def['value'] . '%');
      default:
        return null;
    }
  }
}

class Field_text extends Field {
  function db_type() {
    return 'text';
  }
}

class Field_textarea extends Field {
  function db_type() {
    return 'text';
  }

  function default_format($key) {
    if($key === null)
      $key = $this->id;

    return "{{ {$key}|nl2br }}";
  }
}

class FieldWithValues extends Field {
  function default_format($key) {
    if($key === null)
      $key = $this->id;

    if($this->def['reference'])
      return "{{ {$key} }}";
    if($this->def['values_mode'] == 'keys')
      return "{{ " . json_encode($this->def['values']) . "[{$key}]|default({$key}) }}";

    return "{{ {$key} }}";
  }
}

class Field_radio extends FieldWithValues {
  function is_multiple() {
    return false;
  }
}

class Field_checkbox extends FieldWithValues {
  function is_multiple() {
    return true;
  }
}

class Field_select extends FieldWithValues {
  function db_type() {
    return 'text';
  }
}
//
//function get_fields() {
//  $ret = array();
//
//  foreach(get_declared_classes() as $class) {
//    if(substr($class, 0, 10) == "Field_")
//      $ret[substr($class, 10)] = $class;
//  }
//
//  return $ret;
//}
