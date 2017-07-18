function random_ids_get (value, form_element, form) {
  var id = form_element.def['random-ids-id']
  var pool = 'random_key_generator_' + id
  return window[pool].shift()
}
