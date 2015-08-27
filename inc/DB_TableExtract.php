<?php
class DB_TableExtract {
  function __construct($table) {
    $this->table = $table;
    $this->filter = null;
    $this->ids = null;
  }

  function count() {
  }

  function set_sort($rules) {
  }

  function set_filter($filter) {
    $this->filter = $filter;
  }

  function set_ids($ids) {
    $this->filter = null;
    $this->ids = $ids;
  }

  function get($offset=0, $limit=null) {
    if($this->ids) {
      $t = $this;
      return $t->table->get_entries_by_id($this->ids);
    }
    else {
      return $this->table->get_entries($this->filter);
    }
  }
}
