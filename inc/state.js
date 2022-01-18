const queryString = require('qs')
const EventEmitter = require('events')

const httpRequest = require('./httpRequest')
const data_from_form = require('./data_from_form')
const ts = require('./ts')

class State extends EventEmitter {
  parse (str) {
    return queryString.parse(str)
  }

  init () {
    let newState = {}
    this.data = {}

    if (location.search && location.search.length > 1) {
      newState = this.parse(location.search.substr(1))
    }

    for (let k in newState) {
      this.data[k] = newState[k]
    }

    this.loader.oninit(this.data)

    window.addEventListener('popstate', e => {
      this.apply(e.state, true)
    })

    ts.wait((err, result) => this.change_detect(err, result))
  }

  change_detect (err, result) {
    if (err) { return alert('Error waiting for changes: ' + err) }

    if (result) {
      this.emit('change_detect', result)
    }

    ts.wait((err, result) => this.change_detect(err, result))
  }

  apply (param, noPushState = false) {
    for (let k in this.data) {
      delete this.data[k]
    }
    for (let k in param) {
      this.data[k] = param[k]
    }

    this.indicate_loading()

    if (!noPushState) {
      history.pushState(this.data, '', '?' + queryString.stringify(this.data))
    }

    return this.loader.onapply(this.data, (err) => {
      document.body.classList.remove('loading')

      if (err) {
        return alert(err)
      }
    })
  }

  indicate_loading () {
    document.body.classList.add('loading')
  }

  abort () {
    document.body.classList.remove('loading')
  }

  change (param, noPushState = false) {
    let newState = JSON.parse(JSON.stringify(this.data))

    for (let k in param) {
      newState[k] = param[k]
    }

    this.apply(newState, noPushState)
  }

  apply_from_form (dom) {
    let data = data_from_form(dom)

    return this.apply(data)
  }

  set_loader (_loader) {
    this.loader = _loader
  }
}

let state = new State()

module.exports = state
