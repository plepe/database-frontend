<?php
class Changeset {
  function __construct($message) {
    $this->status = false;
    $this->message = $message;
  }

  function add($object) {
    if($this->status === false)
      $this->commit();
  }

  function open() {
    $this->status = true;

    call_hooks("changeset_open", $this);
  }

  function commit() {
    $this->status = false;

    call_hooks("changeset_commit", $this);
  }

  function is_open() {
    return $this->status;
  }
}
