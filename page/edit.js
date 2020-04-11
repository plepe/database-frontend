const DB_Table = require('../inc/DB_Table.js')
const DB_Entry = require('../inc/DB_Entry.js')

const state = require('../inc/state.js')
const db_execute = require('../inc/db_execute.js')

let current_form
let current_entry

function load (param, callback) {
  DB_Table.get(param.table, (err, table) => {
    if ('clone' in param) {
      // clone entry
      table.get_entry(param.clone, (err, orig_entry) => {
        if (err) { return callback(err) }

        let data = JSON.parse(JSON.stringify(orig_entry._data))
        let entry = new DB_Entry(table)

        entry._data = data
        entry._data.id = null

        callback(null, table, entry)
      })
    } else if (!('id' in param)) {
      // new entry
      let entry = new DB_Entry(table)
      entry._data = {}

      callback(null, table, entry)
    } else {
      // edit existing entry
      return table.get_entry(param.id, (err, entry) => {
        callback(err, table, entry)
      })
    }
  })
}

function connect (param) {
  let dom_form = document.getElementById('form-edit')

  dom_form.onsubmit = (e) => {
    let data = current_form.get_data()

    switch (e.submitter.name) {
      case 'cancel':
        state.apply({ page: 'show', table: current_entry.table.id, id: current_entry.id })
        break
      case 'delete':
        current_entry.remove(data, null, (err) => {
          state.apply({ page: 'list', table: current_entry.table.id })
        })
        break
      default:
        let script = []

        let ob_index = script.length
        script.push({
          action: (current_entry.id ? 'update' : 'create'),
          table: current_entry.table.id,
          id: current_entry.id,
          data
        })

        // Invalidate cache
        delete current_entry.table.entries_cache[current_entry.id]

        db_execute(script, null, (err, result) => {
          result = JSON.parse(result.body)

          state.apply({ page: 'show', table: current_entry.table.id, id: result[ob_index].id })
        })
    }

    return false
  }
}

module.exports = {
  get (param, callback) {
    let pageData = {}

    pageData.app = global.app

    load (param, (err, table, entry) => {
      current_entry = entry
      // pageData.title = entry.title()
      let def = table.def
      pageData.form = {show: () => '<div id="show-edit"></div>'}

      table.def((err, def) => {
        if (err) { return callback(err) }

        pageData.form_edit = new form('edit', def)
        pageData.form_edit.set_data(entry.data())
        current_form = pageData.form_edit

        callback(null, pageData)
      })
    })
  },

  post_render (param, pageData, callback) {
    pageData.form_edit.show(document.getElementById('show-edit'))

    connect(param)

    callback(null)
  },

  connect_server_rendered (param) {
    current_form = global.form_data
    connect(param)
  }
}
