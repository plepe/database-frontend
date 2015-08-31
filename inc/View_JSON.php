<?php
class View_JSON extends View {
  function show() {
    $object = $this->extract->get(0, 1);

    return
      "<pre class='view_json'>\n" .
      htmlspecialchars(json_readable_encode($object[0]->view())) .
      "</pre>\n";
  }
}
