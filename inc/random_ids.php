<?php
$random_ids_pool = null;

// reserve random ids
function random_ids_init() {
  global $random_ids_pool;

  if ($random_ids_pool !== null) {
    return;
  }
  $random_ids_pool = array();

  $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $used = array();

  foreach (get_db_tables() as $table) {
    if ($table->field('id')->def['type'] === 'random') {
      foreach ($table->get_entries() as $entry) {
          $used[] = $entry->id;
      }
    }
  }

  for ($i = 0; $i < 32; $i++) {
    $r = '';

    for ($j = 0; $j < 4; $j++) {
      $r .= $chars[rand(0, strlen($chars) - 1)];
    }

    if (in_array($r, $used)) {
      continue;
    }

    $random_ids_pool[] = $r;
    $used[] = $r;
  }
}

function random_ids_get() {
  global $random_ids_pool;

  random_ids_init();

  return array_shift($random_ids_pool);
}

register_hook('page_ready', function () {
  global $random_ids_pool;

  html_export_var(array('random_ids_pool' => $random_ids_pool));
});
