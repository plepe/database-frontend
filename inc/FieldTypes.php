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
}

class FieldType_radio extends FieldType {
  function is_multiple() {
    return false;
  }
}

class FieldType_checkbox extends FieldType {
  function is_multiple() {
    return true;
  }
}

class FieldType_select extends FieldType {
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
