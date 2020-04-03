const DB_Table = require('../inc/DB_Table')
const DB_TableExtract = require('../inc/DB_TableExtract')
const async = {
  parallel: require('async/parallel')
}
const Views = require('../inc/Views.js')
const pager = require('../inc/pager.js')

module.exports = {
  get (param, callback) {
    let table
    let result = {
      table: param.table,
      app: global.app,
      param,
    }

    async.parallel([
      done => DB_Table.get_all({}, (err, tables) => {
        if (err) {
          return done(err)
        }

        done()
      }),
      done => DB_Table.get_table_list((err, table_list) => {
        result.table_list = table_list
        done(err)
      }),
      done => DB_Table.get(param.table,
        (err, _table) => {
          if (err) {
            return done(err)
          }

          table = _table
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

          // modify_table_fields(param, viewDef)

          // remove show_priority=0
          done()
        }
      )
    ], err => {
      if (err) {
        alert(err)
      }

      let table_extract = new DB_TableExtract(table)

      let viewDef = table.view_def(param.view)
      if (viewDef === false) {
        viewDef = table.view_def('default')
      }

      let viewClass = (viewDef.class || 'Table')
      let view = new Views[viewClass](viewDef, param)
      result.view = view
      result.views = table.views('list')

      view.set_extract(table_extract)

      async.parallel([
        done => view.render_list(param, done),
        done => table_extract.pager_info((err, info) => {
          if (info) {
            result.result_count = info.result_count
          }

          done(err)
        })
      ], (err) => {
        callback(err, result)
      })
    })
  },

  connect (param) {
    let el = document.getElementById('choose_view')
    if (el) {
      el.onsubmit = () => {
        global.state_apply_from_form(el)
        return false
      }

      el.elements.view.onchange = () => {
        global.state_apply_from_form(el)
        return false
      }
    }

    pager.connect(param)
  }
}
