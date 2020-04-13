const httpRequest = require('./httpRequest')

module.exports = function db_execute (script, changeset, callback) {
  httpRequest(
    'api.php?script=1',
    {
      method: 'POST',
      body: JSON.stringify(script)
    },
    (err, result) => {
      if (err) { return callback(err) }

      result = JSON.parse(result.body)

      callback(null, result)
    }
  )
}
