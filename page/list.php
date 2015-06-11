<?
class Page_list extends Page {
  function content($param) {
    $type = $param['type'];
    $ret  = htmlspecialchars($type) . ":\n";

    $data = array();
    foreach(get_objects($type) as $o) {
      $data[$o->id] = $o->data;
    }

    $def = get_object_type($type)->def();
    $def['__links'] = array(
      "name" => "",
      "format" => 
        "<a href='" .  
	strtr(page_url(array('page' => 'show', 'type' => $type, 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Show</a> <a href='" .
	strtr(page_url(array('page' => 'edit', 'type' => $type, 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Edit</a>",
    );

    $table = new table($def, $data, array("template_engine"=>"twig"));
    $ret .= $table->show();

    $ret .= "<div><a href='" . page_url(array('page' => 'index')) . "'>Index</a> | <a href='" . page_url(array('page' => 'edit', 'type' => $param['type'])) . "'>Create new entry</a></div>\n";

    return $ret;
  }
}
