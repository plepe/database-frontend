<?php
$db_system_is_init = false;

function db_system_init() {
  global $db_system_is_init;
  global $db_conn;

  if($db_system_is_init)
    return;

  if(!$db_conn->tableExists('__system__')) {
    $db_conn->query(<<<EOT
create table __system__ (
  id		varchar(255) not null,
  data		text	null,
  primary key(id)
);
EOT
    );
  }

  $db_system_is_init = true;
}

class DB_System {
  function __construct($db_settings) {
    global $db_conn;

    $this->db_settings = $db_settings;
    $this->db = $db_conn;

    $this->load();
  }

  function load() {
    db_system_init();

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

    if(($changeset === null) || is_string($changeset))
      $changeset = new Changeset($changeset);

    $this->db->query($query);

    $this->load();

    $changeset->add($this);

    return true;
  }
}
