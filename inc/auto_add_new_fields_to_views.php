<?php
register_hook('admin_table_views_form', function (&$form_def) {
  $form_def = array_insert_before(
    $form_def,
    'fields', 
    array(
      'auto_add_new_fields' => array(
        'type'    => 'boolean',
        'name'    => 'Automatically add new fields',
        'default' => true,
        'show_depend' => array('check', 'class', array('is', 'Table')),
      ),
    )
  );
});

register_hook('table_pre_save', function (&$data, $table) {
  if (!isset($data['views'])) {
    return;
  }

  $new_views = $data['views'];
  $changed = false;

  foreach ($data['views'] as $view_id => $view_def) {
    if ($view_def['class'] === 'Table') {
      if (isset($data['fields'])) {
        foreach ($data['fields'] as $field_id => $field_def) {
          // new field detected
          if ($field_def['old_key'] === null) {
            if (isset($view_def['auto_add_new_fields']) && $view_def['auto_add_new_fields']) {
              $new_views[$view_id]['fields'][] = array('key' => $field_id);
              $changed = true;
            }
          }
          // rename
          else if ($field_def['old_key'] !== $field_def['key']) {
            foreach ($view_def['fields'] as $view_field_id => $view_field_def) {
              if ($view_field_def['key'] === $field_def['old_key']) {
                $new_views[$view_id]['fields'][$view_field_id]['key'] = $field_id;
                $changed = true;
              }
            }
          }
        }
      }

    }
  }

  if ($changed) {
    $data['views'] = $new_views;
  }
});
