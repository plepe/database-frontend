<?php
class Page_admin_table_views extends Page {
  function content() {
    global $app;

    if(!base_access('admin')) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "admin_table_views", "table" => $this->param['table'], "view" => $this->param['view'])));
      return "Permission denied.";
    }

    $table = get_db_table($this->param['table']);
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
          'index_type' => 'array',
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
          'sortable_custom'    => array(
            'type'      => 'radio',
            'name'      => 'Sort options',
            'default'     => 'default',
            'values'      => array(
              'default'     => 'Use sort options from fields definition',
              'simple'      => 'Simple sort options',
              'custom'      => 'Use custom sort options',
            ),
          ),
          'sortable'    => array(
            'type'        => 'switch',
            'switch'      => 'sortable_custom',
            'def'         => array(
              'default'     => array(
                'type'        => 'fixed',
                'name'        => 'Sortable',
                'value'       => null,
                'show_depend' => false,
              ),
              'simple'      => array(
                'type'        => 'boolean',
                'name'        => 'Sortable',
                'default'     => true,
              ),
              'custom'      => array(
                'type'        => 'form',
                'name'        => 'Sortable options',
                'def'         => array(
                  'type'        => array(
                    'name'        => 'Sort type',
                    'type'        => 'radio',
                    'values'      => array(
                      'alpha'       => 'Alphabetic, case sensitive',
                      'case'        => 'Alphabetic, case insensitive',
                      'num'         => 'Numeric',
                      'nat'         => 'Natural sort',
                    ),
                    'default'     => 'case',
                  ),
                  'dir'         => array(
                    'name'        => 'Preferred sort direction',
                    'type'        => 'radio',
                    'values'      => array(
                      'asc'         => 'ascending',
                      'desc'        => 'descending',
                    ),
                    'default'     => 'asc',
                  ),
                  'null'        => array(
                    'name'        => 'Order NULL values',
                    'type'        => 'radio',
                    'values'      => array(
                      'lower'       => 'Lower than all other values',
                      'higher'      => 'Higher than all other values',
                      'first'       => 'Always first (independent of sort direction)',
                      'last'        => 'Always last (independent of sort direction)',
                    ),
                    'default'     => 'lower',
                  ),
                ),
              ),
            ),
          ),
	  'show_priority' => array(
	    'name' => 'Include in list view',
	    'type' => 'select',
	    'placeholder' => 'always',
	    'values' => array(
	      ' 3' => 'high priority',
	      ' 2' => 'medium priority',
	      ' 1' => 'low priority',
              ' 0' => 'never',
	    ),
	  ),
	),
      ),
      'filter' => array(
        'type' => 'form',
        'name' => 'Filter',
        'def'  => get_filter_form_def($table),
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
      'weight' => array(
        'type'   => 'integer',
	'name'   => 'Weight',
	'default' => 0,
	'desc'   => 'Specify order of Views',
      ),
    );

    call_hooks('admin_table_views_form', $form_def);

    $form = new form('data', $form_def);

    if (isset($this->param['remove'])) {
      $views = $table->data['views'];

      if (isset($this->param['view'])) {
        unset($views[$this->param['view']]);
        $table->save(array('views' => $views));
        messages_add("View removed.");
        page_reload(page_url(array("page" => "admin_table", "table" => $table->id)));
        return true;
      }
    }

    if($form->is_complete()) {
      $view_data = $form->save_data();

      if(!array_key_exists('views', $table->data))
	$views = array();
      else
	$views = $table->data['views'];

      $view_key = $view_data['title'];

      // if view has been renamed, remove old view name
      if(array_key_exists('view', $this->param) && ($view_key != $this->param['view']))
        unset($views[$this->param['view']]);

      // set new view name
      $views[$view_key] = $view_data;

      $data['views'] = $views;

      $result = $table->save($data, $this->param['message']);

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

      if(isset($this->param['view']) && (array_key_exists($this->param['view'], $views))) {
	$view_data = $views[$this->param['view']];
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
      'table' => $this->param['table'],
      'form' => $form,
      'replacement_patterns' => $replacement_patterns,
      'data' => $table ? $table->view() : null,
      'view_data' => $view_data,
      'app' => $app,
    );
  }
}
