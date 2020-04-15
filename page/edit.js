const async = {
  eachOf: require('async/eachOf'),
  parallel: require('async/parallel')
}
const forEach = require('foreach')

const DB_Table = require('../inc/DB_Table.js')
const DB_Entry = require('../inc/DB_Entry.js')

const state = require('../inc/state.js')
const db_execute = require('../inc/db_execute.js')

let current_form
let current_entry
let current_reference_fields

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

function save (data, callback) {
  let script = []
  let references = {}

  forEach(current_reference_fields, (f, k) => {
    if (f.count) {
      forEach(data[k] || [], (value, i) => {
        if (value.value) {
          data[k][i] = value.value
        } else {
          references[k + '.' + i] = script.length + '.id'

          script.push({
            action: 'create',
            table: f.table,
            data: value.new
          })

          data[k][i] = null
        }
      })
    } else {
      if (data[k].value) {
        data[k] = data[k].value
      } else {
        references[k] = script.length + '.id'

        script.push({
          action: 'create',
          table: f.table,
          data: data[k].new
        })

        data[k] = null
      }
    }
  })

  let ob_index = script.length
  script.push({
    action: (current_entry.id ? 'update' : 'create'),
    table: current_entry.table.id,
    id: current_entry.id,
    references,
    data
  })

  let old_id = current_entry.id
  let old_referenced_entries = current_entry.referenced_entries()
  let table = current_entry.table

  // Invalidate cache
  DB_Table.invalidate_entries([[table.id, current_entry.id]])

  db_execute(script, null, (err, result) => {
    if (err) { return callback(err) }

    DB_Table.invalidate_entries([[table.id, old_id]])
    current_entry = new DB_Entry(table, result[ob_index].id, result[ob_index])
    DB_Table.invalidate_entries(old_referenced_entries)
    DB_Table.invalidate_entries(current_entry.referenced_entries())

    callback(null, result[ob_index].id)
  })
}


function connect (param) {
  let dom_form = document.getElementById('form-edit')

  dom_form.onsubmit = (e) => {
    let data = current_form.get_data()

    switch (e.submitter ? e.submitter.name : 'save') {
      case 'cancel':
        state.apply({ page: 'show', table: current_entry.table.id, id: current_entry.id })
        break
      case 'delete':
        current_entry.remove(data, null, (err) => {
          state.apply({ page: 'list', table: current_entry.table.id })
        })
        break
      default:
        save(data, (err, id) => {
          state.apply({ page: 'show', table: current_entry.table.id, id: id })
        })
    }

    return false
  }
}

function compile_def (table, callback) {
  let reference_fields = {}

  table.def((err, def) => {
    if (err) { return callback(err) }

    def = JSON.parse(JSON.stringify(def))

    async.eachOf(def,
      (d, k, done) => {
        if (d.reference && !['checkbox'].includes(d.type) && d.reference_create_new) {
          reference_fields[k] = {
            count: d.count,
            table: d.reference
          }

          DB_Table.get(d.reference, (err, ref_table) => {
            if (err) { return done(err) }

            ref_table.def((err, sub_def) => {
              sub_def = JSON.parse(JSON.stringify(sub_def))

              if (err) { return done(err) }

              for (let subk in sub_def) {
                if (sub_def.backreference) {
                  delete sub_def[subk]
                }
              }

              if (d.count) {
                d.count = {
                  default: 0,
                  'button:add_element': lang('edit:add_subelement', 0, d.name),
                }
              }

              def[k] = {
                type: 'form',
                name: d.name,
                count: d.count,
                def: {
                  'value': JSON.parse(JSON.stringify(d)),
                  'new': {
                    type: 'form',
                    hide_label: true,
                    def: sub_def,
                    show_depend: ['check', 'value', ['not', ['has_value']]]
                  }
                }
              }

              d = def[k]
              delete d.def.value.count
              d.def.value.hide_label = true
              d.def.value.placeholder = '-- create new --'

              done()
            })
          })
        } else {
          done()
        }
      },
      (err) => {
        current_reference_fields = reference_fields

        callback(err, def, reference_fields)
      }
    )
  })
}

module.exports = {
  get (param, callback) {
    let pageData = {}

    pageData.app = global.app

    async.parallel([
      done => DB_Table.get_table_list((err, table_list) => {
        pageData.table_list = table_list
        done(err)
      }),
      done => load (param, (err, table, entry) => {
        current_entry = entry
        // pageData.title = entry.title()
        let def
        pageData.form = {show: () => '<div id="show-edit"></div>'}

        pageData.table = table.id
        pageData.table_name = table.name()
        pageData.title = !param.id ? null : entry.title()
        pageData.id = param.id

        compile_def(table, (err, def, reference_fields) => {
          if (err) { return callback(err) }

          pageData.form_edit = new form('edit', def)
          let data = JSON.parse(JSON.stringify(entry.data()))

          forEach(reference_fields, (f, k) => {
            if (f.count) {
              if (k in data && typeof data[k] === 'object') {
                data[k] = Object.values(data[k]).map(value => {
                  return {value}
                })
              }
            } else {
              data[k] = {value: data[k]}
            }
          })

          if (param.id || param.clone) {
            pageData.form_edit.set_data(data)
          }
          current_form = pageData.form_edit

          done()
        })
      })
    ], (err) => {
      callback(err, pageData)
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
