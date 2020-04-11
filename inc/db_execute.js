const httpRequest = require('./httpRequest')

module.exports = function db_execute (script, changeset, callback) {
  httpRequest(
    'api.php?script=1',
    {
      method: 'POST',
      body: JSON.stringify(script)
    },
    callback
  )
}
