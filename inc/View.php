<?php
class View {
  function __construct($def, $param) {
    $this->def = $def;
    $this->id = $def['title'];
    $this->param = $param;
  }

  function set_extract($extract) {
    $this->extract = $extract;
  }
}
