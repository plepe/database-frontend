const queryString = require('qs')

const httpRequest = require('./httpRequest')
const data_from_form = require('./data_from_form')

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
      history.pushState(currentState, '', '?' + queryString.stringify(currentState))
    }
    document.body.classList.remove('loading')
  })
}

function change (param, noPushState = false) {
  let newState = JSON.parse(JSON.stringify(currentState))

  for (let k in param) {
    newState[k] = param[k]
  }

  apply(newState, noPushState)
}

function apply_from_form (dom) {
  let data = data_from_form(dom)

  return apply(data)
}

module.exports = {
  init,
  apply,
  apply_from_form,
  change,
  parse,
  set_loader: (_loader) => loader = _loader,
  data: currentState
}
