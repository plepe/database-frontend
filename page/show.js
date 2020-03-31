const DB_Table = require('../inc/DB_Table')
const DB_TableExtract = require('../inc/DB_TableExtract')
const async = {
  parallel: require('async/parallel')
}
const Views = require('../inc/Views.js')

module.exports = {
  get (param, callback) {
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

        result.table_list = tables.map(t => t.name())
        done()
      }),
      done => DB_Table.get(param.table,
        (err, table) => {
          if (err) {
            return done(err)
          }

          result.table_name = table.name()

          let viewId
          if (param.view) {
            viewId = param.view
            // TODO: update session TABLEID_view_show
          } else {
            // viewId = global.SESSION.TABLEID_view_show
            viewId = table.data('default_view_show')
          }
          param.view = viewId

          let viewDef = table.view_def(viewId)
          if (viewDef === false) {
            viewDef = table.view_def('default')
          }
          // modify_table_fields(param, viewDef)

          // remove show_priority=0
          
          let table_extract = new DB_TableExtract(table)
          //let filterValues = get_filter(param)
          table_extract.set_ids([param.id])

          let viewClass = (viewDef.class || 'Table')
          let view = new Views[viewClass](viewDef, param)
          result.view = view

          view.set_extract(table_extract)
          view.render_single(param, done)
        }
      )
    ], err => {
      if (err) {
        alert(err)
      }

      callback(null, result)
    })
  }
}
