const Twig = require('twig')

module.exports = {
  init () {
    Twig.extendFilter("show", (value, param) => {
      if (!value) {
        return ''
      }

      return value.show(param)
    })

    Twig.extendFilter("show_list", (value, param) => {
      if (!value) {
        return ''
      }

      return value.show_list(param)
    })

    Twig.extendFunction("entry_title", (table, id) => {
      if (id == null) {
        return ''
      }

      let entry = DB_Table.get_loaded_entry_sync(table, id)
      if (entry) {
        return entry.title()
      }

      return table + '/' + id
    })

    Twig.extendFunction("get_entry", (table, id) => {
      let ob = DB_Table.get_loaded_entry_sync(table, id)
      if (ob) {
        return ob.view()
      }
      return null
    })

    Twig.extendFunction("get_entries", (table, filter, sort, offset, limit) => {
      let obs = DB_Table.get_loaded_entries(table, filter, sort, offset, limit)
      if (obs) {
        return obs.map(ob => ob.view())
      } else {
        return []
      }
    })

    Twig.extendFunction("entry_titles", (table, filter, sort, offset, limit) => {
      let obs = DB_Table.get_loaded_entries(table, filter, sort, offset, limit)
      if (obs) {
        let result = {}
        obs.forEach(ob => result[ob.id] = ob.title())
        return result
      } else {
        return []
      }
    })
  }
}

