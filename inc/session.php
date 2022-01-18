<?php
register_hook('init', function () {
  $regexp = array();

  call_hooks('session_regexp_allowed', $regexp);

  $ret = array();
  foreach ($_SESSION as $k => $v) {
    foreach ($regexp as $r) {
      if (preg_match($r, $k)) {
        $ret[$k] = $v;
      }
    }
  }

  html_export_var(array('session_vars' => $ret));
});
