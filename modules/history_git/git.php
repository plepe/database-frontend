<?php
/**
 * git_dump() - save all data to the git repository
 * parameter: $changeset - see class Changeset
 */
function git_dump($changeset) {
  global $git;
  global $system;

  $message = $changeset->message;

  if(!isset($git))
    return;

  $cwd = getcwd();

  if(!file_exists($git['path'])) {
    mkdir($git['path']);
  }

  if(chdir($git['path']) === false) {
    messages_add("Git: cannot chdir to git directory", MSG_ERROR);
    return;
  }

  history_git_lock();

  if(!is_dir(".git")) {
    system("git init");
  }

  system("rm -r *");

  mkdir("__system__");

  file_put_contents("__system__/__system__.json", json_readable_encode($system->data()) . "\n");

  foreach(get_db_tables() as $table) {
    file_put_contents("__system__/{$table->id}.json", json_readable_encode($table->data()) . "\n");

    if($table->data('history_git_enabled') === false)
      continue;

    mkdir($table->id);
    foreach($table->get_entries() as $entry) {
      file_put_contents("{$table->id}/{$entry->id}.json", json_readable_encode($entry->data()) . "\n");
    }
  }

  global $auth;
  $user = $auth->current_user()->name();
  $email = $auth->current_user()->email();

  if(!$email)
    $email = "unknown@unknown";

  system("git add *");
  $result = adv_exec("git " .
           "-c user.name=" . shell_escape($user) . " " .
           "-c user.email=" . shell_escape($email) . " " .
           "commit " .
           "-a -m " . shell_escape($message) . " " .
           "--allow-empty-message ".
           "--author=" . shell_escape("{$user} <{$email}>")
        );

  history_git_unlock();

  if(in_array($result[0], array(0, 1))) {
    //messages_add("<pre>Git commit:\n" . htmlspecialchars($result[1]) . "</pre>\n");
  }
  else {
    messages_add("<pre>Git commit failed:\n" . htmlspecialchars($result[1]) . "</pre>\n", MSG_ERROR);
  }

  chdir($cwd);
}

function history_git_get_path ($object) {
  if (get_class($object) === 'DB_Entry') {
    if($object->table->data('history_git_enabled') === false)
      return;

    return "{$object->table->id}/{$object->id}.json";
  }
  elseif (get_class($object) === 'DB_Table') {
    return "__system__/{$object->id}.json";
  }
  elseif (get_class($object) === 'DB_System') {
    return "__system__/__system__.json";
  }
}

function git_commit ($changeset) {
  global $git;
  global $system;

  $message = $changeset->message;

  if(!isset($git))
    return;

  $cwd = getcwd();

  if(!is_dir("{$git['path']}/.git")) {
    return git_dump($changeset);
  }

  if(chdir($git['path']) === false) {
    messages_add("Git: cannot chdir to git directory", MSG_ERROR);
    return;
  }

  history_git_lock();

  file_put_contents("__system__/__system__.json", json_readable_encode($system->data()) . "\n");

  foreach ($changeset->changes['add'] as $object) {
    $path = history_git_get_path($object);

    if ($path) {
      file_put_contents($path, json_readable_encode($object->data()) . "\n");
    }
  }

  foreach ($changeset->changes['remove'] as $object) {
    $path = history_git_get_path($object);

    if ($path) {
      unlink($path);
    }
  }

  global $auth;
  $user = $auth->current_user()->name();
  $email = $auth->current_user()->email();

  if(!$email)
    $email = "unknown@unknown";

  system("git add *");
  $result = adv_exec("git " .
           "-c user.name=" . shell_escape($user) . " " .
           "-c user.email=" . shell_escape($email) . " " .
           "commit " .
           "-a -m " . shell_escape($message) . " " .
           "--allow-empty-message ".
           "--author=" . shell_escape("{$user} <{$email}>")
        );

  history_git_unlock();

  if(in_array($result[0], array(0, 1))) {
    //messages_add("<pre>Git commit:\n" . htmlspecialchars($result[1]) . "</pre>\n");
  }
  else {
    messages_add("<pre>Git commit failed:\n" . htmlspecialchars($result[1]) . "</pre>\n", MSG_ERROR);
  }

  chdir($cwd);
}

function history_git_lock () {
  global $history_git_fd;

  $history_git_fd = fopen('.lock', 'w');
  if (!flock($history_git_fd, LOCK_EX)) {
    messages_add("History: could not get lock!", MSG_ERROR);
  }
}

function history_git_unlock () {
  global $history_git_fd;

  flock($history_git_fd, LOCK_UN);
  fclose($history_git_fd);
}

register_hook("panel_items", function(&$items, $param) {
  if(!in_array($param['page'], array("show", "list")))
    return;

  $table = get_db_table($param['table']);
  if($table->data('history_git_enabled') === false)
    return;

  $ret = array(
    'title' => 'History',
    'url' => array(
      'page'  => 'history',
      'table' => $param['table'],
    ),
  );

  if(array_key_exists('id', $param)) {
    $ret['url']['id'] = $param['id'];
  }

  $items[] = $ret;
});

register_hook("changeset_commit", "git_commit");

register_hook("admin_table_general", function(&$def) {
  $def['history_git_enabled'] = array(
    'type' => 'boolean',
    'name' => 'Enable History for data of this table',
    'default' => true,
  );
});
