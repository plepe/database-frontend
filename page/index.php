<?
class Page_index extends Page {
  function content($param) {
    $data = array(
      'tables' => array()
    );

    foreach(get_db_tables() as $type) {
      $data['tables'][] = $type->view();
    }

    return twig_render("index.html", $data);
  }
}
