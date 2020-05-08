<?php
class Field_user extends Field {
  function db_type () {
    return 'text';
  }

  function form_type () {
    return 'text';
  }

  function db_quote ($value, $db_conn) {
    global $auth;

    if ($value === '@current@') {
      return $db_conn->quote($auth->current_user()->id());
    }
    else {
      return $db_conn->quote($value);
    }
  }
}

register_hook("twig_init", function() {
  global $twig;
  $twig->addFilter(new Twig_SimpleFilter('user_username', function($value) {
    if (!$value) {
      return '';
    }

    return explode('@', $value)[0];
  }));

  $twig->addFilter(new Twig_SimpleFilter('user_domain', function($value) {
    if (!$value) {
      return '';
    }

    return explode('@', $value)[1];
  }));
});
