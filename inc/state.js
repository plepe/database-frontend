const queryString = require('qs')

const page = require('./page')
const httpRequest = require('./httpRequest')

let currentState = {}
global.currentState = currentState

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
  page.connect(currentState)

  window.addEventListener('popstate', e => {
    apply(e.state, true)
  })
}

function apply (param, noPushState = false) {
  for (let k in currentState) {
    delete currentState[k]
  }
  for (let k in param) {
    currentState[k] = param[k]
  }

  document.body.classList.add('loading')
  return page.load(currentState, () => {
    updateLinks()
    if (!noPushState) {
      history.pushState(currentState, '', decodeURI(page_url(currentState).replace(/&amp;/g, '&')))
    }
    document.body.classList.remove('loading')
  })
}

function updateLinks () {
  let links = document.getElementsByTagName('a')

  for (let i = 0; i < links.length; i++) {
    let link = links[i]

    link.onclick = () => {
      let appPath = location.origin + location.pathname
      if (link.href.substr(0, appPath.length) === appPath) {
        let param = queryString.parse(link.href.substr(appPath.length + 1))

        if (apply(param)) {
          return false
        }
      }
    }
  }
}

function apply_from_form (dom) {
  let data = {}
  for (let i = 0; i < dom.elements.length; i++) {
    if (dom.elements[i].name) {
      data[dom.elements[i].name] = dom.elements[i].value
    }
  }

  return apply(data)
}

global.state_apply = apply
global.state_apply_from_form = apply_from_form

module.exports = {
  init,
  apply,
  parse,
  data: currentState
}
