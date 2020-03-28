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

            dom = document.createElement('form')
            dom.className = 'editable'
            document.body.appendChild(dom)

            let form_def = {}
            form_def[field_id] = table._data.fields[field_id]

            let form_editable = new form('editable', form_def)

            form_editable.show(dom)
            form_editable.set_data(entry.view())

            let input = document.createElement('input')
            input.type = 'submit'
            input.value = 'Save'
            dom.appendChild(input)

            dom.onsubmit = () => {
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
          })
        })
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
