<?
function str_to_id($str) {
  $str = strtolower($str);

  return strtr($str, array(
    ' ' => '_',
    '-' => '_',
  ));
}
