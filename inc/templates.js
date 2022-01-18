const httpRequest = require('./httpRequest')
const twig = require('twig').twig

let templates = {}

module.exports = {
  get (page, callback) {
    let pageId = page || 'index'

    if (page in templates) {
      return callback(null, templates[page])
    }

    twig({
      id: pageId,
      href: 'templates/' + pageId + '.html',
      async: true,
      load: function (template) {
        templates[page] = template

        callback(null, template)
      }
    })
  }
}
