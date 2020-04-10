const queryString = require('qs')
const Twig = require('twig')
const async = {
  parallel: require('async/parallel')
}

const templates = require('./templates')
const pages = require('./pages')
const update_links = require('./update_links')

function load (param, callback) {
  if (!((param.page || 'index') in pages)) {
    return false
  }

  let pageId = param.page || 'index'

  let page = pages[pageId]
  let pageData
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
      if ('connect' in page) {
        page.connect(param)
      }

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

  let page = pages[pageId]

  if ('connect_server_rendered' in page) {
    page.connect_server_rendered(param)
  } else {
    page.connect(param)
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
  load
}
