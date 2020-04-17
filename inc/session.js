const httpRequest = require('./httpRequest')

let to_set = {}
let set_timeout

function save () {
  httpRequest('session.php',
    {
      method: 'POST',
      body: JSON.stringify(to_set)
    },
    (err) => {
      if (err) {
        alert('Error saving session: ' + err)
      }
    }
  )

  to_set = {}
}

module.exports = {
  set (key, value) {
    global.session_vars[key] = value

    to_set[key] = value

    if (set_timeout) {
      global.clearTimeout(set_timeout)
    }
    set_timeout = global.setTimeout(save, 1000)
  },

  get (key) {
    return global.session_vars[key]
  },

  data: global.session_vars
}
