const DB_Table = require('./DB_Table')
const DB_TableExtract = require('./DB_TableExtract')
const async = {
  parallel: require('async/parallel')
}
const Views = require('./Views.js')
const pager = require('./pager.js')
const filter = require('./filter.js')
const state = require('./state.js')
const table_fields = require('./table_fields.js')

let current_filter

module.exports = {
  get (param, page, callback) {
    let table
    let result = {
      table: param.table,
      app: global.app,
      param,
    }

    if (!param.table_fields) {
      let table_fields_value = table_fields.current_value[param.table]
      if (table_fields_value) {
        param.table_fields = table_fields_value
      }
    }

    async.parallel([
      done => DB_Table.get_table_list((err, table_list) => {
        result.table_list = table_list
        done(err)
      }),
      done => {
        filter.get(param, (err, filter_form) => {
          current_filter = filter_form

          if ('filter' in param) {
            current_filter.set_data(param.filter)
          }

          result.filter = {show: () => '<div id="show-filter"></div>'}
          result.filter_values = current_filter.get_data()
          done(err)
        })
      },
      done => DB_Table.get(param.table,
        (err, _table) => {
          if (err) {
            return done(err)
          }

          table = _table
          result.table_object = table
          result.table_name = table.name()

          let viewId
          if (param.view) {
            viewId = param.view
            // TODO: update session TABLEID_view_list
          } else {
            // viewId = global.SESSION.TABLEID_view_list
            viewId = table.data('default_view_list')
          }
          param.view = viewId

          // remove show_priority=0
          done()
        }
      )
    ], err => {
      if (err) {
        alert(err)
      }

      let table_extract = new DB_TableExtract(table)
      result.table_extract = table_extract
      let filter_values = filter.convert(table, current_filter)

      table_extract.set_filter(filter_values)

      let viewDef = table.view_def(param.view)
      if (viewDef === false) {
        viewDef = table.view_def('default')
      }

      table_fields.modify_viewdef(param, table, viewDef, (err) => {
        if (err) { return callback(err) }

        let viewClass = (viewDef.class || 'Table')
        let view = new Views[viewClass](viewDef, param)
        result.view = view
        result.views = table.views(param.page)
        result.table_fields = {show: () => '<div id="table_fields_placeholder"></div>'}
        result.table_fields_values = param.table_fields

        page(result, (err) => {
          callback(err, result)
        })
      })
    })
  },

  connect (param) {
    let el = document.getElementById('choose_view')
    if (el) {
      el.onsubmit = () => {
        state.apply_from_form(el)
        return false
      }

      el.elements.view.onchange = () => {
        state.apply_from_form(el)
        return false
      }
    }

    if (current_filter) {
      current_filter.show(document.getElementById('show-filter'))
    } else {
      current_filter = form_filter
    }

    pager.connect(param)
    filter.connect(param, current_filter)
  }
}
