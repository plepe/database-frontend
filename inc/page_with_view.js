const DB_Table = require('./DB_Table')
const DB_TableExtract = require('./DB_TableExtract')
const async = {
  each: require('async/each'),
  parallel: require('async/parallel')
}
const Views = require('./Views.js')
const pager = require('./pager.js')
const state = require('./state.js')

const modules = [
  require('./table_fields.js'),
  require('./filter.js')
]

module.exports = {
  get (param, page, callback) {
    let table
    let result = {
      table: param.table,
      app: global.app,
      param,
    }

    modules.forEach(module => module.permalink(param))

    async.parallel([
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

          let views = table.views('list')
          if (!(param.view in views)) {
            param.view = 'default'
          }

          table.view_def(param.view, (err, ret) => {
            result.view_def = ret
            done(err)
          })
        }
      )
    ], err => {
      if (err) {
        alert(err)
      }

      let table_extract = new DB_TableExtract(table)
      result.table_extract = table_extract

      result.view_def = table.view_def(param.view)
      if (result.view_def === false) {
        result.view_def = table.view_def('default')
      }

      async.each(modules,
        (module, done) => module.pre_render(param, result, done),
        (err) => {
          if (err) { return callback(err) }

          let viewClass = (result.view_def.class || 'Table')
          let view = new Views[viewClass](result.view_def, param)
          result.view = view
          result.views = table.views(param.page)

          page(result, (err) => {
            callback(err, result)
          })
        }
      )
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

    pager.connect(param)

    modules.forEach(module => module.connect_server_rendered(param))
  },

  post_render(param, page_data, callback) {
    async.each(modules,
      (module, done) => module.post_render(param, page_data, done),
      callback
    )
  }
}
