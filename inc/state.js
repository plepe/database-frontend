const queryString = require('qs')

const httpRequest = require('./httpRequest')

let currentState = {}

let loader

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

  loader.oninit(currentState)

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
  return loader.onapply(currentState, (err) => {
    if (err) {
      return alert(err)
    }

    if (!noPushState) {
      history.pushState(currentState, '', decodeURI(page_url(currentState).replace(/&amp;/g, '&')))
    }
    document.body.classList.remove('loading')
  })
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

module.exports = {
  init,
  apply,
  parse,
  set_loader: (_loader) => loader = _loader,
  data: currentState
}
