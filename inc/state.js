const hash = require('sheet-router/hash')
const queryString = require('query-string')

const page = require('./page')
const httpRequest = require('./httpRequest')

let currentState = {}

function parse (str) {
  return queryString.parse(str)
}

function init () {
  let newState = {}

  if (location.search && location.search.length > 1) {
    newState = parse(location.search.substr(1))
  }

  for (let k in currentState) {
    delete currentState[k]
  }
  for (let k in newState) {
    currentState[k] = newState[k]
  }

  updateLinks()
}

function apply (param) {
  for (let k in currentState) {
    delete currentState[k]
  }
  for (let k in param) {
    currentState[k] = param[k]
  }

  return page.load(currentState, () => {
    updateLinks()
  })
}

function updateLinks () {
  let links = document.getElementsByTagName('a')

  for (let i = 0; i < links.length; i++) {
    let link = links[i]

    link.onclick = () => {
      let appPath = location.origin + location.pathname
      if (link.href.substr(0, appPath.length) === appPath) {
        let param = queryString.parse(link.href.substr(appPath.length))

        if (apply(param)) {
          return false
        }
      }
    }
  }
}

module.exports = {
  init,
  apply,
  parse,
  data: currentState
}
