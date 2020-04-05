const queryString = require('qs')

const page = require('./page')
const state = require('./state')

const loader = {
  onapply (param, callback) {
    return page.load(param, (err) => {
      update_links()
      callback(err)
    })
  },

  oninit (param) {
    update_links()
    page.connect(param)
  }
}

function update_links () {
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
}

function init () {
  state.set_loader(loader)
}

module.exports = {
  init
}
