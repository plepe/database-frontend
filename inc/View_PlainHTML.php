<?php
class View_PlainHTML extends View {
  function set_data($data) {
    $this->data = $data;
  }

  function show() {
    $ret = $this->def['format_header'];

    $ret .= twig_render_custom($this->def['format_each'], $this->data[0]);

    $ret .= $this->def['format_footer'];

    return $ret;
  }

  function show_list($entries) {
    $ret = $this->def['format_header'];

    foreach($this->data as $d) {
      $ret .= twig_render_custom($this->def['format_each'], $d);
    }

    $ret .= $this->def['format_footer'];

    return $ret;
  }
}
