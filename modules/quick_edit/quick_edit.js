const state = require('../../inc/state.js')
const DB_Table = require('../../inc/DB_Table.js')
const Changeset = require('../../inc/Changeset.js')

let dom

function connect (param) {
  let tds = document.getElementsByTagName('td')
  for (let i = 0; i < tds.length; i++) {
    let td = tds[i]

    if (td.hasAttribute('data-field')) {
      td.ondblclick = () => {
        let table_id = td.getAttribute('data-table')
        let entry_id = td.getAttribute('data-id')
        let field_id = td.getAttribute('data-field')

        if (!table_id || !entry_id || !field_id) {
          return
        }

        if (dom) {
          document.body.removeChild(dom)
        }

        dom = document.createElement('div')
        dom.className = 'editable'
        document.body.appendChild(dom)

        let f = document.createElement('form')
        dom.appendChild(f)

        let loading_indicator = document.createElement('div')
        loading_indicator.className = 'loading_indicator'
        loading_indicator.innerHTML = 'Loading ...'
        f.appendChild(loading_indicator)

        DB_Table.get(table_id, (err, table) => {
          if (err) {
            return alert(err)
          }

          table.get_entry(entry_id, (err, entry) => {
            if (err) {
              return alert(err)
            }

            table.def((err, _orig_form_def) => {
              if (err) { return alert(err) }

              if (!(field_id in _orig_form_def)) {
                return
              }

              f.removeChild(loading_indicator)

              let form_def = {}
              form_def[field_id] = JSON.parse(JSON.stringify(_orig_form_def[field_id]))

              let form_editable = new form('editable', form_def)

              observe(f, {attributes: true}, () => form_editable.resize())

              form_editable.show(f)
              form_editable.set_data(entry.view())

              let actions = document.createElement('div')
              actions.className = 'actions'
              f.appendChild(actions)

              let input = document.createElement('input')
              input.type = 'submit'
              input.value = 'Save'
              actions.appendChild(input)

              actions.appendChild(document.createTextNode(' '))

              let commit_message = document.createElement('input')
              commit_message.name = 'message'
              commit_message.type = 'text'
              commit_message.placeholder = 'commit message'
              actions.appendChild(commit_message)

              f.onsubmit = () => {
                state.indicate_loading()

                let data = form_editable.get_data()

                let changeset = new Changeset(commit_message.value)

                entry.save(data, changeset, (err) => {
                  if (err) {
                    alert(err)
                  }

                  document.body.removeChild(dom)
                  dom = null

                  if ('id' in state.data) {
                    state.change({id: entry.id})
                  } else {
                    state.change()
                  }
                })

                return false
              }

              actions.appendChild(document.createTextNode(' '))

              input = document.createElement('input')
              input.type = 'button'
              input.value = 'Cancel'
              actions.appendChild(input)
              input.onclick = () => {
                document.body.removeChild(dom)
                dom = null

                return false
              }
            })
          })
        })

        return false
      }
    }
  }
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
