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

  chdir($git['path']);

  if(!is_dir(".git")) {
    system("git init");
  }

  system("rm -r *");

  foreach(get_db_tables() as $table) {
    mkdir($table->id);

    foreach(get_db_entries($table->id) as $entry) {
      file_put_contents("{$table->id}/{$entry->id}.json", json_readable_encode($entry->data));
    }
  }

  system("git add *");
  system("git " .
           "-c user.name=" . shell_escape($git['user']) . " " .
           "-c user.email=" . shell_escape($git['email']) . " " .
           "commit " .
           "-m " . shell_escape($message) . " " .
           "--allow-empty-message ".
           "--author=\"Web User <no@body.com>\""
        );
}
