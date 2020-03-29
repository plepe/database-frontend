let dom

window.addEventListener('load', () => {
  let tds = document.getElementsByTagName('td')
  for (let i = 0; i < tds.length; i++) {
    let td = tds[i]

    if (td.hasAttribute('data-field')) {
      td.ondblclick = () => {
        let table_id = td.getAttribute('data-table')
        let entry_id = td.getAttribute('data-id')
        let field_id = td.getAttribute('data-field')

        get_table(table_id, (err, table) => {
          if (err) {
            return alert(err)
          }

          table.get_entry(entry_id, (err, entry) => {
            if (err) {
              return alert(err)
            }

            if (dom) {
              document.body.removeChild(dom)
            }

            dom = document.createElement('div')
            dom.className = 'editable'
            document.body.appendChild(dom)

            let f = document.createElement('form')
            dom.appendChild(f)

            let form_def = {}
            form_def[field_id] = table._data.fields[field_id]
            form_def[field_id].hide_label = true

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

            f.onsubmit = () => {
              let data = form_editable.get_data()

              entry.save(data, null, (err) => {
                if (err) {
                  alert(err)
                }

                document.body.removeChild(dom)
                dom = null

                update_entry(table_id, entry_id)
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

        return false
      }
    }
  }
})

function update_entry (table_id, entry_id) {
  get_table(table_id, (err, table) => {
    if (err) {
      return alert(err)
    }

    table.get_entry(entry_id, (err, entry) => {
      if (err) {
        return alert(err)
      }

      let tds = document.getElementsByTagName('td')
      for (let i = 0; i < tds.length; i++) {
        let td = tds[i]

        if (td.hasAttribute('data-field')) {
          let t = td.getAttribute('data-table')
          let e = td.getAttribute('data-id')

          if (t === table_id && e === entry.id) {
            let field_id = td.getAttribute('data-field')
            let view_id = td.getAttribute('data-view')

            let field
            for (let f in table._data.views[view_id].fields) {
              if (table._data.views[view_id].fields[f].key === field_id) {
                field = table._data.views[view_id].fields[f]
              }
            }

            if (field) {
              let template = Twig.twig({data: field.format || '{{ ' + field.key + '|nl2br }}'})
              td.innerHTML = template.render(entry.view())
            }
          }
        }
      }
    })
  })
}