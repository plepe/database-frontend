const Twig = require('twig')
const queryString = require('query-string')
const async = {
  each: require('async/each'),
  eachOf: require('async/eachOf')
}

const httpRequest = require('./httpRequest')
const DB_Entry = require('./DB_Entry')
const Fields = require('./Fields')

let db_table_cache = {}
let db_table_complete = false
let _load_tables_callbacks = null
const missing_entries = {}

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
          err = new Error('table ' + this.id + ' does not exist')
        }

        this._load_callbacks.forEach(cb => cb(err, db_table_cache[this.id]))
        this._load_callbacks = null
      }
    }

    req.open('GET', 'api.php?table=' + encodeURIComponent(this.id), true)
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

  name () {
    return this.data('name') || this.id
  }

  get_entry (id, callback) {
    if (id in this.entries_cache) {
      let entry = this.entries_cache[id]

      if (!entry) {
        return callback(new Error('entry ' + this.id + '/' + id + ' does not exist'), null)
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

  get_loaded_entry_sync (id) {
    if (id in this.entries_cache) {
      return this.entries_cache[id]
    } else {
      if (!(this.id in missing_entries)) {
        missing_entries[this.id] = {}
      }
      missing_entries[this.id][id] = true
    }
  }

  get_entries_by_id (ids, callback) {
    let toLoad = ids.filter(id => !(id in this.entries_cache))

    async.each(toLoad,
      (id, done) => this.get_entry(id, done),
      (err) => {
        if (err) {
          return callback(err)
        }

        let result = ids.map(id => this.entries_cache[id])
        callback(null, result)
      }
    )
  }

  get_entry_ids (filter, sort, offset, limit, callback) {
    let param = {
      table: this.id,
      list: 1
    }

    if (filter != null) {
      param.filter = filter
    }
    if (sort != null) {
      param.sort = sort
    }
    if (offset != null) {
      param.offset = offset
    }
    if (limit != null) {
      param.limit = limit
    }

    httpRequest('api.php?' + queryString.stringify(param), {},
      (err, result) => {
        if (err) {
          return callback(err)
        }

        let ids = JSON.parse(result.body)

        callback(null, ids)
      }
    )
  }

  get_entries (filter, sort, offset, limit, callback) {
    this.get_entry_ids(filter, sort, offset, limit,
      (err, ids) => {
        if (err) {
          return callback(err)
        }

        this.get_entries_by_id(ids, callback)
      }
    )
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

  def () {
    let ret = JSON.parse(JSON.stringify(this._data.fields))

    for (let k in this._data.fields) {
      let d = this._data.fields[k]
      let field = this.field(k)

      ret[k].type = field.form_type()
    }

    return ret
  }

  fields () {
    if (!this._fields) {
      this._fields = {}
      for (let columnId in this._data.fields) {
        let columnDef = this._data.fields[columnId]

        let type = 'default'
        if (columnDef.type in Fields) {
          type = columnDef.type
        }

        this._fields[columnId] = new Fields[type](columnId, columnDef, this)
      }
    }

    return this._fields
  }

  field (fieldId) {
    this.fields()

    if (fieldId in this._fields) {
      return this._fields[fieldId]
    }
  }

  view_def (k) {
    if (k == 'json') {
      return {
        title: 'JSON',
        class: 'JSON',
        weight: 100,
        fields: this.def()
      }
    }

    if (!(k in this._data.views)) {
      alert('View does not exist!')
      return false
    }

    let def = this.def()
    let ret = JSON.parse(JSON.stringify(this._data.views[k]))

    ret.fields = {}
    for (let i in this._data.views[k].fields) {
      let d = this._data.views[k].fields[i]
      let key = d.key
      let field

      if (key == '__custom__') {
        key = '__custom' + i + '__'
        field = new Fields.default(null, [], this)
      } else {
        field = this.field(d.key)
      }

      d.name = d.title || field.def.name

      if (!d.format) {
        d.format = field.view_def().format
      }

      if (!d.sortable) {
        d.sortable = key in this._data.fields ? this._data.fields[key].sortable : null
      }

      ret.fields[key] = d
    }

    return ret
  }
}

function get_table (id, callback) {
  if (id in db_table_cache) {
    let table = db_table_cache[id]

    if (!table) {
      return callback(new Error('table ' + id + ' does not exist'), null)
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

function get_db_tables (query, callback) {
  if (db_table_complete) {
    return callback(null, Object.values(db_table_cache))
  }

  if (_load_tables_callbacks !== null) {
    return _load_tables_callbacks.push(callback)
  }

  _load_tables_callbacks = [callback]

  httpRequest('api.php?list=1&full=1', {}, (err, result) => {
    if (err) {
      return callback(err)
    }

    let data = JSON.parse(result.body)

    data.forEach(d => {
      if (!(d.id in db_table_cache)) {
        new DB_Table(d.id, d)
      } else if (db_table_cache[d.id]._load_callbacks !== null) {
        db_table_cache[d.id]._data = d
      }
    })

    let r = Object.values(db_table_cache)
    _load_tables_callbacks.forEach(cb => cb(null, r))
    _load_tables_callbacks = null
    db_table_complete = true
  })
}

function get_table_list (callback) {
  get_db_tables(null, (err, tables) => {
    if (err) {
      return callback(err)
    }

    let result = {}

    tables.forEach(table => result[table.id] = table.name())

    callback(null, result)
  })
}

function get_loaded_entry_sync (table_id, id) {
  if (table_id in db_table_cache) {
    return db_table_cache[table_id].get_loaded_entry_sync(id)
  } else {
    if (!(table_id in missing_entries)) {
      missing_entries[table.id] = {}
    }
    missing_entries[table.id][id] = true
  }
}

function load_missing_entries (callback) {
  async.eachOf(missing_entries,
    (ids, table_id, done) => {
      get_table(table_id, (err, table) => {
        if (err) {
          return done(err)
        }

        table.get_entries_by_id(Object.keys(ids), done)
      })
    },
    (err) => {
      if (err) {
        return callback(err)
      }

      for (let k in missing_entries) {
        delete(missing_entries[k])
      }

      callback(null)
    }
  )
}

function has_missing_entries () {
  return !!Object.keys(missing_entries).length
}

module.exports = {
  get: get_table,
  get_all: get_db_tables,
  get_table_list,
  get_loaded_entry_sync,
  missing_entries,
  has_missing_entries,
  load_missing_entries,
  cache: db_table_cache
}
