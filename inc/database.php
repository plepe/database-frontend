<?php
$db_conn = new PDO("sqlite:{$db['path']}");
$db_conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function db_quote_ident($str) {
  return "\"{$str}\"";
}
