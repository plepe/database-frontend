const queryString = require('qs')
const Twig = require('twig')
const async = {
  parallel: require('async/parallel')
}

const templates = require('./templates')
const pages = require('./pages')
const update_links = require('./update_links')
const state = require('./state')
const extensions = require('./extensions.js')

let pageData
let page
let page_loaded = false
let request_update = false

function load (param, callback) {
  if (!((param.page || 'index') in pages)) {
    return false
  }

  let pageId = param.page || 'index'

  page = pages[pageId]
  page_loaded = false
  let template
  async.parallel([
    done => {
      page.get(param, (err, result) => {
        page_loaded = true
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

    async.parallel([
      (done) => {
        if ('post_render' in page) {
          page.post_render(param, pageData, callback)
        } else {
          done()
        }
      },
      (done) => extensions.call_async('post_render', pageData, done)
    ], callback)
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
    page.connect_server_rendered(param)
  }

  extensions.call('connect_server_rendered', param)
}

function update (callback) {
  if (page && 'update' in page) {
    if (!page_loaded) {
      return page.get(state.data, (err, _pageData) => {
        pageData = _pageData
        page_loaded = true

        page.update(pageData, callback)
      })
    } else {
      page.update(pageData, callback)
    }
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
