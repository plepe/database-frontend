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

  req.open('GET', '?page=list&table=' + page_param.table)
  req.send()

  return false;
}

register_hook('init', function () {
  if (page_param.page === 'list') {

  }
})
