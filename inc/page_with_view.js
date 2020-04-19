const DB_Table = require('./DB_Table')
const DB_TableExtract = require('./DB_TableExtract')
const async = {
  each: require('async/each'),
  parallel: require('async/parallel')
}
const Views = require('./Views.js')
const pager = require('./pager.js')
const state = require('./state.js')
const session = require('./session.js')

const modules = [
  require('./table_fields.js'),
  require('./filter.js'),
  require('./pager.js')
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

          let views = table.views(param.page)
          if (!param.view || !(param.view in views)) {
            param.view = session.get(table.id + '_view_' + param.page) || table.data('default_view_' + param.page)
          } else {
            session.set(table.id + '_view_' + param.page, param.view)
          }

          // remove show_priority=0
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

      if (param.sort) {
        session.set(table.id + '_view_sort', param.sort)
        session.set(table.id + '_view_sort_dir', param.sort_dir)
      } else {
        let sort = session.get(table.id + '_view_sort')
        if (sort) {
          param.sort = sort
          param.sort_dir = session.get(table.id + '_view_sort_dir')
        } else {
          param.sort = table.data('sort')
        }
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

    modules.forEach(module => module.connect_server_rendered(param))
  },

  post_render(param, page_data, callback) {
    async.each(modules,
      (module, done) => module.post_render(param, page_data, done),
      callback
    )
  },

  post_update (param, page_data, callback) {
    async.each(modules,
      (module, done) => {
        if ('post_update' in module) {
          module.post_update(param, page_data, done)
        } else {
          done()
        }
      },
      callback
    )
  }
}
