<?php
class DB_TableExtract {
  function __construct($table) {
    $this->table = $table;
    $this->filter = null;
  }

  function count() {
  }

  function set_sort($rules) {
  }

  function set_filter($filter) {
    $this->filter = $filter;
  }

  function get($offset=0, $limit=null) {
    return $this->table->get_entries($this->filter);
  }
}
