<?php
$db_conn = new PDOext($db);
$db_conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function db_return_error_info($db_conn) {
  $error = $db_conn->errorInfo();
  $res = $db_conn->query("rollback");
  return $error[2];
}
