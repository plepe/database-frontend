<?php
class FieldType {
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

  function need_values() {
    return false;
  }

  function default_format($key) {
    return "{{ {$key} }}";
  }
}

class FieldType_text extends FieldType {
  function db_type() {
    return 'text';
  }
}

class FieldType_textarea extends FieldType {
  function db_type() {
    return 'text';
  }

  function default_format($key) {
    return "{{ {$key}|nl2br }}";
  }
}

class FieldType_date extends FieldType {
  function db_type() {
    return 'date';
  }
}

class FieldType_datetime extends FieldType {
  function db_type() {
    return 'datetime';
  }
}

class FieldType_radio extends FieldType {
  function is_multiple() {
    return false;
  }

  function need_values() {
    return true;
  }
}

class FieldType_checkbox extends FieldType {
  function is_multiple() {
    return true;
  }

  function need_values() {
    return true;
  }
}

class FieldType_select extends FieldType {
  function db_type() {
    return 'text';
  }

  function need_values() {
    return true;
  }
}

class FieldType_boolean extends FieldType {
  function db_type() {
    return 'bool';
  }
}

class FieldType_random extends FieldType {
  function db_type() {
    return 'text';
  }
}

function get_field_types() {
  $ret = array();

  foreach(get_declared_classes() as $class) {
    if(substr($class, 0, 10) == "FieldType_")
      $ret[substr($class, 10)] = new $class();
  }

  return $ret;
}
