class DB_Entry {
  constructor (table, id) {
    this.table = table
    this.table.entries_cache[id] = this
    this.id = id
    this._load_callbacks = []
  }

  _load () {
    let req = new XMLHttpRequest()

    req.onreadystatechange = () => {
      if (req.readyState == 4) {
        let err = null

        if (req.status == 200) {
          this._data = JSON.parse(req.responseText)
        } else {
          this.table.entries_cache[this.id] = null
          err = new Error('entry does not exist')
        }

        this._load_callbacks.forEach(cb => cb(err, this.table.entries_cache[this.id]))
        this._load_callbacks = null
      }
    }

    req.open('GET', 'api.php?table=' + encodeURIComponent(this.table.id) + '&id=' + encodeURIComponent(this.id), true)
    req.send()
  }

  data (key) {
    if (key !== null) {
      return this._data[key]
    }

    return this._data
  }

  save (data, changeset, callback) {
    let req = new XMLHttpRequest()

    req.onreadystatechange = () => {
      if (req.readyState == 4) {
        let err = null

        if (req.status == 200) {
          this._data = JSON.parse(req.responseText)
          return callback(null)
        } else {
          return callback(new Error('entry updating object'))
        }
      }
    }

    req.open('PATCH', 'api.php?table=' + encodeURIComponent(this.table.id) + '&id=' + encodeURIComponent(this.id), true)
    req.send(JSON.stringify(data))
  }
}

module.exports = DB_Entry
