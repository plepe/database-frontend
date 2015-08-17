<?php
class Page_admin_table_views extends Page {
  function content($param) {
     if(!base_access('admin')) {
      page_reload(array("page" => "login", "return_to" => array("page" => "admin_table_views", "table" => $param['table'], "view" => $param['view'])));
      return "Permission denied.";
    }

   $table = get_db_table($param['table']);
    $keys = $table->def();
    $keys['__custom__'] = 'Custom Field';

    $form_def = array(
      'title' => array(
        'type'  => 'text',
	'name'  => 'View Title',
	'req'   => true,
      ),
      'class'   => array(
        'type'   => 'select',
        'name'   => 'View Class',
        'req'    => true,
        'default'=> 'Table',
        'values' => array(
          'Table'       => 'Table of fields',
          'PlainText'   => 'Plain Text',
          'PlainHTML'   => 'Plain HTML',
        ),
      ),
      'fields' => array(
	'type'   => 'form',
	'name'   => 'Fields',
	'count'  => array(
          'default' => 1,
          'show_depend' => array('check', 'class',
            array('is', 'Table'),
          ),
        ),
	'def'    => array(
	  'key' => array(
	    'type' => 'select',
	    'name' => 'Field',
	    'values' => $keys,
	    'values_mode' => 'keys',
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
	    'desc' => 'Specify a different format for this field (mandatory for custom fields). This field uses the <a href="http://twig.sensiolabs.org/">Twig template engine</a>. You can use replacement patterns (see below).',
	    'req' => array("check", "key", array("not", array("is", "__custom__"))),
	  ),
	  'sortable'    => array(
	    'type'	  => 'boolean',
	    'name'	  => 'Sortable',
	    'default'     => true,
	  ),
	),
      ),
      'format_header'   => array(
        'type' => 'textarea',
        'name' => 'Format Header',
        'show_depend' => array('check', 'class',
          array('not', array('is', 'Table')),
        ),
      ),
      'format_each'   => array(
        'type' => 'textarea',
        'name' => 'Format each entry',
        'desc' => 'Specify a format for every entry. This field uses the <a href="http://twig.sensiolabs.org/">Twig template engine</a>. You can use replacement patterns (see below).',
        'show_depend' => array('check', 'class',
          array('not', array('is', 'Table')),
        ),
      ),
      'format_footer'   => array(
        'type' => 'textarea',
        'name' => 'Format Header',
        'show_depend' => array('check', 'class',
          array('not', array('is', 'Table')),
        ),
      ),
      'weight_show' => array(
        'type'   => 'integer',
	'name'   => 'Weight (Show)',
	'default' => 0,
	'desc'   => 'Specify order of Views, when a single entry is shown',
      ),
      'weight_list' => array(
        'type'   => 'integer',
	'name'   => 'Weight (List)',
	'default' => 0,
	'desc'   => 'Specify order of Views, when a list is shown',
      ),
    );

    $form = new form('data', $form_def);

    if($form->is_complete()) {
      $view_data = $form->save_data();

      if(!array_key_exists('views', $table->data))
	$views = array();
      else
	$views = $table->data['views'];

      $view_key = $view_data['title'];

      // if view has been renamed, remove old view name
      if(array_key_exists('view', $param) && ($view_key != $param['view']))
        unset($views[$param['view']]);

      // set new view name
      $views[$view_key] = $view_data;

      $data['views'] = $views;

      $result = $table->save($data, $param['message']);

      if($result === true) {
	messages_add("View saved.");
	page_reload(page_url(array("page" => "admin_table", "table" => $table->id)));
      }
      else {
	messages_add("An error occured while saving: {$result}", MSG_ERROR);
      }
    }

    if($form->is_empty()) {
      $views = $table->data['views'];
      $view_data = array();

      if(isset($param['view']) && (array_key_exists($param['view'], $views))) {
	$view_data = $views[$param['view']];
      }

      $form->set_data($view_data);
    }

    $replacement_patterns = array();
    foreach($table->data['fields'] as $field_id=>$field) {
      $replacement_patterns[] = array(
	'pattern' => "{{ {$field_id} }}",
        'name'    => $field['name'],
      );
    }

    $replacement_patterns = new table(array(
      'pattern' => array(
        'name' => "Pattern",
      ),
      'name' => array(
        'name' => "Name",
      ),
    ), $replacement_patterns);

    return array(
      'template' => 'admin_table_views.html',
      'table' => $param['table'],
      'form' => $form,
      'replacement_patterns' => $replacement_patterns,
    );
  }
}
