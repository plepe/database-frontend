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

register_hook("twig_init", function() {
  global $twig;
  $twig->addFilter(new Twig_SimpleFilter('markdown', function($value) {
    $descspec = array(
      0 => array('pipe', 'r'),
      1 => array('pipe', 'w')
    );

    $proc = proc_open('bin/marked', $descspec, $pipes);

    if (is_resource($proc)) {
      fwrite($pipes[0], $value);
      fclose($pipes[0]);

      $result = stream_get_contents($pipes[1]);

      fclose($pipes[1]);
    }

    proc_close($proc);
    return $result;
  }, array("is_safe"=>array("html"))));
});
