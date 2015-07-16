<?php
class View_JSON {
  function set_data($data) {
    $this->data = $data;
  }

  function show() {
    return
      "<pre>\n" .
      htmlspecialchars(json_readable_encode($this->data)) .
      "</pre>\n";
  }
}
