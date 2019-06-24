<?php
class Field {
  function __construct($column_id, $column_def, $table) {
    $this->id = $column_id;
    $this->def = $column_def;
    $this->table = $table;
  }

  function type() {
    return substr(get_called_class(), 6);
  }

  function name() {
    return $this->def['name'];
  }

  function form_type() {
    return substr(get_called_class(), 6);
  }

  function db_type() {
    return 'json';
  }

  function is_multiple() {
    if (!isset($this)) {
      return null;
    }

    if(array_key_exists('count', $this->def) && $this->def['count'])
      return true;

    return false;
  }

  function need_values() {
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

  function default_format($key=null) {
    if($key === null)
      $key = $this->id;

    return "{{ $key }}";
  }

  function view_def() {
    $ret = $this->def;

    if($this->def['reference'] || $this->def['backreference']) {
      $ref_table = null;
      if ($this->def['reference']) {
        $ref_table = $this->def['reference'];
      }
      else {
        list($ref_table, $ref_field) = explode(':', $this->def['backreference']);
      }
      $ref_table = json_encode($ref_table);

      if($this->is_multiple() === true) {
	$ret['format'] =
	  "<ul class='MultipleValues'>\n" .
	  "{% for _ in {$this->id} %}\n" .
	  "<li><a href='{{ page_url({ \"page\": \"show\", \"table\": {$ref_table}, \"id\": _.id }) }}'>" .
          "{{ entry_title({$ref_table}, _.id) }}" .
	  "</a>" .
	  "{% endfor %}\n" .
	  "</ul>\n";
      }
      else {
	$ret['format'] =
	  "<a href='{{ page_url({ \"page\": \"show\", \"table\": {$ref_table}, \"id\": {$this->id}.id }) }}'>" .
          "{{ entry_title({$ref_table}, {$this->id}.id) }}" .
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

    $ret = array(
      'table' => $this->sql_table_quoted(),
      'id_field' => 'id',
      'query' => '',
    );

    $column = $this->sql_table_quoted() . '.' . $this->sql_column_quoted();

    switch($def['op']) {
      case 'contains':
        $ret['query'] = "{$column} like " . $db_conn->quote('%' . $def['value'] . '%');
        break;
      case 'is':
        $ret['query'] = "{$column}=" . $db_conn->quote($def['value']);
        break;
      case '>':
      case '>=':
      case '<':
      case '<=':
        $ret['query'] = "{$column}{$def['op']}" . $db_conn->quote($def['value']);
        break;
      default:
        return null;
    }

    return $ret;
  }

  function compile_sort($def) {
    global $db_conn;

    $ret = array(
      'table' => $this->sql_table_quoted(),
      'id_field' => 'id',
      'sort' => '',
      'select' => $this->sql_table_quoted() . '.' . $this->sql_column_quoted()
    );

    if($def['type'] == 'nat')
      return null;

    $modifier = "";

    $column = $this->sql_table_quoted() . '.' . $this->sql_column_quoted();

    if($def['type'] == 'alpha')
      $modifier = "BINARY";
    elseif(($def['type'] == 'num') || ($def['type'] == 'numeric'))
      $modifier = "1 *";

    if(isset($def['null'])) {
      switch($def['null']) {
        case 'higher':
          $ret['sort'] .= $column . ' is null ' . (array_key_exists('dir', $def) && ($def['dir'] == 'desc') ? ' desc' : ' asc') . ', ';
          break;
        case 'first':
          $ret['sort'] .= $column . ' is null desc, ';
          break;
        case 'last':
          $ret['sort'] .= $column . ' is null asc, ';
          break;
        case 'lower':
        default:
      }
    }

    $ret['sort'] .= $modifier . ' ' . $column . (array_key_exists('dir', $def) && ($def['dir'] == 'desc') ? ' desc' : ' asc');

    return $ret;
  }

  function filters() {
    return array(
      'contains' => array(
        'name' => 'contains',
        'value_type' => 'text'
      ),
      'is' => array(
        'name' => 'is',
        'value_type' => 'text'
      ),
    );
  }

  function additional_form_def() {
    return array();
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

  function default_format($key=null) {
    if($key === null)
      $key = $this->id;

    return "{{ {$key}|nl2br }}";
  }
}

class Field_integer extends Field {
  function db_type() {
    return 'integer';
  }
}

class Field_float extends Field {
  function db_type() {
    return 'real';
  }
}

class Field_date extends Field {
  function db_type() {
    return 'date';
  }

  function default_format($key=null) {
    if($key === null)
      $key = $this->id;

    return "{% if {$key} %}{{ {$key}|date('j.n.Y') }}{% endif %}";
  }
}

class Field_datetime extends Field {
  function db_type() {
    return 'datetime';
  }

  function form_type() {
    return 'datetime-local';
  }

  function default_format($key=null) {
    if($key === null)
      $key = $this->id;

    return "{% if {$key} %}{{ {$key}|date('j.n.Y G:i:s') }}{% endif %}";
  }
}

class FieldWithValues extends Field {
  function default_format($key=null) {
    if($key === null)
      $key = $this->id;

    if($this->def['reference'])
      return "{{ {$key} }}";
    if($this->def['values_mode'] == 'keys')
      return "{{ " . json_encode($this->def['values']) . "[{$key}]|default({$key}) }}";

    return "{{ {$key} }}";
  }

  function need_values() {
    return true;
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

class Field_autocomplete extends FieldWithValues {
  function db_type() {
    return 'text';
  }
}

class Field_boolean extends Field {
  function db_type() {
    return 'boolean';
  }

  function default_format($key=null) {
    if($key === null)
      $key = $this->id;

    return <<<EOT
{% if {$key} is null %}
{% elseif {$key} == true %}
true
{% else %}
false
{% endif %}
EOT
;
  }
}

class Field_random extends Field {
  function __construct($column_id, $column_def, $table) {
    global $db_conn;

    parent::__construct($column_id, $column_def, $table);

    $this->generator = new RandomIdGenerator(array(
      'id' => $table->id . '_' . $column_id,
      'db' => $db_conn,
      'length' => array_key_exists('random-id-length', $column_def) ? $column_def['random-id-length'] : 4,
      'prefix' => array_key_exists('random-id-prefix', $column_def) ? $column_def['random-id-prefix'] : '',
    ));
    $this->generator->setCheckFun(function ($id) use ($table, $column_id) {
      $entries = $table->get_entry_ids(array(array('field' => $column_id, 'op' => 'is', 'value' => $id)));
      return !!sizeof($entries);
    });
  }

  function form_type() {
    return 'text';
  }

  function additional_form_def() {
    $ret = parent::additional_form_def();

    $ret['default_func'] = array(
      'php' => "random_ids_get",
      'js' => "random_ids_get",
    );
    $ret['random-ids-id'] = $this->generator->id;
    $this->generator->exportToJs(32);

    return $ret;
  }
}

class Field_backreference extends FieldWithValues {
  function db_type() {
    return null;
  }

  function form_type() {
    return 'display';
  }

  function is_multiple() {
    return true;
  }
}

function get_field_types() {
  $ret = array();

  foreach(get_declared_classes() as $class) {
    if(substr($class, 0, 6) == "Field_")
      $ret[substr($class, 6)] = $class;
  }

  return $ret;
}
