const httpRequest = require('./httpRequest')
const Changeset = require('./Changeset')

module.exports = function db_execute (script, changeset, callback) {
  let url = 'api.php?script=1'

  if (!(changeset instanceof Changeset)) {
    changeset = new Changeset(changeset)
  }
  url = changeset.modify_url(url)

  httpRequest(
    url,
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
