<?php
class View_JSON extends View {
  function show() {
    $object = array_values($this->extract->get(0, 1))[0];

    return
      "<pre class='view_json'>\n" .
      htmlspecialchars(json_readable_encode($object->view())) .
      "</pre>\n";
  }
}
