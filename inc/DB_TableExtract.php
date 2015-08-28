<?php
class DB_TableExtract {
  function __construct($table) {
    $this->table = $table;
    $this->filter = null;
    $this->sort = null;
    $this->ids = null;
  }

  function count() {
    return $this->table->get_entry_count($this->filter);
  }

  function set_sort($sort) {
    $this->sort = $sort;
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
      return $this->table->get_entries($this->filter, $this->sort, $offset, $limit);
    }
  }

  /**
   * return index of the given id in the filtered and sorted list
   * @param string id id of the object
   * @return integer|false index in the list or false if object is not in list
   */
  function index($id) {
    $offset = 0;
    $limit = 128;

    while(true) {
      $ids = $this->table->get_entry_ids($this->filter, $this->sort, $offset, $limit);
      if(sizeof($ids) == 0)
        return false;

      $pos = array_search($id, $ids);

      if($pos !== false)
        return $pos + $offset;

      $offset += $limit;
    }
  }
}
