const DB_Table = require('../inc/DB_Table.js')

function load (param, callback) {
  DB_Table.get(param.table, (err, table) => {
    if (!('id' in param)) {
      callback(null, new DB_Entry(table))

    } else if (param.clone) {
      table.get_entry(param.id, (err, entry) => {
        if (err) { return callback(err) }
        entry.id = null
        callback(null, entry)
      })
    } else {
      return table.get_entry(param.id, callback)
    }
  })
}

module.exports = {
  get (param, callback) {
    let pageData = {}

    pageData.app = global.app

    load (param, (err, entry) => {
      // pageData.title = entry.title()
      pageData.form = () => JSON.stringify(entry._data)
      callback(null, pageData)
    })
  },

  post_render (param, pageData, callback) {
    callback(null)
  },

  connect_server_rendered (param) {
  }
}
