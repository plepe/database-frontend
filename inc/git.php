<?php
/**
 * git_dump() - save all data to the git repository
 * parameter: $changeset - see class Changeset
 */
function git_dump($changeset) {
  global $git;

  $message = $changeset->message;

  if(!isset($git))
    return;

  $cwd = getcwd();

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
    file_put_contents("__system__/{$table->id}.json", json_readable_encode($table->data) . "\n");

    mkdir($table->id);
    foreach(get_db_entries($table->id) as $entry) {
      file_put_contents("{$table->id}/{$entry->id}.json", json_readable_encode($entry->data) . "\n");
    }
  }

  global $auth;
  $user = $auth->current_user()->name();
  $email = $auth->current_user()->email();

  if(!$email)
    $email = "unknown@unknown";

  system("git add .");
  $result = adv_exec("git " .
           "-c user.name=" . shell_escape($user) . " " .
           "-c user.email=" . shell_escape($email) . " " .
           "commit " .
           "-a -m " . shell_escape($message) . " " .
           "--allow-empty-message ".
           "--author=" . shell_escape("{$user} <{$email}>")
        );

  if(in_array($result[0], array(0, 1))) {
    messages_add("<pre>Git commit:\n" . htmlspecialchars($result[1]) . "</pre>\n");
  }
  else {
    messages_add("<pre>Git commit failed:\n" . htmlspecialchars($result[1]) . "</pre>\n", MSG_ERROR);
  }

  chdir($cwd);
}

register_hook("changeset_commit", "git_dump");
