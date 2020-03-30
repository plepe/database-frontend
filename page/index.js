const DB_Table = require('../inc/DB_Table')

module.exports = {
  get (param, callback) {
    let data = {
    }

    DB_Table.get_all({}, (err, tables) => {
      data.tables = tables.map(t => t.view())
      let table_list = tables.map(t => t.name())

      callback(null, {
        data,
        app: {title: 'Foo'},
        table_list
      })
    })
  }
}
