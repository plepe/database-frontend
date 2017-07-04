<?php
class Changeset {
  function __construct($message) {
    global $db_conn;

    $this->status = false;
    $this->message = $message;

    $db_conn->beginTransaction();
  }

  function disableForeignKeyChecks() {
    global $db_conn;

    $db_conn->disableForeignKeyChecks();
    $this->foreign_key_checks_disabled = true;
  }

  function enableForeignKeyChecks() {
    global $db_conn;

    $db_conn->enableForeignKeyChecks();
    unset($this->foreign_key_checks_disabled);
  }

  function add($object) {
    $object->data = null;

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

    if(isset($this->foreign_key_checks_disabled))
      $db_conn->enableForeignKeyChecks();

    $this->status = false;
    $db_conn->rollBack();
  }

  function commit() {
    global $db_conn;

    if(isset($this->foreign_key_checks_disabled))
      $db_conn->enableForeignKeyChecks();

    $this->status = false;
    $db_conn->commit();

    call_hooks("changeset_commit", $this);
  }

  function is_open() {
    return $this->status;
  }
}
