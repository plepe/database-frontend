function str_to_id(str) {
  str = str.toLowerCase();
  str.replace(' ', '_');
  str.replace('-', '_');

  return str;
}
