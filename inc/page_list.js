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

  var r = form_filter.get_request_data()
  for (var k in r) {
    page_param[k] = r[k]
  }

  var r = form__.get_request_data()
  for (var k in r) {
    page_param[k] = r[k]
  }

  var url = '?' + ajax_build_request(page_param)

  history.pushState(page_param, null, '?' + ajax_build_request(page_param))

  req.open('GET', url)
  req.send()

  return false;
}

register_hook('init', function () {
  if (page_param.page === 'list') {
    form_filter.onchange = function () {
      page_param.offset = 0
      page_list_reload()
    }
    form__.onchange = page_list_reload
  }
})
