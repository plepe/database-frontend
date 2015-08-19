<?php
class Changeset {
  function __construct($message) {
    global $db_conn;

    $this->status = false;
    $this->message = $message;

    $db_conn->beginTransaction();
  }

  function add($object) {
    if($this->status === false) {
      $this->open();
      $this->commit();
    }
  }

  function open() {
    $this->status = true;

    call_hooks("changeset_open", $this);
  }

  function rollBack() {
    global $db_conn;
    call_hooks("changeset_rollback", $this);

    $this->status = false;
    $db_conn->rollBack();
  }

  function commit() {
    global $db_conn;

    $this->status = false;
    $db_conn->commit();

    call_hooks("changeset_commit", $this);
  }

  function is_open() {
    return $this->status;
  }
}
