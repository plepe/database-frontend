function str_to_id(str) {
  str = str.toLowerCase();
  str = str.replace(' ', '_');
  str = str.replace('-', '_');

  return str;
}
