<?php
class Page_admin {
  function content() {
    $data = array(
      'tables' => array()
    );

    foreach(get_db_tables() as $type) {
      $data['tables'][] = $type->view();
    }

    return array(
      'template' => "admin.html",
      'data' => $data,
    );
  }
}
