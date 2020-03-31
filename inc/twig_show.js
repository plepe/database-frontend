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

      return table + '/' + id
    })

    Twig.extendFunction("get_entry", (table, id) => {
      return 'get_entry("' + table + '", "' + id + '")'
    })

    Twig.extendFunction("get_entries", (table, filter) => {
      return 'get_entries("' + table + '", ' + JSON.stringify(filter) + ')'
    })

    Twig.extendFilter("age", param => {
    })
  }
}

