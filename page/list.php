<?
class Page_list extends Page {
  function content($param) {
    $type = get_object_type($param['type']);

    $ret  = htmlspecialchars($type->name()) . ":\n";

    $data = array();
    foreach(get_objects($param['type']) as $o) {
      $data[$o->id] = $o->data;
    }

    $def = $type->def();
    $def['__links'] = array(
      "name" => "",
      "format" => 
        "<a href='" .  
	strtr(page_url(array('page' => 'show', 'type' => $param['type'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Show</a> <a href='" .
	strtr(page_url(array('page' => 'edit', 'type' => $param['type'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Edit</a>",
    );

    $table = new table($def, $data, array("template_engine"=>"twig"));
    $ret .= $table->show();

    $ret .= "<div><a href='" . page_url(array('page' => 'index')) . "'>Index</a> | <a href='" . page_url(array('page' => 'edit', 'type' => $param['type'])) . "'>Create new entry</a></div>\n";

    return $ret;
  }
}
