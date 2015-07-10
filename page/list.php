<?
class Page_list extends Page {
  function content($param) {
    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    $data = array();
    foreach(get_db_entries($param['table']) as $o) {
      $data[$o->id] = $o->view();
    }

    $def = $table->def();
    $def['__links'] = array(
      "name" => "",
      "format" => 
        "<a class='TableLink' href='" .  
	strtr(page_url(array('page' => 'show', 'table' => $param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Show</a> <a class='TableLink' href='" .
	strtr(page_url(array('page' => 'edit', 'table' => $param['table'], 'id' => "ID")
	), array("ID" => "{{ id }}")) .
	"'>Edit</a>",
    );

    $table = new table($def, $data, array("template_engine"=>"twig"));

    return array(
      'template' => 'list.html',
      'table' => $param['table'],
      'view' => $table,
    );
  }
}
