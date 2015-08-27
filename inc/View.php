<?php
class View {
  function __construct($def, $param) {
    $this->def = $def;
    $this->param = $param;
  }

  function set_extract($extract) {
    $this->extract = $extract;
  }
}
