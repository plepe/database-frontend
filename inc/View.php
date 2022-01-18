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

register_hook('session_regexp_allowed', function (&$ret) {
  $ret[] = '/^(.*)_view_(list|show|sort|sort_dir)$/';
});
