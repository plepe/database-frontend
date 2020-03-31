const queryString = require('query-string')

module.exports = function page_url (param) {
  let data = {}

  for (let k in param) {
    if (k !== '_keys') {
      data[k] = param[k]
    }
  }

  return '?' + queryString.stringify(data)
}
