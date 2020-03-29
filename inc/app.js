window.Twig = require('twig')

const state = require('./state')

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
  state.init()
  require('./page').init()

  //window.setTimeout(() => state.apply({page:'index'}), 2000)
})
