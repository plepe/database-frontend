window.addEventListener('load', () => {
  let tds = document.getElementsByTagName('td')
  for (let i = 0; i < tds.length; i++) {
    let td = tds[i]

    if (td.hasAttribute('data-field')) {
      td.ondblclick = () => {
        let field = td.getAttribute('data-field')
        let id = td.getAttribute('data-id')

        console.log(id, field)
      }
    }
  }
})
