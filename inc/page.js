const Twig = require('twig')
const queryString = require('query-string')

const templates = require('./templates')

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
  templates.get(
    param.page || 'index',
    (err, result) => {
      if (err) {
        return alert(err)
      }

      let div = document.createElement('div')
      div.innerHTML = result.render({app: {title: 'Foo'}, data: {tables: []}})
      document.body.appendChild(div)

      document.body.removeChild(document.getElementById('content'))
      div.id = 'content'

      callback()
    }
  )

  return true
}

module.exports = {
  init () {
    Twig.extendFunction("page_url", param => page_url(param))
  },
  load
}
