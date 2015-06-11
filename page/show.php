<?
class Page_show extends Page {
  function content($param) {
    $object = get_object($param['type'], $param['id']);
    $ret  = "<pre>" . print_r($object, 1) . "</pre>";
    $ret .= "<a href='" . page_url(array("page" => "edit", "type" => $param['type'], "id" => $param['id'] )) . "'>Edit</a>\n|";
    $ret .= "<a href='" . page_url(array("page" => "list", "type" => $param['type'] )) . "'>Back</a>\n";
    return $ret;
  }
}
