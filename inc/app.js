const forEach = require('foreach')

window.Twig = require('twig')
window.twig = require('twig').twig

const markdown = require('./markdown')

const state = require('./state')
const loader = require('./loader')

window.DB_Table = require('./DB_Table')
window.observe = require('./observe')

window.get_table = (table_id, callback) => {
  DB_Table.get(table_id, callback)
}

window.get_entry = (table_id, id, callback) => {
  DB_Table.get(table_id,
    (err, table) => {
      if (err) {
        return callback(err)
      }

      table.get_entry(id, callback)
    }
  )
}

window.addEventListener('load', () => {
  loader.init()
  state.init()
  require('./page').init()
  require('./panel').init()
  require('./twig_show').init()
  require('./pager').init()
  require('./table_fields').init()
  markdown.init()

  //window.setTimeout(() => state.apply({page:'index'}), 2000)

  state.on('change_detect', (result) => {
    let to_invalidate = []
    if (result.entries) {
      forEach(result.entries, (entries, table_id) => {
        forEach(entries, (id) => {
          to_invalidate.push([table_id, id])
        })
      })

      DB_Table.invalidate_entries(to_invalidate)
    }

    loader.update((err) => { if (err) { alert(err) }})
  })
})
