const Twig = require('twig')
const queryString = require('query-string')
const async = {
  parallel: require('async/parallel')
}

const templates = require('./templates')
const pages = require('./pages')

function page_url (param) {
  let data = {}

  for (let k in param) {
    if (k !== '_keys') {
      data[k] = param[k]
    }
  }

  return '?' + queryString.stringify(data)
}

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

    callback()
  })

  return true
}

module.exports = {
  init () {
    Twig.extendFunction("page_url", param => page_url(param))
  },
  load
}
