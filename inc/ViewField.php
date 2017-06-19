<?php
class ViewField {
  function __construct($def) {
    $this->def = $def;
    $this->id = $this->def['id'];
  }

  function id() {
    return $this->def['id'];
  }

  function view_def() {
    return $this->def;
  }
}
