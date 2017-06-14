<?php
function array_insert_before($arr, $key, $to_insert) {
  $i = array_search($key, array_keys($arr));

  return array_merge(
    array_slice($arr, 0, $i),
    $to_insert,
    array_slice($arr, $i)
  );
}
