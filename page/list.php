<?
class Page_list extends Page {
  function content($param) {
    $table = get_db_table($param['table']);

    $ret  = htmlspecialchars($table->name()) . ":\n";

    $data = array();
    foreach(get_db_entries($param['table']) as $o) {
      $data[$o->id] = $o->view();
    }

    $def = $table->def();
    $def['__links'] = array(
      "name" => "",
      "format" => 
        "<a href='" .  
	strtr(page_url(array('page' => 'show', 'table' => $param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Show</a> <a href='" .
	strtr(page_url(array('page' => 'edit', 'table' => $param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Edit</a>",
    );

    $table = new table($def, $data, array("template_engine"=>"twig"));
    $ret .= $table->show();

    $ret .= "<div><a href='" . page_url(array('page' => 'index')) . "'>Index</a> | <a href='" . page_url(array('page' => 'edit', 'table' => $param['table'])) . "'>Create new entry</a></div>\n";

    return $ret;
  }
}
