const forEach = require('foreach')

const DB_Table = require('./DB_Table.js')
const state = require('./state.js')

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
  let field_id = getAttribute(dom, 'data-field')
  let action = getAttribute(dom, 'data-action')
  let value = dom.value || getAttribute(dom, 'data-value')

  state.indicate_loading()

  DB_Table.get_table_entry(table_id, entry_id,
    (err, entry) => {
      if (err) {
        state.abort()
        return alert("Can't execute action: " + err)
      }

      let data = {}
      data[field_id] = value

      entry.save(data, {}, (err) => {
        state.abort()
        if (err) { return alert("Error on save: " + err) }
      })
    }
  )
}

function connect (param) {
  let inputs = document.getElementsByTagName('input')
  forEach(inputs, (input) => {
    if (input.getAttribute('data-action')) {
      input.onclick = () => execute(input)
    }
  })

  let buttons = document.getElementsByTagName('button')
  forEach(buttons, (button) => {
    if (button.getAttribute('data-action')) {
      button.onclick = () => execute(button)
    }
  })

  let links = document.getElementsByTagName('a')
  forEach(links, (link) => {
    if (link.getAttribute('data-action')) {
      link.onclick = () => {
        execute(link)
        return false
      }
    }
  })
}

module.exports = {
  connect_server_rendered: connect,
  post_render: (param, page_data, callback) => {
    connect(param)
    callback()
  },
  post_update: (param, page_data, callback) => {
    connect(param)
    callback()
  }
}
