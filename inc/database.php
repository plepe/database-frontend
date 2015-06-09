<?php
$db_conn = new PDO("sqlite:{$db['path']}");

function db_quote_ident($str) {
  return "\"{$str}\"";
}
