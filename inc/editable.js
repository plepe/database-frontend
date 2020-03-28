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
              })

              return false
            }
          })
        })
      }
    }
  }
})
