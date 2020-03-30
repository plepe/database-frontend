const Twig = require('twig')

module.exports = {
  init () {
    Twig.extendFilter("show", param => {
    })

    Twig.extendFilter("show_list", (value, param) => {
      return value.show_list(param)
    })

    Twig.extendFilter("entry_title", param => {
    })

    Twig.extendFilter("get_entry", param => {
    })

    Twig.extendFilter("get_entries", param => {
    })

    Twig.extendFilter("age", param => {
    })
  }
}

