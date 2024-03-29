const forEach = require('foreach')

const DB_Table = require('../../inc/DB_Table.js')
const state = require('../../inc/state.js')

function getAttribute(dom, field) {
  if (!dom || !dom.hasAttribute) {
    return null
  }

  if (!dom.hasAttribute(field)) {
    return getAttribute(dom.parentNode, field)
  }

  return dom.getAttribute(field)
}

function execute (dom) {
  let table_id = getAttribute(dom, 'data-table')
  let entry_id = getAttribute(dom, 'data-id')
  let action = getAttribute(dom, 'data-action')
  let data = {}
  if (getAttribute(dom, 'data-data')) {
    data = JSON.parse(getAttribute(dom, 'data-data'))
  } else {
    let field_id = getAttribute(dom, 'data-field')
    data[field_id] = dom.value || getAttribute(dom, 'data-value')
  }

  state.indicate_loading()

  DB_Table.get_table_entry(table_id, entry_id,
    (err, entry) => {
      if (err) {
        state.abort()
        return alert("Can't execute action: " + err)
      }

      entry.save(data, {}, (err) => {
        state.abort()
        if (err) { return alert("Error on save: " + err) }

        if ('id' in state.data && state.data.table === table_id && state.data.id === entry_id) {
          state.change({id: entry.id})
        } else {
          state.change()
        }
      })
    }
  )
}

function connect (param) {
  let inputs = document.getElementsByTagName('input')
  forEach(inputs, (input) => {
    if (input.getAttribute('data-action')) {
      input.addEventListener('click', () => execute(input))
    }
  })

  let buttons = document.getElementsByTagName('button')
  forEach(buttons, (button) => {
    if (button.getAttribute('data-action')) {
      button.addEventListener('click', () => execute(button))
    }
  })

  let links = document.getElementsByTagName('a')
  forEach(links, (link) => {
    if (link.getAttribute('data-action')) {
      link.addEventListener('click', () => {
        execute(link)
        return false
      })
    }
  })
}

module.exports = {
  connect_server_rendered: connect,
  post_render: (page_data, callback) => {
    connect(page_data.param)
    callback()
  },
  post_update: (page_data, callback) => {
    connect(page_data.param)
    callback()
  }
}
