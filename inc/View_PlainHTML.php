<?php
class View_PlainHTML extends View {
  function show() {
    $ret = $this->def['format_header'];
    $object = $this->extract->get(0, 1);

    $ret .= twig_render_custom($this->def['format_each'], $object[0]->view());

    $ret .= $this->def['format_footer'];

    return $ret;
  }

  function show_list($entries) {
    $ret = $this->def['format_header'];

    foreach($this->extract->get() as $d) {
      $ret .= twig_render_custom($this->def['format_each'], $d->view());
    }

    $ret .= $this->def['format_footer'];

    return $ret;
  }
}
