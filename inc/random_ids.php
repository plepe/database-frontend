<?php
$random_ids_pool = null;

register_hook('init', function() {
  global $db_conn;

  if(!$db_conn->tableExists('__random_ids__')) {
    $db_conn->query(<<<EOT
create table __random_ids__ (
  id		varchar(4) not null,
  ts            text,
  primary key(id)
);
EOT
    );
  }
  $db_conn->query("delete from __random_ids__ where ts<" . $db_conn->quote(Date('Y-m-d H:i:s')));
});

// reserve random ids
function random_ids_init() {
  global $random_ids_pool;
  global $db_conn;
  $db_timeout = Date('Y-m-d H:i:s', time() + 3600);

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

  $db_conn->beginTransaction();

  $res = $db_conn->query("select * from __random_ids__");
  while ($e = $res->fetch()) {
    $used[] = $e['id'];
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
    $res = $db_conn->query("insert into __random_ids__ values (" . $db_conn->quote($r) . ", " . $db_conn->quote($db_timeout) . ")");
  }

  $db_conn->commit();
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
