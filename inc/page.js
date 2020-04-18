const queryString = require('qs')
const Twig = require('twig')
const async = {
  parallel: require('async/parallel')
}

const templates = require('./templates')
const pages = require('./pages')
const update_links = require('./update_links')

let pageData
let page
let connecting_server
let request_update = false

function load (param, callback) {
  if (!((param.page || 'index') in pages)) {
    return false
  }

  let pageId = param.page || 'index'

  page = pages[pageId]
  let template
  async.parallel([
    done => {
      page.get(param, (err, result) => {
        pageData = result
        done(err)
      })
    },
    done => {
      templates.get(pageId, (err, result) => {
        template = result
        done(err)
      })
    }
  ], (err) => {
    if (err) {
      return alert(err)
    }

    let div = document.createElement('div')
    div.innerHTML = template.render(pageData)
    document.body.appendChild(div)

    document.body.removeChild(document.getElementById('content'))
    div.id = 'content'

    update_links()

    if ('post_render' in page) {
      page.post_render(param, pageData, callback)
    } else {
      callback()
    }
  })

  return true
}

function connect_server_rendered (param) {
  if (!((param.page || 'index') in pages)) {
    return false
  }

  let pageId = param.page || 'index'

  page = pages[pageId]

  if ('connect_server_rendered' in page) {
    connecting_server = true
    page.connect_server_rendered(param)
    page.get(param, (err, _pageData) => {
      pageData = _pageData
      connecting_server = false

      if (request_update) {
        update(request_update)
        request_update = null
      }
    })
  }
}

function update (callback) {
  if (connecting_server) {
    request_update = callback
    return
  }

  if (page && 'update' in page) {
    page.update(pageData, callback)
  } else {
    callback(null)
  }
}

module.exports = {
  init () {
    Twig.extendFunction("page_url", param => {
      delete param._keys
      return '?' + queryString.stringify(param)
    })
  },
  connect_server_rendered,
  load,
  update
}
