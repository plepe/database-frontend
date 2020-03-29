const httpRequest = require('./httpRequest')
const twig = require('twig').twig

let templates = {}

module.exports = {
  get (page, callback) {
    if (page in templates) {
      return callback(null, templates[page])
    }

    httpRequest(
      'templates/' + (page || 'index') + '.html',
      {},
      (err, result) => {
        if (err) {
          return callback(err)
        }

        templates[page] = twig({data: result.body})
        callback(null, templates[page])
      }
    )
  }
}
