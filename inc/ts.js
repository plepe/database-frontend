const httpRequest = require('./httpRequest')

function wait (callback) {
  httpRequest('ts.php?wait=600&ts=' + encodeURIComponent(global.ts),
    {
      responseType: 'json'
    },
    (err, result) => {
      if (err) {
        if (result.status === 0) {
          // request aborted -> no error
          return callback(null)
        }

        return callback(err)
      }

      if (global.ts === result.body.ts) {
        callback(null)
      }
      else {
        global.ts = result.body.ts
        callback(null, result.body)
      }
    }
  )
}

module.exports = {
  wait
}
