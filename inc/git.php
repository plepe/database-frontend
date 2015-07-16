<?php
/**
 * git_dump() - save all data to the git repository
 * parameter: message: git commit message; if message is boolean false, do not save to git repository
 */
function git_dump($message="") {
  global $git;

  if($message === false)
    return;

  if(!isset($git))
    return;

  if(chdir($git['path']) === false) {
    messages_add("Git: cannot chdir to git directory", MSG_ERROR);
    return;
  }

  if(!is_dir(".git")) {
    system("git init");
  }

  system("rm -r *");

  mkdir("__system__");

  foreach(get_db_tables() as $table) {
    file_put_contents("__system__/{$table->id}.json", json_readable_encode($table->data));

    mkdir($table->id);
    foreach(get_db_entries($table->id) as $entry) {
      file_put_contents("{$table->id}/{$entry->id}.json", json_readable_encode($entry->data));
    }
  }

  system("git add .");
  $result = adv_exec("git " .
           "-c user.name=" . shell_escape($git['user']) . " " .
           "-c user.email=" . shell_escape($git['email']) . " " .
           "commit " .
           "-a -m " . shell_escape($message) . " " .
           "--allow-empty-message ".
           "--author=\"Web User <no@body.com>\""
        );

  if($result[0] == 0) {
    messages_add("<pre>Git commit:\n" . htmlspecialchars($result[1]) . "</pre>\n");
  }
  else {
    messages_add("<pre>Git commit failed:\n" . htmlspecialchars($result[1]) . "</pre>\n", MSG_ERROR);
  }
}
