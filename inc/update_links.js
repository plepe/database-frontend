const forEach = require('foreach')
const queryString = require('qs')

const state = require('./state')

function catch_jump_links () {
  let Jump_links = document.getElementsByClassName('Jump_links')
  forEach(Jump_links, form => {
    form.addEventListener('submit', () => {
      state.apply_from_form(form)
      return false
    })

    let selects = form.getElementsByTagName('select')
    forEach(selects, (select) => {
      select.onchange = () => {
        state.apply_from_form(form)
        return false
      }
    })
  })
}

module.exports = function update_links () {
  let links = document.getElementsByTagName('a')

  for (let i = 0; i < links.length; i++) {
    let link = links[i]

    link.onclick = () => {
      let appPath = location.origin + location.pathname
      if (link.href.substr(0, appPath.length) === appPath) {
        let param = queryString.parse(link.href.substr(appPath.length + 1))

        if (state.apply(param)) {
          return false
        }
      }
    }
  }

  catch_jump_links()
}
