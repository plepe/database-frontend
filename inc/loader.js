const queryString = require('qs')

const page = require('./page')
const state = require('./state')
const update_links = require('./update_links')

const loader = {
  onapply (param, callback) {
    return page.load(param, (err) => {
      callback(err)
    })
  },

  oninit (param) {
    update_links()
    page.connect_server_rendered(param)
  }
}

function init () {
  state.set_loader(loader)
}

module.exports = {
  init
}
