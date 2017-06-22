function page_list_reload() {
  var req = new XMLHttpRequest()
  
  req.addEventListener('load', function () {
    var text = req.responseXML

    var table_new = text.getElementById('table')
    var table_old = document.getElementById('table')
    table_old.innerHTML = table_new.innerHTML
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
  req.responseType = 'document'
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
