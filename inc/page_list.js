function page_list_connect () {
  var pager = document.getElementsByClassName('Pager')
  var result_count = parseInt(document.getElementsByClassName('result_count')[0].textContent)
  for (var i = 0; i < pager.length; i++) {
    var links = pager[i].getElementsByClassName('LinkButton')
    links[0].onclick = function () {
      param.offset = 0
      page_list_reload()
      return false
    }
    links[1].onclick = function () {
      param.offset = Math.max(0, parseInt(param.offset) - parseInt(param.limit))
      page_list_reload()
      return false
    }
    links[2].onclick = function () {
      param.offset = Math.min(result_count - result_count % parseInt(param.limit), parseInt(param.offset) + parseInt(param.limit))
      page_list_reload()
      return false
    }
    links[3].onclick = function () {
      param.offset = result_count - result_count % parseInt(param.limit)
      page_list_reload()
      return false
    }
  }

}

function page_list_reload () {
  var req = new XMLHttpRequest()
  
  req.addEventListener('load', function () {
    var text = req.responseXML

    var table_new = text.getElementById('table')
    var table_old = document.getElementById('table')
    table_old.innerHTML = table_new.innerHTML

    var pager_new = text.getElementsByClassName('Pager')
    var pager_old = document.getElementsByClassName('Pager')
    for (var i = 0; i < pager_new.length; i++) {
      pager_old[i].innerHTML = pager_new[i].innerHTML
    }

    page_list_connect()
  })

  req.addEventListener('error', function () {
    alert('An error occured when reloading table')
    console.log(req)
  })

  var r = form_filter.get_request_data()
  for (var k in r) {
    param[k] = r[k]
  }

  var r = form__.get_request_data()
  for (var k in r) {
    param[k] = r[k]
  }

  var url = '?' + ajax_build_request(param)

  history.pushState(param, null, '?' + ajax_build_request(param))

  req.open('GET', url)
  req.responseType = 'document'
  req.send()

  return false;
}

register_hook('init', function () {
  if (param && param.page === 'list') {
    form_filter.onchange = function () {
      param.offset = 0
      page_list_reload()
    }
    form__.onchange = page_list_reload
    page_list_connect()
  }
})
