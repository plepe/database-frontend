window.addEventListener('load', () => {
  let tds = document.getElementsByTagName('td')
  for (let i = 0; i < tds.length; i++) {
    let td = tds[i]

    if (td.hasAttribute('data-field')) {
      td.ondblclick = () => {
        let table = td.getAttribute('data-table')
        let id = td.getAttribute('data-id')
        let field = td.getAttribute('data-field')

        get_entry(table, id, (err, ob) => {
          if (err) {
            return alert(err)
          }

          console.log(table, id, field, ob.view(field))
        })
      }
    }
  }
})
