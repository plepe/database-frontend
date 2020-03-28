const Twig = require('twig')

const DB_Entry = require('./DB_Entry')

let db_table_cache = {}

class DB_Table {
  constructor (id, data) {
    this.id = id
    db_table_cache[id] = this
    this.entries_cache = {}
    this._load_callbacks = []

    if (data) {
      this._load_callbacks = null
      this._data = data
    }
  }

  _load () {
    let req = new XMLHttpRequest()

    req.onreadystatechange = () => {
      if (req.readyState == 4) {
        let err = null

        if (req.status == 200) {
          this._data = JSON.parse(req.responseText)
        } else {
          delete db_table_cache[this.id]
          err = new Error('table does not exist')
        }

        this._load_callbacks.forEach(cb => cb(err, db_table_cache[this.id]))
        this._load_callbacks = null
      }
    }

    req.open('GET', 'api.php?table=' + encodeURIComponent(this.id), true)
    req.send()
  }

  data (key) {
    if (key !== null) {
      return this._data[key]
    }

    return this._data
  }

  get_entry (id, callback) {
    if (id in this.entries_cache) {
      let entry = this.entries_cache[id]

      if (!entry) {
        return callback(new Error('entry does not exist'), null)
      }

      if (entry._load_callbacks === null) {
        return callback(null, entry)
      }

      return entry._load_callbacks.push(callback)
    }

    let entry = new DB_Entry(this, id)
    entry._load_callbacks.push(callback)
    entry._load()
  }

  create_entry (data, changeset, callback) {
    let entry = new DB_Entry(this)
    entry.save(data, changeset, callback)
  }

  title_template () {
    if (!this._title_template) {
      let data

      if (!(data = this.data('title'))) {
        data = '{{ id }}'
      }

      this._title_template = new Twig.twig({data})
    }

    return this._title_template
  }
}

function get_table (id, callback) {
  if (id in db_table_cache) {
    let table = db_table_cache[id]

    if (!table) {
      return callback(new Error('table does not exist'), null)
    }

    if (table._load_callbacks === null) {
      return callback(null, table)
    }

    return table._load_callbacks.push(callback)
  }

  let table = new DB_Table(id)
  table._load_callbacks.push(callback)
  table._load()
}

module.exports = {
  get: get_table,
  cache: db_table_cache
}
