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

  /**
   * true ... result is always an array
   * false ... result is always a single value
   * null ... may be part of an array
   */
  function is_multiple() {
    return null;
  }

  function default_format($key) {
    return "{{ {$key} }}";
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
    return "{{ {$key}|nl2br }}";
  }
}

class Field_radio extends Field {
  function is_multiple() {
    return false;
  }
}

class Field_checkbox extends Field {
  function is_multiple() {
    return true;
  }
}

class Field_select extends Field {
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
