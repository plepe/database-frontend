<?php
class View_JSON {
  function set_data($data) {
    $this->data = $data;
  }

  function show() {
    return
      "<pre class='view_json'>\n" .
      htmlspecialchars(json_readable_encode($this->data)) .
      "</pre>\n";
  }
}
