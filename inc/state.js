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
}

function apply (param) {
  for (let k in currentState) {
    delete currentState[k]
  }
  for (let k in param) {
    currentState[k] = param[k]
  }

  page.load(currentState)
}

module.exports = {
  init,
  apply,
  parse,
  data: currentState
}
