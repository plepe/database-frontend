class DB_Entry {
  constructor (table, id) {
    this.table = table
    if (id) {
      this.table.entries_cache[id] = this
      this.id = id
      this._load_callbacks = []
    } else {
      this.id = null
      this._load_callbacks = null
    }
  }

  title () {
    return this.table.title_template().render(this.view())
  }

  _load () {
    let req = new XMLHttpRequest()

    req.onreadystatechange = () => {
      if (req.readyState == 4) {
        let err = null

        if (req.status == 200) {
          this._data = JSON.parse(req.responseText)
        } else {
          delete this.table.entries_cache[this.id]
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
    if (key !== undefined) {
      return this._data[key]
    }

    return this._data
  }

  view (key) {
    return this.data(key)
  }

  save (data, changeset, callback) {
    let req = new XMLHttpRequest()

    req.onreadystatechange = () => {
      if (req.readyState == 4) {
        let err = null

        if (req.status == 200) {
          this._data = JSON.parse(req.responseText)

          if ((this.id === null) || (this.id !== this._data.id)) {
            delete this.table.entries_cache[this.id]
            this.id = this._data.id
            this.table.entries_cache[this.id] = this
          }

          return callback(null)
        } else {
          return callback(new Error('entry updating object'))
        }
      }
    }

    if (this.id === null) {
      req.open('POST', 'api.php?table=' + encodeURIComponent(this.table.id), true)
    } else {
      req.open('PATCH', 'api.php?table=' + encodeURIComponent(this.table.id) + '&id=' + encodeURIComponent(this.id), true)
    }

    req.send(JSON.stringify(data))
  }
}

module.exports = DB_Entry
