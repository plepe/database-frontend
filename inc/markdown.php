<?php
class Field_markdown extends Field {
  function db_type() {
    return 'text';
  }

  function default_format($key=null) {
    if($key === null)
      $key = $this->id;

    return "{{ {$key}|markdown }}";
  }
}

function markdown_format ($value) {
  global $markdown_pipes;

  if (!isset($markdown_pipes)) {
    $descspec = array(
      0 => array('pipe', 'r'),
      1 => array('pipe', 'w')
    );

    $proc = proc_open('bin/marked', $descspec, $markdown_pipes);

    if (!is_resource($proc)) {
      messages_add("Can't start marked process");
    }
  }

  fwrite($markdown_pipes[0], strtr($value, array("\\" => "\\\\", "\n" => "\\n")) . "\n");
  fflush($markdown_pipes[0]);
  $result = fgets($markdown_pipes[1]);

  $result = strtr($value, array("\\\\" => "\\", "\\n" => "\n"));

  return $result;
}


register_hook("twig_init", function() {
  global $twig;
  $twig->addFilter(new Twig_SimpleFilter('markdown', function($value) {
    return markdown_format($value)
  }, array("is_safe"=>array("html"))));
});
