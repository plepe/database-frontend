<?
class Page_show extends Page {
  function content($param) {
    $ret = "";

    $type = get_object_type($param['type']);
    $object = get_object($param['type'], $param['id']);

    $def = $type->def();
    $table = new table($def, array($object->view()), array("template_engine"=>"twig"));
    $ret .= $table->show("html-transposed");

    $ret .= "<a href='" . page_url(array("page" => "edit", "type" => $param['type'], "id" => $param['id'] )) . "'>Edit</a>\n|";
    $ret .= "<a href='" . page_url(array("page" => "list", "type" => $param['type'] )) . "'>Back</a>\n";
    return $ret;
  }
}
