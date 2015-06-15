<?
class Page_show extends Page {
  function content($param) {
    $ret = "";

    $table = get_db_table($param['table']);
    $object = get_db_entry($param['table'], $param['id']);

    $def = $table->def();
    $table = new table($def, array($object->view()), array("template_engine"=>"twig"));
    $ret .= $table->show("html-transposed");

    $ret .= "<a href='" . page_url(array("page" => "edit", "table" => $param['table'], "id" => $param['id'] )) . "'>Edit</a>\n|";
    $ret .= "<a href='" . page_url(array("page" => "list", "table" => $param['table'] )) . "'>Back</a>\n";
    return $ret;
  }
}
