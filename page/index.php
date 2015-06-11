<?
class Page_index extends Page {
  function content($param) {
    $ret  = "The following types exist:\n";
    $ret .= "<ul>";
    foreach(get_object_types() as $type) {
      $ret .= "<li><a href='" . page_url(array("page" => "list", "type" => $type->id)) . "'>" .
	$type->name() . "</a></li>\n";
    }
    $ret .= "</ul>\n";

    return $ret;
  }
}
