<?php
register_hook("init", function() {
  global $auth;

  $auth = new Auth();
});
