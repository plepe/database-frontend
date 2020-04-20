const Twig = require('twig')
const htmlentities = require('html-escaper').escape

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

    Twig.extendFilter("age", date => {
      if (date == null) {
        return ''
      }

      let now = new Date()
      let diff = (now - new Date(date)) / 1000
      let text

      if (diff < 0) {
        text = 'not yet'
      } else if (diff < 2 * 60) {
        text = 'just now'
      } else if (diff < 45 * 60) {
        text = Math.round(diff / 60) + ' minutes ago'
      } else if (diff < 90 * 60) {
        text = 'an hour ago'
      } else if (diff < 86400) {
        text = Math.round(diff / 3600) + ' hours ago'
      } else if (diff < 2 * 86400) {
        text = 'yesterday'
      } else if (diff < 61 * 86400) {
        text = Math.round(diff / 86400) + ' days ago'
      } else if (diff < 380 * 86400) {
        text = Math.round(diff / 30.4 / 86400) + ' months ago'
      } else {
        text = Math.round(diff / 365.25 / 86400) + ' years ago'
      }

      return '<span title="' + htmlentities(date) + '">' + text + '</span>'
    })
  }
}

