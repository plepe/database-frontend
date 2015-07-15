<?php
class Page_admin_views extends Page {
  function content($param) {
    $table = get_db_table($param['table']);
    $keys = $table->def();
    $keys = array_merge(array(
	'__custom__' => 'Custom Field',
      ), $keys);

    $form_def = array(
      'title' => array(
        'type'  => 'text',
	'name'  => 'View Title',
	'req'   => true,
      ),
      'fields' => array(
	'type'   => 'form',
	'name'   => 'Fields',
	'count'  => array('default' => 1),
	'def'    => array(
	  'key' => array(
	    'type' => 'select',
	    'name' => 'Field',
	    'values' => $keys,
	    'req'  => 'true',
	    'default' => '__custom__',
	  ),
	  'title' => array(
	    'type' => 'text',
	    'name' => 'Title',
	    'show_depend' => array('check', 'key', array('is', '__custom__')),
	  ),
	  'format' => array(
	    'type' => 'textarea',
	    'name' => 'Override Format',
	    'desc' => 'Specify a different format for this field (mandatory for custom fields). You may use replacement patterns.',
	    'req' => array("check", "key", array("not", array("is", "__custom__"))),
	  ),
	),
      ),
      'weight' => array(
        'type'   => 'integer',
	'name'   => 'Weight',
	'default' => 0,
	'desc'   => 'Specify order of Views',
      ),
    );

    $form = new form('data', $form_def);

    if($form->is_complete()) {
      $view_data = $form->save_data();

      if(!array_key_exists('views', $table->data))
	$views = array();
      else
	$views = $table->data['views'];

      $views[$view_data['title']] = $view_data;

      $data = $table->data;
      $data['views'] = $views;

      $table->save($data);

      messages_add("View saved.");

      page_reload();
    }

    if($form->is_empty()) {
      $views = $table->data['views'];
      $view_data = array();

      if(isset($param['view']) && (array_key_exists($param['view'], $views))) {
	$view_data = $views[$param['view']];
      }

      $form->set_data($view_data);
    }

    return array(
      'template' => 'admin_views.html',
      'table' => $param['table'],
      'form' => $form,
    );
  }
}
