<?php
class Page_list extends Page {
  function content($param) {
    global $app;
    global $auth;
    global $default_settings;
    $user_settings = $auth->current_user()->settings();

    $table = get_db_table($param['table']);
    if(!$table)
      return null;

    if(array_key_exists('limit', $param)) {
      $user_settings->save(array("limit" => $param['limit']));
    }
    else {
      if($user_settings->data('limit') !== null)
        $param['limit'] = $user_settings->data('limit');
      elseif(array_key_exists('limit', $default_settings))
        $param['limit'] = $default_settings['limit'];
      else
        $param['limit'] = 25;
    }
    if(!array_key_exists('offset', $param))
      $param['offset'] = 0;

    if(array_key_exists('sort', $param)) {
      $_SESSION['sort'] = $param['sort'];
      $_SESSION['sort_dir'] = !array_key_exists('sort_dir', $param) ? $param['sort_dir'] : 'asc';
    }
    else {
      if(array_key_exists('sort', $_SESSION)) {
	$param['sort'] = $_SESSION['sort'];
	$param['sort_dir'] = $_SESSION['sort_dir'];
      }
      else {
        $param['sort'] = $table->data('sort');
      }
    }

    if(!base_access('view') || !access($table->data('access_view'))) {
      global $auth;
      if(!$auth->is_logged_in())
	page_reload(array("page" => "login", "return" => array("page" => "list", "table" => $param['table'])));
      return "Permission denied.";
    }

    $table_extract = new DB_TableExtract($table);
    $filter_values = get_filter($param);
    $table_extract->set_filter($filter_values);

//    $data = array();
//    foreach($table_extract->get() as $o) {
//      $data[$o->id] = $o->view();
//    }

    // if no 'view'-parameter is set, use session or view with lowest weight
    if(!isset($param['view'])) {
      if(array_key_exists("{$table->id}_view_list", $_SESSION))
        $view = $_SESSION["{$table->id}_view_list"];
      else
        $view = $table->data('default_view_list');
    }
    else {
      $view = $param['view'];
      $_SESSION["{$table->id}_view_list"] = $view;
    }
    $param['view'] = $view;

    $def = $table->view_def($view);
    if ($def === false) {
      $def = $table->view_def('default');
    }
    modify_table_fields($param, $def);

    foreach($def['fields'] as $field_id => $field_def) {
      if($field_def['show_priority'] == ' 0')
        unset($def['fields'][$field_id]);
    }

    if(array_key_exists('class', $def)) {
      $view_class = "View_{$def['class']}";
      $view = new $view_class($def, $param);
    }
    else {
      $view = new View_Table($def, $param);
    }

    $view->set_extract($table_extract);

    html_export_var(array("param" => array(
      "page" => "list",
      "table" => $param['table'],
      "offset" => array_key_exists('offset', $param) ? $param['offset'] : 0,
      "limit" => $param['limit'],
    )));

    $table_fields = get_table_fields_form($param, $def);
    $table_fields_values = $table_fields->get_data();
    $table_fields_values = $table_fields_values['table_fields'];
    foreach ($table_fields_values as $i => $v) {
      if (!$v) {
        unset($table_fields_values[$i]);
      }
    }

    return array(
      'template' => 'list.html',
      'table' => $param['table'],
      'table_name' => $table->name(),
      'result_count' => $table_extract->count(),
      'filter' => get_filter_form($param),
      'filter_values' => $filter_values,
      'table_fields' => $table_fields,
      'table_fields_values' => $table_fields_values,
      'view' => $view,
      'param' => $param,
      'views' => $table->views('list'),
      'app' => $app,
    );
  }
}

register_hook("auth_user_settings_form", function(&$form_def) {
  $form_def['limit'] = array(
    'type' => 'select',
    'name' => 'Results per page',
    'values_mode' => 'keys',
    'values' => array(
      '10' => '10',
      '25' => '25',
      '50' => '50',
      '100' => '100',
      '0' => '∞',
    ),
  );
});
