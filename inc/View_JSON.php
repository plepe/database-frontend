<?php
class View_JSON extends View {
  function show() {
    return
      "<pre class='view_json'>\n" .
      htmlspecialchars(json_readable_encode($this->data[0])) .
      "</pre>\n";
  }
}
