function page_list_reload() {
  var req = new XMLHttpRequest()
  
  req.addEventListener('load', function () {
    var text = req.responseText
    var pos_start = text.search('<!--start-->')
    var pos_end = text.search('<!--end-->')

    text = text.substr(pos_start + 12, pos_end - pos_start - 12)

    var table = document.getElementById('table')
    table.innerHTML = text
  })

  req.addEventListener('error', function () {
    alert('An error occured when reloading table')
    console.log(req)
  })

  var url = '?page=list&table=' + page_param.table

  url += '&' + ajax_build_request(form_filter.get_request_data())
  url += '&' + ajax_build_request(form__.get_request_data())

  req.open('GET', url)
  req.send()

  return false;
}

register_hook('init', function () {
  if (page_param.page === 'list') {
    form_filter.onchange = page_list_reload
    form__.onchange = page_list_reload
  }
})
