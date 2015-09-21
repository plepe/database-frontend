<?php
class DB_System {
  function __construct($db_settings) {
    global $db_conn;

    $this->db_settings = $db_settings;
    $this->db = $db_conn;

    $this->load();
  }

  function load() {
    $res = $this->db->query("select * from __system__ where id='__system__'");
    if($elem = $res->fetch())
      $this->data = json_decode($elem['data'], true);
    else
      $this->data = array();
  }

  function data($key=null) {
    if($key !== null)
      return $this->data[$key];

    return $this->data;
  }

  function view() {
    return $this->data();
  }

  function save($data, $changeset=null) {
    $new_data = $this->data;
    foreach($data as $k=>$d) {
      if($d === null)
	unset($new_data[$k]);
      else
	$new_data[$k] = $d;
    }

    $data = $new_data;

    $query = "replace into __system__ (id, data) values ('__system__', " . $this->db->quote(json_readable_encode($data)) . ")";
    $this->db->query($query);
  }
}
